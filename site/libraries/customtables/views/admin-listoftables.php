<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class ListOfTables
{
	var CT $ct;

	function __construct(CT $ct)
	{
		$this->ct = $ct;
	}

	public static function getNumberOfRecords($realtablename): int
	{
		try {
			$whereClause = new MySQLWhereClause();
			$rows = database::loadObjectList($realtablename, ['COUNT_ROWS'], $whereClause, null, null, 1);
		} catch (Exception $e) {
			common::enqueueMessage('Table "' . $realtablename . '" - ' . $e->getMessage());
			return 0;
		}
		return $rows[0]->record_count;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getItems($published = null, $search = null, $category = null, $orderCol = null, $orderDirection = null, $limit = 0, $start = 0): array
	{
		return $this->getListQuery($published, $search, $category, $orderCol, $orderDirection, $limit, $start);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getListQuery($published = null, $search = null, int $category = 0, $orderCol = null, $orderDirection = null, $limit = 0, $start = 0, bool $returnQueryString = false)
	{
		$selects = TableHelper::getTableRowSelectArray();

		//Check if table exists
		$rows = database::getTableStatus('categories', 'categories');

		$tableExists = !(count($rows) == 0);

		if ($tableExists)
			$selects[] = 'CATEGORY_NAME';

		$selects[] = 'FIELD_COUNT';
		$selects[] = 'tablecategory';

		$whereClause = new MySQLWhereClause();

		$whereClausePublished = new MySQLWhereClause();

		// Filter by published state
		if (is_numeric($published))
			$whereClausePublished->addCondition('a.published', (int)$published);
		elseif ($published === null or $published === '') {
			$whereClausePublished->addOrCondition('a.published', 0);
			$whereClausePublished->addOrCondition('a.published', 1);
		}

		if ($whereClausePublished->hasConditions())
			$whereClause->addNestedCondition($whereClausePublished);

		// Filter by search.
		if (!empty($search)) {
			$whereClauseSearch = new MySQLWhereClause();
			if (stripos($search, 'id:') === 0) {
				$whereClauseSearch->addCondition('a.id', (int)substr($search, 3));
			} else {
				$whereClauseSearch->addCondition('a.tablename', '%' . $search . '%', 'LIKE');
			}
			if ($whereClauseSearch->hasConditions())
				$whereClause->addNestedCondition($whereClauseSearch);
		}

		// Filter by Category.
		if (!empty($category))
			$whereClause->addCondition('a.tablecategory', $category);

		return database::loadAssocList('#__customtables_tables AS a', $selects, $whereClause, $orderCol, $orderDirection, $limit, $start, null, $returnQueryString);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function save(?int $tableId): ?array
	{
		$data = [];
		// Check if running in WordPress context
		if (defined('WPINC')) {
			check_admin_referer('create-edit-table');

			// Check user capabilities
			if (!current_user_can('install_plugins')) {
				wp_die(
					'<h1>' . __('You need a higher level of permission.') . '</h1>' .
					'<p>' . __('Sorry, you are not allowed to create custom tables.') . '</p>',
					403
				);
			}
		}

		// Get database name and prefix
		$database = database::getDataBaseName();
		$dbPrefix = database::getDBPrefix();

		// Initialize variables
		$moreThanOneLanguage = false;
		$fields = Fields::getListOfExistingFields('#__customtables_tables', false);
		$tableTitle = null;

		// Process table name
		if (function_exists("transliterator_transliterate"))
			$newTableName = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", common::inputPostString('tablename', null, 'create-edit-table'));
		else
			$newTableName = common::inputPostString('tablename', null, 'create-edit-table');

		$newTableName = strtolower(trim(preg_replace("/\W/", "", $newTableName)));

		$data ['customphp'] = common::inputPostString('customidfield', null, 'create-edit-table');;
		$data ['customidfield'] = common::inputPostString('customidfield', null, 'create-edit-table');
		$data ['customidfieldtype'] = common::inputPostString('customidfieldtype', null, 'create-edit-table');
		$data ['primarykeypattern'] = stripcslashes(common::inputPostString('primarykeypattern', null, 'create-edit-table'));
		$data ['customfieldprefix'] = common::inputPostString('customfieldprefix', null, 'create-edit-table');

		if ($newTableName == "")
			return ['Please provide the table name.'];

		// Save as Copy
		$old_tablename = '';
		if (common::inputPostCmd('task', null, 'create-edit-table') === 'save2copy') {
			$originalTableId = common::inputPostInt('originaltableid', null, 'create-edit-table');
			if ($originalTableId !== null) {
				$old_tablename = TableHelper::getTableName($originalTableId);

				// Handle copy table name
				$copyTableName = $newTableName;
				if ($old_tablename == $newTableName) {
					$copyTableName = 'copy_of_' . $newTableName;
				}

				while (TableHelper::getTableID($newTableName) != 0) {
					$copyTableName = 'copy_of_' . $newTableName;
				}

				$tableId = null;
				$newTableName = $copyTableName;
			}
		}

		// Process multilingual fields
		foreach ($this->ct->Languages->LanguageList as $lang) {
			$id_title = 'tabletitle';
			$id_desc = 'description';
			if ($moreThanOneLanguage) {
				$id_title .= '_' . $lang->sef;
				$id_desc .= '_' . $lang->sef;
			} else {
				$tableTitle = common::inputPostString($id_title, null, 'create-edit-table');
			}

			if (!in_array($id_title, $fields)) {
				Fields::addLanguageField('#__customtables_tables', $id_title, $id_title, 'null');
			}

			if (!in_array($id_desc, $fields))
				Fields::addLanguageField('#__customtables_tables', $id_desc, $id_desc, 'null');

			$tableTitleValue = common::inputPostString($id_title, null, 'create-edit-table');
			if ($tableTitleValue !== null)
				$data [$id_title] = $tableTitleValue;

			$tableDescription = common::inputPostString($id_desc, null, 'create-edit-table');
			if ($tableDescription !== null)
				$data [$id_desc] = $tableDescription;
			$moreThanOneLanguage = true; //More than one language installed
		}

		// If it's a new table, check if field name is unique or add number "_1" if it's not.
		if ($tableId === null) {
			$already_exists = TableHelper::getTableID($newTableName);
			if ($already_exists == 0) {
				$data ['tablename'] = $newTableName;
			} else {
				return ['Table with this name already exists.'];
			}

			try {
				database::insert('#__customtables_tables', $data);
			} catch (Exception $e) {
				return [$e->getMessage()];
			}

		} else {

			//Case: Table renamed, check if the new name is available.
			$this->ct->getTable($tableId);
			if ($newTableName != $this->ct->Table->tablename) {
				$already_exists = TableHelper::getTableID($newTableName);
				if ($already_exists != 0) {
					return ['Table rename aborted. Table with this name already exists.'];
				}
			}

			if (common::inputPostString('customtablename', null, 'create-edit-table') == '')//do not rename real table if it's a third-party table - not part of the Custom Tables
			{
				//This function will find the old Table Name of existing table and rename MySQL table.
				TableHelper::renameTableIfNeeded($tableId, $newTableName);
				$data['tablename'] = $newTableName;
			}

			try {
				$whereClauseUpdate = new MySQLWhereClause();
				$whereClauseUpdate->addCondition('id', $tableId);
				database::update('#__customtables_tables', $data, $whereClauseUpdate);
			} catch (Exception $e) {
				return [$e->getMessage()];
			}
		}

		//Create MySQLTable
		$messages = array();
		$customTableName = common::inputPostString('customtablename', null, 'create-edit-table');
		if ($customTableName == '-new-') {
			// Case: Creating a new third-party table
			$customTableName = $newTableName;
			TableHelper::createTableIfNotExists($dbPrefix, $newTableName, $tableTitle, $customTableName ?? '');

			//Add fields if it's a third-party table and no fields added yet.
			TableHelper::addThirdPartyTableFieldsIfNeeded($database, $newTableName, $customTableName);
		} else {
			// Case: Updating an existing table or creating a new custom table
			$originalTableId = common::inputPostInt('originaltableid', 0, 'create-edit-table');

			if ($originalTableId != 0 and $old_tablename != '') {
				// Copying an existing table
				TableHelper::copyTable($this->ct, $originalTableId, $newTableName, $old_tablename, $customTableName);
			} else {
				// Creating a new custom table (without copying)
				TableHelper::createTableIfNotExists($dbPrefix, $newTableName, $tableTitle, $customTableName ?? '');
			}
		}
		return $messages;
	}
}