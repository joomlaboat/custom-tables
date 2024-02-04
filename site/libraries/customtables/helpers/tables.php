<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Fields;
use CustomTables\MySQLWhereClause;

class ESTables
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

			$already_exists = ESTables::getTableID($new_tablename);
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
			$rows = database::loadObjectList('information_schema.columns', ['COUNT(*) AS c'], $whereClause, null, null, 1);
		} else {
			$whereClause->addCondition('table_schema', $database);
			$whereClause->addCondition('table_name', $realtablename);
			$rows = database::loadObjectList('information_schema.tables', ['COUNT(*) AS c'], $whereClause, null, null, 1);
		}

		$c = (int)$rows[0]->c;
		if ($c > 0)
			return true;

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableRowByID(int $tableid): ?object
	{
		if ($tableid == 0)
			return null;

		$row = ESTables::getTableRowByIDAssoc($tableid);
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

		return ESTables::getTableRowByWhere(['id' => $tableid]);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getTableRowByWhere(array $where)
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addConditionsFromArray($where);

		//$query = 'SELECT ' . ESTables::getTableRowSelects() . ' FROM #__customtables_tables AS s WHERE ' . $where . ' LIMIT 1';
		$rows = database::loadAssocList('#__customtables_tables AS s', ESTables::getTableRowSelectArray(), $whereClause, null, null, 1);

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
		$serverType = database::getServerType();
		if ($serverType == 'postgresql') {
			$realtablename_query = 'CASE WHEN customtablename!=\'\' THEN customtablename ELSE CONCAT(\'#__customtables_table_\', tablename) END AS realtablename';
			$realidfieldname_query = 'CASE WHEN customidfield!=\'\' THEN customidfield ELSE \'id\' END AS realidfieldname';
		} else {
			$realtablename_query = 'IF((customtablename IS NOT NULL AND customtablename!=\'\'	), customtablename, CONCAT(\'#__customtables_table_\', tablename)) AS realtablename';
			$realidfieldname_query = 'IF(customidfield!=\'\', customidfield, \'id\') AS realidfieldname';
		}

		return ['*', $realtablename_query, $realidfieldname_query, '1 AS published_field_found'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function createTableIfNotExists($database, $dbPrefix, $tableName, $tableTitle, $complete_table_name = ''): bool
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

					if ($row2->Engine != 'InnoDB') {
						database::setTableInnoDBEngine($realTableName);
						//$query = 'ALTERTABLE ' . $table_name . ' ENGINE = InnoDB';
					}

					database::changeTableComment($realTableName, $tableTitle);
					//$query = 'ALTERTABLE ' . $table_name . ' COMMENT = "' . $tabletitle . '";';
					return false;
				}
			}
		}

		if (!$tableExists) {
			$columns = [
				'published tinyint(1) NOT NULL DEFAULT 1'
			];
			database::createTable($realTableName, 'id', $columns, $tableTitle);
			return true;
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function renameTableIfNeeded($tableid, $tablename): void
	{
		$old_tablename = ESTables::getTableName($tableid);

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
	public static function getTableName($tableid = 0): ?string
	{
		if ($tableid == 0)
			$tableid = common::inputGetInt('tableid', 0);

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', (int)$tableid);

		//$query = 'SELECT tablename FROM #__customtables_tables AS s WHERE id=' . (int)$tableid . ' LIMIT 1';
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
		$fields = Fields::getFields($tablename, false, true);
		if (count($fields) > 0)
			return false;

		//Add third-party fields

		$tableRow = ESTables::getTableRowByName($tablename);

		$whereClause = new MySQLWhereClause();

		$serverType = database::getServerType();

		if ($serverType == 'postgresql') {
			$selects = ['column_name', 'data_type', 'is_nullable', 'column_default'];
		} else {
			$selects = [
				'COLUMN_NAME AS column_name',
				'DATA_TYPE AS data_type',
				'COLUMN_TYPE AS column_type',
				'IF(COLUMN_TYPE LIKE "%unsigned", "YES", "NO") AS is_unsigned',
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
		$ordering = 1;
		foreach ($fields as $field) {
			if ($primary_key_column == '' and strtolower($field->column_key) == 'pri') {
				$primary_key_column = $field->column_name;
			} else {
				$ct_field_type = Fields::convertMySQLFieldTypeToCT($field->data_type, $field->column_type);
				if ($ct_field_type['type'] === null) {
					common::enqueueMessage('third-party table field type "' . $field->data_type . '" is unknown.');
					return false;
				}

				$data['tableid'] = (int)$tableRow->id;
				$data['fieldname'] = strtolower($field->column_name);
				$data['fieldtitle'] = ucwords(strtolower($field->column_name));
				$data['allowordering'] = true;
				$data['type'] = $ct_field_type['type'];

				if (key_exists('typeparams', $ct_field_type))
					$data['typeparams'] = $ct_field_type['typeparams'];

				$data['ordering'] = $ordering;
				$data['defaultvalue'] = $field->column_default != '' ? $field->column_default : null;
				$data['description'] = $field->column_comment != '' ? $field->column_comment : null;
				$data['customfieldname'] = $field->column_name;
				$data['isrequired'] = 0;

				database::insert('#__customtables_fields', $data);
				$ordering += 1;
			}
		}

		if ($primary_key_column != '') {
			//Update primary key column

			$data = [
				'customidfield' => $primary_key_column
			];
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('id', (int)$tableRow->id);
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

		$row = ESTables::getTableRowByNameAssoc($tablename);
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

		return ESTables::getTableRowByWhere(['tablename' => $tablename]);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function copyTable(CT $ct, $originalTableId, $new_table, $old_table, $customTableName = '')
	{
		//Copy Table
		//get ID of new table
		$new_table_id = ESTables::getTableID($new_table);

		if ($customTableName === null) {
			//Do not copy real third-party tables
			database::copyCTTable($new_table, $old_table);
		}

		//Copy Fields
		$fields = array('fieldname', 'allowordering', 'isrequired', 'isdisabled', 'alwaysupdatevalue', 'parentid', 'ordering', 'defaultvalue', 'customfieldname', 'type', 'typeparams', 'valuerule', 'valuerulecaption',
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

		if (count($rows) == 0)
			die('Original table has no fields.');

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
}
