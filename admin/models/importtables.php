<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage importtables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\FileUploader;
use CustomTables\ImportTables;
use CustomTables\MySQLWhereClause;

use Joomla\CMS\MVC\Model\ListModel;

class CustomTablesModelImportTables extends ListModel
{
    var CT $ct;

    function __construct()
    {
        require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'uploader.php');

        parent::__construct();
    }

    /**
     * @throws Exception
     * @since 3.3.4
     */
    function importTables(&$msg): bool
    {
        $fileId = common::inputGetCmd('fileid', '');
        $filename = FileUploader::getFileNameByID($fileId);
        $menuType = 'Custom Tables Import Menu';

        $importFields = common::inputGetInt('importfields', 0);
        $importLayouts = common::inputGetInt('importlayouts', 0);
        $importMenu = common::inputGetInt('importmenu', 0);

        $category = '';
        return ImportTables::processFile($filename, $menuType, $msg, $category, $importFields, $importLayouts, $importMenu);
    }

    /*
    function getColumns($line): array
    {
        $columns = explode(",", $line);
        if (count($columns) < 1) {
            echo 'incorrect field header<br/>';
            return array();
        }

        for ($i = 0; $i < count($columns); $i++) {
            $columns[$i] = trim($columns[$i]);
        }
        return $columns;
    }
    */

    /*
    function parseLine($allowedColumns, $fieldTypes, $line, &$maxId): array
    {
        $result = array();
        $values = CTMiscHelper::csv_explode(',', $line);
        $maxId++;
        $result[] = $maxId;                                // id

        $c = 0;
        for ($i = 0; $i < count($values); $i++) {
            if ($allowedColumns[$c]) {

                $fieldTypePair = explode(':', $fieldTypes[$c]);

                if ($fieldTypePair[0] == 'string' or $fieldTypePair[0] == 'multistring' or $fieldTypePair[0] == 'text' or $fieldTypePair[0] == 'multitext')
                    $result[] = '"' . $values[$i] . '"';

                elseif ($fieldTypePair[0] == 'email')
                    $result[] = '"' . $values[$i] . '"';

                elseif ($fieldTypePair[0] == 'url')
                    $result[] = '"' . $values[$i] . '"';

                elseif ($fieldTypePair[0] == 'float' or $fieldTypePair[0] == 'int')
                    $result[] = $values[$i];

                elseif ($fieldTypePair[0] == 'checkbox')
                    $result[] = $values[$i];

                elseif ($fieldTypePair[0] == 'date')
                    $result[] = '"' . $values[$i] . '"';

                elseif ($fieldTypePair[0] == 'radio')
                    $result[] = '"' . $values[$i] . '"';

                else
                    $result[] = '""';//type unsupported
            }
            $c++;
        }
        return $result;
    }
    */

    /**
     * @throws Exception
     * @since 3.2.2
     */
    /*
    function findMaxId($table): int
    {
        $whereClause = new MySQLWhereClause();

        //$query = ' SELECT id FROM #__customtables_table_' . $table . ' ORDER BY id DESC LIMIT 1';
        $maxIdRecords = database::loadObjectList('#__customtables_table_' . $table, ['ig'], $whereClause, 'id', 'DESC', 1);

        if (count($maxIdRecords) != 1)
            return -1;

        return $maxIdRecords[0]->id;
    }
    */

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function getLanguageByCODE($code): int
    {
        //Example: $code='en-GB';
        //$query = ' SELECT id FROM #__customtables_languages WHERE language="' . $code . '" LIMIT 1';

        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('language', $code);

        $rows = database::loadObjectList('#__customtables_languages', ['id'], $whereClause, null, null, 1);
        if (count($rows) != 1)
            return -1;

        return $rows[0]->id;
    }
}
