<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @subpackage integrity/tables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\Integrity;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\database;
use Exception;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;

use \ESTables;

class IntegrityTables extends \CustomTables\IntegrityChecks
{
    public static function checkTables(&$ct)
    {
        $tables = IntegrityTables::getTables();
        IntegrityTables::checkIfTablesExists($tables);
        $result = [];

        foreach ($tables as $table) {

            $table['tablename'];
            //Check if table exists
            $query_check_table = 'SHOW TABLES LIKE ' . database::quote(database::realTableName($table['tablename']));
            $rows = database::loadObjectList($query_check_table);
            $tableExists = !(count($rows) == 0);

            if ($tableExists) {

                $ct->setTable($table, null, false);
                $link = Uri::root() . 'administrator/index.php?option=com_customtables&view=databasecheck&tableid=' . $table['id'];
                $content = IntegrityFields::checkFields($ct, $link);

                if ($ct->Env->advancedTagProcessor)
                    IntegrityOptions::checkOptions($ct);

                $zeroId = IntegrityTables::getZeroRecordID($table['realtablename'], $table['realidfieldname']);

                if ($content != '' or $zeroId > 0) {
                    if (!str_contains($link, '?'))
                        $link .= '?';
                    else
                        $link .= '&';

                    $result[] = '<p><span style="font-size:1.3em;">' . $table['tabletitle'] . '</span><br/><span style="color:gray;">' . $table['realtablename'] . '</span>'
                        . ' <a href="' . $link . 'task=fixfieldtype&fieldname=all_fields">Fix all fields</a>'
                        . '</p>'
                        . $content
                        . ($zeroId > 0 ? '<p style="font-size:1.3em;color:red;">Records with ID = 0 found. Please fix it manually.</p>' : '');
                }
            }
        }

        return $result;
    }

    protected static function getTables(): ?array
    {
        // Create a new query object.
        $query = self::getTablesQuery();

        try {
            return database::loadAssocList($query);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            //return null;
        }

        $query = self::getTablesQuery(true);
        try {
            return database::loadAssocList($query);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return null;
        }
    }

    protected static function getTablesQuery(bool $simple = false): string
    {
        $selects = array();

        if ($simple) {
            $selects[] = 'id';
            $selects[] = 'tablename';
        } else {
            $categoryname = '(SELECT categoryname FROM #__customtables_categories AS categories WHERE categories.id=a.tablecategory LIMIT 1)';
            $fieldcount = '(SELECT COUNT(fields.id) FROM #__customtables_fields AS fields WHERE fields.tableid=a.id AND fields.published=1 LIMIT 1)';

            $selects[] = ESTables::getTableRowSelects();
            $selects[] = $categoryname . ' AS categoryname';
            $selects[] = $fieldcount . ' AS fieldcount';
        }

        // Add the list ordering clause.
        $orderCol = 'tablename';
        $orderDirection = 'asc';

        return 'SELECT ' . implode(',', $selects) . ' FROM ' . database::quoteName('#__customtables_tables') . ' AS a WHERE a.published = 1 ORDER BY '
            . database::quoteName($orderCol) . ' ' . $orderDirection;
    }

    protected static function checkIfTablesExists($tables_rows)
    {
        $dbPrefix = database::getDBPrefix();

        foreach ($tables_rows as $row) {
            if (!ESTables::checkIfTableExists($dbPrefix . 'customtables_table_' . $row['tablename'])) {
                $database = database::getDataBaseName();

                if ($row['customtablename'] === null or $row['customtablename'] == '') {
                    if (ESTables::createTableIfNotExists($database, $dbPrefix, $row['tablename'], $row['tabletitle'], $row['customtablename'])) {
                        Factory::getApplication()->enqueueMessage('Table "' . $row['tabletitle'] . '" created.', 'notice');
                    }
                }
            }
        }
    }

    protected static function getZeroRecordID($realtablename, $realidfieldname)
    {
        $query = 'SELECT COUNT(' . $realidfieldname . ') AS cd_zeroIdRecords FROM ' . database::quoteName($realtablename) . ' AS a'
            . ' WHERE ' . $realidfieldname . '=0 LIMIT 1';

        $rows = database::loadAssocList($query);
        $row = $rows[0];

        return $row['cd_zeroIdRecords'];
    }
}