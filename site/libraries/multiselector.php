<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\database;
use CustomTables\DataTypes\Tree;
use Joomla\CMS\Factory;

class ESMultiSelector
{
    function getMultiString($parent, $prefix)
    {
        $parentid = Tree::getOptionIdFull($parent);
        $a = $this->getMultiSelector($parentid, $parent, $prefix);
        return implode(',', $a);
    }

    function getMultiSelector($parentid, $parentname, $langpostfix, $ObjectName, &$ItemList, &$count, $field_value, $place_holder = '')
    {
        $result = '';
        $rows = $this->getList($parentid, $langpostfix);

        if (count($rows) < 1)
            return "";

        $result .= '<ul>';
        $list_ids = array();

        $count = count($rows);
        foreach ($rows as $row) {

            $list_ids[] = $row->id;

            $temp_Ids = "";
            $count_child = 0;

            if (strlen($parentname) == 0)
                $optionnamefull = $row->optionname;
            else
                $optionnamefull = $parentname . '.' . $row->optionname;


            $ChildHTML = $this->getMultiSelector($row->id, $optionnamefull, $langpostfix, $ObjectName, $temp_Ids, $count_child, $field_value, $place_holder);


            if ($count_child > 0) {
                if ((!str_contains($field_value, $optionnamefull . '.'))) {
                    $result .= '<li><input type="checkbox" id="' . $ObjectName . '_' . $row->id . '" name="' . $ObjectName . '_' . $row->id . '" onClick=\'CustomTablesChildClick("' . $ObjectName . '_' . $row->id . '", "div' . $ObjectName . '_' . $row->id . '")\'  >';
                    $result .= '<b>' . $row->title . '</b> (' . $count_child . ')';
                    $result .= '<div name="div' . $ObjectName . '_' . $row->id . '" id="div' . $ObjectName . '_' . $row->id . '" style="display: none;">';
                } else {
                    $result .= '<li><input type="checkbox" id="' . $ObjectName . '_' . $row->id . '" name="' . $ObjectName . '_' . $row->id . '" onClick=\'CustomTablesChildClick("' . $ObjectName . '_' . $row->id . '", "div' . $ObjectName . '_' . $row->id . '")\' checked="checked" >';
                    $result .= '<b>' . $row->title . '</b> (' . $count_child . ')';
                    $result .= '<div name="div' . $ObjectName . '_' . $row->id . '" id="div' . $ObjectName . '_' . $row->id . '" style="display: block;">';
                }

                if ($count_child > 1) {
                    $result .= '
				<div style="margin-left:100px">
				<a href=\'javascript:ESCheckAll("' . $ObjectName . '",Array(' . $temp_Ids . '))\'>' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CHECK_ALL') . '</a>&nbsp;&nbsp;
				<a href=\'javascript:ESUncheckAll("' . $ObjectName . '",Array(' . $temp_Ids . '))\'>' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNCHECK_ALL') . '</a>
				</div>';
                }

                $result .= $ChildHTML . '</div></li>';

            } else {


                if ((strpos($field_value, $parentname . '.' . $row->optionname . '.') === false))
                    $ItemSelected = false;
                else
                    $ItemSelected = true;

                $result .= '<li><input type="checkbox" name="' . $ObjectName . '_' . $row->id . '" id="' . $ObjectName . '_' . $row->id . '" ' . ($ItemSelected ? ' checked="checked" ' : '') . '> ' . $row->title . '</li>';
            }


        }
        $result .= '</ul>';
        $ItemList = '"' . implode('","', $list_ids) . '"';
        return $result;
    }

    function getList($parentId, $langpostfix)
    {
        $query = 'SELECT id, optionname, title' . $langpostfix . ' AS title FROM #__customtables_options WHERE parentid=' . (int)$parentId;
        $query .= ' ORDER BY ordering, title';
        return database::loadObjectList($query);
    }
}
