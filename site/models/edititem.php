<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CustomTablesModelEditItem extends BaseDatabaseModel
{
	/*
	function store(string &$link, bool $isCopy = false, ?string $listing_id = null): bool
	{
		$record = new record($this->ct);

		//IP Filter
		$USER_IP = SaveFieldQuerySet::getUserIP();
		$IP_Black_List = array();

		if (in_array($USER_IP, $IP_Black_List))
			return false;

		if (!empty($this->ct->Params->tableName))
			$this->ct->getTable($this->ct->Params->tableName);

		$record->editForm = new Edit($this->ct);
		$record->editForm->load();//Load Menu Item parameters


		if (!$this->ct->CheckAuthorization()) {
			$returnToEncoded = common::makeReturnToURL();
			$link = $this->ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $returnToEncoded;
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
			//$this_->setRedirect($link, common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
			return false;
		}

		echo 'Save new record<br/>';

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

			if (!empty($this->listing_id))
				common::inputSet("listing_id", $this->listing_id);

			echo 'OK<br/>';
			die;
			return true;
		} else {
			echo 'Not OK<br/>';
			die;
			return false;
		}
	}
	*/

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	//function load(CT $ct): bool
	//{
	//	$this->ct = $ct;

	//if ($this->ct->Env->legacySupport)
	//require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'layout.php');

	//$this->ct->getTable($ct->Params->tableName, $this->ct->Params->userIdField);

	//if ($this->ct->Table === null) {
	//	$this->ct->errors[] = 'Table not selected (61).';
	//	return false;
	//}

	//$this->ct->Params->userIdField = $this->findUserIDField($this->ct->Params->userIdField);//to make sure that the field name is real and two userid fields can be used

	//$this->listing_id = $this->ct->Params->listing_id;

	//Load the record
	/*
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
	*/
//	}
	/*
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
	*/

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	/*
	function processCustomListingID(): ?int
	{
		if (!empty($this->listing_id) and (is_numeric($this->listing_id) or (!str_contains($this->listing_id, '=') and !str_contains($this->listing_id, '<') and !str_contains($this->listing_id, '>')))) {
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
*/
	/**
	 * @throws Exception
	 * @since 3.2.3
	 */

	/*
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
*/

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

		if (!empty($from_listing_id)) {
			$this->ct->Params->listing_id = $from_listing_id;
			$this->ct->getRecord();
		}

		$from_row = $this->ct->Table->record;

		if (!empty($to_listing_id)) {
			$this->ct->Params->listing_id = $to_listing_id;
			$this->ct->getRecord();
		}

		$to_row = $this->ct->Table->record;

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
