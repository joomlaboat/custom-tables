<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use api\components\com_customtables\Controller\LoginController;
use CustomTablesImageMethods;
use CustomTablesKeywordSearch;
use Exception;
use Throwable;

class CT
{
	var Languages $Languages;
	var Environment $Env;
	var ?Params $Params;
	var ?Table $Table;
	var ?array $Records;
	var ?string $GroupBy; // real field name
	var ?Ordering $Ordering;
	var ?Filtering $Filter;
	var ?string $alias_fieldname;
	var int $Limit;
	var int $LimitStart;
	var bool $isEditForm;
	var array $editFields;
	var array $editFieldTypes;
	var array $LayoutVariables;
	var array $messages;

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function __construct(?array $menuParams = [], bool $blockExternalVars = true)
	{
		$this->messages = [];

		$this->Languages = new Languages;

		$this->Env = new Environment();
		$this->Params = new Params($menuParams, $blockExternalVars);

		$this->GroupBy = null;
		$this->isEditForm = false;
		$this->LayoutVariables = [];
		$this->editFields = [];
		$this->editFieldTypes = [];

		$this->Limit = 0;
		$this->LimitStart = 0;

		$this->Table = null;
		$this->Records = null;
		$this->Ordering = null;
		$this->Filter = null;
	}

