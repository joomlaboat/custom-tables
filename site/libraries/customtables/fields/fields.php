<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTablesFileMethods;
use CustomTablesImageMethods;
use Exception;
use JoomlaBasicMisc;
use ESTables;

use Joomla\CMS\Factory;

class Field
{
	var CT $ct;

	var int $id;
	var array $params;
	var ?string $type;
	var int $isrequired;
	var ?string $defaultvalue;

	var string $title;
	var ?string $description;
	var string $fieldname;
	var string $realfieldname;
	var ?string $comesfieldname;
	var ?string $valuerule;
	var ?string $valuerulecaption;

	var array $fieldrow;
	var string $prefix; //part of the table class

	var ?string $layout; //output layout, used in Search Boxes

	function __construct(CT &$ct, array $fieldRow, $row = null, $parseParams = true)
	{
		$this->ct = &$ct;

		if (!array_key_exists('id', $fieldRow)) {
			echo 'Field not found.';
			return;
		}

		$this->id = $fieldRow['id'];
		$this->fieldname = $fieldRow['fieldname'];
		$this->realfieldname = $fieldRow['realfieldname'];

		if ($fieldRow['type'] !== null) {
			$this->type = $fieldRow['type'];
			$this->fieldrow = $fieldRow;
			$this->layout = $fieldRow['layout'] ?? null; //rendering layout

			if (!array_key_exists('fieldtitle' . $ct->Languages->Postfix, $fieldRow)) {
				$this->title = 'fieldtitle' . $ct->Languages->Postfix . ' - not found';
			} else {
				$vlu = $fieldRow['fieldtitle' . $ct->Languages->Postfix];
				if ($vlu == '')
					$this->title = $fieldRow['fieldtitle'];
				else
					$this->title = $vlu;
			}

			if (!array_key_exists('description' . $ct->Languages->Postfix, $fieldRow)) {
				$this->description = 'description' . $ct->Languages->Postfix . ' - not found';
			} else {
				$vlu = $fieldRow['description' . $ct->Languages->Postfix];
				if ($vlu == '')
					$this->description = $fieldRow['description'];
				else
					$this->description = $vlu;
			}

			$this->isrequired = intval($fieldRow['isrequired']);
			$this->defaultvalue = $fieldRow['defaultvalue'];
			$this->valuerule = $fieldRow['valuerule'];
			$this->valuerulecaption = $fieldRow['valuerulecaption'];
			$this->prefix = $this->ct->Env->field_input_prefix;
			$this->comesfieldname = $this->prefix . $this->fieldname;
			$this->params = JoomlaBasicMisc::csv_explode(',', $fieldRow['typeparams'], '"', false);

			if ($parseParams and $this->type != 'virtual')
				$this->parseParams($row, $this->type);
		} else
			$this->type = null;
	}

	function parseParams(?array $row, string $type): void
	{
		$new_params = [];

		$index = 0;
		foreach ($this->params as $type_param) {
			if ($type_param !== null) {
				$type_param = str_replace('****quote****', '"', $type_param);
				$type_param = str_replace('****apos****', '"', $type_param);

				if (is_numeric($type_param))
					$new_params[] = $type_param;
				elseif (!str_contains($type_param, '{{') and !str_contains($type_param, '{%'))
					$new_params[] = $type_param;
				else {

					if ($type == 'user' and ($index == 1 or $index == 2)) {
						//Do not parse
						$new_params[] = $type_param;
					} else {
						$twig = new TwigProcessor($this->ct, $type_param, false, false, false);
						$new_params[] = $twig->process($row);

						if ($twig->errorMessage !== null)
							$this->ct->errors[] = $twig->errorMessage;
					}
				}
			}
			$index++;
		}
		$this->params = $new_params;
	}
}

class Fields
{
	public static function isFieldNullable(string $realtablename, string $relaFieldName): bool
	{
		$realtablename = database::realTableName($realtablename);
		$serverType = database::getServerType();
		if ($serverType == 'postgresql') {
			$query = 'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ' . database::quote($realtablename)
				. ' AND column_name=' . database::quote($relaFieldName);
		} else {

			$database = database::getDataBaseName();

			$query = 'SELECT COLUMN_NAME AS column_name,'
				. 'DATA_TYPE AS data_type,'
				. 'COLUMN_TYPE AS column_type,'
				. 'IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') AS is_unsigned,'
				. 'IS_NULLABLE AS is_nullable,'
				. 'COLUMN_DEFAULT AS column_default,'
				. 'EXTRA AS extra'
				. ' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=' . database::quote($database)
				. ' AND TABLE_NAME=' . database::quote($realtablename)
				. ' AND column_name=' . database::quote($relaFieldName)
				. ' LIMIT 1';
		}

