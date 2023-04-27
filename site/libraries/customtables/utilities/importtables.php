<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use Joomla\CMS\Factory;
use ESTables;
use JoomlaBasicMisc;
use JTableNested;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class ImportTables
{
    public static function processFile($filename, $menutype, &$msg, $category = '', $importfields = true, $importlayouts = true, $importmenu = true)
    {
        $ct = new CT;

        if (file_exists($filename)) {
            $data = file_get_contents($filename);
            if ($data == '') {
                $msg = 'Uploaded file "' . $filename . '" is empty.';
                return false;
            }

            return ImportTables::processContent($ct, $data, $menutype, $msg, $category, $importfields, $importlayouts, $importmenu);
        } else {
            $msg = 'Uploaded file "' . $filename . '" not found.';
            return false;
        }
    }

    public static function processContent(CT &$ct, $data, $menutype, &$msg, $category = '', $importfields = true, $importlayouts = true, $importmenu = true)
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
        return ImportTables::processData($ct, $JSON_data, $menutype, $msg, $category, $importfields, $importlayouts, $importmenu);
    }

    protected static function processData(CT &$ct, $jsondata, $menutype, &$msg, $category, $importfields, $importlayouts, $importmenu)
    {
        foreach ($jsondata as $table) {
            $tableid = ImportTables::processTable($table['table'], $msg, $category);

            if ($tableid != 0) {
                //Ok, table created or found and updated
                //Next: Add/Update Fields

                if ($importfields)
                    ImportTables::processFields($tableid, $table['table']['tablename'], $table['fields'], $msg);


                if ($importlayouts)
                    ImportTables::processLayouts($ct, $tableid, $table['layouts'], $msg);

                if ($importmenu)
                    ImportTables::processMenu($table['menu'], $menutype, $msg);

                $result = IntegrityChecks::check($ct, $check_core_tables = false, $check_custom_tables = true);

                if (isset($table['records']))
                    ImportTables::processRecords($table['table']['tablename'], $table['records'], $msg);
            } else {
                $msg = 'Could not Add or Update table "' . $table['table']['tablename'] . '"';
                Factory::getApplication()->enqueueMessage($msg, 'error');

                return false;
            }
        }
        return true;
    }

    protected static function processTable(&$table_new, &$msg, $categoryname)
    {
        //This function creates the table and returns table's id.
        //If table with same name already exists then existing table will be updated, and it's ID will be returned.

        $db = Factory::getDBO();
        $tablename = $table_new['tablename'];

        $table_old = ESTables::getTableRowByNameAssoc($tablename);

        if (is_array($table_old) and count($table_old) > 0) {
            $tableid = $table_old['id'];
            //table with the same name already exists
            //Lets update it
            ImportTables::updateRecords('tables', $table_new, $table_old, true, ['categoryname']);
        } else {
            //Create table record
            $tableid = ImportTables::insertRecords('tables', $table_new, true, ['categoryname']);
        }

        //Create mysql table
        $tabletitle = $table_new['tabletitle'] ?? $table_new['tabletitle_1'];

        $query = '
                CREATE TABLE IF NOT EXISTS #__customtables_table_' . $tablename . '
                (
                	id int(10) NOT NULL auto_increment,
                	published tinyint(1) DEFAULT "1",
                	PRIMARY KEY  (id)
                ) ENGINE=InnoDB COMMENT="' . $tabletitle . '" DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;
        ';

        $db->setQuery($query);
        $db->execute();

        ImportTables::updateTableCategory($tableid, $table_new, $categoryname);
        return $tableid;
    }

    public static function updateRecords(string $table, array $rows_new, array $rows_old, $addPrefix = true, array $exceptions = array(), bool $force_id = false, $save_checked_out = false): void
    {
        if ($addPrefix)
            $mySQLTableName = '#__customtables_' . $table;
        else
            $mySQLTableName = $table;

        $db = Factory::getDBO();
        $sets = array();
        $keys = array_keys($rows_new);

        $ignore_fields = ['asset_id', 'created_by', 'modified_by', 'version', 'hits', 'publish_up', 'publish_down'];

        if (!$save_checked_out) {
            $ignore_fields[] = 'checked_out';
            $ignore_fields[] = 'checked_out_time';
        }

        foreach ($keys as $key) {
            $type = null;

            if (!in_array($key, $ignore_fields)) {
                $fieldname = ImportTables::checkFieldName($key, $force_id, $exceptions);

                if ($fieldname != '' and Fields::checkIfFieldExists($mySQLTableName, $fieldname)) {
                    if (array_key_exists($key, $rows_new) and (!array_key_exists($key, $rows_old) or $rows_new[$key] != $rows_old[$key])) {
                        $sets[] = $fieldname . '=' . ImportTables::dbQuoteByType($rows_new[$key], $type);
                    }
                }
            }
        }

        if (count($sets) > 0) {
            $query = 'UPDATE ' . $mySQLTableName . ' SET ' . implode(', ', $sets) . ' WHERE id=' . (int)$rows_old['id'];
            $db->setQuery($query);
            $db->execute();
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

    protected static function dbQuoteByType($value, $type = null)
    {
        $db = Factory::getDBO();

        if ($type === null) {
            if ($value === null)
                return 'NULL';

            if (is_numeric($value)) {
                if (floor($value) != $value)
                    $type = 'float';
                else
                    $type = 'int';
            } else
                $type = 'string';
        }

        if ($type == '' or $type == 'string')
            return $db->Quote($value);

        if ($type == 'int')
            return (int)$value;

        if ($type == 'float')
            return (float)$value;

        return null;
    }

    public static function insertRecords(string $table, array $rows, bool $addPrefix = true, array $exceptions = array(), bool $force_id = false,
                                         string $add_field_prefix = '', array $field_conversion_map = array(), bool $save_checked_out = false): int
    {
        if ($addPrefix)
            $mysqlTableName = '#__customtables_' . $table;
        else
            $mysqlTableName = $table;

        $inserts = array();
        $keys = array_keys($rows);
        $ignore_fields = ['asset_id', 'created_by', 'modified_by', 'version', 'hits', 'publish_up', 'publish_down', 'checked_out_time'];

        if (!$save_checked_out) {
            $ignore_fields[] = 'checked_out';
            $ignore_fields[] = 'checked_out_time';
        }
        $core_fields = ['id', 'published'];

        foreach ($keys as $key) {
            $isOk = false;
            $type = null;

            if (isset($field_conversion_map[$key])) {
                $isOk = true;
                if (is_array($field_conversion_map[$key])) {
                    $fieldname = $field_conversion_map[$key]['name'];
                    $type = $field_conversion_map[$key]['type'];
                } else
                    $fieldname = $field_conversion_map[$key];
            } elseif (count($field_conversion_map) > 0 and in_array($key, $field_conversion_map)) {
                $isOk = true;
                if (in_array($key, $core_fields))
                    $fieldname = $key;
                else
                    $fieldname = $add_field_prefix . $key;
            } else {
                $fieldname = ImportTables::checkFieldName($key, $force_id, $exceptions);
                if ($fieldname != '') {
                    $isOk = true;
                    if (!in_array($fieldname, $core_fields))
                        $fieldname = $add_field_prefix . $fieldname;
                }
            }

            if ($isOk and !in_array($fieldname, $ignore_fields)) {
                if (!Fields::checkIfFieldExists($mysqlTableName, $fieldname)) {
                    //Add field
                    $isLanguageFieldName = Fields::isLanguageFieldName($fieldname);

                    if ($isLanguageFieldName) {
                        //Add language field
                        //Get non language field type
                        $nonLanguageFieldName = Fields::getLanguageLessFieldName($key);
                        $filedType = Fields::getFieldType($mysqlTableName, $nonLanguageFieldName);

                        if ($filedType != '') {
                            Fields::AddMySQLFieldNotExist($mysqlTableName, $key, $filedType, '');
                            if ($rows[$key] === null) {

                            }

                            $inserts[] = $fieldname . '=' . ImportTables::dbQuoteByType($rows[$key], $type);
                        }
                    }
                } else {
                    $inserts[] = $fieldname . '=' . ImportTables::dbQuoteByType($rows[$key], $type);
                }
            }
        }
        return ESTables::insertRecords($mysqlTableName, $inserts);
    }

    protected static function updateTableCategory($tableid, &$table_new, $categoryName)
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

        $db = Factory::getDBO();
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
                $inserts = array();
                $inserts[] = 'categoryname=' . $db->Quote($categoryName);

                $categoryId = ESTables::insertRecords('#__customtables_categories', $inserts);
            }
        }


        if ($categoryId != $categoryId_ or is_null($categoryId)) {
            //Update Category ID in table
            $mysqlTableName = '#__customtables_tables';

            $query = 'UPDATE ' . $mysqlTableName . ' SET tablecategory=' . (int)$categoryId . ' WHERE id=' . (int)$tableid;

            $db->setQuery($query);
            $db->execute();
        }
    }

    public static function getRecordByField($table, $fieldname, $value, $addPrefix = true)
    {
        if ($addPrefix)
            $mysqlTableName = '#__customtables_' . $table;
        else
            $mysqlTableName = $table;

        $db = Factory::getDBO();

        if (is_null($value))
            $query = 'SELECT * FROM ' . $mysqlTableName . ' WHERE ' . $fieldname . ' IS NULL';
        else
            $query = 'SELECT * FROM ' . $mysqlTableName . ' WHERE ' . $fieldname . '=' . $db->Quote($value);

        $db->setQuery($query);

        $rows = $db->loadAssocList();
        if (count($rows) == 0)
            return 0;

        return $rows[0];
    }

    protected static function processFields($tableid, $tableName, $fields, &$msg)
    {
        $ct = new CT;

        foreach ($fields as $field) {
            $fieldid = ImportTables::processField($ct, $tableid, $tableName, $field, $msg);
            if ($fieldid != 0) {
                //Good
            } else {
                $msg = 'Could not Add or Update field "' . $field['fieldname'] . '"';
                return false;
            }
        }
        return true;
    }

    protected static function processField(CT &$ct, $tableid, $tableName, &$field_new)
    {
        //This function creates the table field and returns field's id.
        //If field with same name already exists then existing field will be updated, and it's ID will be returned.

        $field_new['tableid'] = $tableid;//replace tableid
        $fieldName = $field_new['fieldname'];

        $field_old = Fields::getFieldAssocByName($fieldName, $tableid);
        if (is_array($field_old) and count($field_old) > 0) {
            $fieldid = $field_old['id'];
            ImportTables::updateRecords('fields', $field_new, $field_old);
        } else {
            //Create field record
            $fieldid = ImportTables::insertRecords('fields', $field_new);
            if ($fieldid != 0) {
                //Field added
                //Lets create mysql field
                $PureFieldType = Fields::getPureFieldType($field_new['type'], $field_new['typeparams']);
                Fields::addField($ct, '#__customtables_table_' . $tableName, $ct->Env->field_prefix . $fieldName,
                    $field_new['type'], $PureFieldType, $field_new['fieldtitle'], $field_new);
            }
        }
        return $fieldid;
    }

    protected static function processLayouts(CT &$ct, $tableid, $layouts, &$msg)
    {
        foreach ($layouts as $layout) {
            $layoutId = ImportTables::processLayout($ct, $tableid, $layout, $msg);
            if ($layoutId == 0) {
                $msg = 'Could not Add or Update layout "' . $layout['layoutname'] . '"';
                return false;
            }
        }
        return true;
    }

    protected static function processLayout(CT &$ct, $tableid, &$layout_new, &$msg)
    {
        //This function creates layout and returns its id.
        //If layout with same name already exists then existing layout will be updated, and it's ID will be returned.

        $layout_new['tableid'] = $tableid;//replace tableid
        $layoutname = $layout_new['layoutname'];

        $layout_old = ImportTables::getRecordByField('layouts', 'layoutname', $layoutname);
        $Layouts = new Layouts($ct);

        if (is_array($layout_old) and count($layout_old) > 0) {
            $layoutId = $layout_old['id'];
            ImportTables::updateRecords('layouts', $layout_new, $layout_old);
        } else {
            //Create layout record
            $layoutId = ImportTables::insertRecords('layouts', $layout_new);
        }
        $Layouts->storeAsFile($layout_new);
        return $layoutId;
    }

    protected static function processMenu($menu, $menutype, &$msg)
    {
        $menus = array();
        foreach ($menu as $menuitem) {
            $menuid = ImportTables::processMenuItem($menuitem, $menutype, $msg, $menus);
            if ($menuid != 0) {
                //All Good
            } else {
                $msg = 'Could not Add or Update menu item "' . $menuitem['title'] . '"';
                return false;
            }
        }
        return true;
    }

    protected static function processMenuItem(&$menuitem_new, $menutype, &$msg, &$menus)
    {
        //This function creates menuitem and returns its id.
        //If menuitem with same alias already exists then existing menuitem will be updated, and it's ID will be returned.

        $menuitem_alias = substr($menuitem_new['alias'], 0, 400);
        $menuItemId = 0;

        $component = ImportTables::getRecordByField('#__extensions', 'element', 'com_customtables', false);
        if (!is_array($component))
            return false;

        $component_id = (int)$component['extension_id'];
        $menuitem_new['component_id'] = (int)$component_id;

        if ($menutype != '' and $menutype != $menuitem_new['menutype'])
            $new_menuType = $menutype;
        else
            $new_menuType = $menuitem_new['menutype'];

        //Check NEW $menuitem_new['menutype']
        $new_menutype_alias = substr(JoomlaBasicMisc::slugify($new_menuType), 0, 24);
        $menutype_old = ImportTables::getRecordByField('#__menu_types', 'menutype', $new_menutype_alias, false);

        if (!is_array($menutype_old) or count($menutype_old) == 0) {
            //Create new menu type
            $db = Factory::getDBO();
            $inserts = array();
            $inserts[] = 'asset_id=0';
            $inserts[] = 'menutype=' . $db->Quote($new_menutype_alias);
            $inserts[] = 'title=' . $db->Quote($new_menuType);
            $inserts[] = 'description=' . $db->Quote('Menu Type created by CustomTables');
            $menu_types_id = ESTables::insertRecords('#__menu_types', $inserts);
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
            $menuitem_new['component_id'] = (int)$component_id;
            $menuitem_new['alias'] = $menuitem_alias;
            $menuitem_new['menytype'] = $new_menutype_alias;

            //Create layout record
            //TODO: Add Menu First

            //Alias,New ID, Old ID
            $menus[] = [$menuitem_alias, $menuItemId, 0];
            $menuItemId = ImportTables::insertRecords('#__menu', $menuitem_new, false);
            $menuitem_new['id'] = $menuItemId;
        }
        return $menuItemId;
    }

    public static function menuGetMaxRgt()
    {
        $db = Factory::getDBO();
        $query = 'SELECT rgt FROM #__menu ORDER BY rgt DESC LIMIT 1';

        $db->setQuery($query);

        $rows = $db->loadAssocList();
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

    protected static function processRecords($tableName, $records): bool
    {
        $mySQLTableName = '#__customtables_table_' . $tableName;

        foreach ($records as $record) {
            $record_old = ImportTables::getRecordByField($mySQLTableName, 'id', $record['id'], false);//get record by id

            if ($record_old != 0)
                ImportTables::updateRecords($mySQLTableName, $record, $record_old, false, array(), true);//update single existing record
            else
                ImportTables::insertRecords($mySQLTableName, $record, false, array(), true);//insert single new record
        }
        return true;
    }

    public static function addMenu($title, $alias, $link, $menuTypeOrTitle, $extension_name, $access_, $menuParamsString, $home = 0)
    {
        $menuType = JoomlaBasicMisc::slugify($menuTypeOrTitle);
        ImportTables::addMenutypeIfNotExist($menuType, $menuTypeOrTitle);

        if ((int)$access_ == 0) {
            //Try to find id by name

            $access_row = ImportTables::getRecordByField('#__viewlevels', 'title', $access_, false);
            if (!is_array($access_row) or count($access_row) == 0) {
                Factory::getApplication()->enqueueMessage('Cannot find access level "' . $access_ . '"', 'error');
                return false;
            }
            $access = $access_row['id'];
        } else
            $access = $access_;

        if ($access == 0) {
            Factory::getApplication()->enqueueMessage('Cannot find access level "' . $access_ . '", found 0.', 'error');
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
            Factory::getApplication()->enqueueMessage('Updating external menu Item "' . $alias . '".', 'notice');

            $menuitem_new['parent_id'] = 1; //TODO: Add menu parent functionality
            $menuitem_new['level'] = 1;
            $lft = ImportTables::menuGetMaxRgt() + 1;
            $menuitem_new['lft'] = $lft;//this is to have separate menu branch
            $menuitem_new['rgt'] = $lft + 1;

            ImportTables::updateRecords('#__menu', $menuitem_new, $menuitem_old, false);
        } else {
            Factory::getApplication()->enqueueMessage('Adding external menu Item "' . $alias . '".', 'notice');

            ImportTables::rebuildMenuTree($menuitem_new);//,'oxford-sms','tos-shared-files',$component_id);
        }

        return true;
    }

    public static function addMenutypeIfNotExist($menutype, $menutype_title)
    {
        $menutype_old = ImportTables::getRecordByField('#__menu_types', 'menutype', $menutype, false);

        if (!is_array($menutype_old) or count($menutype_old) == 0) {
            //Create new menu type
            $db = Factory::getDBO();
            $inserts = array();
            $inserts[] = 'asset_id=0';
            $inserts[] = 'menutype=' . $db->Quote($menutype);
            $inserts[] = 'title=' . $db->Quote($menutype_title);
            $inserts[] = 'description=' . $db->Quote('Menu Type created by CustomTables');
        }
    }

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
            Factory::getApplication()->enqueueMessage($menuTable->getError(), 'error');
        }

        return $menuTable->id;
    }
}
