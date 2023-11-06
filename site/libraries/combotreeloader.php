<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\DataTypes\Tree;
use Joomla\CMS\Factory;

$independat = false;

if (!defined('_JEXEC')) {

    define('_JEXEC', 1);

    $path = dirname(__FILE__);
    $path_p = strrpos($path, DIRECTORY_SEPARATOR);
    $path = substr($path, 0, $path_p);
    $path_p = strrpos($path, DIRECTORY_SEPARATOR);
    $path = substr($path, 0, $path_p);
    $path_p = strrpos($path, DIRECTORY_SEPARATOR);
    $path = substr($path, 0, $path_p);

    define('JPATH_BASE', $path);
    define('JPATH_SITE', $path);

    require_once(JPATH_BASE . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'defines.php');
    require_once(JPATH_BASE . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'framework.php');

    //JDEBUG ? $_PROFILER->mark( 'afterLoad' ) : null;

    // CREATE THE APPLICATION

    // Instantiate the application.
    $app = Factory::getApplication('site');

    // Initialise the application.
    //$app->initialise();
    $independat = true;
} else {
    $independat = false;
}


if ($independat) {
    require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'combotreeloader.php');

    $tableName = common::inputGet('establename', '', 'CMD');
    $fieldName = common::inputGet('esfieldname', '', 'CMD');
    $optionName = common::inputGetCmd('optionname');

    $MyESDynCombo = new ESDynamicComboTree();
    $MyESDynCombo->initialize($tableName, $fieldName, $optionName, common::inputGetString('prefix'));
    $MyESDynCombo->langpostfix = common::inputGetCmd('langpostfix', '');
    $MyESDynCombo->cssclass = common::inputGetString('cssclass');
    $MyESDynCombo->onchange = common::inputGetString('onchange');
    $MyESDynCombo->innerjoin = common::inputGetInt('innerjoin');
    $MyESDynCombo->isRequired = common::inputGet('isrequired', 0, 'INT');
    $MyESDynCombo->requirementdepth = common::inputGetInt('requirementdepth');
    $MyESDynCombo->where = common::inputGetString('where');
    $MyESDynCombo->place_holder = common::inputGetString('place_holder');

    $MyESDynCombo->parentname = '';


    $filterwhere = '';
    $filterwherearr = array();
    $urlwhere = '';
    $urlwherearr = array();

    $html_ = $MyESDynCombo->renderComboBox($filterwhere, $urlwhere, $filterwherearr, $urlwherearr, false, '',
        $MyESDynCombo->place_holder, '', '');

    echo $html_;
} else {
    $MyESDynCombo = new ESDynamicComboTree();
    $MyESDynCombo->parentname = '';
}

class ESDynamicComboTree
{
    var CT $ct;

    var $ObjectName;
    var $tableName;
    var $fieldName;
    var $listingtable;
    var $optionname;
    var $cssclass = '';
    var $onchange = '';
    var $innerjoin;
    var $where;
    var $parentname;
    var $prefix;
    var $isRequired;
    var $requirementdepth;
    var $place_holder;

    function __construct()
    {
        $this->ct = new CT;
    }

    function initialize($tablename, $fieldname, $optionname, $prefix): void
    {
        $this->requirementdepth = 0;
        $this->prefix = $prefix;

        $this->tableName = $tablename;
        $this->fieldName = $fieldname;
        $this->optionname = $optionname;
        $this->listingtable = '#__customtables_table_' . $this->tableName;
        $this->ObjectName = $this->prefix . 'combotree_' . $this->tableName . '_' . $this->fieldName;
    }

    function renderComboBox(&$filterwhere, &$urlwhere, &$filterwherearr, &$urlwherearr, $simpleList = false,
                            $value = '', $place_holder = '', $valuerule = '', $valuerulecaption = '')
    {
        $result = '';

        $i = 1;

        $temp_parent = $this->optionname;
        $this->parentname = $temp_parent;

        do {
            if ($this->innerjoin)
                $rows = $this->getOptionListWhere($temp_parent, $filterwhere, $this->fieldName);
            else
                $rows = $this->getOptionList($temp_parent);

            $object_name = $this->ObjectName . '_' . $i;

            $values = explode('.', $value);

            if (count($values) > 0) {
                $value = $values[count($values) - 1];
                if ($value == ',' and count($values) > 1)
                    $value = $values[count($values) - 2];
            }

            if ($result != '')
                $result .= '<br/>';

            if ($i <= $this->requirementdepth and $this->isRequired == 1)
                $result .= $this->renderSelectBox($object_name, $rows, $urlwhere, 'class="inputbox required"', $simpleList, $value, $place_holder, $valuerule, $valuerulecaption);
            else
                $result .= $this->renderSelectBox($object_name, $rows, $urlwhere, 'class="inputbox"', $simpleList, $value, $place_holder, $valuerule, $valuerulecaption);

            if (common::inputGetCmd($object_name)) {
                $temp_parent .= '.' . common::inputGetCmd($object_name);
                $this->parentname = $temp_parent;

                $this->getInstrWhereAdv($object_name, $temp_parent, $filterwhere, $urlwhere, $filterwherearr, $urlwherearr, $this->fieldName);
            } else
                break;

            $i++;

        } while (common::inputGetCmd($object_name));
        return $result;
    }

