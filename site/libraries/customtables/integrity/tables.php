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

defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\IntegrityChecks;
use CustomTables\MySQLWhereClause;
use Exception;

class IntegrityTables extends IntegrityChecks
{
    /**
     * @throws Exception
     * @since 3.2.2
     */
    public static function checkTables(&$ct): array
    {
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('a.published', 1);
        $tables = database::loadAssocList('#__customtables_tables AS a', ['id', 'tablename', 'tabletitle', 'customtablename'], $whereClause, 'tablename', 'asc');

        IntegrityTables::checkIfTablesExists($tables);
        $result = [];

        foreach ($tables as $table) {

            //Check if table exists
            $rows = database::getTableStatus($table['tablename']);
            $tableExists = !(count($rows) == 0);

            if ($tableExists) {

                $ct->getTable($table['id']);
                $link = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=databasecheck&tableid=' . $table['id'];
                $content = IntegrityFields::checkFields($ct, $link);

                $zeroId = IntegrityTables::getZeroRecordID($ct->Table->realtablename, $ct->Table->realidfieldname);

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

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected static function checkIfTablesExists(array $tables_rows)
    {
        $dbPrefix = database::getDBPrefix();

        foreach ($tables_rows as $row) {
            if (!TableHelper::checkIfTableExists($dbPrefix . 'customtables_table_' . $row['tablename'])) {
                if ($row['tablename'] === null)
                    throw new Exception('checkIfTablesExists: tablename value cannot be null');

                if ($row['customtablename'] === null or $row['customtablename'] == '') {
                    if (TableHelper::createTableIfNotExists($dbPrefix, $row['tablename'], $row['tabletitle'], $row['customtablename'] ?? '')) {
                        common::enqueueMessage('Table "' . $row['tabletitle'] . '" created.', 'notice');
                    }
                }
            }
        }
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected static function getZeroRecordID($realtablename, $realidfieldname)
    {
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition($realidfieldname, 0);

        $rows = database::loadAssocList($realtablename, ['COUNT_ROWS'], $whereClause, null, null, 1);
        $row = $rows[0];

        return $row['record_count'];
    }
}