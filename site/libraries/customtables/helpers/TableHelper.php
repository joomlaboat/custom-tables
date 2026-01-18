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

use Exception;

class TableHelper
{
	//This function works with MySQL not PostgreeSQL
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function checkTableName($tablename)
	{
		$new_tablename = $tablename;
		$i = 1;
		while (1) {

			$already_exists = self::getTableID($new_tablename);
			if ($already_exists != 0) {
				$pair = explode('_', $new_tablename);

				$cleanTableName = $pair[0];
				$new_tablename = $cleanTableName . '_' . $i;
				$i++;
			} else
				break;
		}

		return $new_tablename;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableID(string $tablename): int
	{
		if (str_contains($tablename, '"'))
			return 0;

		if ($tablename == '')
			return 0;

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('tablename', $tablename);
		$rows = database::loadObjectList('#__customtables_tables', ['id'], $whereClause, null, null, 1);

		if (count($rows) != 1)
			return 0;

		return $rows[0]->id;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function checkIfTableExists(string $realtablename): bool
	{
		$database = database::getDataBaseName();
		$realtablename = database::realTableName($realtablename);

		$whereClause = new MySQLWhereClause();

		if (database::getServerType() == 'postgresql') {
			$whereClause->addCondition('table_name', $realtablename);
			$rows = database::loadObjectList('information_schema.columns', ['COUNT_ROWS'], $whereClause, null, null, 1);
		} else {
			$whereClause->addCondition('table_schema', $database);
			$whereClause->addCondition('table_name', $realtablename);
			$rows = database::loadObjectList('information_schema.tables', ['COUNT_ROWS'], $whereClause, null, null, 1);
		}

		$c = (int)$rows[0]->record_count;
		if ($c > 0)
			return true;

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function createTableIfNotExists(string $dbPrefix, string $tableName, string $tableTitle,
												  string $complete_table_name = '', string $privateKey = 'id',
												  string $primaryKeyType = 'int UNSIGNED NOT NULL AUTO_INCREMENT'): bool
	{
		if ($complete_table_name == '')
			$realTableName = $dbPrefix . 'customtables_table_' . $tableName;
		elseif ($complete_table_name == '-new-')
			$realTableName = $tableName;
		else
			$realTableName = $complete_table_name;// used for custom table names - to connect to third-part tables for example

		$serverType = database::getServerType();

		//Check if table exists
		$tableExists = false;
		if ($serverType == 'postgresql') {
			$fields = Fields::getListOfExistingFields($realTableName, false);

			if (count($fields) > 0)
				$tableExists = true;
		} else {
			//Mysql;
			$rows2 = database::getTableStatus($realTableName, 'native');

			if (count($rows2) > 0) {

				$tableExists = true;

				if ($complete_table_name == '') {
					//do not modify third-party tables
					$row2 = $rows2[0];

					$realTableName = $dbPrefix . 'customtables_table_' . $tableName;

					if ($row2->Engine !== 'VIEW' and (string)$row2->Engine !== '') {
						if ($row2->Engine !== 'InnoDB')
							database::setTableInnoDBEngine($realTableName);

						database::changeTableComment($realTableName, $tableTitle);
					}
					return false;
				}
			}
		}

		if (!$tableExists) {
			$columns = [
				'published tinyint(1) NOT NULL DEFAULT 1'
			];
			database::createTable($realTableName, $privateKey, $columns, $tableTitle, null, $primaryKeyType);
			return true;
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function renameTableIfNeeded(int $tableid, string $tablename): void
	{
		$old_tablename = self::getTableName($tableid);

		if ($old_tablename != $tablename) {
			//rename table
			$tableStatus = database::getTableStatus($old_tablename);

			if (count($tableStatus) > 0)
				database::renameTable($old_tablename, $tablename);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableName(int $tableid = 0): ?string
	{
		if ($tableid == 0)
			$tableid = common::inputGetInt('tableid', 0);

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', $tableid);
		$rows = database::loadObjectList('#__customtables_tables AS s', ['tablename'], $whereClause, null, null, 1);
		if (count($rows) != 1)
			return null;

		return $rows[0]->tablename;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function addThirdPartyTableFieldsIfNeeded($database, $tablename, $realtablename): bool
	{
		$ct = new CT([], true);
		$ct->getTable($tablename);

		if (count($ct->Table->fields) > 0)
			return false;

		// Add third-party fields
		$whereClause = new MySQLWhereClause();
		$serverType = database::getServerType();

		if ($serverType == 'postgresql') {
			$selects = ['column_name', 'data_type', 'is_nullable', 'column_default'];
		} else {
			$selects = [
				'COLUMN_NAME AS column_name',
				'DATA_TYPE AS data_type',
				'COLUMN_TYPE AS column_type',
				'COLUMN_IS_UNSIGNED',
				'IS_NULLABLE AS is_nullable',
				'COLUMN_DEFAULT AS column_default',
				'COLUMN_COMMENT AS column_comment',
				'COLUMN_KEY AS column_key',
				'EXTRA AS extra'
			];
			$whereClause->addCondition('table_schema', $database);
		}

		$whereClause->addCondition('table_name', $realtablename);
		$fields = database::loadObjectList('information_schema.columns', $selects, $whereClause);

		$primary_key_column = '';
		$primary_key_column_type = '';
		$ordering = 1;

		foreach ($fields as $field) {
			if ($primary_key_column == '' && strtolower($field->column_key) == 'pri') {
				if (strtolower($field->is_nullable) == 'yes') {
					throw new Exception('Primary key column "' . $field->column_name . '" cannot be NULL.');
				}
				$primary_key_column = $field->column_name;
				$primary_key_column_type = $field->column_type;
			} else {
				$ct_field_type = Fields::convertMySQLFieldTypeToCT($field->column_type);

				if ($ct_field_type['type'] === null) {
					error_log('Unknown MySQL field type: ' . print_r($field, true));
					throw new Exception('Add Third-Party Table Fields: third-party table field type "' . $field->data_type . '" is unknown.');
				}

				$data = [
					'tableid' => $ct->Table->tableid,
					'fieldname' => $field->column_name,
					'fieldtitle' => str_replace('_', ' ', ucwords(strtolower($field->column_name), '_')),
					'type' => $ct_field_type['type'],
					'ordering' => $ordering,
					'defaultvalue' => isset($field->column_default) ? $field->column_default : null,
					'description' => isset($field->column_comment) && $field->column_comment !== '' ? $field->column_comment : null,
					'isrequired' => (strtolower($field->is_nullable) == 'no') ? 1 : 0
				];

				if (key_exists('typeparams', $ct_field_type)) {
					$data['typeparams'] = $ct_field_type['typeparams'];
				}

				database::insert('#__customtables_fields', $data);
				$ordering += 1;
			}
		}

		if ($primary_key_column != '') {
			// Update primary key column
			$data = [
				'customidfield' => $primary_key_column,
				'customidfieldtype' => $primary_key_column_type . ' NOT NULL',
				'customfieldprefix' => null
			];

			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('id', $ct->Table->tableid);
			database::update('#__customtables_tables', $data, $whereClauseUpdate);
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableRowByName($tablename = ''): ?object
	{
		if ($tablename === null)
			return null;

		$row = self::getTableRowByNameAssoc($tablename);
		if (!is_array($row))
			return null;

		return (object)$row;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableRowByNameAssoc($tablename = '')
	{
		if ($tablename === null)
			return null;

		return self::getTableRowByWhere(['tablename' => $tablename]);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableRowByWhere(array $where)
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addConditionsFromArray($where);
		$rows = database::loadAssocList('#__customtables_tables AS s', self::getTableRowSelectArray(), $whereClause, null, null, 1);

		if (count($rows) != 1)
			return null;

		$row = $rows[0];
		$published_field_found = true;

		if ($row['customtablename'] != '') {
			$realFields = Fields::getListOfExistingFields($row['realtablename'], false);

			if (!in_array('published', $realFields))
				$published_field_found = false;
		}
		$row['published_field_found'] = $published_field_found;

		return $row;
	}

	public static function getTableRowSelectArray(): array
	{
		return ['*', 'REAL_TABLE_NAME', 'REAL_ID_FIELD_NAME', 'PUBLISHED_FIELD_FOUND'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function copyTable(CT $ct, $originalTableId, $new_table, $old_table, $customTableName = '')
	{
		//Copy Table
		//get ID of new table
		$new_table_id = self::getTableID($new_table);

		if (empty($customTableName)) {
			//Do not copy real third-party tables
			database::copyCTTable($new_table, $old_table);
		}

		//Copy Fields
		$fields = array('fieldname', 'isrequired', 'isdisabled', 'alwaysupdatevalue', 'parentid', 'ordering', 'defaultvalue', 'type', 'typeparams', 'valuerule', 'valuerulecaption',
			'created_by', 'modified_by', 'created', 'modified');

		$moreThanOneLanguage = false;

		foreach ($ct->Languages->LanguageList as $lang) {
			if ($moreThanOneLanguage) {
				$fields[] = 'fieldtitle' . '_' . $lang->sef;
				$fields[] = 'description' . '_' . $lang->sef;
			} else {
				$fields[] = 'fieldtitle';
				$fields[] = 'description';

				$moreThanOneLanguage = true;
			}
		}

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addCondition('tableid', $originalTableId);

		$rows = database::loadAssocList('#__customtables_fields', ['*'], $whereClause);

		foreach ($rows as $row) {

			$data = [];
			$data['tableid'] = $new_table_id;

			foreach ($fields as $fld) {

				if ($fld == 'parentid') {
					if ((int)$row[$fld] == 0)
						$data[$fld] = null;
					else
						$data[$fld] = (int)$row[$fld];
				} elseif ($fld == 'created_by' or $fld == 'modified_by') {
					if ((int)$row[$fld] == 0)
						$data[$fld] = $ct->Env->user->id;
					else
						$data[$fld] = (int)$row[$fld];
				} elseif ($fld == 'created' or $fld == 'modified') {
					if ($row[$fld] == "")
						$data[$fld] = ['NOW()', 'sanitized'];
					else
						$data[$fld] = $row[$fld];
				} else {
					$data[$fld] = $row[$fld];//str_replace('"', '\"', $row[$fld]);
				}
			}
			database::insert('#__customtables_fields', $data);
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.4.3
	 */
	public static function deleteTable(int $tableId): object
	{
		$table_row = TableHelper::getTableRowByID($tableId);

		if (isset($table_row->tablename) and (!isset($table_row->customtablename) or empty($table_row->customtablename))) // do not delete third-party tables
			database::dropTableIfExists($table_row->tablename);

		database::deleteRecord('#__customtables_fields', 'tableid', (string)$tableId);
		database::deleteRecord('#__customtables_tables', 'id', $tableId);
		//database::deleteTableLessFields();//TODO: No longer needed but lets keep ot for one year till Oct 2025 to make sure that all unused fields deleted.
		return $table_row;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableRowByID(int $tableid): ?object
	{
		if ($tableid == 0)
			return null;

		$row = self::getTableRowByIDAssoc($tableid);
		if (!is_array($row))
			return null;

		return (object)$row;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableRowByIDAssoc(int $tableid)
	{
		if ($tableid == 0)
			return null;

		return self::getTableRowByWhere(['id' => $tableid]);
	}

	/**
	 * @throws Exception
	 * @since 3.4.4
	 */
	public static function getAllTables(?int $categoryId = null): array
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		if ($categoryId !== null)
			$whereClause->addCondition('tablecategory', $categoryId);

		return database::loadAssocList('#__customtables_tables', ['id', 'tablename', 'tabletitle'], $whereClause, 'tablename');
	}

	/**
	 * @throws Exception
	 * @since 3.5.6
	 */
	public static function getListOfExistingTables(): array
	{
		$prefix = database::getDBPrefix();
		$serverType = database::getServerType();

		$whereClause = new MySQLWhereClause();

		if ($serverType == 'postgresql') {
			$whereClause->addCondition('table_type', 'BASE TABLE');
			$whereClause->addCondition('table_schema NOT IN (\'pg_catalog\', \'information_schema\')', null);
			$whereClause->addCondition('POSITION(\'' . $prefix . 'customtables_\' IN table_name)', 1, '!=');
			$whereClause->addCondition('table_name', $prefix . 'user_keys', '!=');
			$whereClause->addCondition('table_name', $prefix . 'user_usergroup_map', '!=');
			$whereClause->addCondition('table_name', $prefix . 'usergroups', '!=');
			$whereClause->addCondition('table_name', $prefix . 'users', '!=');
			$rows = database::loadAssocList('information_schema.tables', ['table_name'], $whereClause);
		} else {
			$database = database::getDataBaseName();
			$whereClause->addCondition('table_schema', $database);
			$whereClause->addCondition('INSTR(TABLE_NAME,\'' . $prefix . 'customtables_\')', 'false', '=', true);
			$whereClause->addCondition('TABLE_NAME', $prefix . 'user_keys', '!=');
			$whereClause->addCondition('TABLE_NAME', $prefix . 'user_usergroup_map', '!=');
			$whereClause->addCondition('TABLE_NAME', $prefix . 'usergroups', '!=');
			$whereClause->addCondition('TABLE_NAME', $prefix . 'users', '!=');
			$rows = database::loadAssocList('information_schema.tables', ['TABLE_NAME AS table_name'], $whereClause, 'table_name');
		}
		$list = array();

		foreach ($rows as $row)
			$list[] = $row['table_name'];

		return $list;
	}
}
