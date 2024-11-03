<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\database;
use CustomTables\Filtering;
use CustomTables\CustomPHP;
use CustomTables\MySQLWhereClause;
use CustomTables\record;
use CustomTables\TwigProcessor;
use CustomTables\SaveFieldQuerySet;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CustomTablesModelEditItem extends BaseDatabaseModel
{
    var CT $ct;
    var bool $userIdField_Unique;
    var bool $userIdField_UniqueUsers;
    var ?string $listing_id;
    var bool $isAuthorized;
    var ?array $row;

    function __construct()
    {
        $this->userIdField_Unique = false;
        $this->userIdField_UniqueUsers = false;
        parent::__construct();
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function CheckAuthorizationACL($access): bool
    {
        $this->isAuthorized = false;

        if ($access == 'core.edit' and $this->listing_id == 0)
            $access = 'core.create'; //add new

        if ($this->ct->Env->user->authorise($access, 'com_customtables')) {
            $this->isAuthorized = true;
            return true;
        }

        if ($access != 'core.edit')
            return false;

        if ($this->ct->Params->userIdField != '') {
            if ($this->ct->checkIfItemBelongsToUser($this->listing_id, $this->ct->Params->userIdField)) {
                if ($this->ct->Env->user->authorise('core.edit.own', 'com_customtables')) {
                    $this->isAuthorized = true;
                    return true;
                } else
                    $this->isAuthorized = false;
            }
        }
        return false;
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    function copy(&$msg, &$link): bool
    {
        $listing_id = common::inputGetCmd('listing_id');
        if ($listing_id === null)
            return false;

        try {
            $whereClause = new MySQLWhereClause();
            $whereClause->addCondition($this->ct->Table->realidfieldname, $listing_id);
            $rows = database::loadAssocList($this->ct->Table->realtablename, ['*'], $whereClause, null, null, 1);
        } catch (Exception $e) {
            $this->ct->errors[] = $e->getMessage();
            $msg = $e->getMessage();
            return false;
        }

        if (count($rows) == 0) {
            $msg = 'Record not found or something went wrong.';
            return false;
        }

        $newRow = $rows[0];
        $newRow[$this->ct->Table->realidfieldname] = null;
        $new_listing_id = database::insert($this->ct->Table->realtablename, $newRow);

        return $this->store($link, true, $new_listing_id);
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    function store(string &$link, bool $isCopy = false, ?string $listing_id = null): bool
    {
        $record = new record($this->ct);

        //IP Filter
        $USER_IP = SaveFieldQuerySet::getUserIP();
        $IP_Black_List = array();

        if (in_array($USER_IP, $IP_Black_List))
            return false;

        $record->editForm->load();//Load Menu Item parameters

        if ($record->save($listing_id, $isCopy)) {
            $this->listing_id = $record->listing_id;

            //Prepare "Accept Return To" Link
            $return2Link = common::getReturnToURL();

            //Refresh menu if needed
            if ($this->ct->Params->msgItemIsSaved !== null and $this->ct->Params->msgItemIsSaved != "") {
                $this->ct->messages[] = $this->ct->Params->msgItemIsSaved;
            }

            if ($this->ct->Env->advancedTagProcessor and !empty($this->ct->Table->tablerow['customphp'])) {
                try {
                    $action = $record->isItNewRecord ? 'create' : 'update';
                    $customPHP = new CustomPHP($this->ct, $action);
                    $customPHP->executeCustomPHPFile($this->ct->Table->tablerow['customphp'], $record->row_new, $record->row_old);
                } catch (Exception $e) {
                    $this->ct->errors[] = 'Custom PHP file: ' . $this->ct->Table->tablerow['customphp'] . ' (' . $e->getMessage() . ')';
                }

                $return2Link_Updated = common::getReturnToURL();
                if ($return2Link != $return2Link_Updated)
                    $link = $return2Link_Updated;
            }

            if ($this->listing_id !== null)
                common::inputSet("listing_id", $this->listing_id);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function load(CT $ct): bool
    {
        $this->ct = $ct;

        if ($this->ct->Env->legacySupport)
            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'layout.php');

        $this->ct->getTable($ct->Params->tableName, $this->ct->Params->userIdField);

        if ($this->ct->Table->tablename === null) {
            $this->ct->errors[] = 'Table not selected (61).';
            return false;
        }

        $this->ct->Params->userIdField = $this->findUserIDField($this->ct->Params->userIdField);//to make sure that the field name is real and two userid fields can be used

        if (is_null($ct->Params->msgItemIsSaved))
            $ct->Params->msgItemIsSaved = common::translate('COM_CUSTOMTABLES_RECORD_SAVED');

        $this->listing_id = $this->ct->Params->listing_id;

        //Load the record
        $app = Factory::getApplication();
        $menu = $app->getMenu();
        $currentMenuItem = $menu->getActive();

        if ($currentMenuItem and $currentMenuItem->query['view'] == 'edititem')
            $this->listing_id = $this->processCustomListingID();

        if (($this->listing_id === null or $this->listing_id == '' or $this->listing_id == 0) and $this->userIdField_UniqueUsers and $this->ct->Params->userIdField != '') {
            //try to find record by userid and load it
            $this->listing_id = $this->findRecordByUserID();
        }

        $this->ct->Params->listing_id = $this->listing_id;
        return true;
    }

    function findUserIDField($userIdField): string
    {
        if ($userIdField != '') {
            $userIdFields = array();
            $statement_items = CTMiscHelper::ExplodeSmartParamsArray($userIdField); //"and" and "or" as separators

            foreach ($statement_items as $item) {
                if (!str_contains($item['equation'], '.')) {
                    //Current table field name
                    //find selected field
                    foreach ($this->ct->Table->fields as $fieldRow) {
                        if ($fieldRow['fieldname'] == $item['equation'] and ($fieldRow['type'] == 'userid' or $fieldRow['type'] == 'user')) {
                            $userIdFields[] = [$item['logic'], $item['equation']];

                            //Following apply to current table fields only and to only one (the last one in the statement)
                            $params = $fieldRow['typeparams'];
                            $parts = CTMiscHelper::csv_explode(',', $params);

                            $this->userIdField_UniqueUsers = false;
                            if (isset($parts[4]) and $parts[4] == 'unique')
                                $this->userIdField_UniqueUsers = true;

                            break;
                        }
                    }
                } else {
                    //Table join
                    //parents(children).user
                    $userIdFields[] = [$item['logic'], $item['equation']];
                }
            }

            $userIdFieldsStr = '';
            $index = 0;
            foreach ($userIdFields as $field) {
                if ($index == 0)
                    $userIdFieldsStr .= $field[1];
                else
                    $userIdFieldsStr .= ' ' . $field[0] . ' ' . $field[1];

                $index += 1;
            }
            return $userIdFieldsStr;
        }
        return '';
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function processCustomListingID(): ?int
    {
        if ($this->listing_id !== null and (is_numeric($this->listing_id) or (!str_contains($this->listing_id, '=') and !str_contains($this->listing_id, '<') and !str_contains($this->listing_id, '>')))) {
            try {
                $whereClause = new MySQLWhereClause();
                $whereClause->addCondition($this->ct->Table->realidfieldname, $this->listing_id);

                $rows = database::loadAssocList($this->ct->Table->realtablename, $this->ct->Table->selects, $whereClause, null, null, 1);
            } catch (Exception $e) {
                $this->ct->errors[] = $e->getMessage();
                return null;
            }

            if (count($rows) < 1)
                return null;

            return $this->listing_id;
        }

        $filter = $this->ct->Params->filter;

        if ($filter == '')
            return null;

        if ($this->ct->Env->legacySupport) {
            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $filter;
            $filter = $LayoutProc->fillLayout(null, null, '[]', true);
        }

        $twig = new TwigProcessor($this->ct, $filter);
        $filter = $twig->process();

        if ($twig->errorMessage !== null)
            $this->ct->errors[] = $twig->errorMessage;

        //TODO
        $this->ct->errors[] = 'Filtering not done.';

        $filtering = new Filtering($this->ct, $this->ct->Params->showPublished);
        $filtering->addWhereExpression($filter);

        if ($this->ct->Table->published_field_found)
            $filtering->whereClause->addCondition('published', 1);

        try {
            $rows = database::loadAssocList($this->ct->Table->realtablename, [$this->ct->Table->realidfieldname], $filtering->whereClause, $this->ct->Table->realidfieldname, 'DESC', 1);
        } catch (Exception $e) {
            $this->ct->errors[] = $e->getMessage();
            return null;
        }

        if (count($rows) < 1)
            return null;

        $this->listing_id = $rows[0][$this->ct->Table->realidfieldname];
        return $this->listing_id;
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    function findRecordByUserID(): ?string
    {
        $whereClause = new MySQLWhereClause();

        if ($this->ct->Table->published_field_found)
            $whereClause->addCondition('published', 1);

        if ($this->listing_id === null) {
            //common::enqueueMessage('Parameter listing_id is null');
            echo 'Parameter listing_id is null';
            return null;
        }

        $whereClauseUser = $this->ct->UserIDField_BuildWheres($this->ct->Params->userIdField, $this->listing_id);
        $whereClause->addNestedCondition($whereClauseUser);

        try {
            $rows = database::loadAssocList($this->ct->Table->realtablename, $this->ct->Table->selects, $whereClause, null, null, 1);
        } catch (Exception $e) {
            $this->ct->errors[] = $e->getMessage();
            return -1;
        }

        if (count($rows) < 1)
            return null;

        $row = $rows[0];
        return $row[$this->ct->Table->realidfieldname];
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function Refresh($save_log = 1): int
    {
        $listing_ids_str = common::inputPostString('ids', '', 'create-edit-record');

        if ($listing_ids_str != '') {
            $listing_ids_ = explode(',', $listing_ids_str);
            foreach ($listing_ids_ as $listing_id) {
                if ($listing_id != '') {
                    $listing_id = preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id);
                    if ($this->ct->RefreshSingleRecord($listing_id, $save_log) == -1)
                        return -count($listing_ids_); //negative value means that there is an error
                }
            }
            return count($listing_ids_);
        }

        $listing_id = common::inputGetCmd('listing_id', 0);

        if ($listing_id == 0 or $listing_id == '')
            return 0;

        return $this->ct->RefreshSingleRecord($listing_id, $save_log);
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    function setPublishStatus($status): int
    {
        $listing_ids_str = common::inputPostString('ids', '', 'create-edit-record');
        if ($listing_ids_str != '') {
            $listing_ids_ = explode(',', $listing_ids_str);
            foreach ($listing_ids_ as $listing_id) {
                if ($listing_id != '') {
                    $listing_id = preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id);
                    if ($this->ct->setPublishStatusSingleRecord($listing_id, $status) == -1)
                        return -count($listing_ids_); //negative value means that there is an error
                }
            }
            return count($listing_ids_);
        }

        $listing_id = $this->listing_id;
        if ($listing_id == '' or $listing_id == 0)
            return 0;

        return $this->ct->setPublishStatusSingleRecord($listing_id, $status);
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function delete(): int
    {
        $listing_ids_str = common::inputPostString('ids', '', 'create-edit-record');
        if ($listing_ids_str != '') {

            $listing_ids_ = explode(',', $listing_ids_str);
            foreach ($listing_ids_ as $listing_id) {
                if ($listing_id != '') {
                    $listing_id = preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id);
                    if ($this->ct->deleteSingleRecord($listing_id) == -1)
                        return -count($listing_ids_); //negative value means that there is an error
                }
            }
            return count($listing_ids_);
        }

        $listing_id = common::inputGetCmd('listing_id', 0);
        if ($listing_id == '' or $listing_id == 0)
            return 0;

        return $this->ct->deleteSingleRecord($listing_id);
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    public function copyContent($from, $to)
    {
        //Copy value from one cell to another (drag and drop functionality)
        $from_parts = explode('_', $from);
        $to_parts = explode('_', $to);

        $from_listing_id = $from_parts[0];
        $to_listing_id = $to_parts[0];

        $from_field = $this->ct->Table->getFieldByName($from_parts[1]);
        $to_field = $this->ct->Table->getFieldByName($to_parts[1]);

        if (!isset($from_field['type']))
            die(common::ctJsonEncode(['error' => 'From field not found.']));

        if (!isset($to_field['type']))
            die(common::ctJsonEncode(['error' => 'To field not found.']));

        $from_row = $this->ct->Table->loadRecord($from_listing_id);
        $to_row = $this->ct->Table->loadRecord($to_listing_id);

        $f = $from_field['type'];
        $t = $to_field['type'];

        $ok = true;

        if ($f != $t) {
            switch ($t) {
                case 'string':
                    if (!($f == 'email' or $f == 'int' or $f == 'float' or $f == 'text'))
                        $ok = false;
                    break;

                default:
                    $ok = false;
            }
        }

        if (!$ok)
            die(common::ctJsonEncode(['error' => 'Target and destination field types do not match.']));

        $new_value = '';

        switch ($to_field['type']) {
            case 'sqljoin':
                if ($to_row[$to_field['realfieldname']] !== '')
                    die(common::ctJsonEncode(['error' => 'Target field type is the Table Join. Multiple values not allowed.']));

                break;

            case 'email':

                if ($to_row[$to_field['realfieldname']] !== '')
                    die(common::ctJsonEncode(['error' => 'Target field type is an Email. Multiple values not allowed.']));

                break;

            case 'string':

                if (str_contains($to_row[$to_field['realfieldname']], $from_row[$from_field['realfieldname']]))
                    die(common::ctJsonEncode(['error' => 'Target field already contains this value.']));

                $new_value = $to_row[$to_field['realfieldname']];
                if ($new_value != '')
                    $new_value .= ',';

                $new_value .= $from_row[$from_field['realfieldname']];
                break;

            case 'records':

                $new_items = [''];
                $to_items = explode(',', $to_row[$to_field['realfieldname']]);

                foreach ($to_items as $item) {
                    if ($item != '' and !in_array($item, $new_items))
                        $new_items[] = $item;
                }

                $from_items = explode(',', $from_row[$from_field['realfieldname']]);

                foreach ($from_items as $item) {
                    if ($item != '' and !in_array($item, $new_items))
                        $new_items[] = $item;
                }

                $new_items[] = '';

                if (count($new_items) == count($to_items))
                    die(common::ctJsonEncode(['error' => 'Target field already contains this value(s).']));

                $new_value = implode(',', $new_items);

                break;
        }

        if ($new_value != '') {

            $data = [
                $to_field['realfieldname'] => $new_value
            ];
            $whereClauseUpdate = new MySQLWhereClause();
            $whereClauseUpdate->addCondition($this->ct->Table->realidfieldname, $to_listing_id);

            try {
                database::update($this->ct->Table->realtablename, $data, $whereClauseUpdate);
            } catch (Exception $e) {
                $this->ct->errors[] = $e->getMessage();
                return false;
            }
            return true;
        }
        return false;
    }
}
