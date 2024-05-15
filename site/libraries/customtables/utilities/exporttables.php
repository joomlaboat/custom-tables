<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Uri\Uri;

class ExportTables
{
    //this function creates json(.txt) file that will include instruction to create selected tables and depended on menu items and layouts.
    //Records can be exported too, if it set in table parameters
    //file is created in /tmp folder or as set in $path parameter

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public static function export($table_ids, $path = 'tmp'): ?array
    {
        $link = '';
        $tables = array();
        $output = array();

        foreach ($table_ids as $table_id) {
            $whereClause = new MySQLWhereClause();
            $whereClause->addCondition('published', 1);
            $whereClause->addCondition('id', (int)$table_id);

            if (defined('_JEXEC'))
                $table_rows = database::loadAssocList('#__customtables_tables', ['*', 'CATEGORY_NAME'], $whereClause, null, null, 1);
            else
                $table_rows = database::loadAssocList('#__customtables_tables', ['*'], $whereClause, null, null, 1);

            //Add the table with dependencies to export array
            if (count($table_rows) == 1) {
                $tables[] = $table_rows[0]['tablename'];
                $output[] = ExportTables::processTable($table_rows[0]);
            }
        }

        //Save the array to file
        if (count($output) > 0) {
            //Prepare output string with data
            $output_str = '<customtablestableexport>' . common::ctJsonEncode($output);

            $tmp_path = CUSTOMTABLES_ABSPATH . $path . DIRECTORY_SEPARATOR;
            $filename = substr(implode('_', $tables), 0, 128);

            $a = '';
            $i = 0;
            while (1) {
                if (!file_exists($tmp_path . $filename . $a . '.txt')) {
                    $filename_available = $filename . $a . '.txt';
                    break;
                }
                $i++;
                $a = $i . '';
            }

            //Save file
            $link = str_replace(DIRECTORY_SEPARATOR, '/', common::UriRoot());

            if ($link[strlen($link) - 1] != '/' and $path[0] != '/')
                $link .= '/';

            $link .= $path . '/' . $filename_available;

            $msg = common::saveString2File($tmp_path . $filename_available, $output_str);
            if ($msg !== null) {
                common::enqueueMessage($tmp_path . $filename_available . ': ' . $msg);
                return null;
            }
            return ['link' => $link, 'filename' => $filename_available];
        }
        return null;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected static function processTable($table): array
    {
        //get fields
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('published', 1);
        $whereClause->addCondition('tableid', (int)$table['id']);

        $fields = database::loadAssocList('#__customtables_fields', ['*'], $whereClause, null, null);

        //get layouts
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('published', 1);
        $whereClause->addCondition('tableid', (int)$table['id']);

        $layouts = database::loadAssocList('#__customtables_layouts', ['*'], $whereClause, null, null);

        //Get depended menu items
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('published', 1);

        $serverType = database::getServerType();
        if ($serverType == 'postgresql') {
            $whereClause->addCondition('POSITION("index.php?option=com_customtables&view=" IN link)', 0, '>');
            $whereClause->addCondition('POSITION("establename":"' . $table['tablename'] . '" IN params)', 0, '>');
        } else {
            $whereClause->addCondition('link', 'index.php?option=com_customtables&view=', 'INSTR');
            $whereClause->addCondition('params', '"establename":"' . $table['tablename'] . '"', 'INSTR');
        }

        $menu = database::loadAssocList('#__menu', ['*'], $whereClause, null, null);

        //Get depended on records
        if (intval($table['allowimportcontent']) == 1) {
            $whereClause = new MySQLWhereClause();
            $whereClause->addCondition('published', 1);

            $records = database::loadAssocList('#__customtables_table_' . $table['tablename'], ['*'], $whereClause, null, null);
        } else
            $records = null;

        return ['table' => $table, 'fields' => $fields, 'layouts' => $layouts, 'records' => $records, 'menu' => $menu];
    }
}