    function getOptionListWhere($parentname, $filterwhere, $listingfield)
    {
        $parentid = Tree::getOptionIdFull($parentname);

        $query = 'SELECT '
            . ' #__customtables_options.id AS optionid, '
            . ' optionname AS tempid, '
            . ' #__customtables_options.title' . $this->ct->Languages->Postfix . ' AS optiontitle, '
            . ' COUNT(' . $this->listingtable . '.id) AS listingcount'
            . ' FROM #__customtables_options'
            . ' INNER JOIN ' . $this->listingtable . ' ON INSTR(' . $this->listingtable . '.es_' . $listingfield . ',concat(",' . $parentname . '.",optionname,"."))';

        $where = array();

        $where[] = '#__customtables_options.published';
        $where[] = '#__customtables_options.parentid=' . $parentid;

        if ($this->where != '')
            $where[] = $this->where;

        if ($filterwhere != '')
            $where[] = $filterwhere;

        $query .= ' WHERE ' . implode(' AND ', $where) . ' GROUP BY optionid ORDER BY ordering, optiontitle';
        return database::loadObjectList($query);
    }

    function getOptionList($parentname)
    {
        $parentid = Tree::getOptionIdFull($parentname);

        $query = 'SELECT '
            . ' id AS optionid, '
            . ' optionname AS tempid, '
            . ' title' . $this->ct->Languages->Postfix . ' AS optiontitle '
            . ' FROM #__customtables_options '
            . ' WHERE parentid=' . $parentid . ' ';

        $query .= ' ORDER BY ordering, optiontitle';
        return database::loadObjectList($query);
    }

    function renderSelectBox($objectname, $rows, $urlwhere, $optionalOptions, $simpleList = false, $value = '', $place_holder = '', $valuerule = '', $valuerulecaption = '')
    {
        if (count($rows) == 1) {
            if ($rows[0]->tempid == "na") //optionname
                return "";
        } elseif (count($rows) == 0)
            return "";

        $optionalarr = Tree::CleanLink(explode("&", $urlwhere), $objectname);
        $optional = implode("&", $optionalarr);

        if ($value == '')
            $value = common::inputGetCmd($objectname, '');

        $result = '';

        $WebsiteRoot = JURI::root(true);
        $WebsiteRoot = str_replace("/components/com_customtables/libraries/", "", $WebsiteRoot);
        $WebsiteRoot = str_replace("/components/com_customtables/libraries", "", $WebsiteRoot);

        if ($WebsiteRoot == '' or $WebsiteRoot[strlen($WebsiteRoot) - 1] != '/') //Root must have the slash character "/" in the end
            $WebsiteRoot .= '/';

        if ($simpleList)
            $onChange = '';
        else
            $onChange = ' onChange="comboSERefreshMe('
                . '\'' . $WebsiteRoot . '\', '
                . 'this, '
                . '\'' . $objectname . '\', '
                . '\'' . $this->ObjectName . '\', '
                . '\'' . $optional . '\', '
                . '\'' . $this->tableName . '\', '
                . '\'' . $this->fieldName . '\', '
                . '\'' . $this->optionname . '\', '
                . '\'' . $this->innerjoin . '\', '
                . '\'' . urlencode($this->cssclass) . '\', '
                . '\'' . $this->parentname . '\', '
                . '\'' . urlencode($this->where) . '\', '
                . '\'' . $this->ct->Languages->Postfix . '\', '
                . '\'' . urlencode($this->onchange) . '\', '
                . '\'' . urlencode($this->prefix) . '\', '
                . '\'' . ((int)$this->isRequired) . '\', '
                . '\'' . ((int)$this->requirementdepth) . '\'); '
                . ' " ';

        $result .= '<select'
            . ' name="' . $objectname . '"'
            . ' id="' . $objectname . '"'
            . ' class="' . $this->cssclass . '"'
            . ' ' . $onChange
            . ' ' . $optionalOptions
            . ' data-label="' . $place_holder . '"'
            . ' data-valuerule="' . str_replace('"', '&quot;', $valuerule) . '"'
            . ' data-valuerulecaption="' . str_replace('"', '&quot;', $valuerulecaption) . '"'
            . ' data-type="customtables">';

        $result .= '<option value="" ' . ($value == "" ? ' SELECTED ' : '') . '>- ' . common::translate('COM_CUSTOMTABLES_SELECT') . ' ' . $place_holder . '</option>';

        $count = 0;

        foreach ($rows as $row) {
            $result .= '<option value="' . $row->tempid . '" ' . ($value == $row->tempid ? ' SELECTED ' : '') . '>' . htmlspecialchars($row->optiontitle ?? '');

            if ($this->innerjoin)
                $result .= ' (' . $row->listingcount . ')';

            $result .= '</option>' . PHP_EOL;
            $count++;
        }
        $result .= '</select>';
        return $result;
    }

    function getInstrWhereAdv($object_name, $temp_parent, &$filterwhere, &$urlwhere, &$filterwherearr, &$urlwherearr, $field)
    {
        if (strlen(common::inputGetString($object_name, '')) > 0) {
            $filterwherearr[] = 'INSTR(' . $this->listingtable . '.es_' . $field . ', ",' . $temp_parent . '.")';
            $urlwherearr[] = $object_name . '=' . common::inputGetCmd($object_name, '');
        }

        if (count($filterwherearr) > 0) {
            $filterwhere = ' ' . implode(" AND ", $filterwherearr);
            $urlwhere = ' ' . implode("&", $urlwherearr);
        }
    }
}
