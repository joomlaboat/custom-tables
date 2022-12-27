<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\CTUser;
use CustomTables\Email;
use CustomTables\Field;
use CustomTables\Fields;
use CustomTables\Filtering;
use CustomTables\Layouts;
use CustomTables\DataTypes\Tree;
use CustomTables\CustomPHP\CleanExecute;
use CustomTables\TwigProcessor;
use CustomTables\SaveFieldQuerySet;

use Joomla\CMS\Factory;

jimport('joomla.application.component.model');

$siteLibPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
require_once($siteLibPath . 'layout.php');

$libPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'tagprocessor' . DIRECTORY_SEPARATOR;
require_once($libPath . 'valuetags.php');

class CustomTablesModelEditItem extends JModelLegacy
{
    var CT $ct;
    var bool $userIdField_Unique;
    var bool $userIdField_UniqueUsers;
    var ?string $listing_id;
    var bool $isAuthorized;
    var string $pageLayout;
    var ?array $row;

    function __construct()
    {
        $this->userIdField_Unique = false;
        $this->userIdField_UniqueUsers = false;
        parent::__construct();
    }

    function load(CT $ct, bool $addHeaderCode = false): bool
    {
        $this->ct = $ct;
        $this->ct->getTable($ct->Params->tableName, $this->ct->Params->userIdField);

        if ($this->ct->Table->tablename === null) {
            $this->ct->app->enqueueMessage('Table not selected (148).', 'error');
            return false;
        }

        $this->ct->Params->userIdField = $this->findUserIDField($this->ct->Params->userIdField);//to make sure that the field name is real and two userid fields can be used

        if (is_null($ct->Params->msgItemIsSaved))
            $ct->Params->msgItemIsSaved = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED');

        if ($this->ct->Params->editLayout != '') {
            $Layouts = new Layouts($this->ct);
            $this->pageLayout = $Layouts->getLayout($this->ct->Params->editLayout, true, false, $addHeaderCode);
        } else
            $this->pageLayout = '';

        $this->listing_id = $this->ct->Params->listing_id;

        //Load the record
        $this->listing_id = $this->processCustomListingID();

        if ($this->listing_id == 0 and $this->userIdField_UniqueUsers and $this->ct->Params->userIdField != '') {
            //try to find record by userid and load it
            $this->listing_id = $this->findRecordByUserID();
        }

        if (isset($this->row))
            $this->getSpecificVersionIfSet();
        else {
            //default record values
            $this->row = null;//[$this->ct->Table->realidfieldname => '', 'listing_published' => 0];
        }

        return true;
    }

