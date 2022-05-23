<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Layouts;
use CustomTables\LinkJoinFilters;
use CustomTables\TwigProcessor;
use Joomla\CMS\Factory;

class JHTMLESSqlJoin
{
    static public function render(array $typeparams, $value, $force_dropdown, $langpostfix, $control_name, $place_holder, $cssclass = '', $attribute = '', $addNoValue = false)
    {
        if (count($typeparams) < 1) {
            Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED'), 'error');
            return '';
        }

        if (count($typeparams) < 2) {
            Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_UNKNOWN_FIELD_LAYOUT'), 'error');
            return '';
        }

        $establename = $typeparams[0];

        if (isset($typeparams[1]))
            $value_field = $typeparams[1];
        else
            $value_field = '';

        if (isset($typeparams[2]))
            $filter = $typeparams[2];
        else
            $filter = '';

        if (isset($typeparams[3]))
            $dynamic_filter = $typeparams[3];
        else
            $dynamic_filter = '';

        if (isset($typeparams[4]))
            $order_by_field = $typeparams[4];
        else
            $order_by_field = '';

        if (isset($typeparams[5]) and $typeparams[5] == 'true')
            $allowunpublished = true;
        else
            $allowunpublished = false;

        if (isset($typeparams[6])) {
            if ($typeparams[6] == 'radio')
                $selector = 'radio';
            if ($typeparams[6] == 'json')
                $selector = 'json';
            else
                $selector = 'dropdown';
        } else
            $selector = 'dropdown';

        if (ESTables::getTableID($establename) == '') {
            Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND'), 'error');
            return '';
        }

        if ($order_by_field == '')
            $order_by_field = $value_field;

        if ($place_holder == '')
            $place_holder = '- ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT');

        //Get Database records
        $ct = new CT;
        self::get_searchresult($ct, $filter, $establename, $order_by_field, $allowunpublished);

        //Process records depending on field type and layout
        $list_values = self::get_List_Values($ct, $value_field, $langpostfix, $dynamic_filter);

        $htmlresult = '';
        //Output section box
        if ($ct->Env->print == 1) {
            $htmlresult .= self::renderPrintResult($list_values, $value, $control_name);
        } elseif ($selector == 'json') {
            //$list_value[0] - value
            //$list_value[1] - title
            //$list_value[2] - published
            //$list_value[3] - filter
            $new_list = [];
            foreach ($list_values as $value)
                $new_list[] = (object)['value' => $value[0], 'title' => $value[1]];

            return $new_list;
        } elseif ($selector == 'dropdown' or $force_dropdown or $dynamic_filter) {
            $htmlresult .= self::renderDynamicFilter($ct, $value, $establename, $dynamic_filter, $control_name);
            $htmlresult .= self::renderDropdownSelector_Box($list_values, $value, $control_name, $cssclass, $attribute, $place_holder, $dynamic_filter, $addNoValue);
        } else
            $htmlresult .= self::renderRadioSelector_Box($list_values, $value, $control_name, $cssclass, $attribute, $value_field);

