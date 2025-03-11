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

use CustomTables\Integrity\IntegrityCoreTables;
use Exception;

class ListOfTables
{
	var CT $ct;

	function __construct(CT $ct)
	{
		$this->ct = $ct;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getNumberOfRecords($realtablename): int
	{
		try {
			$whereClause = new MySQLWhereClause();
			$rows = database::loadObjectList($realtablename, ['COUNT_ROWS'], $whereClause, null, null, 1);
		} catch (Exception $e) {
			throw new Exception('Get Number of Record: Table "' . $realtablename . '" - ' . $e->getMessage());
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
	 * @since 3.5.8
	 */
	function save(int $tableId): void
	{
		// Check if running in WordPress context
		if (defined('WPINC')) {
			check_admin_referer('create-edit-table');

			// Check user capabilities
			if (!current_user_can('install_plugins'))
				throw new Exception('You need a higher level of permission.');
		}

		$data = [];
		$data['tablename'] = common::inputPostString('tablename', null, 'create-edit-table');
		$data ['customphp'] = common::inputPostString('customphp', null, 'create-edit-table');
		$data ['customtablename'] = common::inputPostString('customtablename', null, 'create-edit-table');
		$data ['customidfield'] = common::inputPostString('customidfield', null, 'create-edit-table');
		$data ['customidfieldtype'] = common::inputPostString('customidfieldtype', null, 'create-edit-table');
		$data ['primarykeypattern'] = stripcslashes(common::inputPostString('primarykeypattern', null, 'create-edit-table'));
		$data ['customfieldprefix'] = common::inputPostString('customfieldprefix', null, 'create-edit-table');

		$task = 'save';//common::inputPostCmd('task', null, 'create-edit-table');

		// Process multilingual fields
		$moreThanOneLanguage = false;

		foreach ($this->ct->Languages->LanguageList as $lang) {
			$id_title = 'tabletitle';
			$id_desc = 'description';
			if ($moreThanOneLanguage) {
				$id_title .= '_' . $lang->sef;
				$id_desc .= '_' . $lang->sef;
			}

			$data [$id_title] = common::inputPostString($id_title, null, 'create-edit-table');
			$data [$id_desc] = common::inputPostString($id_desc, null, 'create-edit-table');
			$moreThanOneLanguage = true; //More than one language installed
		}

		$this->saveWithData($tableId, $data, $task);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */

	function saveWithData(int $tableId, array $data, string $task): int
	{
		// Get database name and prefix
		$database = database::getDataBaseName();
		$dbPrefix = database::getDBPrefix();

		// Process table name
		if (function_exists("transliterator_transliterate"))
			$newTableName = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", $data['tablename']);
		else
			$newTableName = $data['tablename'];

		$newTableName = strtolower(trim(preg_replace("/\W/", "", $newTableName)));

		if ($newTableName == "")
			throw new Exception('Please provide the table name.');

		$data ['primarykeypattern'] = stripcslashes($data ['primarykeypattern']);

		$old_tablename = null;

		//If it's a new table, check if field name is unique or add number "_1" if it's not.
		if ($tableId === 0) {
			$newTableName = TableHelper::checkTableName($newTableName);
		} else {

			$originalTableId = common::inputPostInt('originaltableid', null, 'create-edit-table');
			if (!empty($originalTableId)) {
				$old_tablename = TableHelper::getTableName($originalTableId);
			}
			// Save as Copy
			if ($task === 'save2copy') {

				if (!empty($originalTableId)) {

					// Handle copy table name
					$copyTableName = $newTableName;
					if ($old_tablename == $newTableName)
						$copyTableName = 'copy_of_' . $newTableName;

					while (TableHelper::getTableID($newTableName) != 0) {
						$copyTableName = 'copy_of_' . $newTableName;
					}

					$tableId = 0;
					$newTableName = $copyTableName;
				}
			}
		}

		if (defined('_JEXEC'))
			$data['tablecategory'] = (int)$data['tablecategory'];

		if ($data['customidfield'] === null)
			$data['customidfield'] = 'id';

		if ($data['customidfieldtype'] === null)
			$data['customidfieldtype'] = 'int UNSIGNED NOT NULL AUTO_INCREMENT';

		$customFieldPrefix = trim(preg_replace("/[^a-zA-Z-_\d]/", "_", ($data['customfieldprefix'] ?? null)));
		if ($customFieldPrefix === "")
			$customFieldPrefix = null;

		$data['customfieldprefix'] = $customFieldPrefix;

		IntegrityCoreTables::addMultilingualTablesFields($this->ct->Languages->LanguageList);

		// If it's a new table, check if field name is unique or add number "_1" if it's not.
		if ($tableId === 0) {
			try {
				$tableId = database::insert('#__customtables_tables', $data);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			if ($data['customtablename'] == '-new-') {

				// Case: Creating a new third-party table
				TableHelper::createTableIfNotExists($dbPrefix, $newTableName, $data['tabletitle'], $newTableName);

				//Add fields if it's a third-party table and no fields added yet.
				//TableHelper::addThirdPartyTableFieldsIfNeeded($database, $newTableName, $newTableName);
			} elseif (empty($data['customtablename'])) {

				$originalTableId = common::inputPostInt('originaltableid', 0, 'create-edit-table');
				if ($originalTableId != 0 and $old_tablename != '' and $task === 'save2copy') {
					// Copying an existing table
					TableHelper::copyTable($this->ct, $originalTableId, $newTableName, $old_tablename, $data['customtablename']);
				} else {
					// Creating a new custom table (without copying)
					TableHelper::createTableIfNotExists($dbPrefix, $newTableName, $data['tabletitle'], $data['customtablename'] ?? '');


				}

				// Creating a new custom table (without copying)
				//TableHelper::createTableIfNotExists($dbPrefix, $newTableName, $data['tabletitle'], $data['customtablename'] ?? '');
			}

		} else {

			//Case: Table renamed, check if the new name is available.
			$this->ct->getTable($tableId);
			if ($newTableName != $this->ct->Table->tablename) {
				$already_exists = TableHelper::getTableID($newTableName);
				if ($already_exists != 0)
					throw new Exception('Table rename aborted. Table with this name already exists.');
			}

			if (empty($data['customtablename']))//do not rename real table if it's a third-party table - not part of the Custom Tables
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
				throw new Exception($e->getMessage());
			}

			// Case: Updating an existing table or creating a new custom table


			//echo '$originalTableId:' . $originalTableId . '<br/>';
			//echo '$old_tablename:' . $old_tablename . '<br/>';
			//echo '$task:' . $task . '<br/>';
			//die;


		}

		//Add fields if it's a third-party table and no fields added yet.
		if (!empty($data['customtablename']) and $data['customtablename'] !== '-new-') {
			TableHelper::addThirdPartyTableFieldsIfNeeded($database, $newTableName, $data['customtablename']);
		}

		return $tableId;
	}
}