    function findUserIDField($userIdField): string
    {
        if ($userIdField != '') {
            $userIdFields = array();
            $statement_items = tagProcessor_If::ExplodeSmartParams($userIdField); //"and" and "or" as separators

            foreach ($statement_items as $item) {
                if ($item[0] == 'or' or $item[0] == 'and') {
                    $field = $item[1];
                    if (!str_contains($field, '.')) {
                        //Current table field name
                        //find selected field
                        foreach ($this->ct->Table->fields as $fieldrow) {
                            if ($fieldrow['fieldname'] == $field and ($fieldrow['type'] == 'userid' or $fieldrow['type'] == 'user')) {
                                $userIdFields[] = [$item[0], $item[1]];

                                //Following apply to current table fields only and to only one (the last one in the statement)
                                $params = $fieldrow['typeparams'];
                                $parts = JoomlaBasicMisc::csv_explode(',', $params);

                                $this->userIdField_UniqueUsers = false;
                                if (isset($parts[4]) and $parts[4] == 'unique')
                                    $this->userIdField_UniqueUsers = true;

                                break;
                            }
                        }
                    } else {
                        //Table join
                        //parents(children).user
                        $userIdFields[] = [$item[0], $item[1]];
                    }
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

    function processCustomListingID()
    {
        if ($this->listing_id !== null and (is_numeric($this->listing_id) or (!str_contains($this->listing_id, '=') and !str_contains($this->listing_id, '<') and !str_contains($this->listing_id, '>')))) {
            //Normal listing ID or CMD
            $query = 'SELECT ' . implode(',', $this->ct->Table->selects) . ' FROM ' . $this->ct->Table->realtablename
                . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($this->listing_id) . ' LIMIT 1';

            $this->ct->db->setQuery($query);
            $rows = $this->ct->db->loadAssocList();

            if (count($rows) < 1)
                return -1;

            $this->row = $rows[0];
            return $this->listing_id;
        }

        $filter = $this->listing_id;
        if ($filter == '')
            return 0;

        if ($this->ct->Env->legacysupport) {
            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $filter;
            $filter = $LayoutProc->fillLayout(array(), null, '[]', true);
        }

        $twig = new TwigProcessor($this->ct, $filter);
        $filter = $twig->process();

        //TODO
        $this->ct->app->enqueueMessage('Filtering not done.', 'error');

        $filtering = new Filtering($this->ct, $this->ct->Params->showPublished);
        $filtering->addWhereExpression($filter);
        $whereArray = $filtering->where;

        if ($this->ct->Table->published_field_found)
            $whereArray[] = 'published=1';

        $where = '';
        if (count($whereArray) > 0)
            $where = ' WHERE ' . implode(" AND ", $whereArray);

        $query = 'SELECT ' . $this->ct->Table->realidfieldname . ' AS listing_id FROM ' . $this->ct->Table->realtablename . ' ' . $where;

        $query .= ' ORDER BY ' . $this->ct->Table->realidfieldname . ' DESC'; //show last
        $query .= ' LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();

        if (count($rows) < 1) {
            $this->row = array();
            return 0;
        }

        $this->row = $rows[0];
        $this->listing_id = $this->row[$this->ct->Table->realidfieldname];
        return $this->listing_id;
    }

    function findRecordByUserID()
    {
        $wheres = array();

        if ($this->ct->Table->published_field_found)
            $wheres[] = 'published=1';

        $wheres_user = CTUser::UserIDField_BuildWheres($this->ct, $this->ct->Params->userIdField, $this->listing_id);
        $wheres = array_merge($wheres, $wheres_user);
        $query = 'SELECT ' . implode(',', $this->ct->Table->selects) . ' FROM ' . $this->ct->Table->realtablename . ' WHERE ' . implode(' AND ', $wheres) . ' LIMIT 1';
        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();

        if (count($rows) < 1)
            return [];

        $this->row = $rows[0];
        return $this->row[$this->ct->Table->realidfieldname];
    }

    function getSpecificVersionIfSet()
    {
        //get specific Version if set
        $version = $this->ct->Env->jinput->get('version', 0, 'INT');
        if ($version != 0) {
            //get log field
            $log_field = $this->getTypeFieldName('log');
            if ($log_field != '') {
                $new_row = $this->getVersionData($this->row, $log_field, $version);
                if (count($new_row) > 0) {
                    $this->row = $this->makeEmptyRecord($this->listing_id, $new_row['listing_published']);

                    //Copy values
                    foreach ($this->ct->Table->fields as $fieldRow)
                        $this->row[$fieldRow['realfieldname']] = $new_row[$fieldRow['realfieldname']];
                }
            }
        }
    }

    function getTypeFieldName($type)
    {
        foreach ($this->ct->Table->fields as $fieldRow) {
            if ($fieldRow['type'] == $type)
                return $fieldRow['realfieldname'];
        }
        return '';
    }

    function getVersionData($row, $log_field, $version)
    {
        $creation_time_field = $this->getTypeFieldName('changetime');
        $versions = explode(';', $row[$log_field]);

        if ($version <= count($versions)) {
            $data_editor = explode(',', $versions[$version - 2]);
            $data_content = explode(',', $versions[$version - 1]);

            if ($data_content[3] != '') {
                //record versions stored in database table text field as base64 encoded json object
                $obj = json_decode(base64_decode($data_content[3]), true);
                $new_row = $obj[0];

                if ($this->ct->Table->published_field_found)
                    $new_row['published'] = $row['published'];

                $new_row[$this->ct->Table->realidfieldname] = $row[$this->ct->Table->realidfieldname];

                $new_row[$log_field] = $row[$log_field];

                if ($creation_time_field) {
                    $timestamp = date('Y-m-d H:i:s', (int)$data_editor[0]);
                    $new_row[$creation_time_field] = $timestamp;
                }
                return $new_row;
            }
        }
        return array();
    }

    function makeEmptyRecord($listing_id, $published): array
    {
        $row = null;
        $row[$this->ct->Table->realidfieldname] = $listing_id;

        if ($this->ct->Table->published_field_found)
            $row['published'] = $published;

        $row['listing_published'] = $published;

        foreach ($this->ct->Table->fields as $fieldRow)
            $row[$fieldRow['realfieldname']] = '';

        return $row;
    }

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
            if (CTUser::checkIfItemBelongsToUser($this->ct, $this->ct->Params->userIdField, $this->listing_id)) {
                if ($this->ct->Env->user->authorise('core.edit.own', 'com_customtables')) {
                    $this->isAuthorized = true;
                    return true;
                } else
                    $this->isAuthorized = false;
            }
        }
        return false;
    }

    function getCustomTablesBranch($optionName, $startFrom, $langPostFix, $defaultValue): array
    {
        $optionId = 0;
        $filterRootParent = Tree::getOptionIdFull($optionName);

        if ($optionName) {
            $available_categories = Tree::getChildren($optionId, $filterRootParent, 1);

            $query = ' SELECT optionname, id, title_' . $langPostFix . ' AS title FROM #__customtables_options WHERE ';
            $query .= ' id=' . $filterRootParent . ' LIMIT 1';
            $this->ct->db->setQuery($query);
            $rootParentName = $this->ct->db->loadObjectList();

            if ($startFrom == 0) {
                if (count($rootParentName) == 1)
                    JoomlaBasicMisc::array_insert(
                        $available_categories,
                        array(
                            "id" => $filterRootParent,
                            "name" => strtoupper($rootParentName[0]->title),
                            "fullpath" => strtoupper($rootParentName[0]->optionname)

                        ), 0);
            }
        } else {
            $available_categories = Tree::getChildren($optionId, 0, 1);
        }
        if ($defaultValue)
            JoomlaBasicMisc::array_insert(
                $available_categories,
                array(
                    "id" => 0,
                    "name" => $defaultValue,
                    "fullpath" => ''

                ), 0);

        if ($startFrom == 0)
            JoomlaBasicMisc::array_insert($available_categories,
                array("id" => 0,
                    "name" => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ROOT'),
                    "fullpath" => ''),
                count($available_categories));

        return $available_categories;
    }

    function convertESParam2Array($par): array
    {
        $newParameter = [];
        $a = explode(',', $par);
        foreach ($a as $b) {
            $c = trim($b);
            if (strlen($c) > 0)
                $newParameter[] = $c;
        }
        return $newParameter;
    }

    function copy(&$msg, &$link)
    {
        $listing_id = $this->ct->Env->jinput->getCmd("listing_id", 0);
        $query = 'SELECT MAX(' . $this->ct->Table->realidfieldname . ') AS maxid FROM ' . $this->ct->Table->realtablename . ' LIMIT 1';
        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadObjectList();

        if (count($rows) == 0)
            $msg = 'Table not found or something wrong.';

        $new_id = (int)($rows[0]->maxid) + 1;

        if ($this->ct->db->serverType == 'postgresql')
            $query = 'DROP TABLE IF EXISTS ct_tmp';
        else
            $query = 'DROP TEMPORARY TABLE IF EXISTS ct_tmp';

        $this->ct->db->setQuery($query);
        $this->ct->db->execute();

        if ($this->ct->db->serverType == 'postgresql') {
            $query = 'CREATE TEMPORARY TABLE ct_tmp AS TABLE ' . $this->ct->Table->realtablename . ' WITH NO DATA';

            $this->ct->db->setQuery($query);
            $this->ct->db->execute();

            $query = 'INSERT INTO ct_tmp (SELECT * FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . ' = ' . $this->ct->db->quote($listing_id) . ')';

        } else {
            $query = 'CREATE TEMPORARY TABLE ct_tmp SELECT * FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . ' = ' . $this->ct->db->quote($listing_id);
        }
        $this->ct->db->setQuery($query);
        $this->ct->db->execute();

        $sets = array();
        $sets[] = $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($new_id);

        $query = 'UPDATE ct_tmp SET ' . implode(',', $sets) . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);
        $this->ct->db->setQuery($query);
        $this->ct->db->execute();

        $query = 'INSERT INTO ' . $this->ct->Table->realtablename . ' SELECT * FROM ct_tmp WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($new_id);
        $this->ct->db->setQuery($query);
        $this->ct->db->execute();

        $this->ct->Env->jinput->set("listing_id", $new_id);
        $this->ct->Env->jinput->set('old_listing_id', $listing_id);
        $this->listing_id = $new_id;

        if ($this->ct->db->serverType == 'postgresql') {
            $query = 'DROP TABLE IF EXISTS ct_tmp';
        } else {
            $query = 'DROP TEMPORARY TABLE IF EXISTS ct_tmp';
        }
        $this->ct->db->setQuery($query);
        $this->ct->db->execute();

        return $this->store($msg, $link, true, $new_id);
    }