        return $htmlresult;
    }

    static protected function get_searchresult(CT &$ct, $filter, $establename, $order_by_field, $allowunpublished): bool
    {
        $paramsArray = array();

        $paramsArray['limit'] = 0;
        $paramsArray['establename'] = $establename;
        if ($allowunpublished)
            $paramsArray['showpublished'] = 2;//0 - published only; 1 - hidden only; 2 - Any
        else
            $paramsArray['showpublished'] = 0;//0 - published only; 1 - hidden only; 2 - Any

        $paramsArray['showpagination'] = 0;
        $paramsArray['groupby'] = '';
        $paramsArray['shownavigation'] = 0;

        if (!str_contains($order_by_field, ':')) //cannot sort by layout only by field name
            $paramsArray['forcesortby'] = $order_by_field;

        if ($filter != '')
            $paramsArray['filter'] = str_replace('|', ',', str_replace('****quote****', '"', $filter));
        else
            $paramsArray['filter'] = ''; //!IMPORTANT - NO FILTER

        $_params = new JRegistry;
        $_params->loadArray($paramsArray);

        $ct->setParams($_params, true);

        // -------------------- Table

        $ct->getTable($ct->Params->tableName);

        if ($ct->Table->tablename == '') {
            $ct->app->enqueueMessage('Catalog View: Table not selected.', 'error');
            return false;
        }

        // --------------------- Filter
        $ct->setFilter('', $ct->Params->showPublished);
        $ct->Filter->addMenuParamFilter();

        // --------------------- Sorting
        $ct->Ordering->parseOrderByParam();

        // --------------------- Limit
        $ct->applyLimits();

        $ct->getRecords();

        return true;
    }

    static protected function get_List_Values(CT &$ct, $field, $langpostfix, $dynamic_filter)
    {
        $layout_mode = false;
        $layoutcode = '';
        $pair = explode(':', $field);
        if (count($pair) == 2) {
            $layout_mode = true;
            if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout') {
                Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_UNKNOWN_FIELD_LAYOUT') . ' "' . $field . '"', 'error');
                return array();
            }

            $Layouts = new Layouts($ct);
            $layoutcode = $Layouts->getLayout($pair[1]);

            if (!isset($layoutcode) or $layoutcode == '') {
                Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LAYOUT_NOT_FOUND') . ' "' . $pair[1] . '"', 'error');
                return array();
            }
        }

        $list_values = array();

        foreach ($ct->Records as $row) {
            if ($layout_mode) {
                if ($ct->Env->legacysupport) {
                    $LayoutProc = new LayoutProcessor($ct);
                    $LayoutProc->layout = $layoutcode;
                    $v = $LayoutProc->fillLayout($row);
                } else
                    $v = $layoutcode;

                $twig = new TwigProcessor($ct, '{% autoescape false %}' . $v . '{% endautoescape %}');
                $v = $twig->process($row);
            } else
                $v = JoomlaBasicMisc::processValue($field, $ct, $row);//TODO try to replace processValue function

            if ($dynamic_filter != '')
                $d = $row[$ct->Env->field_prefix . $dynamic_filter];
            else
                $d = '';

            $list_values[] = [$row[$ct->Table->realidfieldname], $v, (int)$row['listing_published'], $d];
        }

        return $list_values;
    }

    static protected function renderPrintResult($list_values, $current_value, $control_name): string
    {
        $htmlresult = '';

        foreach ($list_values as $list_value) {
            if ($list_value[0] == $current_value) {
                $htmlresult .= '<input type="hidden" name="' . $control_name . '"'
                    . ' id="' . $control_name . '" value="' . $list_value[0] . '"'
                    . ' data-type="sqljoin" />';
                $htmlresult .= $list_value[1];
                break;
            }
        }

        if ($htmlresult == '')
            $htmlresult .= '<input type="hidden"'
                . ' name="' . $control_name . '"'
                . ' id="' . $control_name . '"'
                . ' value=""'
                . ' data-type="sqljoin" />';

        return $htmlresult;
    }

    static protected function renderDynamicFilter(&$ct, $value, $establename, $dynamic_filter, $control_name): string
    {
        $htmlresult = '';

        if ($dynamic_filter != '') {
            $filtervalue = '';
            foreach ($ct->Records as $row) {
                if ($row[$ct->Table->realidfieldname] == $value) {
                    $filtervalue = $row[$ct->Env->field_prefix . $dynamic_filter];
                    break;
                }
            }

            $htmlresult .= LinkJoinFilters::getFilterBox($establename, $dynamic_filter, $control_name, $filtervalue);
        }

        return $htmlresult;
    }

    static protected function renderDropdownSelector_Box($list_values, $current_value, $control_name, $cssclass, $attribute, $place_holder, $dynamic_filter, $addNoValue = false)
    {
        if (strpos($cssclass, ' ct_improved_selectbox') !== false)
            return self::renderDropdownSelector_Box_improved($list_values, $current_value, $control_name, $cssclass, $attribute, $place_holder, $dynamic_filter);
        else
            return self::renderDropdownSelector_Box_simple($list_values, $current_value, $control_name, $cssclass, $attribute, $place_holder, $dynamic_filter, $addNoValue);
    }

    static protected function renderDropdownSelector_Box_improved($list_values, $current_value, $control_name, $cssclass, $attribute, $place_holder, $dynamic_filter, $addNoValue = false)
    {
        JHtml::_('formbehavior.chosen', '.ct_improved_selectbox');
        return self::renderDropdownSelector_Box_simple($list_values, $current_value, $control_name, $cssclass, $attribute, $place_holder, $dynamic_filter, $addNoValue);
    }

    static protected function renderDropdownSelector_Box_simple($list_values, $current_value, $control_name, $cssclass, $attribute, $place_holder, $dynamic_filter, $addNoValue = false)
    {
        $htmlresult = '';
        $htmlresult_select = '<SELECT'
            . ' name="' . $control_name . '"'
            . ' id="' . $control_name . '"'
            . ($cssclass != '' ? ' class="' . $cssclass . '"' : '')
            . ($attribute != '' ? ' ' . $attribute : '')
            . ' data-label="' . $place_holder . '"'
            . ' data-type="sqljoin">';

        $htmlresult_select .= '<option value="">- ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT') . ' ' . $place_holder . '</option>';

        foreach ($list_values as $list_value) {
            if ($list_value[2] == 0)//if unpublished
                $style = ' style="color:red"';
            else
                $style = '';

            if ($dynamic_filter == '')
                $htmlresult_select .= '<option value="' . $list_value[0] . '"' . ($list_value[0] == $current_value ? ' selected="SELECTED"' : '') . '' . $style . '>' . strip_tags($list_value[1]) . '</option>';
        }

        if ($addNoValue)
            $htmlresult_select .= '<option value="-1"' . ((int)$current_value == -1 ? ' selected="SELECTED"' : '') . '>- ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_SPECIFIED') . '</option>';

        $htmlresult_select .= '</SELECT>';

        if ($dynamic_filter != '') {
            $elements = array();
            $elementsID = array();
            $elementsFilter = array();
            $elementsPublished = array();

            foreach ($list_values as $list_value) {
                $elementsID[] = $list_value[0];
                $elements[] = $list_value[1];
                $elementsPublished[] = $list_value[2];
                $elementsFilter[] = $list_value[3];
            }

            $htmlresult .= '
			<div id="' . $control_name . '_elements" style="display:none;">' . json_encode($elements) . '</div>
			<div id="' . $control_name . '_elementsID" style="display:none;">' . implode(',', $elementsID) . '</div>
			<div id="' . $control_name . '_elementsFilter" style="display:none;">' . implode(';', $elementsFilter) . '</div>
			<div id="' . $control_name . '_elementsPublished" style="display:none;">' . implode(',', $elementsPublished) . '</div>
			';

            $htmlresult .= $htmlresult_select;

            $htmlresult .= '
			<script>
				ctInputboxRecords_current_value["' . $control_name . '"]="' . $current_value . '";
				ctInputbox_removeEmptyParents("' . $control_name . '","");
				ctInputbox_UpdateSQLJoinLink("' . $control_name . '","");
			</script>
			';
        } else {
            $htmlresult .= $htmlresult_select;
        }
        return $htmlresult;
    }

    static protected function renderRadioSelector_Box($list_values, $current_value, $control_name, $cssclass, $attribute, $field)
    {
        $pair = explode(':', $field);

        $withtable = false;

        if ($pair[0] == 'layout')
            $withtable = true;

        $htmlresult = '';

        if ($withtable)
            $htmlresult .= '<table rel="radioboxselector" style="border:none;" id="sqljoin_table_' . $control_name . '" ' . ($cssclass != '' ? 'class="' . $cssclass . '"' : '') . '>';
        else
            $htmlresult .= '<div rel="radioboxselector" id="sqljoin_table_' . $control_name . '" ' . ($cssclass != '' ? 'class="' . $cssclass . '"' : '') . '>';

        $i = 0;
        foreach ($list_values as $list_value) {
            if ($withtable)
                $htmlresult .= '<tr><td>';
            else
                $htmlresult .= '<div id="sqljoin_table_' . $control_name . '_' . $list_value[0] . '">';

            $htmlresult .= '<input type="radio" '
                . ' name="' . $control_name . '"'
                . ' id="' . $control_name . '_' . $i . '"'
                . ' value="' . $list_value[0] . '"'
                . ($list_value == ' ' . $current_value ? ' checked="checked" ' : '')
                . ' data-type="sqljoin" />';

            if ($withtable)
                $htmlresult .= '</td><td>';

            $htmlresult .= '<label for="' . $control_name . '_' . $i . '">' . $list_value[1] . '</label>';

            if ($withtable)
                $htmlresult .= '</td></tr>';
            else
                $htmlresult .= '</div>';

            $i++;
        }

        if ($withtable)
            $htmlresult .= '</table>';
        else
            $htmlresult .= '</div>';

        return $htmlresult;
    }
}
