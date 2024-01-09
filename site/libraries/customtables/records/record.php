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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

//use CustomTablesImageMethods;
use Edit;
use Exception;
use JCaptcha;
use Joomla\CMS\Factory;
use CustomTables\CustomPHP\CleanExecute;
use CustomTables\ctProHelpers;

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

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
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
				if ($isItNewRecords or $isCopy) {
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
}