    function store(&$msg, &$link, $isCopy = false, $listing_id = '')
    {
        //IP Filter
        $USER_IP = SaveFieldQuerySet::getUserIP();

        $IP_Black_List = array();

        if (in_array($USER_IP, $IP_Black_List))
            return true;

        if (!$this->check_captcha()) {
            $msg = 'Incorrect Captcha';
            return false;
        }

        $isDebug = $this->ct->Env->jinput->getInt('debug', 0);

        if ($listing_id == '') {
            $listing_id = $this->ct->Params->listing_id;
            if ($listing_id == 0)
                $listing_id = '';
        }

        if ($listing_id == '') {
            $listing_id = $this->ct->Env->jinput->getCmd("listing_id", ''); //TODO : this inconsistency must be fixed
            if ($listing_id == 0)
                $listing_id = '';
        }

        $msg = '';
        $row_old = null;

        if ($listing_id != '')
            $row_old = $this->ct->Table->loadRecord($listing_id);
        else
            $row_old[$this->ct->Table->realidfieldname] = '';

        $fieldsToSave = $this->getFieldsToSave($row_old); //will Read page Layout to find fields to save

        $phpOnChangeFound = false;
        $phpOnAddFound = false;
        $saveField = new SaveFieldQuerySet($this->ct, $row_old, $isCopy);

        foreach ($this->ct->Table->fields as $fieldrow) {

            if (!$saveField->checkIfFieldAlreadyInTheList($fieldrow['fieldname'])) {

                if (in_array($fieldrow['fieldname'], $fieldsToSave))
                    $saveFieldSet = $saveField->getSaveFieldSet($fieldrow);
                else
                    $saveFieldSet = $saveField->applyDefaults($fieldrow);

                $this->row = $saveField->row;
                if ($saveFieldSet !== null) {
                    if (is_array($saveFieldSet))
                        $saveField->saveQuery = array_merge($saveField->saveQuery, $saveFieldSet);
                    else
                        $saveField->saveQuery[] = $saveFieldSet;
                }
            }

            if ($fieldrow['type'] == 'phponadd' and ($listing_id == 0 or $listing_id == '' or $isCopy))
                $phpOnAddFound = true;

            if ($fieldrow['type'] == 'phponchange')
                $phpOnChangeFound = true;
        }

        $listing_id_temp = 0;
        $isItNewRecords = false;
        
        if ($listing_id == 0 or $listing_id == '') {
            $isItNewRecords = true;

            if ($this->ct->Table->published_field_found)
                $saveField->saveQuery[] = 'published=' . $this->ct->Params->publishStatus;

            $listing_id_temp = ESTables::insertRecords($this->ct->Table->realtablename, $saveField->saveQuery);
        } else {
            $this->updateLog($saveField, $listing_id);
            $saveField->runUpdateQuery($saveField->saveQuery, $listing_id);
        }

        if (count($saveField->saveQuery) < 1) {
            $this->ct->app->enqueueMessage('Nothing to save', 'Warning');
            return false;
        }

        if (($listing_id == 0 or $listing_id == '') and $listing_id_temp != 0) {
            $row = $this->ct->Table->loadRecord($listing_id_temp);

            if ($row !== null) {
                $this->ct->Env->jinput->set("listing_id", $row[$this->ct->Table->realidfieldname]);

                if ($phpOnAddFound)
                    $this->doPHPonAdd($row);

                if ($phpOnChangeFound)
                    $this->doPHPonChange($row);

                $listing_id = $row[$this->ct->Table->realidfieldname];
            }
            $this->ct->Table->saveLog($listing_id, 1);
        } else {
            $this->ct->Table->saveLog($listing_id, 2);
            $row = $this->ct->Table->loadRecord($listing_id);

            if ($row !== null) {
                $this->ct->Env->jinput->set("listing_id", $row[$this->ct->Table->realidfieldname]);

                if ($phpOnChangeFound or $this->ct->Table->tablerow['customphp'] != '')
                    $this->doPHPonChange($row);

                if ($phpOnAddFound and $isCopy)
                    $this->doPHPonAdd($row);

            }
        }

        if ($this->ct->Params->onRecordSaveSendEmailTo != '' or $this->ct->Params->onRecordAddSendEmailTo != '') {
            if ($this->ct->Params->onRecordAddSendEmail == 3) {
                //check conditions
                if ($this->checkSendEmailConditions($listing_id, $this->ct->Params->sendEmailCondition)) {
                    //Send email conditions met
                    $this->sendEmailIfAddressSet($listing_id, $row);
                }
            } else {
                if ($isItNewRecords or $isCopy) {
                    //New record
                    if ($this->ct->Params->onRecordAddSendEmail == 1 or $this->ct->Params->onRecordAddSendEmail == 2)
                        $this->sendEmailIfAddressSet($listing_id, $row);
                } else {
                    //Old record
                    if ($this->ct->Params->onRecordAddSendEmail == 2) {
                        $this->sendEmailIfAddressSet($listing_id, $row);
                    }
                }
            }
        }

        //Prepare "Accept Return To" Link
        $art_link = $this->PrepareAcceptReturnToLink($this->ct->Env->jinput->get('returnto', '', 'BASE64'));
        if ($art_link != '')
            $link = $art_link;

        $link = str_replace('*new*', $row[$this->ct->Table->realidfieldname], $link);

        //Refresh menu if needed
        $msg = $this->ct->Params->msgItemIsSaved;

        if ($this->ct->Env->advancedtagprocessor)
            CleanExecute::executeCustomPHPfile($this->ct->Table->tablerow['customphp'], $row, $row_old);

        if ($isDebug)
            die('Debug mode.');//debug mode

        $this->ct->Env->jinput->set("listing_id", $listing_id);
        return true;
    }

