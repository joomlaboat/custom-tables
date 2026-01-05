<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @subpackage integrity/tables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\Integrity;

defined('_JEXEC') or die();

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
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function checkCoreTables($ct): void
	{
		$fieldTypes['tables_id'] = ['name' => 'id', 'ct_fieldtype' => '_id', 'mysql_type' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT', 'postgresql_type' => 'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_tables_seq\')'];
		$fieldTypes['id'] = ['name' => 'id', 'ct_fieldtype' => '_id', 'mysql_type' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT', 'postgresql_type' => 'id INT check (id > 0) NOT NULL DEFAULT 1'];
		$fieldTypes['published'] = ['name' => 'published', 'ct_fieldtype' => '_published', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 1', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 1'];
		$fieldTypes['created_by'] = ['name' => 'created_by', 'ct_fieldtype' => 'userid', 'mysql_type' => 'INT UNSIGNED NULL DEFAULT NULL', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$fieldTypes['modified_by'] = ['name' => 'modified_by', 'ct_fieldtype' => 'userid', 'mysql_type' => 'INT UNSIGNED NULL DEFAULT NULL', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$fieldTypes['created'] = ['name' => 'created', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$fieldTypes['modified'] = ['name' => 'modified', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$fieldTypes['checked_out'] = ['name' => 'checked_out', 'ct_fieldtype' => 'userid', 'mysql_type' => 'INT UNSIGNED NULL DEFAULT NULL', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$fieldTypes['checked_out_time'] = ['name' => 'checked_out_time', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];

		IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Tables($fieldTypes));
		IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Fields($fieldTypes));
		IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Layouts($fieldTypes));
		IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Log($fieldTypes));

		if (defined('_JEXEC'))
			IntegrityCoreTables::createCoreTableIfNotExists($ct, IntegrityCoreTables::getCoreTableFields_Categories($fieldTypes));
	}

	/**
	 * @throws Exception
	 * @since 3.2.6
	 */
	protected static function createCoreTableIfNotExists(CT $ct, object $table): void
	{
		if ($table->realtablename === null or $table->realtablename === '') {
			throw new Exception('createCoreTableIfNotExists: Table real name cannot be NULL');
		}

		if (!TableHelper::checkIfTableExists($table->realtablename))
			IntegrityCoreTables::createCoreTable($ct, $table);
		else
			IntegrityCoreTables::checkCoreTable($table->realtablename, $table->fields);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function createCoreTable(CT $ct, object $table): bool
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

		$dbPrefix = database::getDBPrefix();
		$tableNameSafe = str_replace($dbPrefix, '#__', $table->realtablename);
		CustomTables\common::enqueueMessage('Table "' . $tableNameSafe . '" created.', 'notice');
		return true;
	}

	protected static function prepareAddFieldQuery(CT $ct, $fields, $db_type, $ignoreId = false): array
	{
		$fields_sql = [];
		foreach ($fields as $field) {
			if (!$ignoreId or $field['name'] != 'id') {
				if (isset($field['multilang']) and $field['multilang']) {
					$moreThanOneLanguage = false;
					foreach ($ct->Languages->LanguageList as $lang) {
						$fieldName = $field['name'];

						if ($moreThanOneLanguage)
							$fieldName .= '_' . $lang->sef;

						$fields_sql[] = '`' . $fieldName . '` ' . $field[$db_type];

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

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function checkCoreTable($realtablename, $projected_fields): void
	{
		$ExistingFields = database::getExistingFields($realtablename, false);

		foreach ($projected_fields as $projected_field) {

			$projected_realfieldname = $projected_field['name'];
			$typeParams = '';

			if (!Fields::checkIfFieldExists($realtablename, $projected_realfieldname)) {
				common::enqueueMessage('Field: ' . $projected_realfieldname . ' added.', 'notice');
				database::addColumn($realtablename, $projected_realfieldname, $projected_field['mysql_type']);
				$ExistingFields = database::getExistingFields($realtablename, false);
			}

			$ct_fieldtype = $projected_field['ct_fieldtype'];

			if (isset($projected_field['ct_typeparams']) and $projected_field['ct_typeparams'] != '')
				$typeParams = $projected_field['ct_typeparams'];

			try {
				IntegrityCoreTables::checkCoreTableFields($realtablename, $ExistingFields, $projected_realfieldname, $ct_fieldtype, $typeParams, $projected_field['name']);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
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
			throw new Exception('Field not created ' . $realfieldname);

		if ($ct_fieldType !== null and $ct_fieldType != '') {
			$projected_data_type = Fields::getProjectedFieldType($ct_fieldType, $ct_typeparams);

			if (!IntegrityFields::compareFieldTypes($existingFieldFound, $projected_data_type)) {
				$PureFieldType = Fields::makeProjectedFieldType($projected_data_type);

				try {
					Fields::fixMYSQLField($realtablename, $realfieldname, $PureFieldType, $field_title);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}
		}
	}

	protected static function getCoreTableFields_Tables(array $fieldTypes): object
	{
		$dbPrefix = database::getDBPrefix();

		//_published
		$tables_projected_fields = [];
		$tables_projected_fields[] = $fieldTypes['tables_id'];
		$tables_projected_fields[] = $fieldTypes['published'];
		$tables_projected_fields[] = ['name' => 'tablename', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NOT NULL DEFAULT "tablename"', 'postgresql_type' => 'VARCHAR(100) NOT NULL DEFAULT \'tablename\''];
		$tables_projected_fields[] = ['name' => 'tabletitle', 'ct_fieldtype' => 'string', 'ct_typeparams' => 255, 'mysql_type' => 'VARCHAR(255) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(255) NULL DEFAULT NULL', 'multilang' => true];
		$tables_projected_fields[] = ['name' => 'description', 'ct_fieldtype' => 'text', 'mysql_type' => 'TEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL', 'multilang' => true];

		//Not used inside Custom Tables
		$tables_projected_fields[] = ['name' => 'tablecategory', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'INT NULL DEFAULT NULL', 'postgresql_type' => 'INT NULL DEFAULT NULL'];

		$tables_projected_fields[] = ['name' => 'customphp', 'ct_fieldtype' => 'link', 'mysql_type' => 'VARCHAR(1024) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(1024) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'customtablename', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'customidfield', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'allowimportcontent', 'ct_fieldtype' => 'checkbox', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 0', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 0'];

		$tables_projected_fields[] = $fieldTypes['created_by'];
		$tables_projected_fields[] = $fieldTypes['modified_by'];
		$tables_projected_fields[] = $fieldTypes['created'];
		$tables_projected_fields[] = $fieldTypes['modified'];
		$tables_projected_fields[] = $fieldTypes['checked_out'];
		$tables_projected_fields[] = $fieldTypes['checked_out_time'];

		$tables_projected_fields[] = ['name' => 'customidfieldtype', 'ct_fieldtype' => 'string', 'ct_typeparams' => 127, 'mysql_type' => 'VARCHAR(127) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(127) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'primarykeypattern', 'ct_fieldtype' => 'string', 'ct_typeparams' => 1024, 'mysql_type' => 'VARCHAR(1024) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(1024) NULL DEFAULT NULL'];
		$tables_projected_fields[] = ['name' => 'customfieldprefix', 'ct_fieldtype' => 'string', 'ct_typeparams' => 50, 'mysql_type' => 'VARCHAR(50) NULL DEFAULT NULL', 'postgresql_type' => 'VARCHAR(50) NULL DEFAULT NULL'];

		$tables_projected_indexes = [];
		$tables_projected_indexes[] = ['name' => 'idx_published', 'field' => 'published'];
		$tables_projected_indexes[] = ['name' => 'idx_tablename', 'field' => 'tablename'];

		return (object)['realtablename' => $dbPrefix . 'customtables_tables',
			'tablename' => 'tables',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'List of Custom Tables tables'];
	}

	protected static function getCoreTableFields_Fields($fieldTypes): object
	{
		$dbPrefix = database::getDBPrefix();
		$fields_projected_fields = array();

		$fields_projected_fields[] = $fieldTypes['id'];
		$fields_projected_fields[] = $fieldTypes['published'];
		$fields_projected_fields[] = ['name' => 'tableid', 'ct_fieldtype' => 'ordering', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];

		$fields_projected_fields[] = ['name' => 'fieldname', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NOT NULL', 'postgresql_type' => 'VARCHAR(100) NOT NULL'];
		$fields_projected_fields[] = ['name' => 'fieldtitle', 'ct_fieldtype' => 'string', 'ct_typeparams' => 255, 'mysql_type' => 'VARCHAR(255) NULL', 'postgresql_type' => 'VARCHAR(255) NULL DEFAULT NULL', 'multilang' => true];
		$fields_projected_fields[] = ['name' => 'description', 'ct_fieldtype' => 'text', 'mysql_type' => 'TEXT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL', 'multilang' => true];

		$fields_projected_fields[] = ['name' => 'isrequired', 'ct_fieldtype' => '_published', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 1', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 1'];
		$fields_projected_fields[] = ['name' => 'isdisabled', 'ct_fieldtype' => 'checkbox', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 0', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 0'];
		$fields_projected_fields[] = ['name' => 'alwaysupdatevalue', 'ct_fieldtype' => 'checkbox', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 0', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 0', 'comment' => 'Update default value every time record is edited.'];

		$fields_projected_fields[] = ['name' => 'parentid', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'INT NULL', 'postgresql_type' => 'INT NULL'];
		$fields_projected_fields[] = ['name' => 'ordering', 'ct_fieldtype' => 'int', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];

		$fields_projected_fields[] = ['name' => 'defaultvalue', 'ct_fieldtype' => 'string', 'ct_typeparams' => 1024, 'mysql_type' => 'VARCHAR(1024) NULL', 'postgresql_type' => 'VARCHAR(1024) NULL'];
		//$fields_projected_fields[] = ['name' => 'customfieldname', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NULL', 'postgresql_type' => 'VARCHAR(100) NULL'];
		$fields_projected_fields[] = ['name' => 'type', 'ct_fieldtype' => 'string', 'ct_typeparams' => 50, 'mysql_type' => 'VARCHAR(50) NULL', 'postgresql_type' => 'VARCHAR(50) NULL'];
		$fields_projected_fields[] = ['name' => 'typeparams', 'ct_fieldtype' => 'string', 'ct_typeparams' => 1024, 'mysql_type' => 'VARCHAR(1024) NULL', 'postgresql_type' => 'VARCHAR(1024) NULL'];
		$fields_projected_fields[] = ['name' => 'valuerule', 'ct_fieldtype' => 'string', 'ct_typeparams' => 1024, 'mysql_type' => 'VARCHAR(1024) NULL', 'postgresql_type' => 'VARCHAR(1024) NULL'];
		$fields_projected_fields[] = ['name' => 'valuerulecaption', 'ct_fieldtype' => 'string', 'ct_typeparams' => 1024, 'mysql_type' => 'VARCHAR(1024) NULL', 'postgresql_type' => 'VARCHAR(1024) NULL'];

		$fields_projected_fields[] = $fieldTypes['created_by'];
		$fields_projected_fields[] = $fieldTypes['modified_by'];
		$fields_projected_fields[] = $fieldTypes['created'];
		$fields_projected_fields[] = $fieldTypes['modified'];
		$fields_projected_fields[] = $fieldTypes['checked_out'];
		$fields_projected_fields[] = $fieldTypes['checked_out_time'];

		$fields_projected_indexes = [];
		$fields_projected_indexes[] = ['name' => 'idx_published', 'field' => 'published'];
		$fields_projected_indexes[] = ['name' => 'idx_tableid', 'field' => 'tableid'];
		$fields_projected_indexes[] = ['name' => 'idx_fieldname', 'field' => 'fieldname'];

		return (object)['realtablename' => $dbPrefix . 'customtables_fields',
			'tablename' => 'fields',
			'fields' => $fields_projected_fields,
			'indexes' => $fields_projected_indexes,
			'comment' => 'Custom Tables Fields'];
	}

	protected static function getCoreTableFields_Layouts(array $fieldTypes): object
	{
		$dbPrefix = database::getDBPrefix();
		$layouts_projected_fields = array();

		$layouts_projected_fields[] = $fieldTypes['id'];
		$layouts_projected_fields[] = $fieldTypes['published'];
		$layouts_projected_fields[] = ['name' => 'tableid', 'ct_fieldtype' => 'ordering', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$layouts_projected_fields[] = ['name' => 'layoutname', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NOT NULL DEFAULT "tablename"', 'postgresql_type' => 'VARCHAR(100) NOT NULL DEFAULT \'\''];
		$layouts_projected_fields[] = ['name' => 'layouttype', 'ct_fieldtype' => 'ordering', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$layouts_projected_fields[] = ['name' => 'layoutcode', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];
		$layouts_projected_fields[] = ['name' => 'layoutmobile', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];
		$layouts_projected_fields[] = ['name' => 'layoutcss', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];
		$layouts_projected_fields[] = ['name' => 'layoutjs', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];
		$layouts_projected_fields[] = ['name' => 'changetimestamp', 'ct_fieldtype' => '', 'mysql_type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$layouts_projected_fields[] = ['name' => 'params', 'ct_fieldtype' => 'text', 'mysql_type' => 'MEDIUMTEXT NULL DEFAULT NULL', 'postgresql_type' => 'TEXT NULL DEFAULT NULL'];

		$layouts_projected_fields[] = $fieldTypes['created_by'];
		$layouts_projected_fields[] = $fieldTypes['modified_by'];
		$layouts_projected_fields[] = $fieldTypes['created'];
		$layouts_projected_fields[] = $fieldTypes['modified'];
		$layouts_projected_fields[] = $fieldTypes['checked_out'];
		$layouts_projected_fields[] = $fieldTypes['checked_out_time'];

		$layouts_projected_indexes = [];
		$layouts_projected_indexes[] = ['name' => 'idx_published', 'field' => 'published'];
		$layouts_projected_indexes[] = ['name' => 'idx_tableid', 'field' => 'tableid'];
		$layouts_projected_indexes[] = ['name' => 'idx_layoutname', 'field' => 'layoutname'];

		return (object)['realtablename' => $dbPrefix . 'customtables_layouts',
			'tablename' => 'layouts',
			'fields' => $layouts_projected_fields,
			'indexes' => $layouts_projected_indexes,
			'comment' => 'Custom Tables Layouts'];
	}

	protected static function getCoreTableFields_Log(array $fieldTypes): object
	{
		$dbPrefix = database::getDBPrefix();
		$log_projected_fields = array();

		$log_projected_fields[] = $fieldTypes['id'];
		$log_projected_fields[] = ['name' => 'userid', 'ct_fieldtype' => 'userid', 'mysql_type' => 'INT UNSIGNED NULL DEFAULT NULL', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$log_projected_fields[] = ['name' => 'datetime', 'ct_fieldtype' => 'changetime', 'mysql_type' => 'DATETIME NULL DEFAULT NULL', 'postgresql_type' => 'TIMESTAMP(0) NULL DEFAULT NULL'];
		$log_projected_fields[] = ['name' => 'tableid', 'ct_fieldtype' => 'ordering', 'mysql_type' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$log_projected_fields[] = ['name' => 'action', 'ct_fieldtype' => 'int', 'mysql_type' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 0'];
		$log_projected_fields[] = ['name' => 'listingid', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'INT UNSIGNED NULL DEFAULT NULL', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];
		$log_projected_fields[] = ['name' => 'Itemid', 'ct_fieldtype' => 'sqljoin', 'mysql_type' => 'INT UNSIGNED NULL DEFAULT NULL', 'postgresql_type' => 'INT NOT NULL DEFAULT 0'];

		$log_projected_indexes = [];
		$log_projected_indexes[] = ['name' => 'idx_userid', 'field' => 'userid'];
		$log_projected_indexes[] = ['name' => 'idx_action', 'field' => 'action'];

		return (object)['realtablename' => $dbPrefix . 'customtables_log',
			'tablename' => 'log',
			'fields' => $log_projected_fields,
			'indexes' => $log_projected_indexes,
			'comment' => 'Custom Tables Action Log'];
	}

	protected static function getCoreTableFields_Categories(array $fieldTypes): object
	{
		$dbPrefix = database::getDBPrefix();
		$categories_projected_fields = array();

		$categories_projected_fields[] = $fieldTypes['id'];
		$categories_projected_fields[] = $fieldTypes['published'];
		$categories_projected_fields[] = ['name' => 'categoryname', 'ct_fieldtype' => 'string', 'ct_typeparams' => 100, 'mysql_type' => 'VARCHAR(100) NOT NULL DEFAULT "tablename"', 'postgresql_type' => 'VARCHAR(100) NOT NULL DEFAULT \'\''];

		$categories_projected_fields[] = $fieldTypes['created_by'];
		$categories_projected_fields[] = $fieldTypes['modified_by'];
		$categories_projected_fields[] = $fieldTypes['created'];
		$categories_projected_fields[] = $fieldTypes['modified'];
		$categories_projected_fields[] = $fieldTypes['checked_out'];
		$categories_projected_fields[] = $fieldTypes['checked_out_time'];

		$categories_projected_fields[] = ['name' => 'admin_menu', 'ct_fieldtype' => 'checkbox', 'mysql_type' => 'TINYINT NOT NULL DEFAULT 0', 'postgresql_type' => 'SMALLINT NOT NULL DEFAULT 0'];

		$categories_projected_indexes = [];
		$categories_projected_indexes[] = ['name' => 'idx_published', 'field' => 'published'];
		$categories_projected_indexes[] = ['name' => 'idx_categoryname', 'field' => 'categoryname'];

		return (object)['realtablename' => $dbPrefix . 'customtables_categories',
			'tablename' => 'categories',
			'fields' => $categories_projected_fields,
			'indexes' => $categories_projected_indexes,
			'comment' => 'Custom Tables Categories'];
	}

	public static function addMultilingualTablesFields($LanguageList): void
	{
		$moreThanOneLanguage = false;
		$fields = Fields::getListOfExistingFields('#__customtables_tables', false);
		foreach ($LanguageList as $lang) {
			$id_title = 'tabletitle';
			$id_desc = 'description';
			if ($moreThanOneLanguage) {
				$id_title .= '_' . $lang->sef;
				$id_desc .= '_' . $lang->sef;
			}

			try {
				if (!in_array($id_title, $fields))
					Fields::addLanguageField('#__customtables_tables', $id_title, $id_title, 'null');

				if (!in_array($id_desc, $fields))
					Fields::addLanguageField('#__customtables_tables', $id_desc, $id_desc, 'null');
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			$moreThanOneLanguage = true; //More than one language installed
		}
	}
}
