<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\DataTypes;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Language\Text;
use \JoomlaBasicMisc;
use \Joomla\CMS\Factory;

class Tree
{
    public static function getChildren($optionid, $parentid, $level)
    {
        $db = Factory::getDBO();

        $result = array();

        $query = ' SELECT concat("' . str_repeat('- ', $level) . '", optionname) AS name, id FROM #__customtables_options WHERE id!=' . $optionid . ' ';
        $query .= ' AND parentid=' . $parentid;
        $query .= ' ORDER BY name';

        $db->setQuery($query);
        $rows = $db->loadObjectList();

        foreach ($rows as $item) {

            JoomlaBasicMisc::array_insert($result, array("id" => $item->id, "name" => $item->name), count($result));
            $children = Tree::getChildren($optionid, $item->id, $level + 1);
            if (count($children) > 0) {
                $result = array_merge($result, $children);
            }
        }
        return $result;
    }

    public static function getAllRootParents()
    {
        $db = Factory::getDBO();

        $query = "SELECT id, optionname FROM #__customtables_options WHERE parentid=0 ORDER BY optionname";
        $db->setQuery($query);
        $available_rootparents = $db->loadObjectList();
        JoomlaBasicMisc::array_insert($available_rootparents, array("id" => 0, "optionname" => Text::_('-Select Parent')), 0);
        return $available_rootparents;

    }

    public static function getMultyValueTitles($PropertyTypes, $langpostfix, $StartFrom, $Separator, array $list_of_params = [])
    {
        if (!str_contains($PropertyTypes, '.') and count($list_of_params) > 0)
            $PropertyTypes = ',' . $list_of_params[0] . '.' . $PropertyTypes . '.,';

        $RowPropertyTypes = explode(",", $PropertyTypes);

        $titles = array();
        foreach ($RowPropertyTypes as $type) {
            $a = trim($type);
            if (strlen($a) > 0) {
                $b = Tree::getOptionTitleFullMulti($a, $langpostfix, $StartFrom);
                $titles[] = implode($Separator, $b);
            }
        }
        return $titles;
    }

    /*
    public static function getMultyValueFinalTitles($PropertyTypes,$langpostfix,$StartFrom)
    {
        $RowPropertyTypes=explode(",", $PropertyTypes);

        $titles=array();
        foreach($RowPropertyTypes as $row)
        {
            $a=trim($row);
            if(strlen($a)>0)
            {
                $b=Tree::	getOptionTitleFullMulti($a,$langpostfix,$StartFrom);
                if(count($b)>0)
                    $titles[]=$b[count($b)-1];
            }
        }
        return $titles;
    }
    */


    public static function getOptionTitleFullMulti($optionname, $langpostfix, $StartFrom)
    {
        $names = explode(".", $optionname);
        $parentid = 0;

        $title = array();
        $i = 0;
        foreach ($names as $optionname) {
            if ($optionname == '')
                break;

            $a = "";
            $parentid = Tree::getOptionTitle($optionname, $parentid, $a, $langpostfix);
            if ($i >= $StartFrom)
                $title[] = $a;
            $i++;
        }

        return $title;
    }

    protected static function getOptionTitle($optionname, $parentid, &$title, $langpostfix)
    {
        // get database handle
        $db = Factory::getDBO();

        $query = 'SELECT id, title' . $langpostfix . ' AS title FROM #__customtables_options WHERE parentid=' . $parentid . ' AND optionname="' . $optionname . '" LIMIT 1';

        $db->setQuery($query);

        $rows = $db->loadObjectList();

        if (count($rows) != 1) {
            $title = "[no name]";
            return 0;
        }

        $title = $rows[0]->title;
        return $rows[0]->id;
    }

    public static function getOptionTitleFull($optionname, $langpostfix)
    {
        $names = explode(".", $optionname);
        $parentid = 0;

        $title = "";
        foreach ($names as $optionname) {
            $optionname = $optionname;
            if ($optionname == '')
                break;


            $parentid = Tree::getOptionTitle($optionname, $parentid, $title, $langpostfix);
        }

        return $title;
    }

    public static function CleanLink($newParams, $deleteWhat)
    {
        $i = 0;
        do {
            $npv = substr($newParams[$i], 0, strlen($deleteWhat));
            if (str_contains($npv, $deleteWhat)) {
                unset($newParams[$i]);
                $newParams = array_values($newParams);
                if (count($newParams) == 0) return $newParams;
                $i = 0;

            } else
                $i++;

        } while ($i < count($newParams));
        return $newParams;
    }

    public static function BuildULHtmlList(&$vlus, &$index, $langpostfix, $isFirstElement = true, $last = '')
    {
        $parent = 'topics';
        $parentId = Tree::getOptionIdFull($parent);
        $count = 0;
        $field_value = implode(',', $vlus);
        $ItemList = '';
        return Tree::getMultiSelector($parentId, $parent, $langpostfix, $ItemList, $count, $field_value);
    }

    //maybe not used
    public static function getOptionIdFull($optionname)
    {
        $names = explode(".", $optionname);
        $parentId = 0;

        foreach ($names as $name) {
            $parentId = Tree::getOptionId($name, $parentId);
        }

        return $parentId;
    }