    function check_captcha(): bool
    {
        $options = array();
        $captcha = JoomlaBasicMisc::getListToReplace('captcha', $options, $this->pageLayout, '{}');

        if (count($captcha) == 0)
            return true;

        $config = Factory::getConfig()->get('captcha');
        $captcha = JCaptcha::getInstance($config);
        try {
            $completed = $captcha->CheckAnswer(null);//null because nothing should be provided

            if ($completed === false)
                return false;

        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    function getFieldsToSave($row): array
    {
        $this->ct->isEditForm = true; //This changes inputbox prefix

        if ($this->ct->Env->legacysupport) {
            $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
            require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');
            require_once($path . 'layout.php');

            $LayoutProc = new LayoutProcessor($this->ct, $this->pageLayout);
            $pageLayout = $LayoutProc->fillLayout(null, null, '||', false, true);
            tagProcessor_Edit::process($this->ct, $pageLayout, $row, true);
        } else
            $pageLayout = $this->pageLayout;

        $twig = new TwigProcessor($this->ct, $pageLayout, true);
        $pageLayout = $twig->process($row);

        $backgroundFieldTypes = ['creationtime', 'changetime', 'server', 'id', 'md5', 'userid'];

        foreach ($this->ct->Table->fields as $fieldrow) {

            $fn = $fieldrow['fieldname'];
            if (in_array($fieldrow['type'], $backgroundFieldTypes)) {

                if (!in_array($fn, $this->ct->editFields))
                    $this->ct->editFields[] = $fn;
            }

            $fn_str = [];

            $fn_str[] = '"comes_' . $fn . '"';
            $fn_str[] = "'comes_" . $fn . "'";

            foreach ($fn_str as $s) {
                if (str_contains($this->pageLayout, $s)) {

                    if (!in_array($fn, $this->ct->editFields))
                        $this->ct->editFields[] = $fn;
                    break;
                }
            }
        }
        return $this->ct->editFields;
    }

    function updateLog($saveField, $listing_id)
    {
        if ($listing_id == 0 or $listing_id == '')
            return;

        //saves previous version of the record
        //get data
        $fields_to_save = array();
        foreach ($this->ct->Table->fields as $fieldrow) {
            if ($fieldrow['type'] == 'multilangstring' or $fieldrow['type'] == 'multilangtext') {
                $firstLanguage = true;

                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $fields_to_save[] = $fieldrow['realfieldname'] . $postfix;
                }
            } elseif ($fieldrow['type'] != 'log' and $fieldrow['type'] != 'dummy')
                $fields_to_save[] = $fieldrow['realfieldname'];
        }

        //get data
        $query = 'SELECT ' . implode(',', $fields_to_save) . ' FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id) . ' LIMIT 1';

        $this->ct->db->setQuery($query);

        $rows = $this->ct->db->loadAssocList();
        if (count($rows) != 1)
            return;

        $data = base64_encode(json_encode($rows));

        $saveLogQuery = [];
        foreach ($this->ct->Table->fields as $fieldrow) {
            if ($fieldrow['type'] == 'log') {
                $value = time() . ',' . $this->ct->Env->userid . ',' . SaveFieldQuerySet::getUserIP() . ',' . $data . ';';
                $saveLogQuery[] = $fieldrow['realfieldname'] . '=CONCAT(' . $fieldrow['realfieldname'] . ',"' . $value . '")';
            }
        }

        if (count($saveLogQuery) > 0)
            $saveField->runUpdateQuery($saveLogQuery, $listing_id);
    }

    /*
	function CheckValueRule($prefix,$fieldname, $fieldType, $typeParams)
	{
		$valuearray=array();
		$value='';

		switch($fieldType)
			{
				case 'records':

					$typeParamsArrayy=explode(',',$typeParams);
					if(count($typeParamsArrayy)>2)
					{
						$esr_selector=$typeParamsArrayy[2];
						$selectorpair=explode(':',$esr_selector);

						switch($selectorpair[0])
						{
							case 'single';
									$value=$this->ct->Env->jinput->getString($prefix.$fieldname);
								break;

							case 'multi';
									$valuearray = $this->ct->Env->jinput->get( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;
							case 'multibox';
									$valuearray = $this->ct->Env->jinput->get( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;

							case 'radio';
									$value=$this->ct->Env->jinput->getString($prefix.$fieldname);
								break;

							case 'checkbox';
									$valuearray = $this->ct->Env->jinput->get( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;
						}

					}

					break;
				case 'radio':
						$value=$this->ct->Env->jinput->getString($prefix.$fieldname);
					break;

				case 'googlemapcoordinates':
						$value=$this->ct->Env->jinput->getString($prefix.$fieldname);
					break;

				case 'string':
						$value=$this->ct->Env->jinput->getString($prefix.$fieldname);
					break;

				case 'multilangstring':

					$firstlanguage=true;
					foreach($this->ct->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$valuearray[]=$this->ct->Env->jinput->getString($prefix.$fieldname.$postfix);

					}
					$value='"'.implode('","',$valuearray).'"';
					break;


				case 'text':
					$value = ComponentHelper::filterText($this->ct->Env->jinput->post->get($prefix.$fieldname, '', 'raw'));
					break;

				case 'multilangtext':

					$firstlanguage=true;
					foreach($this->ct->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$value_ = ComponentHelper::filterText($this->ct->Env->jinput->post->get($prefix.$fieldname.$postfix, '', 'raw'));

						$valuearray[]=$value_;

					}
					$value='"'.implode('","',$valuearray).'"';
					break;

				case 'int':
						$value=$this->ct->Env->jinput->getInt($prefix.$fieldname,0);
					break;

				case 'user':
						$value=(int)$this->ct->Env->jinput->getInt($prefix.$fieldname,0);
					break;

				case 'float':
						$value=$this->ct->Env->jinput->get($prefix.$fieldname,0,'FLOAT');
					break;


				case 'article':
						$value=$this->ct->Env->jinput->getInt($prefix.$fieldname,0);
					break;

				case 'multilangarticle':

					$firstlanguage=true;
					foreach($this->ct->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$valuearray[]=$this->ct->Env->jinput->getInt($prefix.$fieldname.$postfix,0);

					}
					$value='"'.implode('","',$valuearray).'"';
					break;

				case 'customtables':

						$typeParams_arr=explode(',',$typeParams);
						$optionname=$typeParamsArray[0];

						if($typeParamsArray[1]=='multi')
							$value=$this->getMultiString($optionname, $prefix.'multi_'.$this->ct->Table->tablename.'_'.$fieldname);
						elseif($typeParamsArray[1]=='single')
							$value=$this->getComboString($optionname, $prefix.'combotree_'.$this->ct->Table->tablename.'_'.$fieldname);

					break;

				case 'email':
						$value=$this->ct->Env->jinput->getString($prefix.$fieldname);
					break;

				case 'checkbox':
						$value=$this->ct->Env->jinput->getCmd($prefix.$fieldname);
					break;

				case 'date':
						$value=$this->ct->Env->jinput->getString($prefix.$fieldname);
					break;
			}

		if($value=='')
			$value='""';

		return;
	}
	*/

    function doPHPonAdd(&$row): bool
    {
        $listing_id = $row[$this->ct->Table->realidfieldname];
        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');

        foreach ($this->ct->Table->fields as $fieldrow) {
            $realfieldname = $fieldrow['realfieldname'];
            $typeParams = $fieldrow['typeparams'];

            if ($fieldrow['type'] == 'phponadd') {
                $parts = JoomlaBasicMisc::csv_explode(',', $typeParams);

                if (count($parts) == 1 and str_contains($fieldrow['typeparams'], '"') and !str_contains($fieldrow['typeparams'], '****quote****'))
                    $theScript = $fieldrow['typeparams'];//to support older version when type params field could contain php script only. Also ****quote****  wasn't supported
                else {
                    $theScript = $parts[0];
                    $theScript = str_replace('****quote****', '"', $theScript);
                    $theScript = str_replace('****apos****', "'", $theScript);
                }

                if ($this->ct->Env->legacysupport) {
                    $LayoutProc = new LayoutProcessor($this->ct);
                    $LayoutProc->layout = $theScript;
                    $theScript = $LayoutProc->fillLayout($row, '', '[]', true);
                }

                $twig = new TwigProcessor($this->ct, $theScript);
                $theScript = $twig->process();

                if ($this->ct->Params->allowContentPlugins)
                    $theScript = JoomlaBasicMisc::applyContentPlugins($theScript);

                $theScript = 'return ' . $theScript . ';';

                $error = '';
                $value = CleanExecute::execute($theScript, $error);

                if ($error != '') {
                    $this->ct->app->enqueueMessage($error, 'error');
                    return false;
                }

                $row[$realfieldname] = $value;

                $savePHPQuery = $realfieldname . '=' . $this->ct->db->quote($value);
                $query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET ' . $savePHPQuery . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);

                $this->ct->db->setQuery($query);
                $this->ct->db->execute();
            }
        }
        return true;
    }

    function doPHPonChange(&$row): bool
    {
        $listing_id = $row[$this->ct->Table->realidfieldname];
        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');

        $LayoutProc = new LayoutProcessor($this->ct);

        foreach ($this->ct->Table->fields as $fieldrow) {
            $realfieldname = $fieldrow['realfieldname'];

            if ($fieldrow['type'] == 'phponchange') {
                $parts = JoomlaBasicMisc::csv_explode(',', $fieldrow['typeparams']);

                if (count($parts) == 1 and str_contains($fieldrow['typeparams'], '"') and !str_contains($fieldrow['typeparams'], '****quote****')) {
                    $theScript = $fieldrow['typeparams'];//to support older version when type params field could countain php script only. Also ****quote****  wasn't supported
                } else {
                    $theScript = $parts[0];
                    $theScript = str_replace('****quote****', '"', $theScript);
                    $theScript = str_replace('****apos****', "'", $theScript);
                }

                if ($this->ct->Env->legacysupport) {
                    $LayoutProc->layout = $theScript;
                    $theScript = $LayoutProc->fillLayout($row, '', '[]', true);
                }

                $twig = new TwigProcessor($this->ct, $theScript);
                $theScript = $twig->process();

                if ($this->ct->Params->allowContentPlugins)
                    $theScript = JoomlaBasicMisc::applyContentPlugins($theScript);

                $theScript = 'return ' . $theScript . ';';

                $error = '';
                $value = CleanExecute::execute($theScript, $error);

                if ($error != '') {
                    $this->ct->app->enqueueMessage($error, 'error');
                    return false;
                }

                $row[$realfieldname] = $value;

                $savePHPQuery = $realfieldname . '=' . $this->ct->db->quote($value);
                $query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET ' . $savePHPQuery . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);

                $this->ct->db->setQuery($query);
                $this->ct->db->execute();
            }
        }
        return true;
    }

    function checkSendEmailConditions($listing_id, $condition): bool
    {
        if ($condition == '')
            return true; //if no conditions

        $this->ct->Table->record = $this->getListingRowByID($listing_id);
        $parsed_condition = $this->parseRowLayoutContent($condition);

        $parsed_condition = '(' . $parsed_condition . ' ? 1 : 0)';

        $error = '';
        $value = CleanExecute::execute($parsed_condition, $error);

        if ($error != '') {
            $this->ct->app->enqueueMessage($error, 'error');
            return false;
        }

        if ((int)$value == 1)
            return true;

        return false;

    }

    function getListingRowByID($listing_id)
    {
        $query = 'SELECT ' . implode(',', $this->ct->Table->selects) . ' FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id) . ' LIMIT 1';
        $this->ct->db->setQuery($query);

        $rows = $this->ct->db->loadAssocList();
        if (count($rows) != 1)
            return false;

        return $rows[0];
    }

    function parseRowLayoutContent($content, $applyContentPlagins = true)
    {
        if ($this->ct->Env->legacysupport) {
            require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');

            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $content;
            $content = $LayoutProc->fillLayout($this->ct->Table->record);
        }

        $twig = new TwigProcessor($this->ct, $content);
        $content = $twig->process($this->ct->Table->record);

        if ($applyContentPlagins and $this->ct->Params->allowContentPlugins)
            $content = JoomlaBasicMisc::applyContentPlugins($content);

        return $content;
    }

    function sendEmailIfAddressSet($listing_id, $row)//,$new_username,$new_password)
    {
        if ($this->ct->Params->onRecordAddSendEmailTo != '')
            $status = $this->sendEmailNote($listing_id, $this->ct->Params->onRecordAddSendEmailTo, $row);
        else
            $status = $this->sendEmailNote($listing_id, $this->ct->Params->onRecordSaveSendEmailTo, $row);

        if ($this->ct->Params->emailSentStatusField != '') {

            foreach ($this->ct->Table->fields as $fieldrow) {
                $fieldname = $fieldrow['fieldname'];
                if ($this->ct->Params->emailSentStatusField == $fieldname) {

                    $query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET es_' . $fieldname . '=' . $status . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);

                    $this->ct->db->setQuery($query);
                    $this->ct->db->execute();
                    return;
                }
            }
        }
    }

    function sendEmailNote($listing_id, $emails, $row): int
    {
        $this->ct->Table->record = $this->getListingRowByID($listing_id);

        //Prepare Email List
        $emails_raw = JoomlaBasicMisc::csv_explode(',', $emails, '"', true);

        $emails = array();
        foreach ($emails_raw as $SendToEmail) {
            $EmailPair = JoomlaBasicMisc::csv_explode(':', trim($SendToEmail));

            $EmailTo = $this->parseRowLayoutContent(trim($EmailPair[0]), false);

            if (isset($EmailPair[1]) and $EmailPair[1] != '')
                $Subject = $this->parseRowLayoutContent($EmailPair[1]);
            else
                $Subject = 'Record added to "' . $this->ct->Table->tabletitle . '"';

            if ($EmailTo != '')
                $emails[] = array('email' => $EmailTo, 'subject' => $Subject);
        }

        $Layouts = new Layouts($this->ct);
        $message_layout_content = $Layouts->getLayout($this->ct->Params->onRecordAddSendEmailLayout);

        $note = $this->parseRowLayoutContent($message_layout_content);

        $status = 0;

        foreach ($emails as $SendToEmail) {
            $EmailTo = $SendToEmail['email'];
            $Subject = $SendToEmail['subject'];

            $attachments = [];

            $options = array();
            $fList = JoomlaBasicMisc::getListToReplace('attachment', $options, $note, '{}');
            $i = 0;
            $note_final = $note;
            foreach ($fList as $fItem) {
                $filename = $options[$i];
                if (file_exists($filename)) {
                    $attachments[] = $filename;//TODO: Check the functionality
                    $vlu = '';
                } else
                    $vlu = '<p>File "' . $filename . '"not found.</p>';

                $note_final = str_replace($fItem, $vlu, $note);
                $i++;
            }

            foreach ($this->ct->Table->fields as $fieldrow) {
                if ($fieldrow['type'] == 'file') {
                    $field = new Field($this->ct, $fieldrow, $row);
                    $FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[0]);

                    $filename = $FileFolder . $this->ct->Table->record[$fieldrow['realfieldname']];
                    if (file_exists($filename))
                        $attachments[] = $filename;//TODO: Check the functionality
                }
            }

            $sent = Email::sendEmail($EmailTo, $Subject, $note_final, true, $attachments);

            if ($sent !== true) {
                //Something went wrong. Email not sent.
                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_SENDING_EMAIL') . ': ' . $EmailTo . ' (' . $Subject . ')', 'error');
                $status = 0;
            } else {
                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EMAIL_SENT_TO') . ': ' . $EmailTo . ' (' . $Subject . ')');
                $status = 1;
            }
        }

        return $status;
    }