	function isRecordNull(?array $row): bool
	{
		if (is_null($row))
			return true;

		if (!is_array($row))
			return true;

		if (count($row) == 0)
			return true;

		if (!isset($row[$this->Table->realidfieldname]))
			return true;

		$id = $row[$this->Table->realidfieldname];

		if (is_null($id))
			return true;

		if ($id == '')
			return true;

		if (is_numeric($id) and intval($id) == 0)
			return true;

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.4.9
	 */
	function getRecord(): bool
	{
		if (is_null($this->Table))
			return false;

		if (is_null($this->Table->tablerow))
			return false;

		$ordering = $this->GroupBy !== null ? [$this->GroupBy] : [];

		$this->Ordering = new Ordering($this->Table, $this->Params);
		$selects = $this->Table->selects;

		$this->setFilter($this->Params->filter, $this->Params->showPublished);

		if (!is_null($this->Params->alias) and $this->Table->alias_fieldname != '')
			$this->Filter->addWhereExpression($this->Table->alias_fieldname . '="' . $this->Params->alias . '"');

		if (!empty($this->Params->listing_id))
			$this->Filter->whereClause->addCondition($this->Table->realidfieldname, $this->Params->listing_id);

		//Get order by fields from menu parameters
		$this->Ordering->parseOrderByParam();
		//Process the string to get the orderby
		$this->Ordering->parseOrderByString();

		if ($this->Ordering->orderby !== null) {
			if ($this->Ordering->selects !== null)
				$selects[] = $this->Ordering->selects;

			$ordering[] = $this->Ordering->orderby;
		}

		$records = database::loadAssocList($this->Table->realtablename, $selects, $this->Filter->whereClause,
			(count($ordering) > 0 ? implode(',', $ordering) : null), 1, null,
			null, $this->Table->realtablename . '.' . $this->Table->realidfieldname
		);

		if (count($records) < 1) {
			$this->Table->record = null;
			return false;
		}

		if (!$this->Params->blockExternalVars and $this->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers'))
			$this->Table->record = ctProHelpers::getSpecificVersionIfSet($this, $records[0]);
		else
			$this->Table->record = $records[0];

		if (!empty($this->Params->recordsTable) and !empty($this->Params->recordsUserIdField) and !empty($this->Params->recordsField)) {

			if (!$this->checkRecordUserJoin($this->Params->recordsTable, $this->Params->recordsUserIdField, $this->Params->recordsField, $this->Params->listing_id)) {
				//YOU ARE NOT AUTHORIZED TO ACCESS THIS SOURCE;
				throw new Exception(common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED') . ' ONLY USER CREATED THIS RECORD ALLOWED TO EDIT IT.');
			}
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function setFilter(?string $filter_string = null, int $showPublished = CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY): void
	{
		$this->Filter = new Filtering($this, $showPublished);
		if ($filter_string != '')
			$this->Filter->addWhereExpression($filter_string);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function checkRecordUserJoin(string $recordsTable, $recordsUserIdField, $recordsField, $listing_id): bool
	{
		$ct = new CT([], true);
		$ct->getTable($recordsTable);
		if ($ct->Table === null) {
			return false;    // Exit if table to connect with not found
		}

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($ct->Table->fieldPrefix . $recordsUserIdField, $this->Env->user->id);
		$whereClause->addCondition($ct->Table->fieldPrefix . $recordsField, ',' . $listing_id . ',', 'INSTR');

		$rows = database::loadAssocList($ct->Table->realtablename, ['COUNT_ROWS'], $whereClause, null, null, 1);
		$num_rows = $rows[0]['record_count'];

		if ($num_rows == 0)
			return false;

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function getTable($tableNameOrID, $userIdFieldName = null, bool $loadAllField = false): void
	{
		$this->Table = new Table($this->Languages, $this->Env, $tableNameOrID, $this->Params->userIdField, $loadAllField);

		if ($this->Table->tablename !== null) {
			$this->Ordering = new Ordering($this->Table, $this->Params);
			$this->prepareSEFLinkBase();
		} else {
			$this->Table = null;
			$this->Ordering = null;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function prepareSEFLinkBase(): void
	{
		if (is_null($this->Table))
			return;

		if (is_null($this->Table->fields))
			return;

		$option = common::inputGetCmd('option');

		if ($option == 'com_customtables') {
			foreach ($this->Table->fields as $fld) {

				if ($fld['type'] == 'alias') {
					$this->alias_fieldname = $fld['fieldname'];
					return;
				}
			}
		}
		$this->alias_fieldname = null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getRecords(bool $all = false, int $limit = 0, ?string $orderby = null, ?string $groupBy = null): bool
	{
		//Grouping
		$realGroupByFieldNames = [];

		if (!empty($groupBy)) {
			$realGroupByFieldNames = $this->getGroupByRealFieldNames($groupBy);
			$this->GroupBy = implode(',', $realGroupByFieldNames);
		} elseif (!empty($this->GroupBy)) {
			$realGroupByFieldNames = explode(',', $this->GroupBy);
		}

		try {
			$count = $this->getNumberOfRecords($this->Filter->whereClause, $realGroupByFieldNames);
		} catch (Throwable $e) {
			throw new Exception($e->getMessage());
		}

		if ($count === null)
			return false;

		//Ordering
		if ($orderby != null)
			$this->Ordering->ordering_processed_string = $orderby;

		if ($this->Ordering->ordering_processed_string !== null) {
			$this->Ordering->parseOrderByString();
		}

		$selects = $this->Table->selects;
		$ordering = [];

		if ($this->Ordering->orderby !== null) {
			if ($this->Ordering->selects !== null)
				$selects[] = $this->Ordering->selects;

			$ordering[] = $this->Ordering->orderby;
		}

		if (count($realGroupByFieldNames) > 0)
			$selects[] = ['COUNT', $this->Table->realtablename, $this->Table->realidfieldname, 'ct_group_count'];

		if ($this->Table->recordcount > 0) {

			if ($limit > 0) {
				//orderBy parameter is NULL because order direction is already included in $ordering
				$this->Records = database::loadAssocList($this->Table->realtablename, $selects, $this->Filter->whereClause,
					(count($ordering) > 0 ? implode(',', $ordering) : null), null, $limit, null, $this->GroupBy);
				$this->Limit = $limit;
			} else {
				$the_limit = $this->Limit;

				if ($all) {
					//orderBy parameter is NULL because order direction is already included in $ordering
					$this->Records = database::loadAssocList($this->Table->realtablename, $selects, $this->Filter->whereClause,
						(count($ordering) > 0 ? implode(',', $ordering) : null), null, 20000, null, $this->GroupBy);
				} else {
					if ($the_limit > 20000)
						$the_limit = 20000;

					if ($the_limit == 0)
						$the_limit = 20000; //or we will run out of memory

					if ($this->Table->recordcount < $this->LimitStart or $this->Table->recordcount < $the_limit)
						$this->LimitStart = 0;

					try {
						//orderBy parameter is NULL because order direction is already included in $ordering
						$this->Records = database::loadAssocList($this->Table->realtablename, $selects, $this->Filter->whereClause,
							(count($ordering) > 0 ? implode(',', $ordering) : null), null, $the_limit, $this->LimitStart, $this->GroupBy);
					} catch (Exception $e) {
						throw new Exception($e->getMessage());
					}
				}
			}
		} else
			$this->Records = [];

		if ($this->Limit == 0)
			$this->Limit = 20000;

		return true;
	}

	function getGroupByRealFieldNames(string $groupBy): array
	{
		$fieldNames = explode(',', $groupBy);
		$fieldNamesClean = array_map('trim', $fieldNames);

		$realGroupByFieldNames = [];

		foreach ($fieldNamesClean as $fieldName) {
			$tempFieldRow = $this->Table->getFieldByName($fieldName);
			if ($tempFieldRow !== null and !in_array($tempFieldRow['realfieldname'], $realGroupByFieldNames))
				$realGroupByFieldNames[] = $tempFieldRow['realfieldname'];
		}

		return $realGroupByFieldNames;
	}

	/**
	 * @throws Exception
	 * @since 3.2.0
	 */
	function getNumberOfRecords(MySQLWhereClause $whereClause, ?array $GroupBy = null): ?int
	{
		if ($this->Table === null or $this->Table->tablerow === null or $this->Table->tablerow['realidfieldname'] === null) {
			throw new Exception('getNumberOfRecords: Table not selected.');
		}

		try {
			if ($GroupBy === null or count($GroupBy) == 0)
				$rows = database::loadObjectList($this->Table->realtablename, ['COUNT_ROWS'], $whereClause, null, null, null, null, 'OBJECT', null);
			else {
				if (count($GroupBy) == 1)
					$rows = database::loadObjectList($this->Table->realtablename, [['COUNT_DISTINCT_ROWS', implode(',', $GroupBy)]], $whereClause, null, null, null, null, 'OBJECT', null);
				else
					$rows = database::loadObjectList($this->Table->realtablename, [['COUNT_DISTINCT_ROWS', 'JSON_ARRAY(' . implode(',', $GroupBy) . ')']], $whereClause, null, null, null, null, 'OBJECT', null);
			}
		} catch (Exception $e) {
			throw new Exception('getNumberOfRecords:' . $e->getMessage());
		}

		if (count($rows) == 0)
			$this->Table->recordcount = 0;
		else
			$this->Table->recordcount = intval($rows[0]->record_count);

		return $this->Table->recordcount;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function getRecordsByKeyword(): void
	{
		//Joomla Method
		if (!empty($this->Params->ModuleId)) {
			$keywordSearch = common::inputGetString('eskeysearch_' . $this->Params->ModuleId, '');
			if ($keywordSearch != '') {
				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'filter' . DIRECTORY_SEPARATOR . 'keywordsearch.php');

				$KeywordSearcher = new CustomTablesKeywordSearch($this);
				$KeywordSearcher->groupby = $this->GroupBy;
				$KeywordSearcher->esordering = $this->Ordering->ordering_processed_string;

				if (defined('_JEXEC')) {
					$limit_var = 'com_customtables.limit_' . $this->Params->ItemId;
					$limit = common::getUserState($limit_var, 0);
				} elseif (defined('WPINC')) {
					if ($this->Table === null) {
						$limit = 0;
					} else {
						$limit_var = 'com_customtables.limit_' . $this->Table->tableid;
						$limit = common::getUserState($limit_var, 0);
					}
				} else {
					$limit = 0;
				}

				$this->Records = $KeywordSearcher->getRowsByKeywords(
					$keywordSearch,
					$this->Table->recordcount,
					$limit,
					$this->LimitStart
				);

				if ($this->Table->recordcount < $this->LimitStart)
					$this->LimitStart = 0;
			}
		}
	}

	function getRecordList(): array
	{
		if ($this->Table->recordlist !== null)
			return $this->Table->recordlist;

		$recordList = [];

		foreach ($this->Records as $row)
			$recordList[] = $row[$this->Table->realidfieldname];

		$this->Table->recordlist = $recordList;
		return $recordList;
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function applyLimits(int $limit = 0): void
	{
		if ($limit != 0) {
			$this->Limit = $limit;
			$this->LimitStart = 0;
			return;
		}

		if (defined('_JEXEC')) {
			$limit_var = 'com_customtables.limit_' . $this->Params->ItemId;
			$this->Limit = common::getUserState($limit_var, 0);
		} elseif (defined('WPINC')) {
			if ($this->Table === null) {
				$this->Limit = 0;
			} else {
				$limit_var = 'com_customtables.limit_' . $this->Table->tableid;
				$this->Limit = common::getUserState($limit_var, 0);
			}
		}

		//Grouping
		$this->GroupBy = null;
		if (!empty($this->Params->groupBy)) {
			$realGroupByFieldNames = $this->getGroupByRealFieldNames($this->Params->groupBy);
			$this->GroupBy = implode(',', $realGroupByFieldNames);
		}

		if ($this->Params->blockExternalVars) {

			if ($this->Limit == 0 and (int)$this->Params->limit > 0)
				$this->Limit = (int)$this->Params->limit;

			if ($this->Limit > 0) {
				$this->LimitStart = common::inputGetInt('start', 0);
				$this->LimitStart = floor($this->LimitStart / $this->Limit) * $this->Limit;
			} else {
				$this->Limit = 0;
				$this->LimitStart = 0;
			}
		} else {
			$this->LimitStart = common::inputGetInt('start', 0);

			if (defined('_JEXEC')) {
				$limit_var = 'com_customtables.limit_' . $this->Params->ItemId;
				$this->Limit = common::getUserState($limit_var, 0);
			} elseif (defined('WPINC')) {
				if ($this->Table === null) {
					$this->Limit = 0;
				} else {
					$limit_var = 'com_customtables.limit_' . $this->Table->tableid;
					$this->Limit = common::getUserState($limit_var, 0);
				}
			}

			if ($this->Limit == 0 and (int)$this->Params->limit > 0) {
				$this->Limit = (int)$this->Params->limit;
			}

			// In case limit has been changed, adjust it
			$this->LimitStart = ($this->Limit != 0 ? (floor($this->LimitStart / $this->Limit) * $this->Limit) : 0);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function deleteSingleRecord(array $row): void
	{
		//delete images if exist
		$imageMethods = new CustomTablesImageMethods;

		foreach ($this->Table->fields as $fieldRow) {
			$field = new Field($this, $fieldRow, $row);

			if ($field->type == 'image') {
				$ImageFolderArray = CustomTablesImageMethods::getImageFolder($field->params);

				//delete single image
				if ($row[$field->realfieldname] !== null) {

					$fileNameType = $field->params[3] ?? '';

					$imageMethods->DeleteExistingSingleImage(
						$row[$field->realfieldname],
						$ImageFolderArray['path'],
						$field->params[0] ?? '',
						$this->Table->realtablename,
						$field->realfieldname,
						$this->Table->realidfieldname,
						$fileNameType
					);
				}
			} elseif ($field->type == 'imagegallery') {
				$ImageFolderArray = CustomTablesImageMethods::getImageFolder($field->params);

				//delete gallery images if exist
				$galleryName = $field->fieldname;
				$photoTableName = '#__customtables_gallery_' . $this->Table->tablename . '_' . $galleryName;

				$whereClause = new MySQLWhereClause();
				$whereClause->addCondition('listingid', $row[$this->Table->realidfieldname]);

				$photoRows = database::loadObjectList($photoTableName, ['photoid'], $whereClause);
				$imageGalleryPrefix = 'g';

				foreach ($photoRows as $photoRow) {
					$imageMethods->DeleteExistingGalleryImage(
						$ImageFolderArray['path'],
						$imageGalleryPrefix,
						$this->Table->tableid,
						$galleryName,
						$photoRow->photoid,
						$field->params[0] ?? '',
						true
					);
				}
			}
		}

		database::deleteRecord($this->Table->realtablename, $this->Table->realidfieldname, $row[$this->Table->realidfieldname]);

		if ($this->Env->advancedTagProcessor)
			$this->Table->saveLog($row[$this->Table->realidfieldname], 5);

		$new_row = array();

		if ($this->Env->advancedTagProcessor and $this->Table->tablerow['customphp'] !== null) {
			$customPHP = new CustomPHP($this, 'delete');
			$customPHP->executeCustomPHPFile($this->Table->tablerow['customphp'], $new_row, $row);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	public function setPublishStatusSingleRecord(string $listing_id, int $status): void
	{
		if (!$this->Table->published_field_found)
			throw new Exception("Field `published` not found.");

		$data = [
			'published' => $status
		];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addCondition($this->Table->realidfieldname, $listing_id);
		database::update($this->Table->realtablename, $data, $whereClauseUpdate);

		if ($status == 1)
			$this->Table->saveLog($listing_id, 3);
		else
			$this->Table->saveLog($listing_id, 4);

		$this->RefreshSingleRecord($listing_id, false, ($status == 1 ? 'publish' : 'unpublish'));
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function RefreshSingleRecord($listing_id, bool $save_log, string $action = 'refresh'): void
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($this->Table->realidfieldname, $listing_id);

		$rows = database::loadAssocList($this->Table->realtablename, $this->Table->selects, $whereClause, null, null, 1);

		if (count($rows) == 0)
			throw new Exception("No record found.");

		$row = $rows[0];
		$saveField = new SaveFieldQuerySet($this, $row, false);

		//Apply default values
		foreach ($this->Table->fields as $fieldRow) {

			if (!$saveField->checkIfFieldAlreadyInTheList($fieldRow['realfieldname']))
				$saveField->applyDefaults($fieldRow);
		}

		if (count($saveField->row_new) > 0) {
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition($this->Table->realidfieldname, $listing_id);
			database::update($this->Table->realtablename, $saveField->row_new, $whereClauseUpdate);
		}

		//End of Apply default values
		//common::inputSet("listing_id", $listing_id);

		if ($this->Env->advancedTagProcessor)
			CustomPHP::doPHPonChange($this, $row);

		//update MD5s
		$this->updateMD5($listing_id);

		if ($save_log)
			$this->Table->saveLog($listing_id, 10);

		//TODO use $saveField->saveField
		//$this->updateDefaultValues($row);

		if ($this->Env->advancedTagProcessor and $this->Table->tablerow['customphp'] !== null) {
			$customPHP = new CustomPHP($this, $action);
			try {
				$customPHP->executeCustomPHPFile($this->Table->tablerow['customphp'], $row, $row);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}

		//Send email note if applicable
		if ($this->Params->onRecordAddSendEmail == 3 and !empty($this->Params->onRecordSaveSendEmailTo)) {
			//check conditions

			try {
				$conditions = $saveField->checkSendEmailConditions($listing_id, $this->Params->sendEmailCondition);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			if ($conditions) {
				//Send email conditions met
				try {
					$saveField->sendEmailIfAddressSet($listing_id, $row, $this->Params->onRecordSaveSendEmailTo);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	protected function updateMD5(string $listing_id): void
	{
		//TODO: Use savefield
		foreach ($this->Table->fields as $fieldRow) {
			if ($fieldRow['type'] == 'md5') {
				$fieldsToCount = explode(',', str_replace('"', '', $fieldRow['typeparams']));//only field names, nothing else

				$fields = array();
				foreach ($fieldsToCount as $f) {
					//to make sure that field exists
					foreach ($this->Table->fields as $fieldRow2) {
						if ($fieldRow2['fieldname'] == $f and $fieldRow['fieldname'] != $f)
							$fields[] = 'COALESCE(' . $fieldRow2['realfieldname'] . ')';
					}
				}

				if (count($fields) > 1) {

					$data = [
						$fieldRow['realfieldname'] => ['MD5(CONCAT_WS(' . implode(',', $fields) . '))', 'sanitized']
					];
					$whereClauseUpdate = new MySQLWhereClause();
					$whereClauseUpdate->addCondition($this->Table->realidfieldname, $listing_id);
					database::update($this->Table->realtablename, $data, $whereClauseUpdate);
				}
			}
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	public function CheckAuthorization(int $action = CUSTOMTABLES_ACTION_EDIT): bool
	{
		if ($this->Table === null)
			throw new Exception("Table is not set.");

		if ($action == 0)
			return true;

		if ($action == CUSTOMTABLES_ACTION_FORCE_EDIT) //force edit
		{
			$action = CUSTOMTABLES_ACTION_EDIT;
		} else {
			if ($action == CUSTOMTABLES_ACTION_EDIT and $this->Table->record === null)
				$action = CUSTOMTABLES_ACTION_ADD; //add new
		}


		//check is authorized or not
		if ($action == CUSTOMTABLES_ACTION_EDIT)
			$userGroups = $this->Params->editUserGroups;
		elseif ($action == CUSTOMTABLES_ACTION_PUBLISH)
			$userGroups = $this->Params->publishUserGroups;
		elseif ($action == CUSTOMTABLES_ACTION_DELETE)
			$userGroups = $this->Params->deleteUserGroups;
		elseif ($action == CUSTOMTABLES_ACTION_ADD)
			$userGroups = $this->Params->addUserGroups;
		elseif ($action == CUSTOMTABLES_ACTION_COPY)
			$userGroups = array_merge($this->Params->addUserGroups, $this->Params->editUserGroups);
		else
			$userGroups = [];

		if ($this->Env->user->isUserAdministrator) {
			//Super Users have access to everything
			return true;
		}

		if ($this->Env->user->checkUserGroupAccess($userGroups)) {

			$authorUserGroupName = (defined('_JEXEC') ? '3' : (defined('WPINC') ? 'author' : null));

			if (in_array($authorUserGroupName, $userGroups))//3 is Author in Joomla
			{
				if ($this->Table->useridrealfieldname !== null and $this->Table->record !== null) {
					if (in_array($authorUserGroupName, $userGroups))//3 is Author in Joomla
					{
						if ($this->checkIfItemBelongsToUser())
							return true;
					}
				}
			} else
				return true;
		}

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	private function checkIfItemBelongsToUser(): bool
	{
		if ($this->Table === null)
			return false;

		if ($this->Table->record === null)
			return false;

		$uid = $this->Table->record[$this->Table->useridrealfieldname];

		if ($uid == $this->Env->user->id and $this->Env->user->id != 0)
			return true;

		//TODO: The record is already loaded, just check if it belongs to a user
		//$whereClause = $this->UserIDField_BuildWheres($this->Table->useridrealfieldname, $this->Table->record[$this->Table->realidfieldname]);
		//$rows = database::loadObjectList($this->Table->realtablename, ['COUNT_ROWS'], $whereClause, null, null, 1);

//		if (count($rows) !== 1)
//			return false;

//		if ($rows->record_count == 1)
//			return true;

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	public function UserIDField_BuildWheres(string $userIdField, string $listing_id): MySQLWhereClause
	{
		$whereClause = new MySQLWhereClause();
		$statement_items = CTMiscHelper::ExplodeSmartParamsArray($userIdField); //"and" and "or" as separators
		$whereClauseOwner = new MySQLWhereClause();

		foreach ($statement_items as $item) {

			if (!str_contains($item['equation'], '.')) {
				//example: user
				//check if the record belong to the current user
				$user_field_row = $this->Table->getFieldByName($item['equation']);
				$whereClauseOwner->addCondition($user_field_row['realfieldname'], $this->Env->user->id);
			} else {
				//example: parents(children).user
				$statement_parts = explode('.', $item['equation']);
				if (count($statement_parts) != 2)
					throw new Exception(common::translate('COM_CUSTOMTABLES_MENUITEM_USERID_FIELD_ERROR'));

				$table_parts = explode('(', $statement_parts[0]);
				if (count($table_parts) != 2)
					throw new Exception(common::translate('COM_CUSTOMTABLES_MENUITEM_USERID_FIELD_ERROR'));

				$parent_tablename = $table_parts[0];
				$parent_join_field = str_replace(')', '', $table_parts[1]);
				$parent_user_field = $statement_parts[1];

				$parent_table_row = TableHelper::getTableRowByName($parent_tablename);

				if (!is_object($parent_table_row))
					throw new Exception(common::translate('COM_CUSTOMTABLES_MENUITEM_TABLENOTFOUND_ERROR'));

				$tempTable = new Table($this->Languages, $this->Env, $parent_table_row->id);

				$parent_join_field_row = $tempTable->getFieldByName($parent_join_field);

				if (count($parent_join_field_row) == 0)
					throw new Exception(common::translate('COM_CUSTOMTABLES_MENUITEM_TABLENOTFOUND_ERROR'));

				if ($parent_join_field_row['type'] != 'sqljoin' and $parent_join_field_row['type'] != 'records')
					throw new Exception(sprintf("Menu Item - 'UserID Field name' parameter has an error: Wrong join field type '%s'. Accepted types: 'sqljoin' and 'records'.", $parent_join_field_row['type']));

				//User field
				$parent_user_field_row = $tempTable->getFieldByName($parent_user_field);

				if (count($parent_user_field_row) == 0)
					throw new Exception(sprintf("Menu Item - 'UserID Field name' parameter has an error: User field '%s' not found.", $parent_user_field));

				if ($parent_user_field_row['type'] != 'userid' and $parent_user_field_row['type'] != 'user')
					throw new Exception(sprintf("Menu Item - 'UserID Field name' parameter has an error: Wrong user field type '%s'. Accepted types: 'userid' and 'user'.", $parent_join_field_row['type']));

				$whereClauseParent = new MySQLWhereClause();

				$whereClauseParent->addCondition('p.' . $parent_user_field_row['realfieldname'], $this->Env->user->id);

				$fieldType = $parent_join_field_row['type'];
				if ($fieldType != 'sqljoin' and $fieldType != 'records')
					return $whereClause;

				if ($fieldType == 'sqljoin') {
					$whereClauseParent->addCondition('p.' . $parent_user_field_row['realfieldname'], 'c.listing_id', '=', true);
				}

				if ($fieldType == 'records')
					$whereClauseParent->addCondition('p.' . $parent_user_field_row['realfieldname'], 'CONCAT(",",c.' . $this->Table->realidfieldname . ',",")', 'INSTR', true);

				$parent_wheres_string = (string)$whereClauseParent;
				$whereClauseOwner->addCondition('(SELECT p.' . $parent_table_row->realidfieldname . ' FROM ' . $parent_table_row->realtablename . ' AS p WHERE ' . $parent_wheres_string . ' LIMIT 1)', null, 'NOT NULL');
			}
		}

		$whereClause->addNestedCondition($whereClauseOwner);

		if (!empty($listing_id))
			$whereClause->addCondition($this->Table->realidfieldname, $listing_id);

		return $whereClause;
	}

	/*
	function CheckAuthorizationACL($access): bool
	{
		$this->isAuthorized = false;

		if ($access == 'core.edit' and empty($this->listing_id))
			$access = 'core.create'; //add new

		if ($this->Env->user->authorise($access, 'com_customtables')) {
			$this->isAuthorized = true;
			return true;
		}

		if ($access != 'core.edit')
			return false;

		if ($this->Params->userIdField != '') {
			if ($this->checkIfItemBelongsToUser($this->listing_id, $this->ct->Params->userIdField)) {
				if ($this->Env->user->authorise('core.edit.own', 'com_customtables')) {
					$this->isAuthorized = true;
					return true;
				} else
					$this->isAuthorized = false;
			}
		}
		return false;
	}
	*/
}