    public static function getOptionId($optionname, $parentid)
    {
        // get database handle
        $db = Factory::getDBO();

        $query = 'SELECT id FROM #__customtables_options WHERE parentid=' . $parentid . ' AND optionname="' . $optionname . '" LIMIT 1';
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if (count($rows) != 1) return 0;

        return $rows[0]->id;
    }

    //Get Option ID
    public static function getMultiSelector($parentid, $parentname, $langpostfix, &$ItemList, &$count, $field_value)
    {
        $result = '';
        $rows = Tree::getList($parentid, $langpostfix);

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
                $optionNameFull = $row->optionname;
            else
                $optionNameFull = $parentname . '.' . $row->optionname;

            $ChildHTML = Tree::getMultiSelector($row->id, $optionNameFull, $langpostfix, $temp_Ids, $count_child, $field_value);

            if ($count_child > 0) {
                if ((!str_contains($field_value, $optionNameFull . '.'))) {

                } else {
                    if ($ChildHTML == '')
                        $result .= '<li class="esSelectedElement">';
                    else
                        $result .= '<li class="esElementParent">';

                    $result .= $row->title;
                    $result .= $ChildHTML . '</li>';
                }
            } else {
                if ((!str_contains($field_value, $parentname . '.' . $row->optionname . '.')))
                    $ItemSelected = false;
                else
                    $ItemSelected = true;

                if ($ItemSelected)
                    $result .= '<li class="esSelectedElement">' . $row->title . '</li>';
            }
        }

        if ($result == '<ul>')
            $result = ''; //empty block
        else
            $result .= '</ul>';

        $ItemList = '"' . implode('","', $list_ids) . '"';
        return $result;
    }

    public static function getList($parentId, $langPostfix)
    {
        $db = Factory::getDBO();
        $query = 'SELECT id, optionname, title' . $langPostfix . ' AS title FROM #__customtables_options WHERE parentid=' . (int)$parentId;
        $query .= ' ORDER BY ordering, title';
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    /*
    public static function getOptionLinkFull($optionname)
    {
        $optid=Tree::getOptionIdFull($optionname);

        if($optid==0)
            return "";


        $db = Factory::getDBO();
        $query = 'SELECT link FROM #__customtables_options WHERE id='.$optid.' LIMIT 1';

        $db->setQuery($query);

        $rows=$db->loadObjectList();

        if(count($rows)!=1)
            return "";

        return $rows[0]->link;
    }
    */

    //Used in various files
    //TODO: replace - Very outdated

    public static function isRecordExist($checkValue, $checkField, $resultField, $table): ?string
    {
        $db = Factory::getDBO();
        $query = ' SELECT ' . $resultField . ' AS resultfield FROM ' . $table . ' WHERE ' . $checkField . '="' . $checkValue . '" LIMIT 1';
        $db->setQuery($query);

        $propertyType = $db->loadObjectList();

        if (count($propertyType) > 0) {

            if ($propertyType[0]->resultfield == '0')
                return null;

            return $propertyType[0]->resultfield;
        }
        return null;
    }

    //Used many times
    public static function getHeritageInfo($parentId, $fieldname)
    {
        if ((int)$parentId == 0)
            return '';

        $db = Factory::getDBO();

        $query = 'SELECT id, ' . $fieldname . ' FROM #__customtables_options WHERE parentid="' . $parentId . '" LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadAssocList();
        if (count($rows) == 1) {
            $row = $rows[0];
            $vlu = $row[$fieldname];

            if (strlen($vlu) > 0)
                return $vlu;
            else
                return Tree::getHeritageInfo($row['id'], $fieldname);
        } else
            return '';
    }

    //Used many times
    public static function getHeritage($parentid, string $where, $limit)
    {
        if ((int)$parentid == 0)
            return array();

        $db = Factory::getDBO();

        $query = 'SELECT * FROM #__customtables_options WHERE parentid="' . $parentid . '" '
            . ($where != '' ? ' AND ' . $where : '')
            . ($limit != '' ? ' LIMIT ' . $limit : '');
        $db->setQuery($query);

        $rows = $db->loadAssocList();
        return $rows;
    }

    //Used in import
    public static function getFamilyTreeByParentID($parentid)
    {
        if ($parentid != 0)
            return Tree::getFamilyTree($parentid, 0) . '-' . $parentid;

        return '';
    }

    //Used many times
    public static function getFamilyTree($optionid, $level)
    {
        $db = Factory::getDBO();
        $query = 'SELECT parentid FROM #__customtables_options WHERE id="' . $optionid . '" LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) != 1)
            return '';

        if ($rows[0]->parentid != 0) {
            $parentid = Tree::getFamilyTree($rows[0]->parentid, $level + 1);
            if ($level > 0)
                $parentid .= '-' . $optionid;
        } else {
            if ($level > 0)
                $parentid = $optionid;
        }

        return $parentid;
    }


    //Used many times
    public static function getFamilyTreeString($optionid, $level)
    {
        $db = Factory::getDBO();
        $query = 'SELECT parentid, optionname FROM #__customtables_options WHERE id="' . $optionid . '" LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) != 1)
            return '';

        if ($rows[0]->parentid != 0) {
            $parentstring = Tree::getFamilyTreeString($rows[0]->parentid, $level + 1);
            if ($level > 0)
                $parentstring .= '.' . $rows[0]->optionname;
        } else {
            if ($level > 0)
                $parentstring = $rows[0]->optionname;
        }

        return $parentstring;
    }
}
