<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @subpackage integrity/tables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\Integrity;

if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use CustomTables;
use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\Fields;
use CustomTables\IntegrityChecks;
use Exception;

class IntegrityCoreTables extends IntegrityChecks
{
	public static function checkCoreTables(&$ct)
	{
		IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Tables());
		IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Fields());
		IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Layouts());

		if (defined('_JEXEC'))
			IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Categories());

		IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Log());
	}

	/**
	 * @throws Exception
	 * @since 3.2.6
	 */
	protected static function createCoreTableIfNotExists(CT &$ct, object $table)
	{
		if ($table->realtablename === null or $table->realtablename === '') {
			throw new Exception('createCoreTableIfNotExists: Table real name cannot be NULL');
		}

		if (!TableHelper::checkIfTableExists($table->realtablename))
			IntegrityCoreTables::createCoreTable($ct, $table);
		else
			IntegrityCoreTables::checkCoreTable($ct, $table->realtablename, $table->fields);
	}

	protected static function createCoreTable(CT &$ct, object $table): bool
	{
		//TODO:
		//Add InnoDB Row Formats to config file
		//https://dev.mysql.com/doc/refman/5.7/en/innodb-row-format.html

		$serverType = database::getServerType();
		$fields_sql = IntegrityCoreTables::prepareAddFieldQuery($ct, $table->fields, ($serverType == 'postgresql' ? 'postgresql_type' : 'mysql_type'), true);
		$indexes_sql = IntegrityCoreTables::prepareAddIndexQuery($table->indexes);

		//Check if table exists
		$tableExists = false;
		if ($serverType == 'postgresql') {
			$fields = Fields::getListOfExistingFields($table->realtablename, false);

			if (count($fields) > 0)
				$tableExists = true;
		} else {
			//Mysql;
			$rows = database::getTableStatus($table->tablename, 'coretable');

			if (count($rows) > 0)
				$tableExists = true;
		}

		if (!$tableExists)
			database::createTable($table->realtablename, 'id', $fields_sql, $table->comment, $indexes_sql);

		CustomTables\common::enqueueMessage('Table "' . $table->realtablename . '" added.', 'notice');
		return true;

		return false;
	}

	protected static function prepareAddFieldQuery(CT &$ct, $fields, $db_type, $ignoreId = false): array
	{
		$fields_sql = [];
		foreach ($fields as $field) {
			if (!$ignoreId or $field['name'] != 'id') {
				if (isset($field['multilang']) and $field['multilang'] == true) {
					$moreThanOneLanguage = false;
					foreach ($ct->Languages->LanguageList as $lang) {
						$fieldname = $field['name'];

						if ($moreThanOneLanguage)
							$fieldname .= '_' . $lang->sef;

						$fields_sql[] = '`' . $fieldname . '` ' . $field[$db_type];

						$moreThanOneLanguage = true;
					}
				} else {
					$fields_sql[] = '`' . ($field['name']) . '` ' . $field[$db_type];
				}
			}
		}
		return $fields_sql;
	}

	protected static function prepareAddIndexQuery($indexes): array
	{
		$indexes_sql = [];
		foreach ($indexes as $index)
			$indexes_sql[] = 'KEY `' . $index['name'] . '` (`' . $index['field'] . '`)';

		return $indexes_sql;
	}

	public static function checkCoreTable(CT &$ct, $realtablename, $projected_fields)
	{
		$ExistingFields = database::getExistingFields($realtablename, false);

		foreach ($projected_fields as $projected_field) {

			if (isset($projected_field['ct_fieldtype']) and $projected_field['ct_fieldtype'] != '') {
				$projected_realfieldname = $projected_field['name'];
				$fieldType = $projected_field['ct_fieldtype'];
				$typeParams = '';

				if (IntegrityFields::addFieldIfNotExists($ct, $realtablename, $ExistingFields, $projected_realfieldname, $fieldType, $typeParams))
					$ExistingFields = database::getExistingFields($realtablename, false);//reload list of existing fields if one field has been added.

				if (isset($projected_field['ct_fieldtype']) and $projected_field['ct_fieldtype'] != '') {
					$ct_fieldtype = $projected_field['ct_fieldtype'];

					if (isset($projected_field['ct_typeparams']) and $projected_field['ct_typeparams'] != '')
						$typeParams = $projected_field['ct_typeparams'];

					IntegrityCoreTables::checkCoreTableFields($realtablename, $ExistingFields, $projected_realfieldname, $ct_fieldtype, $typeParams, $projected_field['name']);
				}
			}
		}
	}

	public static function checkCoreTableFields($realtablename, $ExistingFields, $realfieldname, $ct_fieldType, string $ct_typeparams = '', ?string $field_title = null)
	{
		$existingFieldFound = null;
		foreach ($ExistingFields as $ExistingField) {
			if ($ExistingField['column_name'] == $realfieldname) {
				$existingFieldFound = $ExistingField;
				break;
			}
		}

		if ($existingFieldFound === null)
			die('field not created ' . $realfieldname);

		if ($ct_fieldType !== null and $ct_fieldType != '') {
			$projected_data_type = Fields::getProjectedFieldType($ct_fieldType, $ct_typeparams);

			if (!IntegrityFields::compareFieldTypes($existingFieldFound, $projected_data_type)) {
				$PureFieldType = Fields::makeProjectedFieldType($projected_data_type);

				$msg = '';
				if (!Fields::fixMYSQLField($realtablename, $realfieldname, $PureFieldType, $msg, $field_title)) {
					common::enqueueMessage($msg);
					return false;
				}
			}
		}
		return true;
	}

	protected static function getCoreTableFields_Tables(): object
	{
		$dbPrefix = database::getDBPrefix();

		$tables_projected_fields = [];
		$tables_projected_fields[] = ['name' => 'id', 'ct_fieldtype' => '_id', 'mysql_type' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT', 'postgresql_type' => 'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_tables_seq\')'];
		$tables_projected_fields[] = ['name' => 'published', 'ct_fieldtype' => '_published', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 1', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'tablename', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NOT NULL DEFAULT "tablename"', 'postgresql_type' => 'VARCHAR(100) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[] = ['name' => 'tabletitle', 'ct_fieldtype' => 'string', 'mysql_type' => 'VARCHAR(255) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(255) NULL DEFAULT NULL', 'multilang' => true];
		$tables_projected_fields[] = ['name' => 'description', 'ct_fieldtype' => 'text', 'mysql_type' => 'TEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL', 'multilang' => true];

		//Not used inside Custom Tables

		$tables_projected_fields[] = ['name' => 'tablecategory', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'INT NULL DEFAULT NULL', 'postgresql_type' => 'INT NULL DEFAULT NULL'];

		$tables_projected_fields[] = ['name' => 'customphp', 'ct_fieldtype' => 'link', 'mysql_type' => 'VARCHAR(1024) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(1024) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'customtablename', 'ct_fieldtype' => 'string', 'mysql_type' => 'VARCHAR(100) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'customidfield', 'ct_fieldtype' => 'string', 'mysql_type' => 'VARCHAR(100) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'allowimportcontent', 'ct_fieldtype' => 'checkbox', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 0', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 0'];

		$tables_projected_fields[] = ['name' => 'created_by', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'modified_by', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'created', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'modified', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'checked_out', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'int UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'checked_out_time', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];

		$tables_projected_indexes = [];
		$tables_projected_indexes[] = ['name' => 'idx_published', 'field' => 'published'];
		$tables_projected_indexes[] = ['name' => 'idx_tablename', 'field' => 'tablename'];

		return (object)['realtablename' => $dbPrefix . 'customtables_tables',
			'tablename' => 'tables',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'List of Custom Tables tables'];
	}

	protected static function getCoreTableFields_Fields(): object
	{
		$dbPrefix = database::getDBPrefix();
		$tables_projected_fields = array();

		$tables_projected_fields[] = ['name' => 'id', 'ct_fieldtype' => '_id', 'mysql_type' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT', 'postgresql_type' => 'id INT check (id > 0) NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'published', 'ct_fieldtype' => '_published', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 1', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'tableid', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL', 'postgresql_type' => 'INT NOT NULL'];

		$tables_projected_fields[] = ['name' => 'fieldname', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NOT NULL', 'postgresql_type' => 'VARCHAR(100) NOT NULL'];
		$tables_projected_fields[] = ['name' => 'fieldtitle', 'ct_fieldtype' => 'string', 'mysql_type' => 'VARCHAR(255) NULL', 'postgresql_type' => 'VARCHAR(255) NULL DEFAULT NULL', 'multilang' => true];
		$tables_projected_fields[] = ['name' => 'description', 'ct_fieldtype' => 'text', 'mysql_type' => 'TEXT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL', 'multilang' => true];

		$tables_projected_fields[] = ['name' => 'allowordering', 'ct_fieldtype' => '', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 1', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'isrequired', 'ct_fieldtype' => '', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 1', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'isdisabled', 'ct_fieldtype' => '', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 0', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'alwaysupdatevalue', 'ct_fieldtype' => 'checkbox', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 0', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 0', 'comment' => 'Update default value every time record is edited.'];

		$tables_projected_fields[] = ['name' => 'parentid', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'INT NULL', 'postgresql_type' => 'INT NULL'];
		$tables_projected_fields[] = ['name' => 'ordering', 'ct_fieldtype' => 'int', 'mysql_type' => 'INT NOT NULL', 'postgresql_type' => 'INT NOT NULL'];

		$tables_projected_fields[] = ['name' => 'defaultvalue', 'ct_fieldtype' => '', 'mysql_type' => 'VARCHAR(1024) NULL', 'postgresql_type' => 'VARCHAR(1024) NULL'];
		$tables_projected_fields[] = ['name' => 'customfieldname', 'ct_fieldtype' => 'string', 'mysql_type' => 'VARCHAR(100) NULL', 'postgresql_type' => 'VARCHAR(100) NULL'];
		$tables_projected_fields[] = ['name' => 'type', 'ct_fieldtype' => '', 'mysql_type' => 'VARCHAR(50) NULL', 'postgresql_type' => 'VARCHAR(50) NULL'];
		$tables_projected_fields[] = ['name' => 'typeparams', 'ct_fieldtype' => '', 'mysql_type' => 'VARCHAR(1024) NULL', 'postgresql_type' => 'VARCHAR(1024) NULL'];
		$tables_projected_fields[] = ['name' => 'valuerule', 'ct_fieldtype' => '', 'mysql_type' => 'VARCHAR(1024) NULL', 'postgresql_type' => 'VARCHAR(1024) NULL'];
		$tables_projected_fields[] = ['name' => 'valuerulecaption', 'ct_fieldtype' => 'string', 'ct_typeparams' => '1024', 'mysql_type' => 'VARCHAR(1024) NULL', 'postgresql_type' => 'VARCHAR(1024) NULL'];

		$tables_projected_fields[] = ['name' => 'created_by', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'modified_by', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'created', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL'];
		$tables_projected_fields[] = ['name' => 'modified', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL'];
		$tables_projected_fields[] = ['name' => 'checked_out', 'ct_fieldtype' => '', 'mysql_type' => 'int UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'checked_out_time', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];

		$tables_projected_indexes = [];
		$tables_projected_indexes[] = ['name' => 'idx_published', 'field' => 'published'];
		$tables_projected_indexes[] = ['name' => 'idx_tableid', 'field' => 'tableid'];
		$tables_projected_indexes[] = ['name' => 'idx_fieldname', 'field' => 'fieldname'];

		return (object)['realtablename' => $dbPrefix . 'customtables_fields',
			'tablename' => 'fields',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Custom Tables Fields'];
	}

	protected static function getCoreTableFields_Layouts(): object
	{
		$dbPrefix = database::getDBPrefix();
		$tables_projected_fields = array();

		$tables_projected_fields[] = ['name' => 'id', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT', 'postgresql_type' => 'id INT check (id > 0) NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'published', 'ct_fieldtype' => '', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 1', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'tableid', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL', 'postgresql_type' => 'INT NOT NULL'];

		$tables_projected_fields[] = ['name' => 'layoutname', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NOT NULL DEFAULT "tablename"', 'postgresql_type' => 'VARCHAR(100) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[] = ['name' => 'layouttype', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];

		$tables_projected_fields[] = ['name' => 'layoutcode', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'layoutmobile', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'layoutcss', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'layoutjs', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];

		$tables_projected_fields[] = ['name' => 'changetimestamp', 'ct_fieldtype' => '', 'mysql_type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];

		$tables_projected_fields[] = ['name' => 'created_by', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'modified_by', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'created', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'modified', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'checked_out', 'ct_fieldtype' => '', 'mysql_type' => 'int UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'checked_out_time', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];

		$tables_projected_indexes = [];
		$tables_projected_indexes[] = ['name' => 'idx_published', 'field' => 'published'];
		$tables_projected_indexes[] = ['name' => 'idx_tableid', 'field' => 'tableid'];
		$tables_projected_indexes[] = ['name' => 'idx_layoutname', 'field' => 'layoutname'];

		return (object)['realtablename' => $dbPrefix . 'customtables_layouts',
			'tablename' => 'layouts',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Custom Tables Layouts'];
	}

	protected static function getCoreTableFields_Categories(): object
	{
		$dbPrefix = database::getDBPrefix();
		$tables_projected_fields = array();

		$tables_projected_fields[] = ['name' => 'id', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT', 'postgresql_type' => 'id INT check (id > 0) NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'published', 'ct_fieldtype' => '', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 1', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 1'];

		$tables_projected_fields[] = ['name' => 'categoryname', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NOT NULL DEFAULT "tablename"', 'postgresql_type' => 'VARCHAR(100) NOT NULL DEFAULT \'\''];

		$tables_projected_fields[] = ['name' => 'created_by', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'modified_by', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'created', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'modified', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'checked_out', 'ct_fieldtype' => '', 'mysql_type' => 'int UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'checked_out_time', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];

		$tables_projected_indexes = [];
		$tables_projected_indexes[] = ['name' => 'idx_published', 'field' => 'published'];
		$tables_projected_indexes[] = ['name' => 'idx_categoryname', 'field' => 'categoryname'];

		return (object)['realtablename' => $dbPrefix . 'customtables_categories',
			'tablename' => 'categories',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Custom Tables Categories'];
	}

	protected static function getCoreTableFields_Log(): object
	{
		$dbPrefix = database::getDBPrefix();
		$tables_projected_fields = array();

		$tables_projected_fields[] = ['name' => 'id', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT', 'postgresql_type' => 'id INT check (id > 0) NOT NULL DEFAULT 1'];
		$tables_projected_fields[] = ['name' => 'userid', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'datetime', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'tableid', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'action', 'ct_fieldtype' => '', 'mysql_type' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'MALLINT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'listingid', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[] = ['name' => 'Itemid', 'ct_fieldtype' => '', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];

		$tables_projected_indexes = [];
		$tables_projected_indexes[] = ['name' => 'idx_userid', 'field' => 'userid'];
		$tables_projected_indexes[] = ['name' => 'idx_action', 'field' => 'action'];

		return (object)['realtablename' => $dbPrefix . 'customtables_log',
			'tablename' => 'log',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Custom Tables Action Log'];
	}
}