    function PrepareAcceptReturnToLink($encoded_link): string
    {
        if ($encoded_link == '')
            return '';

        $link = base64_decode($encoded_link);

        if ($link == '')
            return '';

        $query = 'SELECT ' . implode(',', $this->ct->Table->selects) . ' FROM ' . $this->ct->Table->realtablename . ' ORDER BY ' . $this->ct->Table->realidfieldname . ' DESC LIMIT 1';
        $this->ct->db->setQuery($query);

        $rows = $this->ct->db->loadAssocList();
        if (count($rows) != 1)
            return '';

        $row = $rows[0];

        if ($this->ct->Env->legacysupport) {
            require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');
            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $link;
            $link = $LayoutProc->fillLayout($row, "", '[]', true);
        }

        $twig = new TwigProcessor($this->ct, $link);
        return $twig->process($row);
    }

    function Refresh($save_log = 1): int
    {
        $listing_ids_str = $this->ct->Env->jinput->getString('ids', '');

        if ($listing_ids_str != '') {
            $listing_ids_ = explode(',', $listing_ids_str);
            foreach ($listing_ids_ as $listing_id) {
                if ($listing_id != '') {
                    $listing_id = preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id);
                    if ($this->RefreshSingleRecord($listing_id, $save_log) == -1)
                        return -count($listing_ids_); //negative value means that there is an error
                }
            }
            return count($listing_ids_);
        }

