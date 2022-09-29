<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\LinkJoinFilters;
use CustomTables\TwigProcessor;

class JHTMLESRecords
{
    static public function render($typeparams, $control_name, $value, $tableName, $theField, $selector, $filter, $style = '',
                                  $cssClass = '', $attribute = '', $dynamic_filter = '', $sortByField = '', $langPostfix = '', $place_holder = ''): string
    {
        $htmlresult = '';
        $fieldArray = explode(';', $theField);
        $field = $fieldArray[0];
        $selectorPair = explode(':', $selector);

        if (isset($typeparams[6]) and $typeparams[6] == 'true')
            $allowUnpublished = true;
        else
            $allowUnpublished = false;

        $ct = self::getCT($tableName, $filter, $allowUnpublished, $sortByField, $field);

        if ($ct == null) {
            return '<p>Table not selected</p>';
        }

        $ct_noFilter = null;

        if ($selectorPair[0] == 'single' or $selectorPair[0] == 'multibox')
            $ct_noFilter = self::getCT($tableName, '', $allowUnpublished, $sortByField, $field);

        $valueArray = explode(',', $value);

        if (!str_contains($field, ':')) {
            //without layout

            $real_field_row = Fields::getFieldRowByName($field, '', $tableName);

            switch ($selectorPair[0]) {

                case 'single' :

                    $control_name_postfix = '';

                    $htmlresult .= JHTMLESRecords::getSingle($ct, $ct_noFilter, $valueArray, $field, $control_name,
                        $control_name_postfix, $style, $cssClass, $attribute, $value, $tableName, $dynamic_filter, $place_holder);

                    break;

                case 'multi' :

                    if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
                        $real_field = $real_field_row->realfieldname . $langPostfix;
                    else
                        $real_field = $real_field_row->realfieldname;


                    $htmlresult .= '<SELECT name="' . $control_name . '[]"'
                        . ' id="' . $control_name . '" MULTIPLE';

                    if (count($selectorPair) > 1)
                        $htmlresult .= ' size="' . $selectorPair[1] . '"';

                    $htmlresult .= ($style != '' ? ' style="' . $style . '"' : '')
                        . ($cssClass != '' ? ' class="' . $cssClass . '"' : '')
                        . ' data-label="' . $place_holder . '"'
                        . ($attribute != '' ? ' ' . $attribute : '')
                        . ' data-type="records">';

                    foreach ($ct->Records as $row) {
                        if ($row['listing_published'] == 0)
                            $style = 'style="color:red"';
                        else
                            $style = '';

                        $htmlresult .= '<option value="' . $row[$ct->Table->realidfieldname] . '" '
                            . ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' SELECTED ' : '')
                            . ' ' . $style . '>';

                        $htmlresult .= $row[$real_field] . '</option>';
                    }

                    $htmlresult .= '</SELECT>';
                    break;

                case 'radio' :

                    $htmlresult .= '<table style="border:none;" id="sqljoin_table_' . $control_name . '">';
                    $i = 0;
                    foreach ($ct->Records as $row) {
                        $htmlresult .= '<tr><td>'
                            . '<input type="radio"'
                            . ' name="' . $control_name . '"'
                            . ' id="' . $control_name . '_' . $i . '"'
                            . ' value="' . $row[$ct->Table->realidfieldname] . '"'
                            . ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked"' : '')
                            . ($cssClass != '' ? ' class="' . $cssClass . '"' : '')
                            . ' data-type="records" />'
                            . '</td>'
                            . '<td>'
                            . '<label for="' . $control_name . '_' . $i . '">' . $row[$real_field_row->realfieldname] . '</label>'
                            . '</td></tr>';
                        $i++;
                    }
                    $htmlresult .= '</table>';
                    break;

                case 'checkbox' :

                    if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
                        $real_field = $real_field_row->realfieldname . $langPostfix;
                    else
                        $real_field = $real_field_row->realfieldname;

                    $htmlresult .= '<table style="border:none;">';
                    $i = 0;
                    foreach ($ct->Records as $row) {
                        $htmlresult .= '<tr><td>'
                            . '<input type="checkbox"'
                            . ' name="' . $control_name . '[]"'
                            . ' id="' . $control_name . '_' . $i . '"'
                            . ' value="' . $row[$ct->Table->realidfieldname] . '"'
                            . ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked" ' : '')
                            . ($cssClass != '' ? ' class="' . $cssClass . '"' : '')
                            . ' data-type="records" />'
                            . '</td>'
                            . '<td>'
                            . '<label for="' . $control_name . '_' . $i . '">' . $row[$real_field] . '</label>'
                            . '</td></tr>';

                        $i++;
                    }
                    $htmlresult .= '</table>';
                    break;

                case 'multibox' :

                    $htmlresult .= JHTMLESRecords::getMultiBox($ct, $ct_noFilter, $valueArray, $field,
                        $control_name, $style, $cssClass, $attribute, $tableName, $dynamic_filter, $langPostfix, $place_holder);

                    break;

                default:
                    return '<p>Incorrect selector</p>';
            }
        } else {
            //with layout
            $pair = JoomlaBasicMisc::csv_explode(':', $field);
            if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout')
                return '<p>unknown field/layout command "' . $field . '" should be like: "layout:' . $pair[1] . '".</p>';

            $Layouts = new Layouts($ct);
            $layoutcode = $Layouts->getLayout($pair[1]);

            if ($layoutcode == '')
                return '<p>layout "' . $pair[1] . '" not found or is empty.</p>';

            $htmlresult .= '<table style="border:none;" id="sqljoin_table_' . $control_name . '">';
            $i = 0;
            foreach ($ct->Records as $row) {
                $htmlresult .= '<tr><td>';

                if ($selectorPair[0] == 'multi' or $selectorPair[0] == 'checkbox') {
                    $htmlresult .= '<input type="checkbox"'
                        . ' name="' . $control_name . '[]"'
                        . ' id="' . $control_name . '_' . $i . '"'
                        . ' value="' . $row[$ct->Table->realidfieldname] . '"'
                        . ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked"' : '')
                        . ' data-type="records" />';
                } elseif ($selectorPair[0] == 'single' or $selectorPair[0] == 'radio') {
                    $htmlresult .= '<input type="radio" '
                        . ' name="' . $control_name . '"'
                        . ' id="' . $control_name . '_' . $i . '"'
                        . ' value="' . $row[$ct->Table->realidfieldname] . '"'
                        . ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked"' : '')
                        . ' data-type="records" />';
                } else
                    return '<p>Incorrect selector</p>';

                $htmlresult .= '</td>';

                $htmlresult .= '<td>';

                //process layout
                $htmlresult .= '<label for="' . $control_name . '_' . $i . '">';

                if ($ct->Env->legacysupport) {
                    $LayoutProc = new LayoutProcessor($ct);
                    $LayoutProc->layout = $layoutcode;
                    $layoutcode_tmp = $LayoutProc->fillLayout($row);
                } else
                    $layoutcode_tmp = $layoutcode;

                $twig = new TwigProcessor($ct, $layoutcode_tmp);
                $htmlresult .= $twig->process($row);

                $htmlresult .= '</label>';

                $htmlresult .= '</td></tr>';
                $i++;
            }
            $htmlresult .= '</table>';
        }

