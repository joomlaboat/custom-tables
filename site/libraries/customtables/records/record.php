<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
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
	var ?array $row_old;
	var ?array $row_new;
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
			$listing_id = common::inputGetCmd('listing_id', ''); //TODO : this inconsistency must be fixed
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

			try {
				$this->listing_id = database::insert($this->ct->Table->realtablename, $saveField->row_new);
			} catch (Exception $e) {
				$this->ct->errors[] = $e->getMessage();
				die($e->getMessage());
			}

		} else {
			$this->updateLog($this->listing_id);

			try {
				database::update($this->ct->Table->realtablename, $saveField->row_new, [$this->ct->Table->realidfieldname => $this->listing_id]);
			} catch (Exception $e) {
				$this->ct->errors[] = $e->getMessage();
				die('Error: ' . $e->getMessage());
			}
		}

		if (count($saveField->row_new) < 1) {
			return false;
		}

		if ($isItNewRecords) {
			if ($this->listing_id !== null) {
				$this->row_new = $this->ct->Table->loadRecord($this->listing_id);

				if ($this->row_new !== null) {

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
				$this->ct->errors[] = $e->getMessage();
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
			$this->ct->errors[] = $e->getMessage();
			return false;
		}

		if (count($rows) != 1)
			return false;

		$data = base64_encode(common::ctJsonEncode($rows));

		foreach ($this->ct->Table->fields as $fieldrow) {
			if ($fieldrow['type'] == 'log') {
				$value = time() . ',' . $this->ct->Env->user->id . ',' . SaveFieldQuerySet::getUserIP() . ',' . $data . ';';
				database::setQuery('UPDATE ' . $this->ct->Table->realtablename . ' SET '
					. database::quoteName($fieldrow['realfieldname']) . '=CONCAT(' . $fieldrow['realfieldname'] . ',' . database::quote($value) . ')');
			}
		}
		return true;
	}

	function getSpecificVersionIfSet(array $row): array
	{
		if ($this->ct->Params->blockExternalVars)
			return $row;

		//get specific Version if set
		$version = common::inputGetInt('version', 0);

		if ($version != 0) {
			//get log field
			$log_field = $this->getTypeFieldName('log');
			if ($log_field != '') {
				$new_row = $this->getVersionData($row, $log_field, $version);
				if ($new_row === null or count($new_row) > 0)
					return $this->makeEmptyRecord($row[$this->ct->Table->realidfieldname], $new_row['listing_published']);

				$new_row2 = $row;

				//Copy values
				foreach ($this->ct->Table->fields as $fieldRow)
					$new_row2[$fieldRow['realfieldname']] = $new_row[$fieldRow['realfieldname']];

				return $new_row2;
			}
		}
		return $row;
	}

	protected function getTypeFieldName($type)
	{
		foreach ($this->ct->Table->fields as $fieldRow) {
			if ($fieldRow['type'] == $type)
				return $fieldRow['realfieldname'];
		}
		return '';
	}

	protected function getVersionData($row, $log_field, $version)
	{
		$creation_time_field = $this->getTypeFieldName('changetime');
		$versions = explode(';', $row[$log_field]);

		if ($version <= count($versions)) {
			if (count($versions) > 1 and $version > 1)
				$data_editor = explode(',', $versions[$version - 2]);
			else
				$data_editor = [''];

			$data_content = explode(',', $versions[$version - 1]); // version 1, 1 - 1 = 0; where 0 is the index

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
					$new_row[$creation_time_field] = $timestamp; //time (int)
				}
				return $new_row;
			}
		}
		return array();
	}

	protected function makeEmptyRecord($listing_id, $published): array
	{
		$row = null;
		$row[$this->ct->Table->realidfieldname] = $listing_id;

		if ($this->ct->Table->published_field_found)
			$row['listing_published'] = $published;

		foreach ($this->ct->Table->fields as $fieldRow)
			$row[$fieldRow['realfieldname']] = '';

		return $row;
	}
}