        $listing_id = $this->ct->Env->jinput->getCmd("listing_id", 0);

        if ($listing_id == 0 or $listing_id == '')
            return 0;

        return $this->RefreshSingleRecord($listing_id, $save_log);
    }

    protected function RefreshSingleRecord($listing_id, $save_log): int
    {
        $query = 'SELECT ' . implode(',', $this->ct->Table->selects) . ' FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id) . ' LIMIT 1';
        $this->ct->db->setQuery($query);

        $rows = $this->ct->db->loadAssocList();
        if (count($rows) == 0)
            return -1;

        $row = $rows[0];

        $saveField = new SaveFieldQuerySet($this->ct, $row, false);

        $this->ct->Env->jinput->set("listing_id", $listing_id);

        $this->doPHPonChange($row);

        //update MD5s
        $this->updateMD5($saveField, $listing_id);

        if ($save_log == 1)
            $this->ct->Table->saveLog($listing_id, 10);

        //TODO use $saveField->saveField
        //$this->updateDefaultValues($row);

        if ($this->ct->Env->advancedtagprocessor)
            CleanExecute::executeCustomPHPfile($this->ct->Table->tablerow['customphp'], $row, $row);

        //Send email note if applicable
        if ($this->ct->Params->onRecordAddSendEmail == 3 and ($this->ct->Params->onRecordSaveSendEmailTo != '' or $this->ct->Params->onRecordAddSendEmailTo != '')) {
            //check conditions
            if ($this->checkSendEmailConditions($listing_id, $this->ct->Params->sendEmailCondition)) {
                //Send email conditions met
                $this->sendEmailIfAddressSet($listing_id, $row);//,$new_username,$new_password);
            }
        }

        return 1;
    }

    function updateMD5($saveField, $listing_id)
    {
        //TODO: Use savefield
        $saveMD5Query = array();
        foreach ($this->ct->Table->fields as $fieldrow) {
            if ($fieldrow['type'] == 'md5') {
                $fieldsToCount = explode(',', str_replace('"', '', $fieldrow['typeparams']));//only field names, nothing else

                $fields = array();
                foreach ($fieldsToCount as $f) {
                    //to make sure that field exists
                    foreach ($this->ct->Table->fields as $fieldrow2) {
                        if ($fieldrow2['fieldname'] == $f and $fieldrow['fieldname'] != $f)
                            $fields[] = 'COALESCE(' . $fieldrow2['realfieldname'] . ')';
                    }
                }

                if (count($fields) > 1)
                    $saveMD5Query[] = $fieldrow['realfieldname'] . '=md5(CONCAT_WS(' . implode(',', $fields) . '))';
            }
        }

        $saveField->runUpdateQuery($saveMD5Query, $listing_id);
    }

    function setPublishStatus($status): int
    {
        $listing_ids_str = $this->ct->Env->jinput->getString('ids', '');
        if ($listing_ids_str != '') {
            $listing_ids_ = explode(',', $listing_ids_str);
            foreach ($listing_ids_ as $listing_id) {
                if ($listing_id != '') {
                    $listing_id = preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id);
                    if ($this->setPublishStatusSingleRecord($listing_id, $status) == -1)
                        return -count($listing_ids_); //negative value means that there is an error
                }
            }
            return count($listing_ids_);
        }

        $listing_id = $this->listing_id;
        if ($listing_id == '' or $listing_id == 0)
            return 0;

        return $this->setPublishStatusSingleRecord($listing_id, $status);
    }

    public function setPublishStatusSingleRecord($listing_id, $status): int
    {
        if (!$this->ct->Table->published_field_found)
            return -1;

        $query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET published=' . (int)$status . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);

        $this->ct->db->setQuery($query);
        $this->ct->db->execute();

        if ($status == 1)
            $this->ct->Table->saveLog($listing_id, 3);
        else
            $this->ct->Table->saveLog($listing_id, 4);

        $this->RefreshSingleRecord($listing_id, 0);

        return 1;
    }

    function delete(): int
    {
        $listing_ids_str = $this->ct->Env->jinput->getString('ids', '');
        if ($listing_ids_str != '') {

            $listing_ids_ = explode(',', $listing_ids_str);
            foreach ($listing_ids_ as $listing_id) {
                if ($listing_id != '') {
                    $listing_id = preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id);
                    if ($this->deleteSingleRecord($listing_id) == -1)
                        return -count($listing_ids_); //negative value means that there is an error
                }
            }
            return count($listing_ids_);
        }

        $listing_id = $this->ct->Env->jinput->getCmd("listing_id", 0);
        if ($listing_id == '' or $listing_id == 0)
            return 0;

        return $this->deleteSingleRecord($listing_id);
    }

    public function deleteSingleRecord($listing_id): int
    {
        //delete images if exist
        $imageMethods = new CustomTablesImageMethods;

        $query = 'SELECT * FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();

        if (count($rows) == 0)
            return -1;

        $row = $rows[0];

        foreach ($this->ct->Table->fields as $fieldrow) {
            $field = new Field($this->ct, $fieldrow, $row);

            if ($field->type == 'image') {
                $ImageFolder_ = CustomTablesImageMethods::getImageFolder($field->params);

                //delete single image
                if ($row[$field->realfieldname] !== null) {
                    $imageMethods->DeleteExistingSingleImage(
                        $row[$field->realfieldname],
                        $ImageFolder_,
                        $field->params[0],
                        $this->ct->Table->realtablename,
                        $field->realfieldname,
                        $this->ct->Table->realidfieldname
                    );
                }
            } elseif ($field->type == 'imagegallery') {
                $ImageFolder_ = CustomTablesImageMethods::getImageFolder($field->params);

                //delete gallery images if exist
                $galleryName = $field->fieldname;
                $photoTableName = '#__customtables_gallery_' . $this->ct->Table->tablename . '_' . $galleryName;

                $query = 'SELECT photoid FROM ' . $photoTableName . ' WHERE listingid=' . $this->ct->db->quote($listing_id);
                $this->ct->db->setQuery($query);

                $photoRows = $this->ct->db->loadObjectList();

                $imageGalleryPrefix = 'g';

                foreach ($photoRows as $photoRow) {
                    $imageMethods->DeleteExistingGalleryImage(
                        $ImageFolder_,
                        $imageGalleryPrefix,
                        $this->ct->Table->tableid,
                        $galleryName,
                        $photoRow->photoid,
                        $field->params[0],
                        true
                    );
                }
            }
        }

        $query = 'DELETE FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);
        $this->ct->db->setQuery($query);
        $this->ct->db->execute();

        $this->ct->Table->saveLog($listing_id, 5);

        $new_row = array();

        if ($this->ct->Env->advancedtagprocessor)
            CleanExecute::executeCustomPHPfile($this->ct->Table->tablerow['customphp'], $new_row, $row);

        return 1;
    }

    public function copyContent($from, $to)
    {
        //Copy value from one cell to another (drag and drop functionality)
        $from_parts = explode('_', $from);
        $to_parts = explode('_', $to);

        $from_listing_id = $from_parts[0];
        $to_listing_id = $to_parts[0];

        $from_field = Fields::FieldRowByName($from_parts[1], $this->ct->Table->fields);
        $to_field = Fields::FieldRowByName($to_parts[1], $this->ct->Table->fields);

        if (!isset($from_field['type']))
            die(json_encode(['error' => 'From field not found.']));

        if (!isset($to_field['type']))
            die(json_encode(['error' => 'To field not found.']));

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
            die(json_encode(['error' => 'Target and destination field types do not match.']));

        $new_value = '';

        switch ($to_field['type']) {
            case 'sqljoin':
                if ($to_row[$to_field['realfieldname']] !== '')
                    die(json_encode(['error' => 'Target field type is the Table Join. Multiple values not allowed.']));

                break;

            case 'customtables':
                if ($to_row[$to_field['realfieldname']] !== '')
                    die(json_encode(['error' => 'Target field type is a Tree. Multiple values not allowed.']));

                break;

            case 'email':

                if ($to_row[$to_field['realfieldname']] !== '')
                    die(json_encode(['error' => 'Target field type is an Email. Multiple values not allowed.']));

                break;

            case 'string':

                if (str_contains($to_row[$to_field['realfieldname']], $from_row[$from_field['realfieldname']]))
                    die(json_encode(['error' => 'Target field already contains this value.']));

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
                    die(json_encode(['error' => 'Target field already contains this value(s).']));

                $new_value = implode(',', $new_items);

                break;
        }

        if ($new_value != '') {
            $query = 'UPDATE ' . $this->ct->Table->realtablename
                . ' SET ' . $to_field['realfieldname'] . '= ' . $this->ct->db->quote($new_value)
                . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($to_listing_id);

            $this->ct->db->setQuery($query);
            $this->ct->db->execute();
            return true;
        }

        return false;
    }
}
