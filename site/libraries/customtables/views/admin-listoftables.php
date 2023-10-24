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
use ESTables;
use Exception;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class ListOfTables
{
    var CT $ct;

    function __construct(CT $ct)
    {
        $this->ct = $ct;
    }

    public static function getNumberOfRecords($realtablename, $realIdField): int
    {
        $query = 'SELECT COUNT(' . $realIdField . ') AS count FROM ' . $realtablename . ' LIMIT 1';

        try {
            $rows = database::loadObjectList($query);
        } catch (Exception $e) {
            if (defined('_JEXEC')) {
                echo $e->getMessage();
                $app = Factory::getApplication();
                $app->enqueueMessage('Table "' . $realtablename . '" - ' . $e->getMessage(), 'error');
            }
            return 0;
        }
        return $rows[0]->count;
    }

    function getItems($published = null, $search = null, $category = null, $orderCol = null, $orderDirection = null, $limit = 0, $start = 0): array
    {
        $query = $this->getListQuery($published, $search, $category, $orderCol, $orderDirection, $limit, $start);
        return database::loadObjectList($query);
    }

    function getListQuery($published = null, $search = null, $category = null, $orderCol = null, $orderDirection = null, $limit = 0, $start = 0): string
    {
        $fieldCount = '(SELECT COUNT(fields.id) FROM #__customtables_fields AS fields WHERE fields.tableid=a.id AND (fields.published=0 or fields.published=1) LIMIT 1)';
        $selects = array();
        $selects[] = ESTables::getTableRowSelects();

        if (defined('_JEXEC')) {
            $categoryName = '(SELECT categoryname FROM #__customtables_categories AS categories WHERE categories.id=a.tablecategory LIMIT 1)';
            $selects[] = $categoryName . ' AS categoryname';
        }
        $selects[] = $fieldCount . ' AS fieldcount';

        $query = 'SELECT ' . implode(',', $selects) . ' FROM ' . database::quoteName('#__customtables_tables') . ' AS a';
        $where = [];

        // Filter by published state
        if (is_numeric($published))
            $where [] = 'a.published = ' . (int)$published;
        elseif ($published === null or $published === '')
            $where [] = '(a.published = 0 OR a.published = 1)';

        // Filter by search.
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $where [] = 'a.id = ' . (int)substr($search, 3);
            } else {
                $search = database::quote('%' . $search . '%');
                $where [] = '(a.tablename LIKE ' . $search . ')';
            }
        }

        // Filter by Tableid.
        if ($category !== null and $category != '' and (int)$category != 0) {
            $where [] = 'a.tablecategory = ' . database::quote((int)$category);
        }

        $query .= ' WHERE ' . implode(' AND ', $where);

        // Add the list ordering clause.
        if ($orderCol != '')
            $query .= ' ORDER BY ' . database::quoteName($orderCol) . ' ' . $orderDirection;

        if ($limit != 0)
            $query .= ' LIMIT ' . $limit;

        if ($start != 0)
            $query .= ' OFFSET ' . $start;

        return $query;
    }

    function deleteTable(int $tableId): bool
    {
        $table_row = ESTables::getTableRowByID($tableId);

        if (isset($table_row->tablename) and (!isset($table_row->customtablename))) // do not delete third-party tables
        {
            $realtablename = database::getDBPrefix() . 'customtables_table_' . $table_row->tablename; //not available for custom tablenames
            $serverType = database::getServerType();
            if ($serverType == 'postgresql')
                $query = 'DROP TABLE IF EXISTS ' . $realtablename;
            else
                $query = 'DROP TABLE IF EXISTS ' . database::quoteName($realtablename);

            database::setQuery($query);
            $serverType = database::getServerType();

            if ($serverType == 'postgresql') {
                $query = 'DROP SEQUENCE IF EXISTS ' . $realtablename . '_seq CASCADE';
                database::setQuery($query);
            }
        }
        database::setQuery('DELETE FROM #__customtables_tables WHERE id=' . $tableId);

        Fields::deleteTableLessFields();
        return true;
    }

    function save(?int $tableId): ?array
    {
        // Check if running in WordPress context
        if (defined('WPINC')) {
            check_admin_referer('create-table', '_wpnonce_create-table');

            // Check user capabilities
            if (!current_user_can('install_plugins')) {
                wp_die(
                    '<h1>' . __('You need a higher level of permission.') . '</h1>' .
                    '<p>' . __('Sorry, you are not allowed to create custom tables.') . '</p>',
                    403
                );
            }
        }


        // Get database name and prefix
        $database = database::getDataBaseName();
        $dbPrefix = database::getDBPrefix();

        // Initialize variables
        $moreThanOneLanguage = false;
        $fields = Fields::getListOfExistingFields('#__customtables_tables', false);
        $sets = [];
        $tableTitle = null;

        // Process table name
        if (function_exists("transliterator_transliterate"))
            $newTableName = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", common::inputGetString('tablename'));
        else
            $newTableName = common::inputGetString('tablename');

        $newTableName = strtolower(trim(preg_replace("/\W/", "", $newTableName)));

        // Save as Copy
        $old_tablename = '';
        if (common::inputGetCmd('task') === 'save2copy') {
            $originalTableId = common::inputGetInt('originaltableid');
            if ($originalTableId !== null) {
                $old_tablename = ESTables::getTableName($originalTableId);

                // Handle copy table name
                $copyTableName = $newTableName;
                if ($old_tablename == $newTableName) {
                    $copyTableName = 'copy_of_' . $newTableName;
                }

                while (ESTables::getTableID($newTableName) != 0) {
                    $copyTableName = 'copy_of_' . $newTableName;
                }

                $tableId = null;
                $newTableName = $copyTableName;
            }
        }

        // Process multilingual fields
        foreach ($this->ct->Languages->LanguageList as $lang) {
            $id_title = 'tabletitle';
            $id_desc = 'description';
            if ($moreThanOneLanguage) {
                $id_title .= '_' . $lang->sef;
                $id_desc .= '_' . $lang->sef;
            } else {
                $tableTitle = common::inputGetString($id_title);
            }

            if (!in_array($id_title, $fields)) {
                Fields::addLanguageField('#__customtables_tables', $id_title, $id_title, 'null');
            }

            if (!in_array($id_desc, $fields))
                Fields::addLanguageField('#__customtables_tables', $id_desc, $id_desc, 'null');

            $tableTitleValue = common::inputGetString($id_title);
            if ($tableTitleValue !== null)
                $sets [] = $id_title . '=' . database::quote($tableTitleValue);

            $tableDescription = common::inputGetString($id_desc);
            if ($tableDescription !== null)
                $sets [] = $id_desc . '=' . database::quote($tableDescription);
            $moreThanOneLanguage = true; //More than one language installed
        }

        // If it's a new table, check if field name is unique or add number "_1" if it's not.
        if ($tableId === null) {
            $already_exists = ESTables::getTableID($newTableName);
            if ($already_exists == 0) {
                $sets [] = 'tablename=' . database::quote($newTableName);
            } else {
                return null; //Abort if the table with this name already exists.
            }

            try {
                $tableId = database::insertSets('#__customtables_tables', $sets);
            } catch (Exception $e) {
                return [$e->getMessage()];
            }

        } else {

            //Case: Table renamed, check if the new name is available.
            $this->ct->getTable($tableId);
            if ($newTableName != $this->ct->Table->tablename) {
                $already_exists = ESTables::getTableID($newTableName);
                if ($already_exists != 0)
                    return null; //Abort if the table with this name already exists.
            }

            if (common::inputPostString('customtablename') == '')//do not rename real table if it's a third-party table - not part of the Custom Tables
            {
                //This function will find the old Table Name of existing table and rename MySQL table.
                ESTables::renameTableIfNeeded($tableId, $database, $dbPrefix, $newTableName);
                $sets [] = 'tablename=' . database::quote($newTableName);
            }

            try {
                database::updateSets('#__customtables_tables', $sets, ['id=' . $tableId]);
            } catch (Exception $e) {
                return [$e->getMessage()];
            }
        }

        //Create MySQLTable
        $messages = array();
        $customTableName = common::inputGetString('customtablename');
        if ($customTableName == '-new-') {
            // Case: Creating a new third-party table
            $customTableName = $newTableName;
            ESTables::createTableIfNotExists($database, $dbPrefix, $newTableName, $tableTitle, $customTableName);
            $messages[] = ['New third-party table created.'];

            //Add fields if it's a third-party table and no fields added yet.
            ESTables::addThirdPartyTableFieldsIfNeeded($database, $newTableName, $customTableName);
            $messages[] = __('Third-party fields added.', 'customtables');
        } else {
            // Case: Updating an existing table or creating a new custom table
            $originalTableId = common::inputGetInt('originaltableid', 0);

            if ($originalTableId != 0 and $old_tablename != '') {
                // Copying an existing table
                ESTables::copyTable($this->ct, $originalTableId, $newTableName, $old_tablename, $customTableName);
                $messages[] = __('Table copied.', 'customtables');
            } else {
                // Creating a new custom table (without copying)
                ESTables::createTableIfNotExists($database, $dbPrefix, $newTableName, $tableTitle, $customTableName);
                $messages[] = __('Table created.', 'customtables');
            }
        }
        return $messages;
    }
}