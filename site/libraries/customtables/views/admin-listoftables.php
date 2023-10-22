<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
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
        $fieldcount = '(SELECT COUNT(fields.id) FROM #__customtables_fields AS fields WHERE fields.tableid=a.id AND fields.published=1 LIMIT 1)';
        $selects = array();
        $selects[] = ESTables::getTableRowSelects();

        if (defined('_JEXEC')) {
            $categoryname = '(SELECT categoryname FROM #__customtables_categories AS categories WHERE categories.id=a.tablecategory LIMIT 1)';
            $selects[] = $categoryname . ' AS categoryname';
        }
        $selects[] = $fieldcount . ' AS fieldcount';

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

        if (isset($table_row->tablename) and (!isset($table_row->customtablename) or $table_row->customtablename === null)) // do not delete third-party tables
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

    function save($tableId): array
    {
        if (defined('WPINC')) {
            check_admin_referer('create-table', '_wpnonce_create-table');

            if (!current_user_can('install_plugins')) {
                wp_die(
                    '<h1>' . __('You need a higher level of permission.') . '</h1>' .
                    '<p>' . __('Sorry, you are not allowed to create custom tables.') . '</p>',
                    403
                );
            }
        }

        $database = database::getDataBaseName();
        $dbPrefix = database::getDBPrefix();
        $moreThanOneLanguage = false;
        $fields = Fields::getListOfExistingFields('#__customtables_tables', false);

        $sets = [];
        if (function_exists("transliterator_transliterate")) {
            //$tablename = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", common::inputPost('tablename'));
            $tablename = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", common::inputGetString('tablename'));

        } else {
            $tablename = common::inputGetString('tablename');
            //$tablename = common::inputPost('tablename');
        }

        $tablename = strtolower(trim(preg_replace("/\W/", "", $tablename)));

        $tableid = 0;//(int)$data['id'];
        $tableTitle = null;
        $sets [] = 'tablename=' . database::quote($tablename);
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

        //If it's a new table, check if field name is unique or add number "_1" if it's not.
        if ($tableid == 0)
            $tablename = ESTables::checkTableName($tablename);

        if ($tableid != 0 and (string)common::inputPostString('customtablename') == '')//do not rename real table if it's a third-party table - not part of the Custom Tables
        {
            ESTables::renameTableIfNeeded($tableid, $database, $dbPrefix, $tablename);
        }

        $old_tablename = '';
        // Alter the unique field for save as copy
        if (common::inputGetCmd('task') === 'save2copy') {
            $originalTableId = common::inputGetInt('originaltableid', 0);
            if ($originalTableId != 0) {
                $old_tablename = ESTables::getTableName($originalTableId);

                if ($old_tablename == $tablename)
                    $tablename = 'copy_of_' . $tablename;

                while (ESTables::getTableID($tablename) != 0)
                    $tablename = 'copy_of_' . $tablename;
            }
        }

        $messages = array();
        $customtablename = common::inputGetString('customtablename');

        try {
            if ($tableId == 0)
                $tableId = database::insertSets('#__customtables_tables', $sets);
            else
                database::updateSets('#__customtables_tables', $sets, ['id=' . $tableId]);

        } catch (Exception $e) {
            return [$e->getMessage()];
        }

        if ($customtablename == '-new-') {
            $customtablename = $tablename;
            ESTables::createTableIfNotExists($database, $dbPrefix, $tablename, $tableTitle, $customtablename);
            return ['New third-party table created.'];

        } else {
            if ($tableId !== null) {
                $originalTableId = common::inputGetInt('originaltableid', 0);

                if ($originalTableId != 0 and $old_tablename != '') {
                    ESTables::copyTable($this->ct, $originalTableId, $tablename, $old_tablename, $customtablename);
                    $messages[] = __('Table copied.', 'customtables');
                }

                ESTables::createTableIfNotExists($database, $dbPrefix, $tablename, $tableTitle, $customtablename);
                $messages[] = __('Table created.', 'customtables');

                //Add fields if it's a third-party table and no fields added yet.
                if ($customtablename !== null and $customtablename != '') {
                    ESTables::addThirdPartyTableFieldsIfNeeded($database, $tablename, $customtablename);
                    $messages[] = __('Third-party fields added.', 'customtables');
                }
            }
        }
        return $messages;
    }
}