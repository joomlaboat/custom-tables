<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class JHTMLESComboTree
{
    static function render($prefix, $tableName, $fieldName, $optionname, $langPostfix, $value, $cssclass = "", $onchange = "",
                           $where = "", $innerJoin = false, $isRequired = false, $requirementDepth = 0, $place_holder = '', $valuerule = '', $valuerulecaption = '')
    {
        $jinput = Factory::getApplication()->input;

        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR
            . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'combotreeloader.php');

        $MyESDynCombo = new ESDynamicComboTree();
        $MyESDynCombo->initialize($tableName, $fieldName, $optionname, $prefix);
        $MyESDynCombo->cssclass = $cssclass;
        $MyESDynCombo->onchange = $onchange;
        $MyESDynCombo->innerjoin = $innerJoin;
        $MyESDynCombo->langpostfix = $langPostfix;
        $MyESDynCombo->isRequired = $isRequired;
        $MyESDynCombo->requirementdepth = $requirementDepth;

        $MyESDynCombo->where = $where;

        $filterWhere = '';
        $filterWhereArray = array();

        $urlWhere = '';
        $urlWhereArray = array();

        //Set current value (count only firet one in case multi-value provided)
        /*
        $value_arr = explode(',', $value);
        if (count($value_arr) > 0) {
            if (count($value_arr) < 2)
                $value_arr[1] = '';

            $i = 1;
            $option_arr = explode('.', $value_arr[1]);
            $parent_arr = explode('.', $optionname);
            if (count($option_arr) > count($parent_arr)) {
                for ($p = count($parent_arr); $p < count($option_arr); $p++) {
                    $opt = $option_arr[$p];
                    if ($opt == '')
                        break;

                    //$jinput->set($MyESDynCombo->ObjectName . '_' . $i, $opt);
                    $i++;
                }
            }
        }
        */

        return '<div id="' . $MyESDynCombo->ObjectName . '" name="' . $MyESDynCombo->ObjectName . '">'
            . $MyESDynCombo->renderComboBox($filterWhere, $urlWhere, $filterWhereArray, $urlWhereArray,
                ($requirementDepth == 1 ? true : false),
                $value,
                $place_holder,
                $valuerule,
                $valuerulecaption
            )

            . '</div>';
    }

}