        return $htmlresult;
    }

    static protected function getCT($tableName, $filter, $allowUnpublished, $sortByField, $field): ?CT
    {
        $menuParams = self::prepareParams($tableName, $filter, $allowUnpublished, $sortByField, $field);

        $ct = new CT;
        $ct->setParams($menuParams, true);

        // -------------------- Table

        $ct->getTable($ct->Params->tableName);

        if ($ct->Table->tablename == '') {
            $ct->app->enqueueMessage('Catalog View: Table not selected.', 'error');
            return null;
        }

        // --------------------- Filter
        $ct->setFilter($ct->Params->filter, $ct->Params->showPublished);

        // --------------------- Sorting
        $ct->Ordering->parseOrderByParam();

        // --------------------- Limit
        $ct->applyLimits();

        $ct->getRecords();

        return $ct;
    }

    static protected function prepareParams($tableName, $filter, $allowUnpublished, $sortByField, $field)
    {
        $paramsArray = array();
        $paramsArray['limit'] = 10000;
        $paramsArray['establename'] = $tableName;
        $paramsArray['filter'] = str_replace('****quote****', '"', $filter);

        if ($allowUnpublished)//0 - published only; 1 - hidden only; 2 - Any
            $paramsArray['showpublished'] = 2;
        else
            $paramsArray['showpublished'] = 0;

        $paramsArray['groupby'] = '';

        if ($sortByField != '')
            $paramsArray['forcesortby'] = $sortByField;
        elseif (!str_contains($field, ':')) //cannot sort by layout only by field name
            $paramsArray['forcesortby'] = $field;

        $_params = new JRegistry;
        $_params->loadArray($paramsArray);

        return $_params;
    }

    static protected function getSingle(CT &$ct, CT &$ct_noFilter, $valueArray,
                                           $field, $control_name, $control_name_postfix, $style, $cssClass, $attribute, ?string $value,
                                           $tableName, $dynamic_filter = '', $place_holder = ''): string
    {

        $htmlresult = '';

        if ($dynamic_filter != '') {
            $htmlResultJS = '';
            $elements = array();
            $elementsID = array();
            $elementsFilter = array();
            $elementsPublished = array();

            $filterValue = '';
            foreach ($ct_noFilter->Records as $row) {
                if ($row[$ct_noFilter->Table->realidfieldname] == $value) {
                    $filterValue = $row[$ct_noFilter->Env->field_prefix . $dynamic_filter];
                    break;
                }
            }
            $htmlresult .= LinkJoinFilters::getFilterBox($tableName, $dynamic_filter, $control_name, $filterValue, $control_name_postfix);

        }

        $htmlresult_options = '';

        if (!str_contains($control_name, '_selector'))
            $htmlresult_options .= '<option value="">- ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT') . ' ' . $place_holder . '</option>';

        if ($value == '' or $value == ',' or $value == ',,')
            $valueFound = true;
        else
            $valueFound = false;

        foreach ($ct->Records as $row) {
            if (in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) {
                $htmlresult_options .= '<option value="' . $row[$ct->Table->realidfieldname] . '" SELECTED ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';
                $valueFound = true;
            } else
                $htmlresult_options .= '<option value="' . $row[$ct->Table->realidfieldname] . '" ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';

            $v = JoomlaBasicMisc::processValue($field, $ct, $row);
            $htmlresult_options .= $v;

            if ($dynamic_filter != '') {
                $elements[] = $v;
                $elementsID[] = $row[$ct->Table->realidfieldname];
                $elementsFilter[] = $row[$ct->Env->field_prefix . $dynamic_filter];
                $elementsPublished[] = (int)$row['listing_published'];
            }
            $htmlresult_options .= '</option>';
        }

        if ($value != '' and $value != ',' and $value != ',,' and !$valueFound) {
            //_noFilter - add all elements, don't remember why, probably if value is not in the list after the filter
            //workaround in case the value not found

            foreach ($ct_noFilter->Records as $row) {
                if (in_array($row[$ct_noFilter->Table->realidfieldname], $valueArray) and count($valueArray) > 0) {
                    $htmlresult_options .= '<option value="' . $row[$ct_noFilter->Table->realidfieldname] . '" SELECTED ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';
                } else
                    $htmlresult_options .= '<option value="' . $row[$ct_noFilter->Table->realidfieldname] . '" ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';

                $v = JoomlaBasicMisc::processValue($field, $ct_noFilter, $row);
                $htmlresult_options .= $v;

                if ($dynamic_filter != '') {
                    $elements[] = $v;
                    $elementsID[] = $row[$ct_noFilter->Table->realidfieldname];
                    $elementsFilter[] = $row[$ct_noFilter->Env->field_prefix . $dynamic_filter];
                    $elementsPublished[] = (int)$row['listing_published'];
                }
                $htmlresult_options .= '</option>';
            }
        }

        $htmlresult .= '<SELECT name="' . $control_name . '" id="' . $control_name . $control_name_postfix . '"'
            . ($style != '' ? ' style="' . $style . '"' : '')
            . ($cssClass != '' ? ' class="' . $cssClass . '"' : '')
            . ($attribute != '' ? ' ' . $attribute : '')
            . ' data-label="' . $place_holder . '"'
            . ' data-type="records" />';

        $htmlresult .= $htmlresult_options;

        $htmlresult .= '</SELECT>';

        if ($dynamic_filter != '') {
            $htmlResultJS .= '
			<div id="' . $control_name . $control_name_postfix . '_elements" style="display:none;">' . json_encode($elements) . '</div>
			<div id="' . $control_name . $control_name_postfix . '_elementsID" style="display:none;">' . implode(',', $elementsID) . '</div>
			<div id="' . $control_name . $control_name_postfix . '_elementsFilter" style="display:none;">' . implode(';', $elementsFilter) . '</div>
			<div id="' . $control_name . $control_name_postfix . '_elementsPublished" style="display:none;">' . implode(',', $elementsPublished) . '</div>
			';
            $htmlresult = $htmlResultJS . $htmlresult;
        }

        return $htmlresult;
    }

    static protected function getMultiBox(CT &$ct, &$ct_noFilter, $valuearray, $field,
                                             $control_name, $style, $cssclass, $attribute, $tableName, $dynamic_filter, $langPostfix = '', $place_holder = ''): string
    {
        $real_field_row = Fields::getFieldRowByName($field, '', $tableName);

        if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
            $real_field = $real_field_row->realfieldname . $langPostfix;
        else
            $real_field = $real_field_row->realfieldname;

        $ctInputboxRecords_r = [];
        $ctInputboxRecords_v = [];
        $ctInputboxRecords_p = [];

        foreach ($ct->Records as $row) {
            if (in_array($row[$ct->Table->realidfieldname], $valuearray) and count($valuearray) > 0) {
                $ctInputboxRecords_r[] = $row[$ct->Table->realidfieldname];
                $ctInputboxRecords_v[] = $row[$real_field];
                $ctInputboxRecords_p[] = (int)$row['listing_published'];
            }
        }

        $htmlresult = '
		<script>
			//Field value
			ctInputboxRecords_r["' . $control_name . '"] = ' . json_encode($ctInputboxRecords_r) . ';
			ctInputboxRecords_v["' . $control_name . '"] = ' . json_encode($ctInputboxRecords_v) . ';
			ctInputboxRecords_p["' . $control_name . '"] = ' . json_encode($ctInputboxRecords_p) . ';
		</script>
		';

        $single_box = JHTMLESRecords::getSingle($ct, $ct_noFilter, $valuearray, $field,
            $control_name, '_selector', $style, $cssclass, $attribute, '', $tableName, $dynamic_filter, $place_holder);

        $icon_path = JURI::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/';
        $htmlresult .= '<div style="padding-bottom:20px;"><div style="width:90%;" id="' . $control_name . '_box"></div>'
            . '<div style="height:30px;">'
            . '<div id="' . $control_name . '_addButton" style="visibility: visible;"><img src="' . $icon_path . 'new.png" alt="Add" title="Add" style="cursor: pointer;" '
            . 'onClick="ctInputboxRecords_addItem(\'' . $control_name . '\',\'_selector\')" /></div>'
            . '<div id="' . $control_name . '_addBox" style="visibility: hidden;">'
            . '<div style="float:left;">' . $single_box . '</div>'
            . '<img src="' . $icon_path . 'plus.png" '
            . 'alt="Add" title="Add" '
            . 'style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;width:16px;height:16px;" '
            . 'onClick="ctInputboxRecords_DoAddItem(\'' . $control_name . '\',\'_selector\')" />'
            . '<img src="' . $icon_path . 'cancel.png" alt="Cancel" title="Cancel" '
            . 'style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;width:16px;height:16px;" '
            . 'onClick="ctInputboxRecords_cancel(\'' . $control_name . '\',\'_selector\')" />'

            . '</div>'
            . '</div>'
            . '<div style="visibility: hidden;"><select name="' . $control_name . '[]" id="' . $control_name . '" MULTIPLE ></select></div>'
            . '</div>

		<script>
			ctInputboxRecords_showMultibox("' . $control_name . '","_selector");
		</script>
		';

        return $htmlresult;
    }
}
