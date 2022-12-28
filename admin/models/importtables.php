<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage importtables.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\DataTypes\Tree;
use CustomTables\ImportTables;
use Joomla\CMS\Factory;

// Import Joomla! libraries
jimport('joomla.application.component.model');

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'uploader.php');

class CustomTablesModelImportTables extends JModelList
{
    var CT $ct;

    function __construct()
    {
        parent::__construct();
    }

    function importTables(&$msg): bool
    {
        $jinput = Factory::getApplication()->input;

        $fileId = $jinput->getCmd('fileid', '');

        $filename = ESFileUploader::getFileNameByID($fileId);
        $menuType = 'Custom Tables Import Menu';

        $importFields = $jinput->getInt('importfields', 0);
        $importLayouts = $jinput->getInt('importlayouts', 0);
        $importMenu = $jinput->getInt('importmenu', 0);

        $category = '';
        return ImportTables::processFile($filename, $menuType, $msg, $category, $importFields, $importLayouts, $importMenu);
    }

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

    function parseLine(&$columns, $allowedColumns, $fieldTypes, $line, &$maxId): array
    {
        $result = array();
        $values = JoomlaBasicMisc::csv_explode(',', $line, '"');
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

                elseif ($fieldTypePair[0] == 'customtables') {
                    //this function must add item if not found
                    $esValue = $this->getOptionListItem($fieldTypePair[1], $values[$i]);

                    $result[] = '"' . $esValue . '"';
                } else {
                    //type unsupported
                    $result[] = '""';
                }
            }
            $c++;
        }
        return $result;
    }

    function getOptionListItem($optionname, $optionTitle): string
    {
        $db = Factory::getDbo();
        $parentId = Tree::getOptionIdFull($optionname);
        $rows = Tree::getHeritage($parentId, $db->quoteName('title') . '=' . $db->quote($optionTitle), 1);

        if (count($rows) == 0) {
            //add item
            $newOptionName_original = strtolower(trim(preg_replace("/[^a-zA-Z\d]/", "", $optionTitle)));
            $newOptionName = $newOptionName_original;
            $n = 0;
            while (1) {
                $rows_check = Tree::getHeritage($parentId, $db->quoteName('optionname') . '=' . $db->quote($newOptionName), 1);
                if (count($rows_check)) {
                    $n++;
                    $newOptionName = $newOptionName_original . $n;
                } else
                    break;
            }

            $familyTree = Tree::getFamilyTreeByParentID($parentId) . '-';

            $db = Factory::getDBO();
            $query = 'INSERT #__customtables_options SET '
                . $db->quoteName('parentid') . '=' . $db->quote($parentId) . ', '
                . $db->quoteName('optionname') . '=' . $db->quote($newOptionName) . ', '
                . $db->quoteName('familytree') . '=' . $db->quote($familyTree) . ', '
                . $db->quoteName('title') . '=' . $db->quote($optionTitle);

            $db->setQuery($query);
            $db->execute();

            return ',' . $optionname . '.' . $newOptionName . '.,';
        } else {
            $row = $rows[0];
            return ',' . Tree::getFamilyTreeString($parentId, 1) . '.' . $row['optionname'] . '.,';
        }
    }

    function findMaxId($table): int
    {
        $db = Factory::getDBO();
        $query = ' SELECT id FROM #__customtables_table_' . $table . ' ORDER BY id DESC LIMIT 1';
        $db->setQuery($query);
        $maxIdRecords = $db->loadObjectList();

        if (count($maxIdRecords) != 1)
            return -1;

        return $maxIdRecords[0]->id;
    }

    function getLanguageByCODE($code): int
    {
        //Example: $code='en-GB';

        $db = Factory::getDBO();
        $query = ' SELECT id FROM #__customtables_languages WHERE language="' . $code . '" LIMIT 1';
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if (count($rows) != 1)
            return -1;

        return $rows[0]->id;
    }
}
