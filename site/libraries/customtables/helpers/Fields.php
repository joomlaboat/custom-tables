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

use CustomTablesFileMethods;
use CustomTablesImageMethods;
use Exception;

class Fields
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function isFieldNullable(string $realtablename, string $realFieldName): bool
	{
		$realtablename = database::realTableName($realtablename);
		$serverType = database::getServerType();
		$whereClause = new MySQLWhereClause();

		if ($serverType == 'postgresql') {

			$selects = [
				'column_name',
				'data_type',
				'is_nullable',
				'column_default'
			];

			$whereClause->addCondition('table_name', $realtablename);
			$whereClause->addCondition('column_name', $realFieldName);

			$rows = database::loadAssocList('information_schema.columns', $selects, $whereClause, null, null, 1);
		} else {

			$database = database::getDataBaseName();

			$selects = [
				'COLUMN_NAME AS column_name',
				'COLUMN_TYPE AS column_type',
				'COLUMN_IS_UNSIGNED',
				'IS_NULLABLE AS is_nullable',
				'COLUMN_DEFAULT AS column_default',
				'EXTRA AS extra'
			];

			$whereClause->addCondition('TABLE_SCHEMA', $database);
			$whereClause->addCondition('TABLE_NAME', $realtablename);
			$whereClause->addCondition('column_name', $realFieldName);

			$rows = database::loadAssocList('information_schema.COLUMNS', $selects, $whereClause, null, null, 1);
		}
		$row = $rows[0];
		return $row['is_nullable'] == 'YES';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function deleteField_byID(CT $ct, int $fieldId): bool
	{
		if ($ct->Table === null) {
			die('deleteField_byID: Table not selected.');
		}

		$ImageFolder = CUSTOMTABLES_IMAGES_PATH;
		$fieldRow = $ct->Table->getFieldById($fieldId);

		if (is_null($fieldRow))
			return false;

		$field = new Field($ct, $fieldRow);
		$tableRow = $ct->Table->tablerow;

		if ($field->type !== null) {
			//for Image Gallery
			if ($field->type == 'imagegallery') {
				//Delete all photos belongs to the gallery

				$imageMethods = new CustomTablesImageMethods;
				$imageMethods->DeleteGalleryImages($tableRow['tablename'] . '_' . $field->fieldname, $field->fieldrow['tableid']
					, $field->fieldname, $field->params, true);

				//Delete gallery table
				database::dropTableIfExists($tableRow['tablename'] . '_' . $field->fieldname, 'gallery');
			} elseif ($field->type == 'filebox') {
				//Delete all files belongs to the filebox

				$fileBoxTableName = '#__customtables_filebox_' . $tableRow['tablename'] . '_' . $field->fieldname;
				CustomTablesFileMethods::DeleteFileBoxFiles($fileBoxTableName, (string)$field->fieldrow['tableid'], $field->fieldname, $field->params);

				//Delete gallery table
				database::dropTableIfExists($tableRow['tablename'] . '_' . $field->fieldname, 'filebox');

			} elseif ($field->type == 'image') {
				if (Fields::checkIfFieldExists($tableRow['realtablename'], $field->realfieldname)) {
					if (defined('_JEXEC')) {
						$imageMethods = new CustomTablesImageMethods;

						$fileNameType = $params[3] ?? '';

						$imageMethods->DeleteCustomImages($tableRow['realtablename'], $field->realfieldname, $ImageFolder, $field->params[0],
							$tableRow['realidfieldname'], true, $fileNameType);
					}
				}
			} elseif ($field->type == 'user' or $field->type == 'userid' or $field->type == 'sqljoin') {
				Fields::removeForeignKey($tableRow['realtablename'], $field->realfieldname);
			} elseif ($field->type == 'file') {
				// TODO: delete all files
				//if(file_exists($filename))
				//unlink($filename);
			}
		}

		$realFieldNames = array();

		if (!str_contains($field->type, 'multilang')) {
			$realFieldNames[] = $field->realfieldname;
		} else {
			$index = 0;
			foreach ($ct->Languages->LanguageList as $lang) {
				if ($index == 0)
					$postfix = '';
				else
					$postfix = '_' . $lang->sef;

				$realFieldNames[] = $field->realfieldname . $postfix;
				$index += 1;
			}
		}

		foreach ($realFieldNames as $realfieldname) {
			if ($field->type != 'dummy' and !Fields::isVirtualField($fieldRow)) {
				$msg = '';
				Fields::deleteMYSQLField($tableRow['realtablename'], $realfieldname, $msg);
			}
		}

		//Delete field from the list
		database::deleteRecord('#__customtables_fields', 'id', $fieldId);
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function checkIfFieldExists($realtablename, $realfieldname): bool
	{
		// Get the list of existing fields
		$realFieldNames = Fields::getListOfExistingFields($realtablename, false);

		// Convert all field names to lowercase
		$realFieldNamesLower = array_map('strtolower', $realFieldNames);

		// Check if the lowercase field name exists in the lowercase array
		return in_array(strtolower($realfieldname), $realFieldNamesLower);
		//TODO:  case sensitive field names renaming doesnt work, fix it.
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getListOfExistingFields($tablename, $add_table_prefix = true): array
	{
		$realFieldNames = database::getExistingFields($tablename, $add_table_prefix);
		$list = [];

		foreach ($realFieldNames as $rec)
			$list[] = $rec['column_name'];

		return $list;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function removeForeignKey($realtablename, $realfieldname): bool
	{
		$constrances = Fields::getTableConstrances($realtablename, $realfieldname);

		if (!is_null($constrances)) {
			foreach ($constrances as $constrance) {
				Fields::removeForeignKey($realtablename, $constrance);
			}
			return true;
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function getTableConstrances($realtablename, $realfieldname): ?array
	{
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			return null;

		//get constrant name
		$tableCreateQuery = database::showCreateTable($realtablename);//::loadAssocList($query, ['', '', '', ''], $whereClause, null, null);

		if (count($tableCreateQuery) == 0)
			return null;

		$rec = $tableCreateQuery[0];
		$constrances = array();
		$q = $rec['Create Table'];
		$lines = explode(',', $q);

		foreach ($lines as $line_) {
			$line = trim(str_replace('`', '', $line_));
			if (str_contains($line, 'CONSTRAINT')) {
				$pair = explode(' ', $line);

				if ($realfieldname == '')
					$constrances[] = $pair;
				elseif ($pair[4] == '(' . $realfieldname . ')')
					$constrances[] = $pair[1];
			}
		}
		return $constrances;
	}

	public static function isVirtualField(array $fieldRow): bool
	{
		$isrequired = (int)$fieldRow['isrequired'];

		if ($fieldRow['type'] == 'virtual') {
			$paramsList = CTMiscHelper::csv_explode(',', $fieldRow['typeparams']);
			return ($paramsList[1] ?? 'virtual') == 'virtual' or '';
		} else
			return $isrequired == 2 or $isrequired == 3;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function deleteMYSQLField($realtablename, $realfieldname, &$msg): bool
	{
		if (Fields::checkIfFieldExists($realtablename, $realfieldname)) {
			try {
				database::dropColumn($realtablename, $realfieldname);
				return true;
			} catch (Exception $e) {
				$msg = '<p style="color:#ff0000;">Caught exception: ' . $e->getMessage() . '</p>';
				return false;
			}
		}
		return false;
	}

	public static function convertMySQLFieldTypeToCT(string $column_type): array
	{
		//column type from information_schema.columns
		//example:  decimal(20,7)
		$type = null;
		$typeParams = null;

		$parts = explode('(', $column_type);

		$data_type = explode(' ', $parts[0])[0];
		$options = null;

		if (isset($parts[1])) {
			$parts2 = explode(')', $parts[1]);

			if (!empty($parts2[0]))
				$options = $parts2[0];
		}

		switch (strtolower(trim($data_type))) {
			case 'bit':
			case 'tinyint':
			case 'int':
			case 'integer':
			case 'smallint':
			case 'mediumint':
			case 'bigint':
				$type = 'int';
				break;

			case 'dec':
			case 'decimal':
			case 'float':
			case 'double':
				if ($options !== null)
					$typeParams = $options;

				$type = 'float';
				break;

			case 'char':
			case 'varchar':
				if ($options !== null)
					$typeParams = $options;

				$type = 'string';
				break;

			case 'tynyblob':
			case 'blob':
			case 'mediumblob':
			case 'longblob':
				$type = 'blob';
				break;

			case 'text':
			case 'mediumtext':
			case 'longtext':
				$type = 'text';
				break;

			case 'datetime':
				return ['type' => 'date', 'typeparams' => 'datetime'];

			case 'date':
				return ['type' => 'date'];
		}
		return ['type' => $type, 'typeparams' => $typeParams];
	}

	public static function isLanguageFieldName($fieldname): bool
	{
		$parts = explode('_', $fieldname);
		if ($parts[0] == 'es') {
			//custom field
			if (count($parts) == 3)
				return true;
			else
				return false;
		}

		if (count($parts) == 2)
			return true;
		else
			return false;
	}

	public static function getLanguageLessFieldName($fieldname): string
	{
		$parts = explode('_', $fieldname);
		if ($parts[0] == 'es') {
			//custom field
			if (count($parts) == 3)
				return $parts[0] . '_' . $parts[1];
			else
				return '';
		}

		if (count($parts) == 2)
			return $parts[0];
		else
			return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function fixMYSQLField(string $realtablename, string $fieldname, array $PureFieldType, string &$msg, string $title): bool
	{
		if ($fieldname == 'id') {
			try {
				$constrances = Fields::getTableConstrances($realtablename, '');
			} catch (Exception $e) {
				$msg = 'Caught exception fixMYSQLField->Fields::getTableConstrances: ' . $e->getMessage();
				return false;
			}

			//Delete same table child-parent constrances
			if (!is_null($constrances)) {
				foreach ($constrances as $constrance) {
					if ($constrance[7] == '(id)')
						Fields::removeForeignKeyConstrance($realtablename, $constrance[1]);
				}
			}

			try {
				database::changeColumn($realtablename, 'id', 'id', $PureFieldType, 'Primary Key');
			} catch (Exception $e) {
				$msg = 'Caught exception fixMYSQLField 1: ' . $e->getMessage();
			}

			$msg = '';
			return true;
		} elseif ($fieldname == 'published') {
			try {
				database::changeColumn($realtablename, 'published', 'published', $PureFieldType, 'Publish Status');
			} catch (Exception $e) {
				$msg = 'Caught exception fixMYSQLField 2: ' . $e->getMessage();
			}
		} else {
			database::changeColumn($realtablename, $fieldname, $fieldname, $PureFieldType, $title);
		}

		$msg = '';
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function removeForeignKeyConstrance($realtablename, $constrance): void
	{
		try {
			database::dropForeignKey($realtablename, $constrance);
		} catch (Exception $e) {
			common::enqueueMessage($e->getMessage());
		}
	}

	public static function shortFieldObjects($fields): array
	{
		$field_objects = [];

		foreach ($fields as $fieldRow)
			$field_objects[] = Fields::shortFieldObject($fieldRow, null, []);

		return $field_objects;
	}

	public static function shortFieldObject(array $fieldRow, $value, $options): array
	{
		$field = [];
		$field['fieldname'] = $fieldRow['fieldname'];
		$field['title'] = $fieldRow['fieldtitle'];
		$field['defaultvalue'] = $fieldRow['defaultvalue'];
		$field['description'] = $fieldRow['description'];
		$field['isrequired'] = $fieldRow['isrequired'];
		$field['isdisabled'] = $fieldRow['isdisabled'];
		$field['type'] = $fieldRow['type'];

		$typeParams = CTMiscHelper::csv_explode(',', $fieldRow['typeparams']);
		$field['typeparams'] = $typeParams;
		$field['valuerule'] = $fieldRow['valuerule'];
		$field['valuerulecaption'] = $fieldRow['valuerulecaption'];

		$field['value'] = $value;

		if (count($options) == 1 and $options[0] == '')
			$field['options'] = null;
		else
			$field['options'] = $options;

		return $field;
	}

	/**
	 * @throws Exception
	 * @since 3.1.8
	 */
	public static function saveField(?int $tableId, ?int $fieldId, ?array $data = null): ?int
	{
		if ($fieldId == 0)
			$fieldId = null; // new field

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'utilities' . DIRECTORY_SEPARATOR . 'importtables.php');

		$ct = new CT([], true);
		$ct->getTable($tableId);
		if ($ct->Table === null) {
			common::enqueueMessage('Table not found');
			return null;
		}

		if ($data === null) {
			if (defined('_JEXEC')) {
				$data = common::inputGet('jform', array(), 'ARRAY');
			} else {
				$data = [];
				$data['tableid'] = $tableId;

				$moreThanOneLang = false;
				foreach ($ct->Languages->LanguageList as $lang) {
					$id_title = 'fieldtitle';
					$id_description = 'description';

					if ($moreThanOneLang) {
						$id_title .= '_' . $lang->sef;
						$id_description .= '_' . $lang->sef;
					}
					$data[$id_title] = common::inputPostString($id_title, null, 'create-edit-field');
					$data[$id_description] = common::inputPostString($id_description, null, 'create-edit-field');
					$moreThanOneLang = true; //More than one language installed
				}

				$data['type'] = common::inputPostCmd('type', null, 'create-edit-field');

				$typeParams = common::inputPostString('typeparams', null, 'create-edit-field');
				$data['typeparams'] = $typeParams !== null ? str_replace('\"', '"', $typeParams) : null;
				$data['isrequired'] = common::inputPostInt('isrequired', 0, 'create-edit-field');
				$data['defaultvalue'] = common::inputPostString('defaultvalue', null, 'create-edit-field');
				$data['valuerule'] = common::inputPostString('valuerule', null, 'create-edit-field');
				$data['valuerulecaption'] = common::inputPostString('valuerulecaption', null, 'create-edit-field');
				$data['fieldname'] = common::inputPostString('fieldname', null, 'create-edit-field');
			}
		}
		$task = common::inputPostCmd('task', null, 'create-edit-field');

		// Process field name
		if (function_exists("transliterator_transliterate"))
			$newFieldName = transliterator_transliterate("Any-Latin; Latin-ASCII;", $data['fieldname']);
		else
			$newFieldName = $data['fieldname'];

		$newFieldName = trim(preg_replace("/\W/", "", $newFieldName));

		//Shorten the Field Name
		if (strlen($newFieldName) > 40)
			$newFieldName = substr($newFieldName, 0, 40);

		$data['fieldname'] = $newFieldName;

		if ($fieldId !== null and $task == 'save2copy') {
			//Checkout
			try {
				$update_data = ['checked_out' => 0, 'checked_out_time' => null];
				$whereClauseUpdate = new MySQLWhereClause();
				$whereClauseUpdate->addCondition('id', $fieldId);

				database::update('#__customtables_fields', $update_data, $whereClauseUpdate);
			} catch (Exception $e) {
				throw new Exception('Update field checkout problem: ' . $e->getMessage());
			}
			$fieldId = null; //To save the field as new
		}

		if ($fieldId === null) {

			$already_exists = $ct->Table->getFieldByName($newFieldName);
			if ($already_exists === null) {
				$data['fieldname'] = $newFieldName;
			} else {
				return null; //Abort if the table with this name already exists.
			}
		}

		$data['checked_out'] = 0;
		$data['checked_out_time'] = NULL;

		//Add language fields to the fields' table if necessary
		$moreThanOneLang = false;
		$fields = Fields::getListOfExistingFields('#__customtables_fields', false);
		foreach ($ct->Languages->LanguageList as $lang) {
			$id_title = 'fieldtitle';
			$id_description = 'description';

			if ($moreThanOneLang) {
				$id_title .= '_' . $lang->sef;
				$id_description .= '_' . $lang->sef;

				if (!in_array($id_title, $fields))
					Fields::addLanguageField('#__customtables_fields', 'fieldtitle', $id_title);

				if (!in_array($id_description, $fields))
					Fields::addLanguageField('#__customtables_fields', 'description', $id_description);
			}
			$moreThanOneLang = true; //More than one language installed
		}

		//if ($table_row['customtablename'] == $table_row['tablename']) {
		//do not create fields to third-party tables
		//Third-party table but managed by the Custom Tables
		//$data['customfieldname'] = $newFieldName;
		//}

		if ($fieldId !== null) {

			try {

				$whereClauseUpdate = new MySQLWhereClause();
				$whereClauseUpdate->addCondition('id', $fieldId);

				database::update('#__customtables_fields', $data, $whereClauseUpdate);
			} catch (Exception $e) {
				throw new Exception('Add field details: ' . $e->getMessage());
			}
		} else {
			$data['ordering'] = self::getMaxOrdering($tableId) + 1;

			try {
				$fieldId = database::insert('#__customtables_fields', $data);
			} catch (Exception $e) {
				throw new Exception('Add field details: ' . $e->getMessage());
			}

			$ct->getTable($tableId);//reload table to include new field

		}

		if (!self::update_physical_field($ct, $fieldId, $data)) {
			//Cannot create
			return null;
		}

		self::findAndFixFieldOrdering();

		if ($data['type'] == 'ordering')
			self::findAndFixOrderingFieldRecords($ct, $data['realfieldname']);

		return $fieldId;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function addLanguageField($tablename, $original_fieldname, $new_fieldname): bool
	{
		$fields = database::getExistingFields($tablename, false);
		foreach ($fields as $field) {
			if ($field['column_name'] == $original_fieldname) {
				$AdditionOptions = '';
				if ($field['is_nullable'] != 'NO')
					$AdditionOptions = 'null';

				Fields::AddMySQLFieldNotExist($tablename, $new_fieldname, $field['column_type'], $AdditionOptions);
				return true;
			}
		}

		//TODO: check it
		//if ($original_fieldname == $new_fieldname) {
		//	Fields::AddMySQLFieldNotExist($tablename, $new_fieldname, $field['column_type'], $AdditionOptions);
		//	return true;
		//}

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.1.8
	 */
	public static function AddMySQLFieldNotExist(string $realtablename, string $realfieldname, string $fieldType, string $options): void
	{
		if ($realfieldname == '')
			throw new Exception('Add New Field: Field name cannot be empty.');

		if (!Fields::checkIfFieldExists($realtablename, $realfieldname)) {

			try {
				database::addColumn($realtablename, $realfieldname, $fieldType, null, $options);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function getMaxOrdering($tableid): int
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addCondition('tableid', (int)$tableid);

		$rows = database::loadObjectList('#__customtables_fields', [['MAX', '#__customtables_fields', 'ordering']], $whereClause, null, null, 1);
		return (int)$rows[0]->vlu;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function update_physical_field(CT $ct, int $fieldId, array $data): bool
	{
		$realtablename = $ct->Table->realtablename;

		if ($fieldId != 0) {
			$fieldRow = $ct->Table->getFieldById($fieldId);
			$ex_type = $fieldRow['type'];
			$ex_typeparams = $fieldRow['typeparams'];
			$realfieldname = $fieldRow['realfieldname'];
		} else {
			$ex_type = '';
			$ex_typeparams = '';
			$realfieldname = '';

			if (empty($ct->Table->tablerow['customtablename']))//Just to be safe
				$realfieldname = $ct->Table->fieldPrefix . $data['fieldname'];
			elseif ($ct->Table->tablerow['customtablename'] == $ct->Table->tablerow['tablename'])
				$realfieldname = $data['fieldname'];
		}

		if ($realfieldname === '')
			throw new Exception('Add New Field: Field name cannot be empty.');

		$new_typeparams = $data['typeparams'];
		$fieldTitle = $data['fieldtitle'];

		//---------------------------------- Convert Field

		$new_type = $data['type'];
		if ($new_type === null)
			return false;

		$PureFieldType = null;
		if ($new_typeparams !== null)
			$PureFieldType = Fields::getPureFieldType($new_type, $new_typeparams);

		if ($realfieldname != '')
			$fieldFound = Fields::checkIfFieldExists($realtablename, $realfieldname);
		else
			$fieldFound = false;

		if ($fieldId != 0 and $fieldFound) {

			if ($PureFieldType !== null) {
				try {
					if (!Fields::ConvertFieldType($realtablename, $realfieldname, $ex_type, $new_type, $ex_typeparams, $new_typeparams, $PureFieldType, $fieldTitle)) {
						$ct->errors[] = 'Field cannot be converted to new type.';
						return false;
					}
				} catch (Exception $e) {
					$ct->errors[] = 'Cannot convert the type: ' . $e->getMessage();
					return false;
				}
			}

			if ($ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers'))
				ctProHelpers::update_physical_field_set_extra_tasks($ex_type, $new_type, $ex_typeparams, $new_typeparams, $fieldId);
		}
		//---------------------------------- end convert field

		if ($fieldId == 0 or !$fieldFound) {
			//Add Field
			Fields::addField($ct, $realtablename, $realfieldname, $PureFieldType, $fieldTitle, $data);
		}

		if ($new_type == 'sqljoin') {
			//Create Index if needed
			Fields::addIndexIfNotExist($realtablename, $realfieldname);

			//Add Foreign Key
			$msg = '';
			Fields::addForeignKey($realtablename, $realfieldname, $new_typeparams, '', 'id', $msg);
		}

		if ($new_type == 'user' or $new_type == 'userid') {
			//Create Index if needed
			Fields::addIndexIfNotExist($realtablename, $realfieldname);

			//Add Foreign Key
			$msg = '';
			Fields::addForeignKey($realtablename, $realfieldname, '', '#__users', 'id', $msg);
		}
		return true;
	}

	public static function getPureFieldType(string $ct_fieldType, string $typeParams): array
	{
		$ct_fieldTypeArray = Fields::getProjectedFieldType($ct_fieldType, $typeParams);
		return Fields::makeProjectedFieldType($ct_fieldTypeArray);
	}

	public static function getProjectedFieldType(string $ct_fieldType, ?string $typeParams): ?array
	{
		//Returns an array of mysql column parameters
		if ($typeParams !== null)
			$typeParamsArray = CTMiscHelper::csv_explode(',', $typeParams);
		else
			$typeParamsArray = null;

		switch (trim($ct_fieldType)) {
			case '_id':
				return ['data_type' => 'int', 'is_nullable' => false, 'is_unsigned' => true, 'length' => null, 'default' => null, 'autoincrement' => true];

			case '_published':
				return ['data_type' => 'tinyint', 'is_nullable' => false, 'is_unsigned' => false, 'length' => null, 'default' => 1];

			case 'filelink':
			case 'file':
			case 'alias':
			case 'usergroups':
			case 'url':
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 1024, 'default' => null];
			case 'color':
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 8, 'default' => null];
			case 'string':
			case 'multilangstring':
				$l = (int)$typeParams;
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => ($l < 1 ? 255 : (min($l, 1024))), 'default' => null];
			case 'signature':

				$format = $typeParamsArray[3] ?? 'svg';

				if ($format == 'svg-db')
					return ['data_type' => 'text', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null];
				else
					return ['data_type' => 'bigint', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null];

			case 'blob':

				if ($typeParamsArray[0] == 'tiny')
					$type = 'tinyblob';
				elseif ($typeParamsArray[0] == 'medium')
					$type = 'mediumblob';
				elseif ($typeParamsArray[0] == 'long')
					$type = 'longblob';
				else
					$type = 'blob';

				return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null];

			case 'text':
			case 'multilangtext':

				$type = 'text';
				if (isset($typeParamsArray[2])) {
					if ($typeParamsArray[2] == 'tiny')
						$type = 'tinytext';
					elseif ($typeParamsArray[2] == 'medium')
						$type = 'mediumtext';
					elseif ($typeParamsArray[2] == 'long')
						$type = 'longtext';
				}

				return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null];

			case 'log':
				//mediumtext
				return ['data_type' => 'text', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null];
			case 'ordering':
				return ['data_type' => 'int', 'is_nullable' => false, 'is_unsigned' => true, 'length' => null, 'default' => 0];
			case 'time':
			case 'int':
				return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null];
			case 'float':

				if (count($typeParamsArray) == 1)
					$l = '20,' . (int)$typeParamsArray[0];
				elseif (count($typeParamsArray) == 2)
					$l = (int)$typeParamsArray[1] . ',' . (int)$typeParamsArray[0];
				else
					$l = '20,2';
				return ['data_type' => 'decimal', 'is_nullable' => true, 'is_unsigned' => false, 'length' => $l, 'default' => null];


			case 'userid':
			case 'user':
			case 'sqljoin':
			case 'article':
				return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null];

			case 'usergroup':
				if (defined('_JEXEC'))
					return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null];
				elseif (defined('WPINC'))
					return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 1024, 'default' => null];
				else
					return null;// not supported

			case 'image':
				$fileNameType = $typeParamsArray[3] ?? '';
				$length = null;

				if ($fileNameType == '') {
					$type = 'bigint';
				} else {
					$type = 'varchar';
					$length = 1024;
				}

				return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => false, 'length' => $length, 'default' => null];

			case 'checkbox':
				return ['data_type' => 'tinyint', 'is_nullable' => false, 'is_unsigned' => false, 'length' => null, 'default' => 0];

			case 'date':
				if ($typeParamsArray !== null and $typeParamsArray[0] == 'datetime')
					return ['data_type' => 'datetime', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null];
				else
					return ['data_type' => 'date', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null];

			case 'creationtime':
			case 'changetime':
			case 'lastviewtime':
				return ['data_type' => 'datetime', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null];

			case 'viewcount':
			case 'imagegallery':
			case 'id':
			case 'filebox':
				return ['data_type' => 'bigint', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null];

			case 'language':
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 5, 'default' => null];

			case 'dummy':
				return ['data_type' => null, 'is_nullable' => null, 'is_unsigned' => null, 'length' => null, 'default' => null];

			case 'virtual':
				$storage = $typeParamsArray[1] ?? '';

				if ($storage == 'storedstring') {
					$l = (int)$typeParamsArray[2] ?? 255;
					return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => ($l < 1 ? 255 : (min($l, 4069))), 'default' => null];
				} elseif ($storage == 'storedintegersigned')
					return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null];
				elseif ($storage == 'storedintegerunsigned')
					return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null];
				else
					return ['data_type' => null, 'is_nullable' => null, 'is_unsigned' => null, 'length' => null, 'default' => null];

			case 'md5':
				return ['data_type' => 'char', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 32, 'default' => null];

			case 'phponadd':
			case 'phponchange':
			case 'phponview':
				if (isset($typeParamsArray[1]) and $typeParamsArray[1] == 'dynamic')
					return ['data_type' => null, 'is_nullable' => null, 'is_unsigned' => null, 'length' => null, 'default' => null]; //do not store field values
				else
					return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 255, 'default' => null];

			default:
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 255, 'default' => null];
		}
	}

	public static function makeProjectedFieldType(array $ct_fieldTypeArray): array
	{
		$type = (object)$ct_fieldTypeArray;
		$elements = [];
		$elements['is_nullable'] = true;
		$elements['default'] = null;
		$elements['autoincrement'] = $ct_fieldTypeArray['autoincrement'] ?? false;

		switch ($ct_fieldTypeArray['data_type']) {
			case 'varchar':
				$elements['data_type'] = 'varchar';
				$elements['length'] = $type->length;
				break;

			case 'tinytext':
				$elements['data_type'] = 'tinytext';
				break;

			case 'text':
				$elements['data_type'] = 'text';
				break;

			case 'mediumtext':
				$elements['data_type'] = 'mediumtext';
				break;

			case 'longtext':
				$elements['data_type'] = 'longtext';
				break;

			case 'tinyblob':
				$elements['data_type'] = 'tinyblob';
				break;

			case 'blob':
				$elements['data_type'] = 'blob';
				break;

			case 'mediumblob':
				$elements['data_type'] = 'mediumblob';
				break;

			case 'longblob':
				$elements['data_type'] = 'longblob';
				break;

			case 'char':
				$elements['data_type'] = 'char';
				$elements['length'] = $type->length;
				break;

			case 'int':
				$elements['data_type'] = 'int';
				$serverType = database::getServerType();
				if ($serverType != 'postgresql') {
					$elements['is_unsigned'] = $type->is_unsigned;
				}
				break;

			case 'bigint':
				$elements['data_type'] = 'bigint';
				$serverType = database::getServerType();
				if ($serverType != 'postgresql') {
					$elements['is_unsigned'] = $type->is_unsigned;
				}
				break;

			case 'decimal':
				$serverType = database::getServerType();
				if ($serverType == 'postgresql')
					$elements['data_type'] = 'numeric';
				else
					$elements['data_type'] = 'decimal';

				$elements['length'] = $type->length;

				break;

			case 'tinyint':
				$serverType = database::getServerType();
				if ($serverType == 'postgresql')
					$elements['data_type'] = 'smallint';
				else
					$elements['data_type'] = 'tinyint';

				break;

			case 'date':
				$elements['data_type'] = 'date';
				break;

			case 'datetime':
				$serverType = database::getServerType();
				if ($serverType == 'postgresql')
					$elements['data_type'] = 'TIMESTAMP';
				else
					$elements['data_type'] = 'datetime';

				break;

			default:
				return [];
		}

		if (is_string($type->is_nullable))
			$elements['is_nullable'] = $type->is_nullable == 'YES';
		else
			$elements['is_nullable'] = $type->is_nullable;

		if (isset($type->default))
			$elements['default'] = $type->default;

		if (isset($type->column_default))
			$elements['default'] = $type->column_default;

		return $elements;
	}

	/**
	 * @throws Exception
	 * @since 3.1.8
	 */
	public static function ConvertFieldType($realtablename, $realfieldname, $ex_type, $new_type, $ex_typeparams, $new_typeparams, $PureFieldType, $fieldtitle): bool
	{
		if ($new_type == 'blob' or $new_type == 'text' or $new_type == 'multilangtext' or $new_type == 'image') {
			if ($new_typeparams == $ex_typeparams)
				return true; //no need to convert
		} else {
			if ($new_type == $ex_type)
				return true; //no need to convert
		}

		$inconvertible_types = array('dummy', 'virtual', 'imagegallery', 'file', 'filebox', 'signature', 'records', 'log');

		if (in_array($new_type, $inconvertible_types) or in_array($ex_type, $inconvertible_types))
			return false;

		try {
			database::changeColumn($realtablename, $realfieldname, $realfieldname, $PureFieldType, $fieldtitle);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function addField(CT $ct, string $realtablename, string $realfieldname, array $PureFieldType, string $fieldTitle, array $fieldRow): void
	{
		if (count($PureFieldType) == 0)
			return;

		if (!str_contains($PureFieldType['data_type'] ?? '', 'multilang')) {
			$AdditionOptions = '';
			$serverType = database::getServerType();
			if ($serverType != 'postgresql')
				$AdditionOptions = ' COMMENT ' . database::quote($fieldTitle);

			if ($PureFieldType['data_type'] != 'dummy' and !Fields::isVirtualField($fieldRow)) {
				$fieldTypeString = fields::projectedFieldTypeToString($PureFieldType);
				Fields::AddMySQLFieldNotExist($realtablename, $realfieldname, $fieldTypeString, $AdditionOptions);
			}
		} else {
			$index = 0;
			foreach ($ct->Languages->LanguageList as $lang) {
				if ($index == 0)
					$postfix = '';
				else
					$postfix = '_' . $lang->sef;

				$AdditionOptions = '';
				$serverType = database::getServerType();
				if ($serverType != 'postgresql')
					$AdditionOptions = ' COMMENT ' . database::quote($fieldTitle);

				$fieldTypeString = fields::projectedFieldTypeToString($PureFieldType);
				Fields::AddMySQLFieldNotExist($realtablename, $realfieldname . $postfix, $fieldTypeString, $AdditionOptions);

				$index++;
			}
		}

		if ($PureFieldType['data_type'] == 'imagegallery') {
			//Create table
			//get CT table name if possible

			$tableName = str_replace(database::getDBPrefix() . 'customtables_table', '', $realtablename);
			$fieldName = str_replace($ct->Table->fieldPrefix, '', $realfieldname);
			Fields::CreateImageGalleryTable($tableName, $fieldName);
		} elseif ($PureFieldType['data_type'] == 'filebox') {
			//Create table
			//get CT table name if possible
			$tableName = str_replace(database::getDBPrefix() . 'customtables_table', '', $realtablename);
			$fieldName = str_replace($ct->Table->fieldPrefix, '', $realfieldname);
			Fields::CreateFileBoxTable($tableName, $fieldName);
		}
	}

	public static function projectedFieldTypeToString(array $PureFieldType): string
	{
		if (key_exists('is_nullable', $PureFieldType) and is_string($PureFieldType['is_nullable']))
			$is_nullable = $PureFieldType['is_nullable'] == 'YES';
		else
			$is_nullable = $PureFieldType['is_nullable'] ?? true;

		return $PureFieldType['data_type']
			. (($PureFieldType['length'] ?? null) !== null ? '(' . $PureFieldType['length'] . ')' : '')
			. (($PureFieldType['is_unsigned'] ?? false) ? ' UNSIGNED' : '')
			. ($is_nullable ? ' NULL' : ' NOT NULL')
			. (($PureFieldType['default'] ?? null) !== null ? ' DEFAULT ' . $PureFieldType['default'] : '')
			. (($PureFieldType['autoincrement'] ?? false) ? ' AUTO_INCREMENT' : '');
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function CreateImageGalleryTable($tablename, $fieldname): bool
	{
		$columns = [
			'listingid bigint not null',
			'ordering int not null',
			'photo_ext varchar(10) not null',
			'title varchar(100) null'
		];

		database::createTable('#__customtables_gallery_' . $tablename . '_' . $fieldname, 'photoid',
			$columns, 'Image Gallery', null, 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function CreateFileBoxTable($tablename, $fieldname): bool
	{
		$columns = [
			'listingid bigint not null',
			'ordering int not null',
			'file_ext varchar(10) not null',
			'title varchar(100) null'
		];
		database::createTable('#__customtables_filebox_' . $tablename . '_' . $fieldname, 'fileid', $columns,
			'File Box', null, 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function addIndexIfNotExist($realtablename, $realfieldname): void
	{
		$serverType = database::getServerType();

		if ($serverType == 'postgresql') {
			//Indexes not yet supported
		} else {
			$rows = database::getTableIndex($realtablename, $realfieldname);

			if (count($rows) == 0)
				database::addIndex($realtablename, $realfieldname);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function addForeignKey($realtablename_, $realfieldname, string $new_typeparams, string $join_with_table_name, string $join_with_table_field, &$msg): bool
	{
		$realtablename = database::realTableName($realtablename_);
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			return false;

		//Create Key only if possible
		$typeParams = explode(',', $new_typeparams);

		if ($join_with_table_name == '') {
			if ($new_typeparams == '') {
				$msg = 'Parameters not set.';
				return false; //Exit if parameters not set
			}

			if (count($typeParams) < 2) {
				$msg = 'Parameters not complete.';
				return false;    // Exit if field not set (just in case)
			}

			$tableRow = TableHelper::getTableRowByName($typeParams[0]); //[0] - is tablename
			if (!is_object($tableRow)) {
				$msg = 'Join with table "' . $join_with_table_name . '" not found.';
				return false;    // Exit if table to connect with not found
			}

			$join_with_table_name = $tableRow->realtablename;
			$join_with_table_field = $tableRow->realidfieldname;
		}

		$join_with_table_name = database::realTableName($join_with_table_name);

		Fields::removeForeignKey($realtablename, $realfieldname);

		if (isset($typeParams[7]) and $typeParams[7] == 'addforeignkey') {
			Fields::cleanTableBeforeNormalization($realtablename, $realfieldname, $join_with_table_name, $join_with_table_field);

			try {
				database::addForeignKey($realtablename, $realfieldname, $join_with_table_name, $join_with_table_field);
				return true;
			} catch (Exception $e) {
				$msg = $e->getMessage();
			}
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function cleanTableBeforeNormalization($realtablename, $realfieldname, $join_with_table_name, $join_with_table_field): void
	{
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			return;

		//Find broken records
		$from = $realtablename . ' a LEFT JOIN ' . $join_with_table_name . ' b ON a.' . $realfieldname . '=b.' . $join_with_table_field;

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('b.' . $join_with_table_field, null, 'NULL');
		$rows = database::loadAssocList($from, ['DISTINCT a.' . $realfieldname . ' AS customtables_distinct_temp_id'], $whereClause);

		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addOrCondition($realfieldname, 0);

		foreach ($rows as $row) {
			if ($row['customtables_distinct_temp_id'] != '')
				$whereClauseUpdate->addOrCondition($realfieldname, $row['customtables_distinct_temp_id']);
		}
		database::update($realtablename, [$realfieldname => null], $whereClauseUpdate);
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	protected static function findAndFixFieldOrdering(): void
	{
		$data = [
			'ordering' => ['id', 'sanitized']
		];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addOrCondition('ordering', null, 'NULL');
		$whereClauseUpdate->addOrCondition('ordering', 0);

		try {
			database::update('#__customtables_fields', $data, $whereClauseUpdate);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	protected static function findAndFixOrderingFieldRecords(CT $ct, string $realFieldName): void
	{
		$data = [$realFieldName => [$ct->Table->realidfieldname, 'sanitized']];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addOrCondition($realFieldName, null, 'NULL');
		$whereClauseUpdate->addOrCondition($realFieldName, 0);

		try {
			database::update($ct->Table->realtablename, $data, $whereClauseUpdate);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */

	public static function convertRawFieldType(array $rawDataType): array
	{
		$newData = [];
		$newData['data_type'] = $rawDataType['data_type'];

		if ($rawDataType['data_type'] == 'varchar' or $rawDataType['data_type'] == 'char' or $rawDataType['data_type'] == 'decimal') {

			$newData['length'] = self::parse_column_type($rawDataType['column_type']);
			if ($newData['length'] == '')
				$newData['length'] = null;
		}

		$newData['is_nullable'] = $rawDataType['is_nullable'] == 'YES';
		$newData['is_unsigned'] = $rawDataType['COLUMN_IS_UNSIGNED'] == 'YES';
		$newData['default'] = $rawDataType['column_default'] ?? null;
		$newData['autoincrement'] = ($rawDataType['extra'] ?? '') == 'auto_increment';

		return $newData;
	}

	protected static function parse_column_type(string $parse_column_type_string): string
	{
		$parts = explode('(', $parse_column_type_string);
		if (count($parts) > 1) {
			$length = str_replace(')', '', $parts[1]);
			if ($length != '')
				return $length;
		}
		return '';
	}

	public static function parseFieldTypeFromString(string $fieldType): array
	{
		$newData = [];

		// Convert to uppercase for consistent comparison
		$upperFieldType = strtoupper($fieldType);

		// Extract base data type
		preg_match('/^\s*([A-Za-z]+)/', $fieldType, $matches);
		$newData['data_type'] = strtolower($matches[1] ?? '');

		// Extract length/precision for supported types
		if (in_array($newData['data_type'], ['varchar', 'char', 'decimal', 'int'])) {
			preg_match('/\(([^)]+)\)/', $fieldType, $matches);
			$newData['length'] = $matches[1] ?? null;
		}

		// Check if unsigned
		$newData['is_unsigned'] = str_contains($upperFieldType, 'UNSIGNED');

		// Check if nullable
		$newData['is_nullable'] = !str_contains($upperFieldType, 'NOT NULL');

		// Check if auto increment
		$newData['autoincrement'] = str_contains($upperFieldType, 'AUTO_INCREMENT');

		// Default value (if specified)
		preg_match('/DEFAULT\s+(\S+)/i', $fieldType, $matches);
		$newData['default'] = $matches[1] ?? null;

		// Clean up default value if it's NULL
		if ($newData['default'] !== null) {
			if (strtoupper($newData['default']) === 'NULL') {
				$newData['default'] = null;
			} else {
				// Remove quotes if present
				$newData['default'] = trim($newData['default'], "'\"");
			}
		}

		return $newData;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	/*
	public static function FixCustomTablesRecords($realtablename, $realfieldname, $optionname, $maxlenght): void
	{
		//CustomTables field type
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			return;

		$whereClause = new MySQLWhereClause();

		$fixRows = database::loadObjectList($realtablename, ['id', $realfieldname . ' AS fldvalue'], $whereClause);
		foreach ($fixRows as $fixRow) {

			$newRow = Fields::FixCustomTablesRecord($fixRow->fldvalue, $optionname, $maxlenght);

			if ($fixRow->fldvalue != $newRow) {
				$data = [
					$realfieldname => $newRow
				];
				$whereClauseUpdate = new MySQLWhereClause();
				$whereClauseUpdate->addCondition('id', $fixRow->id);
				database::update($realtablename, $data, $whereClauseUpdate);
			}
		}
	}
	*/

	/*
	public static function FixCustomTablesRecord($record, $optionname, $maxlen): string
	{
		$l = 2;
		$e = explode(',', $record);
		$r = array();

		foreach ($e as $a) {
			$p = explode('.', $a);
			$b = array();

			foreach ($p as $t) {
				if ($t != '')
					$b[] = $t;
			}
			if (count($b) > 0) {
				$d = implode('.', $b);
				if ($d != $optionname)
					$e = implode('.', $b) . '.';

				$l += strlen($e) + 1;
				if ($l >= $maxlen)
					break;

				$r[] = $e;
			}
		}

		if (count($r) > 0)
			$newRow = ',' . implode(',', $r) . ',';
		else
			$newRow = '';

		return $newRow;
	}
*/

	/*
	protected static function getFieldRowSelects(): string
	{
		return implode(',', self::getFieldRowSelectArray());
	}
	*/

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	/*
	protected static function checkFieldName($tableId, $fieldName): string
	{
		$new_fieldname = $fieldName;

		while (1) {
			$already_exists = Fields::getFieldID($tableId, $new_fieldname);

			if ($already_exists != 0) {
				$new_fieldname .= 'copy';
			} else
				break;
		}

		return $new_fieldname;
	}
	*/
}

