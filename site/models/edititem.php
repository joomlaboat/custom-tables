<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\Fields;
use CustomTables\Filtering;
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
    var ?array $row;
    var Edit $editForm;

    function __construct()
    {
        $this->userIdField_Unique = false;
        $this->userIdField_UniqueUsers = false;
        parent::__construct();
    }

    function load(CT $ct): bool
    {
        $this->ct = $ct;
        $this->ct->getTable($ct->Params->tableName, $this->ct->Params->userIdField);
        $this->row = null;

        if ($this->ct->Table->tablename === null) {
            $this->ct->app->enqueueMessage('Table not selected (61).', 'error');
            return false;
        }

        $this->ct->Params->userIdField = $this->findUserIDField($this->ct->Params->userIdField);//to make sure that the field name is real and two userid fields can be used

        if (is_null($ct->Params->msgItemIsSaved))
            $ct->Params->msgItemIsSaved = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED');

        $this->listing_id = $this->ct->Params->listing_id;
        //Load the record
        $this->listing_id = $this->processCustomListingID();

        if ($this->listing_id == 0 and $this->userIdField_UniqueUsers and $this->ct->Params->userIdField != '') {
            //try to find record by userid and load it
            $this->listing_id = $this->findRecordByUserID();
        }

        if (isset($this->row))
            $this->getSpecificVersionIfSet();

        $this->editForm = new Edit($ct);
        $this->editForm->load();
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
                . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . database::quote($this->listing_id) . ' LIMIT 1';

            $rows = database::loadAssocList($query);

            if (count($rows) < 1)
                return -1;

            $this->row = $rows[0];
            return $this->listing_id;
        }

        $filter = $this->listing_id;
        if ($filter == '')
            return 0;

        if ($this->ct->Env->legacySupport) {
            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $filter;
            $filter = $LayoutProc->fillLayout(null, null, '[]', true);
        }

        $twig = new TwigProcessor($this->ct, $filter);
        $filter = $twig->process();

        if ($twig->errorMessage !== null)
            $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

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
        $rows = database::loadAssocList($query);

        if (count($rows) < 1) {
            $this->row = array();
            return 0;
        }

        $this->row = $rows[0];
        $this->listing_id = $this->row[$this->ct->Table->realidfieldname];
        return $this->listing_id;
    }

    function findRecordByUserID(): ?string
    {
        $wheres = array();

        if ($this->ct->Table->published_field_found)
            $wheres[] = 'published=1';

        $wheres_user = CTUser::UserIDField_BuildWheres($this->ct, $this->ct->Params->userIdField, $this->listing_id);
        $wheres = array_merge($wheres, $wheres_user);
        $query = 'SELECT ' . implode(',', $this->ct->Table->selects) . ' FROM ' . $this->ct->Table->realtablename . ' WHERE ' . implode(' AND ', $wheres) . ' LIMIT 1';
        $rows = database::loadAssocList($query);

        if (count($rows) < 1)
            return null;

        $this->row = $rows[0];
        return $this->row[$this->ct->Table->realidfieldname];
    }

    function getSpecificVersionIfSet()
    {
        //get specific Version if set
        $version = common::inputGet('version', 0, 'INT');
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
                    $new_row['listing_published'] = $row['listing_published'];

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
            $row['listing_published'] = $published;

        //$row['listing_published'] = $published;

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
            $rootParentName = database::loadObjectList($query);

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
        $listing_id = common::inputGetCmd("listing_id", 0);
        $query = 'SELECT MAX(' . $this->ct->Table->realidfieldname . ') AS maxid FROM ' . $this->ct->Table->realtablename . ' LIMIT 1';
        $rows = database::loadObjectList($query);

        if (count($rows) == 0)
            $msg = 'Table not found or something wrong.';

        $new_id = (int)($rows[0]->maxid) + 1;
        $serverType = database::getServerType();
        if ($serverType == 'postgresql')
            $query = 'DROP TABLE IF EXISTS ct_tmp';
        else
            $query = 'DROP TEMPORARY TABLE IF EXISTS ct_tmp';

        database::setQuery($query);

        $serverType = database::getServerType();
        if ($serverType == 'postgresql') {
            $query = 'CREATE TEMPORARY TABLE ct_tmp AS TABLE ' . $this->ct->Table->realtablename . ' WITH NO DATA';
            database::setQuery($query);

            $query = 'INSERT INTO ct_tmp (SELECT * FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . ' = ' . database::quote($listing_id) . ')';

        } else {
            $query = 'CREATE TEMPORARY TABLE ct_tmp SELECT * FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . ' = ' . database::quote($listing_id);
        }
        database::setQuery($query);

        $sets = array();
        $sets[] = $this->ct->Table->realidfieldname . '=' . database::quote($new_id);

        $query = 'UPDATE ct_tmp SET ' . implode(',', $sets) . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . database::quote($listing_id);
        database::setQuery($query);

        $query = 'INSERT INTO ' . $this->ct->Table->realtablename . ' SELECT * FROM ct_tmp WHERE ' . $this->ct->Table->realidfieldname . '=' . database::quote($new_id);
        database::setQuery($query);

        common::inputSet("listing_id", $new_id);
        common::inputSet('old_listing_id', $listing_id);
        $this->listing_id = $new_id;
        $serverType = database::getServerType();
        if ($serverType == 'postgresql') {
            $query = 'DROP TABLE IF EXISTS ct_tmp';
        } else {
            $query = 'DROP TEMPORARY TABLE IF EXISTS ct_tmp';
        }
        database::setQuery($query);
        return $this->store($msg, $link, true, $new_id);
    }

    function store(&$msg, &$link, $isCopy = false, $listing_id = '')
    {
        //IP Filter
        $USER_IP = SaveFieldQuerySet::getUserIP();

        $IP_Black_List = array();

        if (in_array($USER_IP, $IP_Black_List))
            return true;

        $isDebug = common::inputGetInt('debug', 0);

        if ($listing_id == '') {
            $listing_id = $this->ct->Params->listing_id;
            if ($listing_id == 0)
                $listing_id = '';
        }

        if ($listing_id == '') {
            $listing_id = common::inputGetCmd("listing_id", ''); //TODO : this inconsistency must be fixed
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

        if (($this->ct->LayoutVariables['captcha'] ?? null)) {
            if (!$this->check_captcha()) {
                $msg = 'COM_CUSTOMTABLES_INCORRECT_CAPTCHA';
                return false;
            }
        }

        $phpOnChangeFound = false;
        $phpOnAddFound = false;
        $saveField = new SaveFieldQuerySet($this->ct, $row_old, $isCopy);

        foreach ($this->ct->Table->fields as $fieldRow) {

            if (!$saveField->checkIfFieldAlreadyInTheList($fieldRow['fieldname'])) {

                if (in_array($fieldRow['fieldname'], $fieldsToSave))
                    $saveFieldSet = $saveField->getSaveFieldSet($fieldRow);
                else
                    $saveFieldSet = $saveField->applyDefaults($fieldRow);

                $this->row = $saveField->row;
                if ($saveFieldSet !== null) {
                    if (is_array($saveFieldSet))
                        $saveField->saveQuery = array_merge($saveField->saveQuery, $saveFieldSet);
                    else
                        $saveField->saveQuery[] = $saveFieldSet;
                }
            }

            if ($fieldRow['type'] == 'phponadd' and ($listing_id == 0 or $listing_id == '' or $isCopy))
                $phpOnAddFound = true;

            if ($fieldRow['type'] == 'phponchange')
                $phpOnChangeFound = true;
        }

        $listing_id_temp = 0;
        $isItNewRecords = false;

        if ($listing_id === null or $listing_id == 0 or $listing_id == '') {
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
                common::inputSet("listing_id", $row[$this->ct->Table->realidfieldname]);

                if ($this->ct->Env->advancedTagProcessor) {
                    if ($phpOnAddFound)
                        CleanExecute::doPHPonAdd($this->ct, $row);

                    if ($phpOnChangeFound)
                        CleanExecute::doPHPonChange($this->ct, $row);
                }

                $listing_id = $row[$this->ct->Table->realidfieldname];
            }
            $this->ct->Table->saveLog($listing_id, 1);
        } else {
            $this->ct->Table->saveLog($listing_id, 2);
            $row = $this->ct->Table->loadRecord($listing_id);
            if ($row !== null) {
                common::inputSet("listing_id", $row[$this->ct->Table->realidfieldname]);
                if ($this->ct->Env->advancedTagProcessor) {
                    if ($phpOnChangeFound or $this->ct->Table->tablerow['customphp'] != '')
                        CleanExecute::doPHPonChange($this->ct, $row);
                    if ($phpOnAddFound and $isCopy)
                        CleanExecute::doPHPonAdd($this->ct, $row);
                }
            }
        }

        if ($this->ct->Params->onRecordSaveSendEmailTo != '' or $this->ct->Params->onRecordAddSendEmailTo != '') {
            if ($this->ct->Params->onRecordAddSendEmail == 3) {
                //check conditions
                if ($saveField->checkSendEmailConditions($listing_id, $this->ct->Params->sendEmailCondition)) {
                    //Send email conditions met
                    $saveField->sendEmailIfAddressSet($listing_id, $row);
                }
            } else {
                if ($isItNewRecords or $isCopy) {
                    //New record
                    if ($this->ct->Params->onRecordAddSendEmail == 1 or $this->ct->Params->onRecordAddSendEmail == 2)
                        $saveField->sendEmailIfAddressSet($listing_id, $row);
                } else {
                    //Old record
                    if ($this->ct->Params->onRecordAddSendEmail == 2) {
                        $saveField->sendEmailIfAddressSet($listing_id, $row);
                    }
                }
            }
        }

        //Prepare "Accept Return To" Link
        $return2Link = common::inputGet('returnto', '', 'BASE64');
        if ($return2Link != '')
            $link = $this->PrepareAcceptReturnToLink($return2Link, $msg);

        //$link = str_replace('*new*', $row[$this->ct->Table->realidfieldname], $link);

        //Refresh menu if needed
        if ($msg != '')
            $msg = $this->ct->Params->msgItemIsSaved;

        if ($this->ct->Env->advancedTagProcessor) {

            try {
                CleanExecute::executeCustomPHPfile($this->ct->Table->tablerow['customphp'], $row, $row_old);
            } catch (Exception $e) {

                echo 'Custom PHP file: ' . $this->ct->Table->tablerow['customphp'] . '<br/>';
                $msg = $e->getMessage();
                echo $msg;
                die;
            }
            $return2Link_Updated = common::inputGet('returnto', '', 'BASE64');
            if ($return2Link != $return2Link_Updated)
                $link = base64_decode($return2Link_Updated);
        }

        if ($isDebug)
            die('Debug mode.');//debug mode

        $this->listing_id = $listing_id;
        common::inputSet("listing_id", $listing_id);
        return true;
    }

    function getFieldsToSave($row): array
    {
        $this->ct->isEditForm = true; //These changes input box prefix

        $pageLayout = $this->editForm->processLayout($row);
        $backgroundFieldTypes = ['creationtime', 'changetime', 'server', 'id', 'md5', 'userid'];
        $fieldsToEdit = [];

        foreach ($this->ct->Table->fields as $fieldRow) {

            $fieldName = $fieldRow['fieldname'];

            if (in_array($fieldName, $this->ct->editFields)) {
                if (!Fields::isVirtualField($fieldRow))
                    $fieldsToEdit[] = $fieldName;

            } else {
                if (in_array($fieldRow['type'], $backgroundFieldTypes)) {

                    if (!in_array($fieldName, $fieldsToEdit) and !Fields::isVirtualField($fieldRow))
                        $fieldsToEdit[] = $fieldName;
                }

                $fn_str = [];

                $fn_str[] = '"comes_' . $fieldName . '"';
                $fn_str[] = "'comes_" . $fieldName . "'";

                foreach ($fn_str as $s) {
                    if (str_contains($pageLayout, $s)) {

                        if (!in_array($fieldName, $fieldsToEdit) and !Fields::isVirtualField($fieldRow))
                            $fieldsToEdit[] = $fieldName;
                        break;
                    }
                }
            }
        }
        return $fieldsToEdit;
    }

    function check_captcha(): bool
    {
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
            } elseif ($fieldrow['type'] != 'log' and $fieldrow['type'] != 'dummy' and !Fields::isVirtualField($fieldrow))
                $fields_to_save[] = $fieldrow['realfieldname'];
        }

        //get data
        $query = 'SELECT ' . implode(',', $fields_to_save) . ' FROM ' . $this->ct->Table->realtablename . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . database::quote($listing_id) . ' LIMIT 1';
        $rows = database::loadAssocList($query);

        if (count($rows) != 1)
            return;

        $data = base64_encode(json_encode($rows));

        $saveLogQuery = [];
        foreach ($this->ct->Table->fields as $fieldrow) {
            if ($fieldrow['type'] == 'log') {
                $value = time() . ',' . $this->ct->Env->user->id . ',' . SaveFieldQuerySet::getUserIP() . ',' . $data . ';';
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
									$value=common::inputGetString($prefix.$fieldname);
								break;

							case 'multi';
									$valuearray = common::inputGet( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;
							case 'multibox';
									$valuearray = common::inputGet( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;

							case 'radio';
									$value=common::inputGetString($prefix.$fieldname);
								break;

							case 'checkbox';
									$valuearray = common::inputGet( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;
						}

					}

					break;
				case 'radio':
						$value=common::inputGetString($prefix.$fieldname);
					break;

				case 'googlemapcoordinates':
						$value=common::inputGetString($prefix.$fieldname);
					break;

				case 'string':
						$value=common::inputGetString($prefix.$fieldname);
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

						$valuearray[]=common::inputGetString($prefix.$fieldname.$postfix);

					}
					$value='"'.implode('","',$valuearray).'"';
					break;


				case 'text':
					$value = ComponentHelper::filterText(common::inputPost($prefix.$fieldname, '', 'raw'));
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

						$value_ = ComponentHelper::filterText(common::inputPost($prefix.$fieldname.$postfix, '', 'raw'));

						$valuearray[]=$value_;

					}
					$value='"'.implode('","',$valuearray).'"';
					break;

				case 'int':
						$value=common::inputGetInt($prefix.$fieldname,0);
					break;

				case 'user':
						$value=(int)common::inputGetInt($prefix.$fieldname,0);
					break;

				case 'float':
						$value=common::inputGet($prefix.$fieldname,0,'FLOAT');
					break;


				case 'article':
						$value=common::inputGetInt($prefix.$fieldname,0);
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

						$valuearray[]=common::inputGetInt($prefix.$fieldname.$postfix,0);

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
						$value=common::inputGetString($prefix.$fieldname);
					break;

				case 'checkbox':
						$value=common::inputGetCmd($prefix.$fieldname);
					break;

				case 'date':
						$value=common::inputGetString($prefix.$fieldname);
					break;
			}

		if($value=='')
			$value='""';

		return;
	}
	*/

    function PrepareAcceptReturnToLink($encoded_link, string &$msg): string
    {
        if ($encoded_link == '')
            return '';

        $link = base64_decode($encoded_link);

        if ($link == '')
            return '';

        $query = 'SELECT ' . implode(',', $this->ct->Table->selects) . ' FROM ' . $this->ct->Table->realtablename . ' ORDER BY ' . $this->ct->Table->realidfieldname . ' DESC LIMIT 1';
        $rows = database::loadAssocList($query);
        if (count($rows) != 1) {
            $msg = 'Record not saved';
            return '';
        }

        $row = $rows[0];

        if ($this->ct->Env->legacySupport) {
            require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');
            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $link;
            $link = $LayoutProc->fillLayout($row, "", '[]', true);
        }

        $twig = new TwigProcessor($this->ct, $link);
        try {
            $link = $twig->process($row);
        } catch (Exception $e) {
            $this->ct->app->enqueueMessage($e->getMessage(), 'error');
            $link = '';
            $msg = $twig->errorMessage;
        }

        if ($twig->errorMessage !== null) {
            $this->ct->app->enqueueMessage($twig->errorMessage, 'error');
            $link = '';
            $msg = $twig->errorMessage;
        }
        return $link;
    }

    function Refresh($save_log = 1): int
    {
        $listing_ids_str = common::inputGetString('ids', '');

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

        $listing_id = common::inputGetCmd("listing_id", 0);

        if ($listing_id == 0 or $listing_id == '')
            return 0;

        return $this->ct->RefreshSingleRecord($listing_id, $save_log);
    }

    function setPublishStatus($status): int
    {
        $listing_ids_str = common::inputGetString('ids', '');
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

    function delete(): int
    {
        $listing_ids_str = common::inputGetString('ids', '');
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

        $listing_id = common::inputGetCmd("listing_id", 0);
        if ($listing_id == '' or $listing_id == 0)
            return 0;

        return $this->ct->deleteSingleRecord($listing_id);
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
                . ' SET ' . $to_field['realfieldname'] . '= ' . database::quote($new_value)
                . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . database::quote($to_listing_id);

            database::setQuery($query);
            return true;
        }
        return false;
    }
}
