<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use CustomTables\CustomPHP;
use CustomTables\ctProHelpers;

class record
{
    var CT $ct;
    var ?array $row_old;
    var ?array $row_new;
    var Edit $editForm;
    var ?string $listing_id;
    var bool $isItNewRecord;

    function __construct(CT $ct)
    {
        $this->ct = $ct;
        $this->row_old = null;
        $this->row_new = null;
        $this->listing_id = null;
        $this->editForm = new Edit($ct);
        $this->isItNewRecord = true;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function save(?string $listing_id, bool $isCopy): bool
    {
        if (empty($listing_id)) {
            $listing_id = $this->ct->Params->listing_id;
            if ($listing_id == 0)
                $listing_id = '';
        }

        if (empty($listing_id)) {
            $listing_id = common::inputGetCmd('listing_id', ''); //TODO : this inconsistency must be fixed
            if ($listing_id == 0)
                $listing_id = '';
        }

        if (empty($listing_id))
            $listing_id = null;

        if ($listing_id !== null) {
            $this->listing_id = $listing_id;
            $this->row_old = $this->ct->Table->loadRecord($this->listing_id);
        } else
            $this->row_old[$this->ct->Table->realidfieldname] = '';// Why?

        $fieldsToSave = $this->getFieldsToSave($this->row_old); //will Read page Layout to find fields to save

        if (($this->ct->LayoutVariables['captcha'] ?? null)) {
            if (!$this->check_captcha()) {
                common::enqueueMessage(common::translate('COM_CUSTOMTABLES_INCORRECT_CAPTCHA'));
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
            }

            if ($fieldRow['type'] == 'phponadd' and ($this->listing_id === null or $isCopy))
                $phpOnAddFound = true;

            if ($fieldRow['type'] == 'phponchange')
                $phpOnChangeFound = true;
        }

        if (empty($this->listing_id)) {

            $this->isItNewRecord = true;

            if ($this->ct->Table->published_field_found)
                $saveField->row_new['published'] = $this->ct->Params->publishStatus;

            try {
                $this->listing_id = database::insert($this->ct->Table->realtablename, $saveField->row_new);
            } catch (Exception $e) {
                $this->ct->errors[] = $e->getMessage();
                die($e->getMessage());
            }

        } else {
            $this->isItNewRecord = false;

            if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers'))
                ctProHelpers::updateLog($this->ct, $this->listing_id);

            try {
                $whereClauseUpdate = new MySQLWhereClause();
                $whereClauseUpdate->addCondition($this->ct->Table->realidfieldname, $this->listing_id);
                database::update($this->ct->Table->realtablename, $saveField->row_new, $whereClauseUpdate);
            } catch (Exception $e) {
                $this->ct->errors[] = $e->getMessage();
                die('Error: ' . $e->getMessage());
            }
        }

        if (count($saveField->row_new) < 1) {
            return false;
        }

        if ($this->isItNewRecord) {
            if ($this->listing_id !== null) {
                $this->row_new = $this->ct->Table->loadRecord($this->listing_id);

                if ($this->row_new !== null) {

                    if ($this->ct->Env->advancedTagProcessor) {
                        if ($phpOnAddFound)
                            CustomPHP::doPHPonAdd($this->ct, $this->row_new);

                        if ($phpOnChangeFound)
                            CustomPHP::doPHPonChange($this->ct, $this->row_new);
                    }

                    try {
                        $this->ct->Table->saveLog($this->listing_id, 1);
                    } catch (Exception $e) {
                        $this->ct->errors[] = $e->getMessage();
                    }
                }
            }
        } else {

            try {
                $this->ct->Table->saveLog($this->listing_id, 2);
            } catch (Exception $e) {
                $this->ct->errors[] = $e->getMessage();
            }

            $this->row_new = $this->ct->Table->loadRecord($this->listing_id);
            if ($this->row_new !== null) {

                if (defined('_JEXEC')) {
                    common::inputSet("listing_id", $this->row_new[$this->ct->Table->realidfieldname]);

                    if ($this->ct->Env->advancedTagProcessor) {
                        if ($phpOnChangeFound or $this->ct->Table->tablerow['customphp'] != '')
                            CustomPHP::doPHPonChange($this->ct, $this->row_new);
                        if ($phpOnAddFound and $isCopy)
                            CustomPHP::doPHPonAdd($this->ct, $this->row_new);
                    }
                }
            }
        }

        if ($this->ct->Params->onRecordSaveSendEmailTo != '' or $this->ct->Params->onRecordAddSendEmailTo != '') {

            //1 When record added
            //2 When record saved
            //3 On Condition

            if ($this->ct->Params->onRecordAddSendEmail == 3 and !empty($this->ct->Params->onRecordAddSendEmailTo)) {
                //check conditions
                if ($saveField->checkSendEmailConditions($this->listing_id, $this->ct->Params->sendEmailCondition)) {
                    //Send email conditions met
                    $saveField->sendEmailIfAddressSet($this->listing_id, $this->row_new, $this->ct->Params->onRecordSaveSendEmailTo);
                }
            } else {
                if ($this->isItNewRecord or $isCopy) {
                    //New record
                    if ($this->ct->Params->onRecordAddSendEmail == 1 and !empty($this->ct->Params->onRecordAddSendEmailTo))
                        $saveField->sendEmailIfAddressSet($this->listing_id, $this->row_new, $this->ct->Params->onRecordAddSendEmailTo);
                } else {
                    //Old record
                    if ($this->ct->Params->onRecordAddSendEmail == 2 and !empty($this->ct->Params->onRecordSaveSendEmailTo))
                        $saveField->sendEmailIfAddressSet($this->listing_id, $this->row_new, $this->ct->Params->onRecordSaveSendEmailTo);
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

    /**
     * @throws Exception
     * @since 3.2.9
     */
    function check_captcha(): bool
    {
        $response = common::inputPostString('g-recaptcha-response', null, 'create-edit-record');
        $secret_key = $this->ct->LayoutVariables['captcha_secret_key'];

        // The IP address of the user
        $remote_ip = $_SERVER['REMOTE_ADDR'];

        // Build the request data
        $data = [
            'secret' => $secret_key,
            'response' => $response,
            'remoteip' => $remote_ip,
        ];

        // Build the request options
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        // Make the request to the Google reCAPTCHA verification API
        $context = stream_context_create($options);
        $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);

        // Decode the JSON response
        $response = json_decode($result, true);

        // Check if the reCAPTCHA is valid
        if ($response['success']) {
            return true;
        }
        return false;
    }
}