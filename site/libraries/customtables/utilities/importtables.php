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

use Exception;
use JTableNested;

// no direct access
defined('_JEXEC') or die();

class ImportTables
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function processFile($filename, $menuType, &$msg, $category = '', $importFields = true, $importLayouts = true, $importMenu = true): bool
	{
		$ct = new CT([], true);

		if (file_exists($filename)) {
			$data = common::getStringFromFile($filename);
			if ($data == '') {
				$msg = 'Uploaded file "' . $filename . '" is empty.';
				return false;
			}
			$ct->Env->folderToSaveLayouts = null;

			return ImportTables::processContent($ct, $data, $menuType, $msg, $category, $importFields, $importLayouts, $importMenu);
		} else {
			$msg = 'Uploaded file "' . $filename . '" not found.';
			return false;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function processContent(CT &$ct, string $data, string $menuType, string &$msg, string $category = '', bool $importFields = true, bool $importLayouts = true, bool $importMenu = true): bool
	{
		$keyword = '<customtablestableexport>';
		if (!str_contains($data, $keyword)) {
			$keyword = '<extrasearchtableexport>';
			if (!str_contains($data, $keyword)) {
				$msg = 'Uploaded file/content does not contain CustomTables table structure data.';
				return false;
			}
		}

		$JSON_data = json_decode(str_replace($keyword, '', $data), true);
		return ImportTables::processData($ct, $JSON_data, $menuType, $msg, $category, $importFields, $importLayouts, $importMenu);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processData(CT &$ct, array $JSON_data, string $menuType, string &$msg, string $category = '', bool $importfields = true, bool $importlayouts = true, bool $importmenu = true): bool
	{
		foreach ($JSON_data as $table) {
			$tableid = ImportTables::processTable($table['table'], $category);

			if ($tableid != 0) {
				//Ok, table created or found and updated
				//Next: Add/Update Fields

				if ($importfields)
					ImportTables::processFields($table['table']['tablename'], $table['fields'], $msg);

				if ($importlayouts)
					ImportTables::processLayouts($ct, $tableid, $table['layouts'], $msg);

				if ($importmenu and is_array($table['menu']))
					ImportTables::processMenu($table['menu'], $menuType, $msg);

				IntegrityChecks::check($ct, false);

				if (isset($table['records']))
					ImportTables::processRecords($table['table']['tablename'], $table['records']);
			} else {
				$msg = 'Could not Add or Update table "' . $table['table']['tablename'] . '"';
				common::enqueueMessage($msg);

				return false;
			}
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processTable(array $table_new, string $categoryName): int
	{
		//This function creates the table and returns table's id.
		//If table with same name already exists then existing table will be updated, and it's ID will be returned.

		$tableName = $table_new['tablename'];

		$table_old = TableHelper::getTableRowByNameAssoc($tableName);

		if (is_array($table_old) and count($table_old) > 0) {
			$tableId = $table_old['id'];
			//table with the same name already exists
			//Lets update it
			ImportTables::updateRecords('tables', $table_new, $table_old, true, ['categoryname']);
		} else {
			//Create table record
			$tableId = ImportTables::insertRecords('#__customtables_tables', $table_new, ['categoryname']);
		}

		//Create mysql table
		$tableTitle = $table_new['tabletitle'] ?? $table_new['tabletitle_1'];

		$columns = ['published tinyint(1) NOT NULL DEFAULT "1"'];

		database::createTable('#__customtables_table_' . $tableName, 'id', $columns, $tableTitle);

		if (defined('_JEXEC'))
			ImportTables::updateTableCategory($tableId, $table_new, $categoryName);

		return $tableId;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function updateRecords(string $table, array $rows_new, array $rows_old, $addPrefix = true, array $exceptions = array(), bool $force_id = false, $save_checked_out = false): void
	{
		if ($addPrefix)
			$mySQLTableName = '#__customtables_' . $table;
		else
			$mySQLTableName = $table;

		$data = [];
		$keys = array_keys($rows_new);

		$ignore_fields = ['asset_id', 'created_by', 'modified_by', 'version', 'hits', 'publish_up', 'publish_down'];

		if (!$save_checked_out) {
			$ignore_fields[] = 'checked_out';
			$ignore_fields[] = 'checked_out_time';
		}

		foreach ($keys as $key) {

			if (!in_array($key, $ignore_fields)) {
				$fieldname = ImportTables::checkFieldName($key, $force_id, $exceptions);

				if ($fieldname != '' and Fields::checkIfFieldExists($mySQLTableName, $fieldname)) {
					if (array_key_exists($key, $rows_new) and (!array_key_exists($key, $rows_old) or $rows_new[$key] != $rows_old[$key])) {
						$data[$fieldname] = $rows_new[$key];
					}
				}
			}
		}

		if (count($data) > 0) {
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('id', $rows_old['id']);
			database::update($mySQLTableName, $data, $whereClauseUpdate);
		}
	}

	public static function checkFieldName($key, $force_id, $exceptions)
	{
		$ok = true;

		if (str_contains($key, 'itemaddedtext'))
			$ok = false;

		if (!$force_id) {
			if ($key == 'id')
				$ok = false;
		}

		for ($k = 3; $k < 11; $k++) {
			if (str_contains($key, '_' . $k))
				$ok = false;
		}

		if (str_contains($key, '_1'))
			$fieldname = str_replace('_1', '', $key);
		elseif (str_contains($key, '_2'))
			$fieldname = str_replace('_2', '_es', $key);
		else
			$fieldname = $key;

		if ($ok and !in_array($key, $exceptions))
			return $fieldname;

		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function insertRecords(string $mysqlTableName, array $rows, array $exceptions = array(),
										 bool   $force_id = false, string $fieldPrefix = null, int $tableId = null): ?int
	{
		$data = [];

		$keys = array_keys($rows);
		$ignore_fields = ['asset_id', 'created_by', 'modified_by', 'version', 'hits', 'publish_up', 'publish_down', 'checked_out_time'];

		//if (!$save_checked_out) {
		$ignore_fields[] = 'checked_out';
		$ignore_fields[] = 'checked_out_time';
		//}

		if (!defined('_JEXEC'))
			$ignore_fields[] = 'tablecategory';

		//$core_fields = ['id', 'published'];
		$tablesToIgnore = ['#__customtables_tables', '#__customtables_fields', '#__customtables_layouts', '#__menu', "#__usergourps", "#__viewlevels"];

		foreach ($keys as $key) {
			$isOk = false;

			//if (isset($field_conversion_map[$key])) {
			//$isOk = true;
			//if (is_array($field_conversion_map[$key])) {
			//$fieldname = $field_conversion_map[$key]['name'];
			//} else
			//$fieldname = $field_conversion_map[$key];
			//} elseif (count($field_conversion_map) > 0 and in_array($key, $field_conversion_map)) {
			//$isOk = true;
			//if (in_array($key, $core_fields))
			//$fieldname = $key;
			//else
			//$fieldname = $add_field_prefix . $key;
			//} else {
			$fieldname = ImportTables::checkFieldName($key, $force_id, $exceptions);
			if ($fieldname != '') {
				$isOk = true;
				//if (!in_array($fieldname, $core_fields))
				//$fieldname = $fieldname;//$add_field_prefix .
			}
			//}

			if ($fieldname != null and !in_array($fieldname, $ignore_fields) and $isOk) {

				if (!Fields::checkIfFieldExists($mysqlTableName, $fieldname)) {
					//Add field
					/*
					if (in_array($mysqlTableName, $tablesToIgnore)) {
						//$data[$fieldname] = $rows[$key];
					} else {
						$data[$fieldname] = $rows[$key];
						$isLanguageFieldName = Fields::isLanguageFieldName($fieldname);

						if ($isLanguageFieldName) {
							//Add language field
							//Get non language field type
							$nonLanguageFieldName = Fields::getLanguageLessFieldName($key);

							//TODO: check how it works
							$whereClause = new MySQLWhereClause();
							$whereClause->addCondition('tableid', $tableId, '=', true);
							$whereClause->addCondition('fieldname', str_replace($fieldPrefix, '', $nonLanguageFieldName));
							$col = database::loadColumn('#__customtables_fields', ['type'], $whereClause, null, null, 1);

							$fieldType = '';
							if (count($col) == 1) {
								$fieldType = $col[0];
							}

							if ($fieldType != '') {
								Fields::AddMySQLFieldNotExist($mysqlTableName, $key, $fieldType, '');
								$data[$fieldname] = $rows[$key];
							}
						}
					}
					*/
				} else {
					$data[$fieldname] = $rows[$key];
				}
			}
		}
		return database::insert($mysqlTableName, $data);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function updateTableCategory(int $tableid, $table_new, string $categoryName): void
	{
		if (isset($table_new['tablecategory']))
			$categoryId_ = $table_new['tablecategory'];
		elseif (isset($table_new['catid']))
			$categoryId_ = $table_new['catid'];
		else
			$categoryId_ = null;

		if ($categoryId_ == '')
			$categoryId_ = null;

		if ($categoryName == '')
			$categoryName = $table_new['categoryname'];

		$categoryId = $categoryId_;

		if ($categoryId != 0) {
			$category_row = ImportTables::getRecordByField('#__customtables_categories', 'id', $categoryId, false);

			if (is_array($category_row) and count($category_row) > 0) {
				if ($category_row['categoryname'] == $categoryName) {
					//Good
				} else {
					//Find Category By name
					$categoryId = null;
				}

			} else
				$categoryId = null;
		}

		if (is_null($categoryId)) {
			//Find Category By name
			$category_row = ImportTables::getRecordByField('#__customtables_categories', 'categoryname', $categoryName, false);

			if (is_array($category_row) and count($category_row) > 0) {
				$categoryId = $category_row['id'];

			} else {
				//Create Category
				if (!empty($categoryName)) {
					$inserts = ['categoryname' => $categoryName];
					$categoryId = database::insert('#__customtables_categories', $inserts);
				}
			}
		}

		if ($categoryId != $categoryId_ or is_null($categoryId)) {
			//Update Category ID in table

			$data = [
				'tablecategory' => (int)$categoryId
			];
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('id', $tableid);
			database::update('#__customtables_tables', $data, $whereClauseUpdate);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getRecordByField($table, $fieldname, $value, $addPrefix = true)
	{
		if ($addPrefix)
			$mysqlTableName = '#__customtables_' . $table;
		else
			$mysqlTableName = $table;

		$whereClause = new MySQLWhereClause();

		if (is_null($value))
			$whereClause->addCondition($fieldname, null, 'NULL');
		else
			$whereClause->addCondition($fieldname, $value);

		$rows = database::loadAssocList($mysqlTableName, ['*'], $whereClause);
		if (count($rows) == 0)
			return 0;

		return $rows[0];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processFields(string $tableName, array $fields, &$msg): bool
	{
		$ct = new CT([], true);
		$ct->getTable($tableName);

		foreach ($fields as $field) {
			$fieldid = ImportTables::processField($ct, $field);
			if ($fieldid == 0) {
				$msg = 'Could not Add or Update field "' . $field['fieldname'] . '"';
				return false;
			}
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processField(CT $ct, array &$field_new)
	{
		//This function creates the table field and returns field's id.
		//If field with same name already exists then existing field will be updated, and it's ID will be returned.

		$field_new['tableid'] = $ct->Table->tableid;//replace tableid
		$fieldName = $field_new['fieldname'];
		$field_old = $ct->Table->getFieldByName($fieldName);

		if (is_array($field_old) and count($field_old) > 0) {
			$fieldid = $field_old['id'];
			ImportTables::updateRecords('fields', $field_new, $field_old);
		} else {
			//Create field record
			$fieldid = ImportTables::insertRecords('#__customtables_fields', $field_new);
			if ($fieldid != 0) {
				//Field added
				//Lets create mysql field
				$PureFieldType = Fields::getPureFieldType($field_new['type'], $field_new['typeparams']);

				Fields::addField($ct, $ct->Table->realtablename, $ct->Table->fieldPrefix . $fieldName,
					$PureFieldType, $field_new['fieldtitle'], $field_new);
			}
		}
		return $fieldid;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processLayouts(CT &$ct, $tableid, $layouts, &$msg): bool
	{
		foreach ($layouts as $layout) {
			$layoutId = ImportTables::processLayout($ct, $tableid, $layout);
			if ($layoutId == 0) {
				$msg = 'Could not Add or Update layout "' . $layout['layoutname'] . '"';
				return false;
			}
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processLayout(CT &$ct, $tableid, &$layout_new)
	{
		//This function creates layout and returns its id.
		//If layout with same name already exists then existing layout will be updated, and it's ID will be returned.

		$layout_new['tableid'] = $tableid;//replace tableid
		$newLayoutName = $layout_new['layoutname'];

		// Process layout name
		if (function_exists("transliterator_transliterate"))
			$newLayoutName = transliterator_transliterate("Any-Latin; Latin-ASCII;", $newLayoutName);

		$newLayoutName = str_replace(" ", "_", $newLayoutName);
		$newLayoutName = trim(preg_replace("/[^a-z A-Z_\d]/", "", $newLayoutName));
		$layout_new['layoutname'] = $newLayoutName;

		$layout_old = ImportTables::getRecordByField('layouts', 'layoutname', $newLayoutName);
		$Layouts = new Layouts($ct);

		//Convert all Layout Types
		if ($layout_new['layouttype'] == CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE)
			$layout_new['layouttype'] = CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG;

		if ($layout_new['layouttype'] == 3 or $layout_new['layouttype'] == CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM)
			$layout_new['layouttype'] = CUSTOMTABLES_LAYOUT_TYPE_DETAILS;

		if (is_array($layout_old) and count($layout_old) > 0) {
			$layoutId = $layout_old['id'];
			ImportTables::updateRecords('layouts', $layout_new, $layout_old);
		} else {
			//Create layout record
			$layoutId = ImportTables::insertRecords('#__customtables_layouts', $layout_new);
		}
		$Layouts->storeAsFile($layout_new);
		return $layoutId;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processMenu(array $listOfMenus, string $menuType, &$msg): bool
	{
		$menus = array();
		foreach ($listOfMenus as $menuitem) {
			$menuId = ImportTables::processMenuItem($menuitem, $menuType, $menus);
			if ($menuId != 0) {
				//All Good
			} else {
				$msg = 'Could not Add or Update menu item "' . $menuitem['title'] . '"';
				return false;
			}
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processMenuItem(array &$menuitem_new, string $menuType, array &$menus)
	{
		//This function creates menuitem and returns its id.
		//If menuitem with same alias already exists then existing menuitem will be updated, and it's ID will be returned.

		$menuitem_alias = substr($menuitem_new['alias'], 0, 400);
		$menuItemId = 0;

		$component = ImportTables::getRecordByField('#__extensions', 'element', 'com_customtables', false);
		if (!is_array($component))
			return false;

		$component_id = (int)$component['extension_id'];
		$menuitem_new['component_id'] = $component_id;

		if ($menuType != '' and $menuType != $menuitem_new['menutype'])
			$new_menuType = $menuType;
		else
			$new_menuType = $menuitem_new['menutype'];

		//Check NEW $menuitem_new['menutype']
		$new_menutype_alias = substr(CTMiscHelper::slugify($new_menuType), 0, 24);
		$menutype_old = ImportTables::getRecordByField('#__menu_types', 'menutype', $new_menutype_alias, false);

		if (!is_array($menutype_old) or count($menutype_old) == 0) {
			//Create new menu type
			$data = [];
			$data['asset_id'] = 0;
			$data['menutype'] = $new_menutype_alias;
			$data['title'] = $new_menuType;
			$data['description'] = 'Menu Type created by CustomTables';
			database::insert('#__menu_types', $data);
		}

		$menuitem_new['checked_out'] = 0;
		$menuitem_old = ImportTables::getRecordByField('#__menu', 'alias', $menuitem_alias, false);

		if (is_array($menuitem_old) and count($menuitem_old) > 0) {
			$menuItemId = $menuitem_old['id'];
			$old_menutype = ImportTables::getRecordByField('#__menu_types', 'menutype', $menuitem_old['menutype'], false);
			$menuitem_new['home'] = 0;// Menu parameter (default page) should not be copied into export file. #4

			if ($old_menutype == 0) {
				$lft = ImportTables::menuGetMaxRgt() + 1;
				$menuitem_new['lft'] = $lft;
				$menuitem_new['rgt'] = $lft + 1;
				$menuitem_new['link'] = str_replace('com_extrasearch', 'com_customtables', $menuitem_new['link']);

				if ($menuitem_old['parent_id'] != 1)
					$menuitem_new['parent_id'] = ImportTables::getMenuParentID($menuitem_old['parent_id'], $menus);
				else
					$menuitem_new['parent_id'] = 1;

				$menuitem_new['level'] = 1;
				$menuitem_new['menutype'] = $new_menutype_alias;
			} else {
				$menuitem_new['lft'] = $menuitem_old['lft'];
				$menuitem_new['rgt'] = $menuitem_old['rgt'];

				if ($menuitem_old['parent_id'] != 1)
					$menuitem_new['parent_id'] = ImportTables::getMenuParentID($menuitem_old['parent_id'], $menus);
				else
					$menuitem_new['parent_id'] = 1;

				$menuitem_new['level'] = $menuitem_old['level'];
				$menuitem_new['menutype'] = $menuitem_old['menutype'];
			}
			ImportTables::updateRecords('#__menu', $menuitem_new, $menuitem_old, false);
			$menus[] = [$menuitem_alias, $menuItemId, $menuitem_old['id']];
		} else {
			$lft = ImportTables::menuGetMaxRgt() + 1;
			$menuitem_new['parent_id'] = 1;
			$menuitem_new['level'] = 1;
			$menuitem_new['lft'] = $lft;
			$menuitem_new['rgt'] = $lft + 1;
			$menuitem_new['link'] = str_replace('com_extrasearch', 'com_customtables', $menuitem_new['link']);
			$menuitem_new['id'] = null;
			$menuitem_new['component_id'] = $component_id;
			$menuitem_new['alias'] = $menuitem_alias;
			$menuitem_new['menutype'] = $new_menutype_alias;

			//Create layout record
			//TODO: Add Menu First

			//Alias,New ID, Old ID
			$menus[] = [$menuitem_alias, $menuItemId, 0];
			$menuItemId = ImportTables::insertRecords('#__menu', $menuitem_new);
			$menuitem_new['id'] = $menuItemId;
		}
		return $menuItemId;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function menuGetMaxRgt()
	{
		$whereClause = new MySQLWhereClause();
		$rows = database::loadAssocList('#__menu', ['rgt'], $whereClause, 'rgt', null, 1);

		if (count($rows) == 0)
			return 0;

		return $rows[0]['rgt'];
	}

	protected static function getMenuParentID($oldparentid, $menus)
	{
		foreach ($menus as $menu) {
			if ($menu[2] == $oldparentid)  // 2 - old id
				return $menu[1];        // 1 - new id
		}
		return 1;// Root
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processRecords(string $tableName, array $records): bool
	{
		$ct = new CT([], true);
		$ct->getTable($tableName);
		if ($ct->Table === null) {
			return false;    // Exit if table to connect with not found
		}

		foreach ($records as $record) {
			$record_old = ImportTables::getRecordByField($ct->Table->realtablename, $ct->Table->realidfieldname, $record[$ct->Table->realidfieldname], false);//get record by id

			if ($record_old != 0)
				ImportTables::updateRecords($ct->Table->realtablename, $record, $record_old, false, array(), true);//update single existing record
			else
				ImportTables::insertRecords($ct->Table->realtablename, $record, array(), true, $ct->Table->fieldPrefix, $ct->Table->tableid);//insert single new record
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function addMenu($title, $alias, $link, $menuTypeOrTitle, $extension_name, $access_, $menuParamsString, $home = 0): bool
	{
		$menuType = CTMiscHelper::slugify($menuTypeOrTitle);
		ImportTables::addMenutypeIfNotExist($menuType, $menuTypeOrTitle);

		if ((int)$access_ == 0) {
			//Try to find id by name

			$access_row = ImportTables::getRecordByField('#__viewlevels', 'title', $access_, false);
			if (!is_array($access_row) or count($access_row) == 0) {
				common::enqueueMessage('Cannot find access level "' . $access_ . '"');
				return false;
			}
			$access = $access_row['id'];
		} else
			$access = $access_;

		if ($access == 0) {
			common::enqueueMessage('Cannot find access level "' . $access_ . '", found 0.');
			return false;
		}

		$menuitem_new = array();
		$menuitem_new['title'] = $title;
		$menuitem_new['link'] = $link;
		$menuitem_new['type'] = ($extension_name == 'url' ? 'url' : 'component');

		$menuitem_new['published'] = 1;
		$menuitem_new['access'] = $access;
		$menuitem_new['language'] = '*';
		$menuitem_new['parent_id'] = 1; //TODO: Add menu parent functionality
		$menuitem_new['menutype'] = $menuType;

		//if($home==1)
		//OxfordSMSComponents::setAllMenuitemAsNotHome();

		$menuitem_new['home'] = $home;
		$menuitem_new['level'] = 1;
		$menuitem_new['lft'] = null;
		$menuitem_new['rgt'] = null;
		$menuitem_new['id'] = null;
		$menuitem_new['params'] = $menuParamsString;

		if ($extension_name == 'url')
			$component_id = 0;
		else {
			$component = ImportTables::getRecordByField('#__extensions', 'element', $extension_name, false);
			$component_id = (int)$component['extension_id'];
		}

		$menuitem_new['component_id'] = $component_id;
		$menuitem_new['alias'] = $alias;

		$menuitem_old = ImportTables::getRecordByField('#__menu', 'alias', $alias, false);

		if (is_array($menuitem_old) and count($menuitem_old) > 0) {
			common::enqueueMessage('Updating external menu Item "' . $alias . '".', 'notice');

			$menuitem_new['parent_id'] = 1; //TODO: Add menu parent functionality
			$menuitem_new['level'] = 1;
			$lft = ImportTables::menuGetMaxRgt() + 1;
			$menuitem_new['lft'] = $lft;//this is to have separate menu branch
			$menuitem_new['rgt'] = $lft + 1;

			ImportTables::updateRecords('#__menu', $menuitem_new, $menuitem_old, false);
		} else {
			common::enqueueMessage('Adding external menu Item "' . $alias . '".', 'notice');
			ImportTables::rebuildMenuTree($menuitem_new);//,'oxford-sms','tos-shared-files',$component_id);
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function addMenutypeIfNotExist($menutype, $menutype_title): void
	{
		$menutype_old = ImportTables::getRecordByField('#__menu_types', 'menutype', $menutype, false);

		if (!is_array($menutype_old) or count($menutype_old) == 0) {
			//Create new menu type
			$data = [];
			$data['asset_id=0'];
			$data['menutype'] = $menutype;
			$data['title'] = $menutype_title;
			$data['description'] = 'Menu Type created by CustomTables';
		}
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	public static function rebuildMenuTree($menuitem_new)
	{
		// https://joomla.stackexchange.com/questions/5104/programmatically-add-menu-item-in-component
		// sorts out the lft rgt issue

		$menuTable = JTableNested::getInstance('Menu');

		// this item is at the root so the parent id needs to be 1
		$parent_id = 1;
		$menuTable->setLocation($parent_id, 'last-child');

		// save is the shortcut method for bind, check and store
		$menuTable->save($menuitem_new);
		if ($menuTable->getError() != '') {
			common::enqueueMessage($menuTable->getError());
		}
		return $menuTable->id;
	}
}
