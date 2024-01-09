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
use Joomla\CMS\Factory;

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

		//$query = 'SELECT id FROM #__customtables_tables AS s WHERE tablename=' . database::quote($tablename) . ' LIMIT 1';
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
			//$query = 'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_name = ' . database::quote($realtablename) . ' LIMIT 1';
			$rows = database::loadObjectList('information_schema.columns', ['COUNT(*) AS c'], $whereClause, null, null, 1);
		} else {
			$whereClause->addCondition('table_schema', $database);
			$whereClause->addCondition('table_name', $realtablename);
			//$query = 'SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = ' . database::quote($database) . ' AND table_name = ' . database::quote($realtablename) . ' LIMIT 1';
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
	public static function createTableIfNotExists($database, $dbPrefix, $tablename, $tabletitle, $complete_table_name = ''): bool
	{
		$serverType = database::getServerType();
		if ($serverType == 'postgresql') {
			//PostgreSQL
			//Check if table exists
			if ($complete_table_name == '')
				$table_name = $dbPrefix . 'customtables_table_' . $tablename;
			elseif ($complete_table_name == '-new-')
				$table_name = $tablename;
			else
				$table_name = $complete_table_name;// used for custom table names - to connect to third-part tables for example

			$fields = Fields::getListOfExistingFields($table_name, false);

			if (count($fields) == 0) {
				//create new table
				database::setQuery('CREATE SEQUENCE IF NOT EXISTS ' . $table_name . '_seq');

				$query = '
				CREATE TABLE IF NOT EXISTS ' . $table_name . '
				(
					id int NOT NULL DEFAULT nextval (\'' . $table_name . '_seq\'),
					published smallint NOT NULL DEFAULT 1,
					PRIMARY KEY (id)
				)';

				database::setQuery($query);
				database::setQuery('ALTER SEQUENCE ' . $table_name . '_seq RESTART WITH 1');
				return true;
			}
		} else {
			//Mysql;
			$rows2 = database::getTableStatus($database, $tablename);

			if (count($rows2) > 0) {
				if ($complete_table_name == '') {
					//do not modify third-party tables
					$row2 = $rows2[0];

					$table_name = $dbPrefix . 'customtables_table_' . $tablename;

					if ($row2->Engine != 'InnoDB') {
						$query = 'ALTER TABLE ' . $table_name . ' ENGINE = InnoDB';
						database::setQuery($query);
					}

					$query = 'ALTER TABLE ' . $table_name . ' COMMENT = "' . $tabletitle . '";';
					database::setQuery($query);
					return false;
				}
			} else {

				if ($complete_table_name == '')
					$table_name = $dbPrefix . 'customtables_table_' . $tablename;
				elseif ($complete_table_name == '-new-')
					$table_name = $tablename;
				else
					$table_name = $complete_table_name;// used for custom table names - to connect to third-part tables for example

				$query = 'CREATE TABLE IF NOT EXISTS ' . $table_name . '
					(
						id int(10) UNSIGNED NOT NULL auto_increment,
						published tinyint(1) NOT NULL DEFAULT 1,
						PRIMARY KEY (id)
					) ENGINE=InnoDB COMMENT="' . $tabletitle . '" DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;';
				database::setQuery($query);
				return true;
			}
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function renameTableIfNeeded($tableid, $database, $dbPrefix, $tablename): void
	{
		$old_tablename = ESTables::getTableName($tableid);

		if ($old_tablename != $tablename) {
			//rename table
			$tableStatus = database::getTableStatus($database, $old_tablename);

			if (count($tableStatus) > 0) {
				$query = 'RENAME TABLE ' . database::quoteName($database . '.' . $dbPrefix . 'customtables_table_' . $old_tablename) . ' TO '
					. database::quoteName($database . '.' . $dbPrefix . 'customtables_table_' . $tablename) . ';';

				database::setQuery($query);
			}
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
			//$query = 'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ' . database::quote($realtablename);
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
			/*
			$query = 'SELECT '
				. 'COLUMN_NAME AS column_name,'
				. 'DATA_TYPE AS data_type,'
				. 'COLUMN_TYPE AS column_type,'
				. 'IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') AS is_unsigned,'
				. 'IS_NULLABLE AS is_nullable,'
				. 'COLUMN_DEFAULT AS column_default,'
				. 'COLUMN_COMMENT AS column_comment,'
				. 'COLUMN_KEY AS column_key,'
				. 'EXTRA AS extra FROM information_schema.columns WHERE table_schema = ' . database::quote($database) . ' AND table_name = ' . database::quote($realtablename);
			*/
			$whereClause->addCondition('table_schema', $database);
		}
		$whereClause->addCondition('table_name', $realtablename);

		$fields = database::loadObjectList('information_schema.columns', $selects, $whereClause);

		//$set_fieldNames = ['tableid', 'fieldname', 'fieldtitle', 'allowordering', 'type', 'typeparams', 'ordering', 'defaultvalue', 'description', 'customfieldname', 'isrequired'];

		$primary_key_column = '';
		$ordering = 1;
		foreach ($fields as $field) {
			if ($primary_key_column == '' and strtolower($field->column_key) == 'pri') {
				$primary_key_column = $field->column_name;
			} else {
				//$set_values = [];

				$ct_field_type = Fields::convertMySQLFieldTypeToCT($field->data_type, $field->column_type);
				if ($ct_field_type['type'] === null) {
					Factory::getApplication()->enqueueMessage('third-party table field type "' . $field->data_type . '" is unknown.', 'error');
					return false;
				}

				//$set_fieldNames = ['tableid', 'fieldname', 'fieldtitle', 'allowordering', 'type', 'typeparams', 'ordering', 'defaultvalue', 'description', 'customfieldname', 'isrequired'];

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

				//$query = 'INSERT INTO #__customtables_fields (' . implode(',', $set_fieldNames) . ') VALUES (' . implode(',', $set_values) . ')';
				database::insert('#__customtables_fields', $data);
				//database::setQuery($query);
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

			//$query = 'UPDATE #__customtables_tables SET customidfield = ' . database::quote($primary_key_column) . ' WHERE id = ' . (int)$tableRow->id;
			//database::setQuery($query);
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
			$serverType = database::getServerType();
			if ($serverType == 'postgresql')
				$query = 'CREATE TABLE #__customtables_table_' . $new_table . ' AS TABLE #__customtables_table_' . $old_table;
			else
				$query = 'CREATE TABLE #__customtables_table_' . $new_table . ' AS SELECT * FROM #__customtables_table_' . $old_table;

			database::setQuery($query);

			$query = 'ALTER TABLE #__customtables_table_' . $new_table . ' ADD PRIMARY KEY (id)';
			database::setQuery($query);

			$query = 'ALTER TABLE #__customtables_table_' . $new_table . ' CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT';
			database::setQuery($query);
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

		//$query = 'SELECT * FROM #__customtables_fields WHERE published=1 AND tableid=' . $originalTableId;

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addCondition('tableid', $originalTableId);

		$rows = database::loadAssocList('#__customtables_fields', ['*'], $whereClause, null, null);

		if (count($rows) == 0)
			die('Original table has no fields.');

		foreach ($rows as $row) {

			$data = [];
			$data['tableid'] = $new_table_id;
			//$inserts = array('tableid=' . $new_table_id);
			foreach ($fields as $fld) {

				if ($fld == 'parentid') {
					if ((int)$row[$fld] == 0)
						$data[$fld] = null;
					//$inserts[] = $fld . '=NULL';
					else
						$data[$fld] = (int)$row[$fld];
					//$inserts[] = $fld . '=' . (int)$row[$fld];
				} elseif ($fld == 'created_by' or $fld == 'modified_by') {
					if ((int)$row[$fld] == 0)
						$data[$fld] = $ct->Env->user->id;
					//$inserts[] = $fld . '=' . $ct->Env->user->id;
					else
						$data[$fld] = (int)$row[$fld];
					//$inserts[] = $fld . '=' . (int)$row[$fld];
				} elseif ($fld == 'created' or $fld == 'modified') {
					if ($row[$fld] == "")
						$data[$fld] = ['NOW()', 'sanitized'];
					//$inserts[] = $fld . '=NOW()';
					else
						$data[$fld] = $row[$fld];
					//$inserts[] = $fld . '="' . $row[$fld] . '"';
				} else {
					//$value = str_replace('"', '\"', $row[$fld]);
					$data[$fld] = str_replace('"', '\"', $row[$fld]);
					//$inserts[] = $fld . '="' . $value . '"';
				}
			}
			database::insert('#__customtables_fields', $data);
			//$iq = 'INSERT INTO #__customtables_fields SET ' . implode(', ', $inserts);
			//database::setQuery($iq);
		}
		return true;
	}
}
