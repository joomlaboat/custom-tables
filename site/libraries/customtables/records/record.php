<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

//use CustomTablesImageMethods;
use Edit;
use Exception;
use JCaptcha;
use Joomla\CMS\Factory;
use CustomTables\CustomPHP\CleanExecute;

class record
{
    var CT $ct;
    //public Field $field;
    var ?array $row_old;
    var ?array $row_new;
    //var bool $isCopy;
    //var array $saveQuery;
    var Edit $editForm;
    var ?string $listing_id;

    function __construct(CT $ct)
    {
        $this->ct = $ct;
        $this->row_old = null;
        $this->row_new = null;
        $this->listing_id = null;

        $this->editForm = new Edit($ct);
    }

    function save(?string $listing_id, bool $isCopy): bool
    {
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

        if ($listing_id == 0 or $listing_id == '')
            $listing_id = null;

        if ($listing_id !== null) {
            $this->listing_id = $listing_id;
            $this->row_old = $this->ct->Table->loadRecord($this->listing_id);
        } else
            $this->row_old[$this->ct->Table->realidfieldname] = '';// Why?

        $fieldsToSave = $this->getFieldsToSave($this->row_old); //will Read page Layout to find fields to save

        if (($this->ct->LayoutVariables['captcha'] ?? null)) {
            if (!$this->check_captcha()) {
                $this->ct->Params->msgItemIsSaved = 'COM_CUSTOMTABLES_INCORRECT_CAPTCHA';
                return false;
            }
        }

        $phpOnChangeFound = false;
        $phpOnAddFound = false;
        $saveField = new SaveFieldQuerySet($this->ct, $this->row_old, $isCopy);

        foreach ($this->ct->Table->fields as $fieldRow) {

            if (!$saveField->checkIfFieldAlreadyInTheList($fieldRow['realfieldname'])) {

                if (in_array($fieldRow['fieldname'], $fieldsToSave))
                    $saveField->getSaveFieldSet($fieldRow);
                else
                    $saveField->applyDefaults($fieldRow);
                /*
                                $this->row_new = $saveField->row;
                                if ($saveFieldValue !== null) {
                                    if (is_array($saveFieldValue))
                                        $saveField->saveQuery = array_merge($saveField->saveQuery, $saveFieldValue);
                                    else
                                        $saveField->saveQuery[$fieldRow['realfieldname']] = $saveField->row[$fieldRow['realfieldname']];
                                }
                                */
            }

            if ($fieldRow['type'] == 'phponadd' and ($this->listing_id === null or $isCopy))
                $phpOnAddFound = true;

            if ($fieldRow['type'] == 'phponchange')
                $phpOnChangeFound = true;
        }

        $isItNewRecords = false;

        if ($this->listing_id === null) {
            $isItNewRecords = true;

            if ($this->ct->Table->published_field_found)
                $saveField->row_new['published'] = $this->ct->Params->publishStatus;

            $this->listing_id = database::insert($this->ct->Table->realtablename, $saveField->row_new);
        } else {
            $this->updateLog($this->listing_id);
            database::update($this->ct->Table->realtablename, $saveField->row_new, [$this->ct->Table->realidfieldname => $this->listing_id]);
        }

        if (count($saveField->row_new) < 1) {
            $this->ct->app->enqueueMessage('Nothing to save', 'Warning');
            return false;
        }

        if ($isItNewRecords) {
            if ($this->listing_id !== null) {
                $this->row_new = $this->ct->Table->loadRecord($this->listing_id);

                if ($this->row_new !== null) {
                    //$this->listing_id = $this->row_new[$this->ct->Table->realidfieldname];
                    //common::inputSet("listing_id", $this->row_new[$this->ct->Table->realidfieldname]);

                    if ($this->ct->Env->advancedTagProcessor) {
                        if ($phpOnAddFound)
                            CleanExecute::doPHPonAdd($this->ct, $this->row_new);

                        if ($phpOnChangeFound)
                            CleanExecute::doPHPonChange($this->ct, $this->row_new);
                    }

                    //$this->listing_id = $this->row_new[$this->ct->Table->realidfieldname];
                    $this->ct->Table->saveLog($this->listing_id, 1);
                }
            }
        } else {
            $this->ct->Table->saveLog($this->listing_id, 2);
            $this->row_new = $this->ct->Table->loadRecord($this->listing_id);
            if ($this->row_new !== null) {
                common::inputSet("listing_id", $this->row_new[$this->ct->Table->realidfieldname]);
                if ($this->ct->Env->advancedTagProcessor) {
                    if ($phpOnChangeFound or $this->ct->Table->tablerow['customphp'] != '')
                        CleanExecute::doPHPonChange($this->ct, $this->row_new);
                    if ($phpOnAddFound and $isCopy)
                        CleanExecute::doPHPonAdd($this->ct, $this->row_new);
                }
            }
        }

        if ($this->ct->Params->onRecordSaveSendEmailTo != '' or $this->ct->Params->onRecordAddSendEmailTo != '') {
            if ($this->ct->Params->onRecordAddSendEmail == 3) {
                //check conditions
                if ($saveField->checkSendEmailConditions($this->listing_id, $this->ct->Params->sendEmailCondition)) {
                    //Send email conditions met
                    $saveField->sendEmailIfAddressSet($this->listing_id, $this->row_new);
                }
            } else {
                if ($isItNewRecords or $isCopy) {
                    //New record
                    if ($this->ct->Params->onRecordAddSendEmail == 1 or $this->ct->Params->onRecordAddSendEmail == 2)
                        $saveField->sendEmailIfAddressSet($this->listing_id, $this->row_new);
                } else {
                    //Old record
                    if ($this->ct->Params->onRecordAddSendEmail == 2) {
                        $saveField->sendEmailIfAddressSet($this->listing_id, $this->row_new);
                    }
                }
            }
        }
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
        if (defined('_JEXEC')) {
            $config = Factory::getConfig()->get('captcha');
            $captcha = JCaptcha::getInstance($config);
            try {
                $completed = $captcha->CheckAnswer(null);//null because nothing should be provided

                if ($completed === false)
                    return false;

            } catch (Exception $e) {
                $this->ct->app->enqueueMessage($e->getMessage(), 'error');
                return false;
            }
            return true;
        } elseif (defined('WPINC')) {
            return true;
        }
        return false;
    }

    function updateLog($listing_id): bool
    {
        if ($listing_id == 0 or $listing_id == '')
            return false;

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

        try {
            $rows = database::loadAssocList($query);
        } catch (Exception $e) {
            $this->ct->app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        if (count($rows) != 1)
            return false;

        $data = base64_encode(json_encode($rows));

        foreach ($this->ct->Table->fields as $fieldrow) {
            if ($fieldrow['type'] == 'log') {
                $value = time() . ',' . $this->ct->Env->user->id . ',' . SaveFieldQuerySet::getUserIP() . ',' . $data . ';';
                database::setQuery('UPDATE ' . $this->ct->Table->realtablename . ' SET '
                    . database::quoteName($fieldrow['realfieldname']) . '=CONCAT(' . $fieldrow['realfieldname'] . ',' . database::quote($value) . ')');
            }
        }
        return true;
    }
}