		$rows = database::loadAssocList($query);
		$row = $rows[0];
		return $row['is_nullable'] == 'YES';
	}

	public static function deleteField_byID(CT &$ct, $fieldid): bool
	{
		if ($ct->Table->tablename === null) {
			die('deleteField_byID: Table not selected.');
		}

		if (defined('_JEXEC'))
			$ImageFolder = JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'esimages';
		else
			$ImageFolder = false;

		$fieldrow = Fields::getFieldRow($fieldid, true);

		if (is_null($fieldrow))
			return false;

		$field = new Field($ct, $fieldrow);
		$tableRow = $ct->Table->tablerow;

		if ($field->type !== null) {
			//for Image Gallery
			if ($field->type == 'imagegallery') {
				//Delete all photos belongs to the gallery

				$imageMethods = new CustomTablesImageMethods;
				$gallery_table_name = '#__customtables_gallery_' . $tableRow['tablename'] . '_' . $field->fieldname;
				$imageMethods->DeleteGalleryImages($gallery_table_name, $field->fieldrow['tableid'], $field->fieldname, $field->params, true);

				//Delete gallery table
				$query = 'DROP TABLE IF EXISTS ' . $gallery_table_name;
				database::setQuery($query);
			} elseif ($field->type == 'filebox') {
				//Delete all files belongs to the filebox

				$fileBoxTableName = '#__customtables_filebox_' . $tableRow['tablename'] . '_' . $field->fieldname;
				CustomTablesFileMethods::DeleteFileBoxFiles($fileBoxTableName, $field->fieldrow['tableid'], $field->fieldname, $field->params);

				//Delete gallery table
				$query = 'DROP TABLE IF EXISTS ' . $fileBoxTableName;
				database::setQuery($query);
			} elseif ($field->type == 'image') {
				if (Fields::checkIfFieldExists($tableRow['realtablename'], $field->realfieldname)) {
					if (defined('_JEXEC')) {
						$imageMethods = new CustomTablesImageMethods;
						$imageMethods->DeleteCustomImages($tableRow['realtablename'], $field->realfieldname, $ImageFolder, $field->params[0], $tableRow['realidfieldname'], true);
					}
				}
			} elseif ($field->type == 'user' or $field->type == 'userid' or $field->type == 'sqljoin') {
				Fields::removeForeignKey($tableRow['realtablename'], $field->realfieldname);
			} elseif ($field->type == 'file') {
				// delete all files
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
			if ($field->type != 'dummy' and !Fields::isVirtualField($fieldrow)) {
				$msg = '';
				Fields::deleteMYSQLField($tableRow['realtablename'], $realfieldname, $msg);
			}
		}

		//Delete field from the list
		$query = 'DELETE FROM #__customtables_fields WHERE id=' . $fieldid;
		database::setQuery($query);
		return true;
	}

	public static function getFieldRow($fieldid = 0, $assocList = false)
	{
		if ($fieldid == 0)
			$fieldid = common::inputGetInt('fieldid', 0);

		$query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS s WHERE id=' . $fieldid . ' LIMIT 1';//published=1 AND

		if ($assocList)
			$rows = database::loadAssocList($query);
		else
			$rows = database::loadObjectList($query);

		if (count($rows) != 1)
			return null;

		return $rows[0];
	}

	protected static function getFieldRowSelects(): string
	{
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			$realfieldname_query = 'CASE WHEN customfieldname!=\'\' THEN customfieldname ELSE CONCAT(\'es_\',fieldname) END AS realfieldname';
		else
			$realfieldname_query = 'IF(customfieldname!=\'\', customfieldname, CONCAT(\'es_\',fieldname)) AS realfieldname';

		return '*, ' . $realfieldname_query;
	}

	public static function checkIfFieldExists($realtablename, $realfieldname): bool
	{
		$realFieldNames = Fields::getListOfExistingFields($realtablename, false);
		return in_array($realfieldname, $realFieldNames);
	}

	public static function getListOfExistingFields($tablename, $add_table_prefix = true): array
	{
		$realFieldNames = Fields::getExistingFields($tablename, $add_table_prefix);
		$list = [];

		foreach ($realFieldNames as $rec)
			$list[] = $rec['column_name'];

		return $list;
	}

	public static function getExistingFields($tablename, $add_table_prefix = true)
	{
		if ($add_table_prefix)
			$realtablename = '#__customtables_table_' . $tablename;
		else
			$realtablename = $tablename;

		$realtablename = database::realTableName($realtablename);
		$serverType = database::getServerType();
		if ($serverType == 'postgresql') {
			//,generation_expression
			$query = 'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ' . database::quote($realtablename);
		} else {

			$database = database::getDataBaseName();
			//Check MySQL Version:

			$mySQLVersion = database::getVersion();
			if ($mySQLVersion < 5.7) {
				$query = 'SELECT COLUMN_NAME AS column_name,'
					. 'DATA_TYPE AS data_type,'
					. 'COLUMN_TYPE AS column_type,'
					. 'IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') AS is_unsigned,'
					. 'IS_NULLABLE AS is_nullable,'
					. 'COLUMN_DEFAULT AS column_default,'
					. 'EXTRA AS extra'
					. ' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=' . database::quote($database) . ' AND TABLE_NAME=' . database::quote($realtablename);
				//. '"" AS generation_expression'
			} else {
				$query = 'SELECT COLUMN_NAME AS column_name,'
					. 'DATA_TYPE AS data_type,'
					. 'COLUMN_TYPE AS column_type,'
					. 'IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') AS is_unsigned,'
					. 'IS_NULLABLE AS is_nullable,'
					. 'COLUMN_DEFAULT AS column_default,'
					. 'EXTRA AS extra'
					. ' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=' . database::quote($database) . ' AND TABLE_NAME=' . database::quote($realtablename);
				//. 'GENERATION_EXPRESSION AS generation_expression'
			}
		}

		return database::loadAssocList($query);
	}

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

	protected static function getTableConstrances($realtablename, $realfieldname): ?array
	{
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			return null;

		//get constrant name
		$query = 'show create table ' . $realtablename;
		$tableCreateQuery = database::loadAssocList($query);

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
			$paramsList = JoomlaBasicMisc::csv_explode(',', $fieldRow['typeparams'], '"', false);
			return ($paramsList[1] ?? 'virtual') == 'virtual' or '';
		} else
			return $isrequired == 2 or $isrequired == 3;
	}

	public static function deleteMYSQLField($realtablename, $realfieldname, &$msg): bool
	{
		if (Fields::checkIfFieldExists($realtablename, $realfieldname)) {
			try {
				$query = 'SET foreign_key_checks = 0;';
				database::setQuery($query);

				$query = 'ALTER TABLE ' . $realtablename . ' DROP ' . $realfieldname;

				database::setQuery($query);

				$query = 'SET foreign_key_checks = 1;';
				database::setQuery($query);

				return true;
			} catch (Exception $e) {
				$msg = '<p style="color:#ff0000;">Caught exception: ' . $e->getMessage() . '</p>';
				return false;
			}
		}
		return false;
	}

	public static function convertMySQLFieldTypeToCT($data_type, $column_type): array
	{
		$type = '';
		$typeParams = '';

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

				$parts = explode('(', $column_type);
				if (count($parts) > 1) {
					$length = str_replace(')', '', $parts[1]);
					if ($length != '')
						$typeParams = $length;
				}
				$type = 'float';
				break;

			case 'char':
			case 'varchar':

				$parts = explode('(', $column_type);
				if (count($parts) > 1) {
					$length = str_replace(')', '', $parts[1]);
					if ($length != '')
						$typeParams = $length;
				}
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
				$type = 'creationtime';
				break;

			case 'date':
				$type = 'date';
				break;
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

	public static function getFieldType(string $realtablename, $realfieldname)
	{
		$realtablename = database::realTableName($realtablename);
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			$query = 'SELECT data_type FROM information_schema.columns WHERE table_name = ' . database::quote($realtablename) . ' AND column_name=' . database::quote($realfieldname);
		else
			$query = 'SHOW COLUMNS FROM ' . $realtablename . ' WHERE ' . database::quoteName('field') . '=' . database::quote($realfieldname);

		$rows = database::loadAssocList($query);

		if (count($rows) == 0)
			return '';

		$row = $rows[0];
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			return $row['data_type'];
		else
			return $row['Type'];
	}

	public static function fixMYSQLField(string $realtablename, string $fieldname, string $PureFieldType, string &$msg): bool
	{
		if ($fieldname == 'id') {
			$constrances = Fields::getTableConstrances($realtablename, '');

			//Delete same table child-parent constrances
			if (!is_null($constrances)) {
				foreach ($constrances as $constrance) {
					if ($constrance[7] == '(id)')
						Fields::removeForeignKeyConstrance($realtablename, $constrance[1]);
				}
			}

			$query = 'ALTER TABLE ' . $realtablename . ' CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT';
			database::setQuery($query);

			$msg = '';
			return true;
		} elseif ($fieldname == 'published')
			$query = 'ALTER TABLE ' . $realtablename . ' CHANGE published published TINYINT NOT NULL DEFAULT 1';
		else
			$query = 'ALTER TABLE ' . $realtablename . ' CHANGE ' . $fieldname . ' ' . $fieldname . ' ' . $PureFieldType;

		try {
			database::setQuery($query);
			$msg = '';
			return true;
		} catch (Exception $e) {
			$msg = '<p style="color:red;">Caught exception fixMYSQLField: ' . $e->getMessage() . '</p>';
			return false;
		}
	}

	protected static function removeForeignKeyConstrance($realtablename, $constrance): void
	{
		$query = 'SET foreign_key_checks = 0;';
		database::setQuery($query);

		$query = 'ALTER TABLE ' . $realtablename . ' DROP FOREIGN KEY ' . $constrance;

		try {
			database::setQuery($query);
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}

		$query = 'SET foreign_key_checks = 1;';
		database::setQuery($query);
	}

	public static function getFieldName(int $fieldid): string
	{
		if ($fieldid == 0)
			$fieldid = common::inputGetInt('fieldid', 0);

		$query = 'SELECT fieldname FROM #__customtables_fields AS s WHERE s.published=1 AND s.id=' . $fieldid . ' LIMIT 1';
		$rows = database::loadObjectList($query);
		if (count($rows) != 1)
			return '';

		return $rows[0]->fieldname;
	}

	public static function getFieldRowByName(string $fieldName, ?int $tableId = null, string $tableName = '')
	{
		if ($fieldName == '')
			return array();

		if ($tableName == '')
			$query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS s WHERE s.published=1 AND tableid=' . $tableId . ' AND fieldname=' . database::quote(trim($fieldName)) . ' LIMIT 1';
		else {
			$query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS s

			INNER JOIN #__customtables_tables AS t ON t.tablename=' . database::quote($tableName) . '
			WHERE s.published=1 AND s.tableid=t.id AND s.fieldname=' . database::quote(trim($fieldName)) . ' LIMIT 1';
		}

		$rows = database::loadObjectList($query);

		if (count($rows) != 1)
			return null;

		return $rows[0];
	}

	public static function getFieldAssocByName(string $fieldname, int $tableid): ?array
	{
		if ($fieldname == '')
			$fieldname = common::inputGetCmd('fieldname', '');

		if ($fieldname == '')
			return null;

		$query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS s WHERE s.published=1 AND tableid=' . $tableid . ' AND fieldname="' . trim($fieldname) . '" LIMIT 1';
		$rows = database::loadAssocList($query);
		if (count($rows) != 1)
			return null;

		return $rows[0];
	}

	public static function FieldRowByName($fieldname, $ctFields)
	{
		if (is_null($ctFields))
			return null;

		foreach ($ctFields as $field) {
			if ($field['fieldname'] == $fieldname)
				return $field;
		}
		return null;
	}

	public static function getRealFieldName($fieldname, $ctfields, $all_fields = false)
	{
		foreach ($ctfields as $row) {
			if (($all_fields or $row['allowordering'] == 1) and $row['fieldname'] == $fieldname)
				return $row['realfieldname'];
		}
		return '';
	}

	public static function shortFieldObjects($fields): array
	{
		$field_objects = [];

		foreach ($fields as $fieldRow)
			$field_objects[] = Fields::shortFieldObject($fieldRow, null, []);

		return $field_objects;
	}

	public static function shortFieldObject($fieldRow, $value, $options): array
	{
		$field = [];
		$field['fieldname'] = $fieldRow['fieldname'];
		$field['title'] = $fieldRow['fieldtitle'];
		$field['defaultvalue'] = $fieldRow['defaultvalue'];
		$field['description'] = $fieldRow['description'];
		$field['isrequired'] = $fieldRow['isrequired'];
		$field['isdisabled'] = $fieldRow['isdisabled'];
		$field['type'] = $fieldRow['type'];

		$typeParams = JoomlaBasicMisc::csv_explode(',', $fieldRow['typeparams'], '"', false);
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

	//MySQL only

	public static function deleteTableLessFields(): void
	{
		$query = 'DELETE FROM #__customtables_fields AS f WHERE (SELECT id FROM #__customtables_tables AS t WHERE t.id = f.tableid) IS NULL';
		database::setQuery($query);
	}

	public static function getSelfParentField($ct)
	{
		//Check if this table has self-parent field - the TableJoin field linked with the same table.

		foreach ($ct->Table->fields as $fld) {
			if ($fld['type'] == 'sqljoin') {
				$type_params = JoomlaBasicMisc::csv_explode(',', $fld['typeparams'], '"', false);
				$join_tablename = $type_params[0];

				if ($join_tablename == $ct->Table->tablename) {
					return $fld;//['fieldname'];
				}
			}
		}
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.1.8
	 */
	public static function saveField(?int $tableId, ?int $fieldId): ?int
	{
		if ($fieldId == 0)
			$fieldId = null; // new field

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'utilities' . DIRECTORY_SEPARATOR . 'importtables.php');

		$ct = new CT;
		$table_row = ESTables::getTableRowByID($tableId);
		if (!is_object($table_row)) {
			if (defined('_JEXEC'))
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
			return null;
		}

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
				$data[$id_title] = common::inputGetString($id_title);
				$data[$id_description] = common::inputGetString($id_description);
				$moreThanOneLang = true; //More than one language installed
			}

			$data['type'] = common::inputGetCmd('type');
			$data['typeparams'] = common::inputGetString('typeparams');
			$data['isrequired'] = common::inputGetInt('isrequired', 0);
			$data['defaultvalue'] = common::inputGetString('defaultvalue');
			$data['allowordering'] = common::inputGetInt('allowordering', 1);
			$data['valuerule'] = common::inputGetString('valuerule');
			$data['valuerulecaption'] = common::inputGetString('valuerulecaption');
			$data['fieldname'] = common::inputGetString('fieldname');
		}

		$task = common::inputGetCmd('task');

		// Process field name
		if (function_exists("transliterator_transliterate"))
			$newFieldName = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", $data['fieldname']);
		else
			$newFieldName = $data['fieldname'];

		$newFieldName = strtolower(trim(preg_replace("/\W/", "", $newFieldName)));

		//Shorten the Field Name
		if (strlen($newFieldName) > 40)
			$newFieldName = substr($newFieldName, 0, 40);

		$data['fieldname'] = $newFieldName;

		if ($fieldId !== null and $task == 'save2copy') {
			//Checkout
			try {
				$update_data = ['checked_out' => 0, 'checked_out_time' => null];
				$where = ['id' => $fieldId];
				database::update('#__customtables_fields', $update_data, $where);
			} catch (Exception $e) {
				throw new Exception('Update field checkout problem: ' . $e->getMessage());
			}
			$fieldId = null; //To save the field as new
		}

		if ($fieldId === null) {
			$already_exists = Fields::getFieldID($tableId, $newFieldName);
			if ($already_exists == 0) {
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

		if ($table_row->customtablename == $table_row->tablename) {
			//do not create fields to third-party tables
			//Third-party table but managed by the Custom Tables
			$data['customfieldname'] = $newFieldName;
		}

		if ($fieldId !== null) {

			$where = ['id' => $fieldId];
			try {
				database::update('#__customtables_fields', $data, $where);
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
		}

		if (!self::update_physical_field($ct, $table_row, $fieldId, $data)) {
			//Cannot create
			return null;
		}

		self::findAndFixFieldOrdering();

		if ($data['type'] == 'ordering')
			self::findAndFixOrderingFieldRecords($table_row, (($data['customfieldname'] ?? '') != '' ? $data['customfieldname'] : 'es_' . $data['fieldname']));

		return $fieldId;
	}

	public static function getFieldID($tableid, $fieldname): int
	{
		$query = 'SELECT id FROM #__customtables_fields WHERE published=1 AND tableid=' . (int)$tableid . ' AND fieldname=' . database::quote($fieldname);
		$rows = database::loadObjectList($query);
		if (count($rows) == 0)
			return 0;

		$row = $rows[0];
		return $row->id;
	}

	public static function addLanguageField($tablename, $original_fieldname, $new_fieldname, ?string $AdditionOptions = ''): bool
	{
		$fields = Fields::getExistingFields($tablename, false);
		foreach ($fields as $field) {
			if ($field['column_name'] == $original_fieldname) {
				$AdditionOptions = '';
				if ($field['is_nullable'] != 'NO')
					$AdditionOptions = 'null';

				Fields::AddMySQLFieldNotExist($tablename, $new_fieldname, $field['column_type'], $AdditionOptions);
				return true;
			}
		}

		if ($original_fieldname == $new_fieldname) {
			Fields::AddMySQLFieldNotExist($tablename, $new_fieldname, $field['column_type'], $AdditionOptions);
			return true;
		}

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
			$query = 'ALTER TABLE ' . $realtablename . ' ADD COLUMN ' . $realfieldname . ' ' . $fieldType . ' ' . $options;

			if (defined('_JEXEC')) {
				try {
					database::setQuery($query);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			} elseif (defined('WPINC')) {
				try {
					database::setQuery($query);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}
		}
	}

	protected static function getMaxOrdering($tableid): int
	{
		$query = 'SELECT MAX(ordering) as max_ordering FROM #__customtables_fields WHERE published=1 AND tableid=' . (int)$tableid;
		$rows = database::loadObjectList($query);
		return (int)$rows[0]->max_ordering;
	}

	protected static function update_physical_field(CT $ct, object $table_row, int $fieldid, array $data): bool
	{
		$realtablename = $table_row->realtablename;
		$realtablename = database::realTableName($realtablename);

		if ($fieldid != 0) {
			$fieldRow = Fields::getFieldRow($fieldid);
			$ex_type = $fieldRow->type;
			$ex_typeparams = $fieldRow->typeparams;
			$realfieldname = $fieldRow->realfieldname;
		} else {
			$ex_type = '';
			$ex_typeparams = '';
			$realfieldname = '';

			if ($table_row->customtablename === null or $table_row->customtablename == '')//Just to be safe
				$realfieldname = 'es_' . $data['fieldname'];
			elseif ($table_row->customtablename == $table_row->tablename)
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

		//Virtuality
		if (isset($data['isrequired']) == 2 and ((int)$data['isrequired'] == 2 or (int)$data['isrequired'] == 3)) {
			$defaultValue = self::addFieldPrefixToExpression($table_row->id, $data['defaultvalue']);
		} else {
			$defaultValue = $data['defaultvalue'];
		}

		$PureFieldType = '';
		if ($new_type !== null and $new_typeparams !== null)
			$PureFieldType = Fields::getPureFieldType($new_type, $new_typeparams, (int)$data['isrequired'], $defaultValue);

		if ($realfieldname != '')
			$fieldFound = Fields::checkIfFieldExists($realtablename, $realfieldname);
		else
			$fieldFound = false;

		if ($fieldid != 0 and $fieldFound) {

			if ($PureFieldType != '') {
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

			$extraTask = '';

			if ($ex_type == $new_type and $new_type == 'image' and ($ex_typeparams != $new_typeparams or str_contains($new_typeparams, '|delete'))) {

				$ex_typeparams_array = JoomlaBasicMisc::csv_explode(',', $ex_typeparams);
				$new_typeparams_array = JoomlaBasicMisc::csv_explode(',', $new_typeparams);

				if ($ex_typeparams_array[0] != $new_typeparams_array[0])
					$extraTask = 'updateimages'; //Resize all images if needed
				elseif (($ex_typeparams_array[2] ?? null) != ($new_typeparams_array[2] ?? null)) {
					common::inputSet('stepsize', 1000);
					$extraTask = 'updateimages'; //Move all images if needed
				}
			}
			if ($ex_type == $new_type and $new_type == 'file' and $ex_typeparams != $new_typeparams)
				$extraTask = 'updatefiles';

			if ($ex_type == $new_type and $new_type == 'imagegallery' and $ex_typeparams != $new_typeparams)
				$extraTask = 'updateimagegallery'; //Resize or move all images in the gallery if needed

			if ($ex_type == $new_type and $new_type == 'filebox' and $ex_typeparams != $new_typeparams)
				$extraTask = 'updatefilebox'; //Resize or move all images in the gallery if needed

			if ($extraTask != '') {
				common::inputSet('extratask', $extraTask);
				common::inputSet('old_typeparams', base64_encode($ex_typeparams));
				common::inputSet('new_typeparams', base64_encode($new_typeparams));
				common::inputSet('fieldid', $fieldid);
			}
		}
		//---------------------------------- end convert field

		if ($fieldid == 0 or !$fieldFound) {
			//Add Field
			Fields::addField($ct, $realtablename, $realfieldname, $new_type, $PureFieldType, $fieldTitle, $data);
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

	public static function addFieldPrefixToExpression(int $tableId, string $expression): string
	{
		//This function adds 'es_' prefix to field name in the expression. Example:
		//concat(namelat," ",namerus)
		//concat(es_namelat," ",es_namerus)
		$prefix = 'es_';

		$fields = self::getFields($tableId);

		foreach ($fields as $field) {
			$fieldName = $field['fieldname'];
			$pos = 0;
			while (1) {
				$pos1 = strpos($expression, $fieldName, $pos);

				if ($pos1) {

					$word = true;
					if ($pos1 > 0) {
						if (ctype_alnum($expression[$pos1 - 1]))
							$word = false;
					}

					if ($pos1 + strlen($fieldName) < strlen($expression)) {
						if (ctype_alnum($expression[$pos1 + strlen($fieldName)]))
							$word = false;
					}

					if ($word) {
						$pos2 = strpos($expression, $prefix . $fieldName, $pos);

						if ($pos1 - 3 != $pos2) {
							$expression1 = substr($expression, 0, $pos1);
							$expression2 = substr($expression, $pos1 + strlen($fieldName), strlen($expression) - strlen($fieldName));
							$expression = $expression1 . $prefix . $fieldName . $expression2;
						} else {
							$pos = $pos1 + strlen($fieldName);
						}
					} else {
						$pos = $pos1 + strlen($fieldName);
					}
				} else {
					break;
				}
			}
		}
		return $expression;
	}

	public static function getFields($tableid_or_name, $as_object = false, $order_fields = true)
	{
		if ($order_fields)
			$order = ' ORDER BY f.ordering, f.fieldname';
		else
			$order = '';

		if ((int)$tableid_or_name > 0)
			$where = 'f.published=1 AND f.tableid=' . (int)$tableid_or_name;
		else {
			$w1 = '(SELECT t.id FROM #__customtables_tables AS t WHERE t.tablename=' . database::quote($tableid_or_name) . ' LIMIT 1)';
			$where = 'f.published=1 AND f.tableid=' . $w1;
		}

		$query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS f WHERE ' . $where . $order;

		if ($as_object)
			return database::loadObjectList($query);
		else
			return database::loadAssocList($query);
	}

	public static function getPureFieldType(string $ct_fieldType, string $typeParams, int $isRequiredOrGenerated = 0, ?string $defaultValue = null): string
	{
		$ct_fieldTypeArray = Fields::getProjectedFieldType($ct_fieldType, $typeParams);
		if ($isRequiredOrGenerated == 2 or $isRequiredOrGenerated == 3) {
			//$ct_fieldTypeArray['generation_expression'] = $defaultValue;
			$ct_fieldTypeArray['extra'] = ($isRequiredOrGenerated == 2 ? 'VIRTUAL' : 'STORED') . ' GENERATED';
			//$ct_fieldTypeArray['required_or_generated'] = $isRequiredOrGenerated;
		} else {
			//$ct_fieldTypeArray['required_or_generated'] = null;
		}

		return Fields::makeProjectedFieldType($ct_fieldTypeArray);
	}

	public static function getProjectedFieldType(string $ct_fieldType, ?string $typeParams): array
	{
		//Returns an array of mysql column parameters
		switch (trim($ct_fieldType)) {
			case '_id':
				return ['data_type' => 'int', 'is_nullable' => false, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => 'auto_increment'];

			case '_published':
				return ['data_type' => 'tinyint', 'is_nullable' => false, 'is_unsigned' => false, 'length' => null, 'default' => 1, 'extra' => null];

			case 'filelink':
			case 'file':
			case 'alias':
			case 'url':
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 1024, 'default' => null, 'extra' => null];
			case 'color':
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 8, 'default' => null, 'extra' => null];
			case 'string':
			case 'multilangstring':
				$l = (int)$typeParams;
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => ($l < 1 ? 255 : (min($l, 1024))), 'default' => null, 'extra' => null];
			case 'signature':

				$typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);
				$format = $typeParamsArray[3] ?? 'svg';

				if ($format == 'svg-db')
					return ['data_type' => 'text', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];
				else
					return ['data_type' => 'bigint', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];

			case 'blob':
				$typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);

				if ($typeParamsArray[0] == 'tiny')
					$type = 'tinyblob';
				elseif ($typeParamsArray[0] == 'medium')
					$type = 'mediumblob';
				elseif ($typeParamsArray[0] == 'long')
					$type = 'longblob';
				else
					$type = 'blob';

				return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

			case 'text':
			case 'multilangtext':

				$typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);
				$type = 'text';
				if (isset($typeParamsArray[2])) {
					if ($typeParamsArray[2] == 'tiny')
						$type = 'tinytext';
					elseif ($typeParamsArray[2] == 'medium')
						$type = 'mediumtext';
					elseif ($typeParamsArray[2] == 'long')
						$type = 'longtext';
				}

				return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

			case 'log':
				//mediumtext
				return ['data_type' => 'text', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];
			case 'ordering':
				return ['data_type' => 'int', 'is_nullable' => false, 'is_unsigned' => true, 'length' => null, 'default' => 0, 'extra' => null];
			case 'time':
			case 'int':
				return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];
			case 'float':

				$typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);

				if (count($typeParamsArray) == 1)
					$l = '20,' . (int)$typeParamsArray[0];
				elseif (count($typeParamsArray) == 2)
					$l = (int)$typeParamsArray[1] . ',' . (int)$typeParamsArray[0];
				else
					$l = '20,2';
				return ['data_type' => 'decimal', 'is_nullable' => true, 'is_unsigned' => false, 'length' => $l, 'default' => null, 'extra' => null];

			case 'customtables':
				$typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);

				if (count($typeParamsArray) < 255)
					$l = 255;
				else
					$l = (int)$typeParamsArray[2];

				if ($l > 65535)
					$l = 65535;

				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => $l, 'default' => null, 'extra' => null];

			case 'userid':
			case 'user':
			case 'usergroup':
			case 'sqljoin':
			case 'article':
			case 'multilangarticle':
				return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => null];

			case 'image':

				$typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);

				$fileNameType = $typeParamsArray[3] ?? '';
				$length = null;

				if ($fileNameType == '') {
					$type = 'bigint';
				} else {
					$type = 'varchar';
					$length = 1024;
				}

				return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => false, 'length' => $length, 'default' => null, 'extra' => null];

			case 'checkbox':
				return ['data_type' => 'tinyint', 'is_nullable' => false, 'is_unsigned' => false, 'length' => null, 'default' => 0, 'extra' => null];

			case 'date':
				return ['data_type' => 'date', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

			case 'creationtime':
			case 'changetime':
			case 'lastviewtime':
				return ['data_type' => 'datetime', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];

			case 'viewcount':
			case 'imagegallery':
			case 'id':
			case 'filebox':
				return ['data_type' => 'bigint', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => null];

			case 'language':
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 5, 'default' => null, 'extra' => null];

			case 'dummy':
				return ['data_type' => null, 'is_nullable' => null, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

			case 'virtual':

				$typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);
				$storage = $typeParamsArray[1] ?? '';

				if ($storage == 'storedstring') {
					$l = (int)$typeParamsArray[2] ?? 255;
					return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => ($l < 1 ? 255 : (min($l, 4069))), 'default' => null, 'extra' => null];
				} elseif ($storage == 'storedintegersigned')
					return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];
				elseif ($storage == 'storedintegerunsigned')
					return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => null];
				else
					return ['data_type' => null, 'is_nullable' => null, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

			case 'md5':
				return ['data_type' => 'char', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 32, 'default' => null, 'extra' => null];

			case 'phponadd':
			case 'phponchange':
			case 'phponview':
				$typeParamsArray = explode(',', $typeParams);

				if (isset($typeParamsArray[1]) and $typeParamsArray[1] == 'dynamic')
					return ['data_type' => null, 'is_nullable' => null, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null]; //do not store field values
				else
					return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 255, 'default' => null, 'extra' => null];

			default:
				return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 255, 'default' => null, 'extra' => null];
		}
	}

	public static function makeProjectedFieldType(array $ct_fieldTypeArray): string
	{
		$type = (object)$ct_fieldTypeArray;
		$elements = [];

		switch ($type->data_type) {
			case 'varchar':
				$elements[] = 'varchar(' . $type->length . ')';
				break;

			case 'tinytext':
				$elements[] = 'tinytext';
				break;

			case 'text':
				$elements[] = 'text';
				break;

			case 'mediumtext':
				$elements[] = 'mediumtext';
				break;

			case 'longtext':
				$elements[] = 'longtext';
				break;

			case 'tinyblob':
				$elements[] = 'tinyblob';
				break;

			case 'blob':
				$elements[] = 'blob';
				break;

			case 'mediumblob':
				$elements[] = 'mediumblob';
				break;

			case 'longblob':
				$elements[] = 'longblob';
				break;

			case 'char':
				$elements[] = 'char(' . $type->length . ')';
				break;

			case 'int':
				$elements[] = 'int';
				$serverType = database::getServerType();
				if ($serverType != 'postgresql') {
					if ($type->is_nullable !== null and $type->is_unsigned)
						$elements[] = 'unsigned';
				}
				break;

			case 'bigint':
				$elements[] = 'bigint';
				$serverType = database::getServerType();
				if ($serverType != 'postgresql') {
					if ($type->is_nullable !== null and $type->is_unsigned)
						$elements[] = 'unsigned';
				}
				break;

			case 'decimal':
				$serverType = database::getServerType();
				if ($serverType == 'postgresql')
					$elements[] = 'numeric(' . $type->length . ')';
				else
					$elements[] = 'decimal(' . $type->length . ')';

				break;

			case 'tinyint':
				$serverType = database::getServerType();
				if ($serverType == 'postgresql')
					$elements[] = 'smallint';
				else
					$elements[] = 'tinyint';

				break;

			case 'date':
				$elements[] = 'date';
				break;

			case 'datetime':
				$serverType = database::getServerType();
				if ($serverType == 'postgresql')
					$elements[] = 'TIMESTAMP';
				else
					$elements[] = 'datetime';

				break;

			default:
				return '';
		}

		//Check for virtuality
		if (isset($type->extra) and str_contains($type->extra, 'GENERATED')) {

			$type->default = null;
			/*
			if ($type->extra == 'VIRTUAL GENERATED')
				$elements[] = 'AS (' . $ct_fieldTypeArray['generation_expression'] . ') VIRTUAL';

			if ($type->extra == 'STORED GENERATED')
				$elements[] = 'AS (' . $ct_fieldTypeArray['generation_expression'] . ') STORED';
			*/

		} elseif (isset($type->required_or_generated)) {

			$type->default = null;
			/*
			if ($type->required_or_generated == 2)
				$elements[] = 'AS (' . $ct_fieldTypeArray['generation_expression'] . ') VIRTUAL';

			if ($type->required_or_generated == 3)
				$elements[] = 'AS (' . $ct_fieldTypeArray['generation_expression'] . ') STORED';
			*/
		}

		if ($type->is_nullable)
			$elements[] = 'null';
		else
			$elements[] = 'not null';

		if (isset($type->default))
			$elements[] = 'default ' . (is_numeric($type->default) ? $type->default : database::quote($type->default));

		if ($type->extra !== null and !str_contains($type->extra, 'GENERATED'))
			$elements[] = $type->extra;

		return implode(' ', $elements);
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

		$inconvertible_types = array('dummy', 'virtual', 'imagegallery', 'file', 'filebox', 'signature', 'records', 'customtables', 'log');

		if (in_array($new_type, $inconvertible_types) or in_array($ex_type, $inconvertible_types))
			return false;

		$PureFieldType_ = $PureFieldType;

		//Check and fix record
		if ($new_type == 'customtables') {
			//get number of string like "varchar(255)"
			$maxlength = (int)preg_replace("/\D/", "", $PureFieldType);
			$typeParamsArray = explode(',', $new_typeparams);
			$optionName = $typeParamsArray[0];

			Fields::FixCustomTablesRecords($realtablename, $realfieldname, $optionName, $maxlength);
		}

		$serverType = database::getServerType();

		if ($serverType == 'postgresql') {
			$parts = explode(' ', $PureFieldType_);
			$query = 'ALTER TABLE ' . $realtablename
				. ' ALTER COLUMN ' . $realfieldname . ' TYPE ' . $parts[0];

		} else {
			$query = 'ALTER TABLE ' . $realtablename . ' CHANGE ' . $realfieldname . ' ' . $realfieldname . ' ' . $PureFieldType_;
			$query .= ' COMMENT ' . database::quote($fieldtitle);
		}

		try {
			database::setQuery($query);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		return true;
	}

	public static function FixCustomTablesRecords($realtablename, $realfieldname, $optionname, $maxlenght): void
	{
		//CustomTables field type
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			return;

		$fixCount = 0;
		$fixQuery = 'SELECT id, ' . $realfieldname . ' AS fldvalue FROM ' . $realtablename;
		$fixRows = database::loadObjectList($fixQuery);
		foreach ($fixRows as $fixRow) {

			$newRow = Fields::FixCustomTablesRecord($fixRow->fldvalue, $optionname, $maxlenght);

			if ($fixRow->fldvalue != $newRow) {
				$fixCount++;
				$fixitQuery = 'UPDATE ' . $realtablename . ' SET ' . $realfieldname . '="' . $newRow . '" WHERE id=' . $fixRow->id;
				database::setQuery($fixitQuery);
			}
		}
	}

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

	public static function addField(CT $ct, string $realtablename, string $realfieldname, string $fieldType, string $PureFieldType, string $fieldTitle, array $fieldRow): void
	{
		if ($PureFieldType == '')
			return;

		if (!str_contains($fieldType, 'multilang')) {
			$AdditionOptions = '';
			$serverType = database::getServerType();
			if ($serverType != 'postgresql')
				$AdditionOptions = ' COMMENT ' . database::quote($fieldTitle);

			if ($fieldType != 'dummy' and !Fields::isVirtualField($fieldRow))
				Fields::AddMySQLFieldNotExist($realtablename, $realfieldname, $PureFieldType, $AdditionOptions);
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

				Fields::AddMySQLFieldNotExist($realtablename, $realfieldname . $postfix, $PureFieldType, $AdditionOptions);

				$index++;
			}
		}

		if ($fieldType == 'imagegallery') {
			//Create table
			//get CT table name if possible

			$tableName = str_replace(database::getDBPrefix() . 'customtables_table', '', $realtablename);
			$fieldName = str_replace($ct->Env->field_prefix, '', $realfieldname);
			Fields::CreateImageGalleryTable($tableName, $fieldName);
		} elseif ($fieldType == 'filebox') {
			//Create table
			//get CT table name if possible
			$tableName = str_replace(database::getDBPrefix() . 'customtables_table', '', $realtablename);
			$fieldName = str_replace($ct->Env->field_prefix, '', $realfieldname);
			Fields::CreateFileBoxTable($tableName, $fieldName);
		}
	}

	public static function CreateImageGalleryTable($tablename, $fieldname): bool
	{
		$image_gallery_table = '#__customtables_gallery_' . $tablename . '_' . $fieldname;

		$query = 'CREATE TABLE IF not EXISTS ' . $image_gallery_table . ' (
  photoid bigint not null auto_increment,
  listingid bigint not null,
  ordering int not null,
  photo_ext varchar(10) not null,
  title varchar(100) null,
   PRIMARY KEY  (photoid)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1 ;
';
		database::setQuery($query);
		return true;
	}

	public static function CreateFileBoxTable($tablename, $fieldname): bool
	{
		$filebox_gallery_table = '#__customtables_filebox_' . $tablename . '_' . $fieldname;

		$query = 'CREATE TABLE IF not EXISTS ' . $filebox_gallery_table . ' (
  fileid bigint not null auto_increment,
  listingid bigint not null,
  ordering int not null,
  file_ext varchar(10) not null,
  title varchar(100) not null,
   PRIMARY KEY  (fileid)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1 ;
';
		database::setQuery($query);
		return true;
	}

	public static function addIndexIfNotExist($realtablename, $realfieldname): void
	{
		$serverType = database::getServerType();

		if ($serverType == 'postgresql') {
			//Indexes not yet supported
		} else {
			$query = 'SHOW INDEX FROM ' . $realtablename . ' WHERE Key_name = "' . $realfieldname . '"';
			$rows = database::loadObjectList($query);

			if (count($rows) == 0) {
				$query = 'ALTER TABLE ' . $realtablename . ' ADD INDEX(' . $realfieldname . ');';
				database::setQuery($query);
			}
		}
	}

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

			$tableRow = ESTables::getTableRowByName($typeParams[0]); //[0] - is tablename
			if (!is_object($tableRow)) {
				$msg = 'Join with table "' . $join_with_table_name . '" not found.';
				return false;    // Exit if table to connect with not found
			}

			$join_with_table_name = $tableRow->realtablename;
			$join_with_table_field = $tableRow->realidfieldname;
		}

		$join_with_table_name = database::realTableName($join_with_table_name);
		$database = database::getDataBaseName();

		Fields::removeForeignKey($realtablename, $realfieldname);

		if (isset($typeParams[7]) and $typeParams[7] == 'addforignkey') {
			Fields::cleanTableBeforeNormalization($realtablename, $realfieldname, $join_with_table_name, $join_with_table_field);

			$query = 'ALTER TABLE ' . database::quoteName($realtablename) . ' ADD FOREIGN KEY (' . $realfieldname . ') REFERENCES '
				. database::quoteName($database . '.' . $join_with_table_name) . ' (' . $join_with_table_field . ') ON DELETE RESTRICT ON UPDATE RESTRICT;';

			try {
				database::setQuery($query);
				return true;
			} catch (Exception $e) {
				$msg = $e->getMessage();
			}
		}
		return false;
	}

	public static function cleanTableBeforeNormalization($realtablename, $realfieldname, $join_with_table_name, $join_with_table_field): void
	{
		$serverType = database::getServerType();
		if ($serverType == 'postgresql')
			return;

		//Find broken records
		$query = 'SELECT DISTINCT a.' . $realfieldname . ' AS customtables_distinct_temp_id FROM
			' . $realtablename . ' a LEFT JOIN ' . $join_with_table_name . ' b ON a.' . $realfieldname . '=b.' . $join_with_table_field
			. ' WHERE b.' . $join_with_table_field . ' IS NULL;';

		$rows = database::loadAssocList($query);
		$where_ids = array();
		$where_ids[] = $realfieldname . '=0';

		foreach ($rows as $row) {
			if ($row['customtables_distinct_temp_id'] != '')
				$where_ids[] = $realfieldname . '=' . $row['customtables_distinct_temp_id'];
		}
		$query = 'UPDATE ' . $realtablename . ' SET ' . $realfieldname . '=NULL WHERE ' . implode(' OR ', $where_ids) . ';';
		database::setQuery($query);
	}

	protected static function findAndFixFieldOrdering(): void
	{
		$query = 'UPDATE #__customtables_fields SET ordering=id WHERE ordering IS NULL or ordering = 0';

		try {
			database::setQuery($query);
		} catch (Exception $e) {
			echo 'Caught exception: ', $e->getMessage(), "\n";
			die;
		}
	}

	protected static function findAndFixOrderingFieldRecords(object $table_row, string $realFieldName): void
	{
		$ct = new CT;
		$table_row_array = (array)$table_row;
		$ct->setTable($table_row_array, null, false);
		$query = 'UPDATE ' . $ct->Table->realtablename . ' SET ' . database::quoteName($realFieldName) . '=' . database::quoteName($ct->Table->realidfieldname) . ' WHERE ' . database::quoteName($realFieldName) . ' IS NULL OR ' . database::quoteName($realFieldName) . ' = 0';

		try {
			database::setQuery($query);
		} catch (Exception $e) {
			echo 'Caught exception: ', $e->getMessage(), "\n";
			die;
		}
	}

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
}

