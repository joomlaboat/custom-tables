<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
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
    static public function render(array  $typeParams, $control_name, ?string $value, $tableName, string $theField, string $selector, $filter, string $style = '',
                                  string $cssClass = '', string $attribute = '', string $dynamic_filter = '', string $sortByField = '', string $langPostfix = '', string $place_holder = ''): string
    {
        $htmlResult = '';
        $fieldArray = explode(';', $theField);
        $field = $fieldArray[0];
        $selectorPair = explode(':', $selector);

        if (isset($typeParams[6]) and $typeParams[6] == 'true')
            $allowUnpublished = true;
        else
            $allowUnpublished = false;

        $ct = self::getCT($tableName, $filter, $allowUnpublished, $sortByField, $field);

        if ($ct == null)
            return '<p>Table not selected</p>';

        $ct_noFilter = null;

        if ($selectorPair[0] == 'single' or $selectorPair[0] == 'multibox')
            $ct_noFilter = self::getCT($tableName, '', $allowUnpublished, $sortByField, $field);

        $valueArray = explode(',', $value);

        if (!str_contains($field, ':')) {
            //without layout
            $real_field_row = Fields::getFieldRowByName($field, null, $tableName);

            switch ($selectorPair[0]) {

                case 'single' :

                    $control_name_postfix = '';

                    $htmlResult .= JHTMLESRecords::getSingle($ct, $ct_noFilter, $valueArray, $field, $control_name,
                        $control_name_postfix, $style, $cssClass, $attribute, $value, $tableName, $dynamic_filter, $place_holder);

                    break;

                case 'multi' :

                    if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
                        $real_field = $real_field_row->realfieldname . $langPostfix;
                    else
                        $real_field = $real_field_row->realfieldname;

                    $htmlResult .= '<SELECT name="' . $control_name . '[]"'
                        . ' id="' . $control_name . '" MULTIPLE';

                    if (count($selectorPair) > 1)
                        $htmlResult .= ' size="' . $selectorPair[1] . '"';

                    $htmlResult .= ($style != '' ? ' style="' . $style . '"' : '')
                        . ($cssClass != '' ? ' class="' . $cssClass . '"' : '')
                        . ' data-label="' . $place_holder . '"'
                        . ($attribute != '' ? ' ' . $attribute : '')
                        . ' data-type="records">';

                    foreach ($ct->Records as $row) {
                        if ($row['listing_published'] == 0)
                            $style = 'style="color:red"';
                        else
                            $style = '';

                        $htmlResult .= '<option value="' . $row[$ct->Table->realidfieldname] . '" '
                            . ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' SELECTED ' : '')
                            . ' ' . $style . '>';

                        $htmlResult .= htmlspecialchars($row[$real_field] ?? '') . '</option>';
                    }

                    $htmlResult .= '</SELECT>';
                    break;

                case 'radio' :

                    $htmlResult .= '<table style="border:none;" id="sqljoin_table_' . $control_name . '">';
                    $i = 0;
                    foreach ($ct->Records as $row) {
                        $htmlResult .= '<tr><td>'
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
                    $htmlResult .= '</table>';
                    break;

                case 'checkbox' :

                    if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
                        $real_field = $real_field_row->realfieldname . $langPostfix;
                    else
                        $real_field = $real_field_row->realfieldname;

                    $htmlResult .= '<table style="border:none;">';
                    $i = 0;
                    foreach ($ct->Records as $row) {
                        $htmlResult .= '<tr><td>'
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
                    $htmlResult .= '</table>'
                        . '<input type="hidden"'
                        . ' id="' . $control_name . '_off" '
                        . ' name="' . $control_name . '_off" '
                        . 'value="1" >';

                    break;

                case 'multibox' :

                    $htmlResult .= JHTMLESRecords::getMultiBox($ct, $ct_noFilter, $valueArray, $field,
                        $control_name, $style, $cssClass, $attribute, $tableName, $dynamic_filter, $langPostfix, $place_holder);
                    break;

                default:
                    return '<p>Incorrect (unknown) selector</p>';
            }
        } else {
            //with layout
            $pair = JoomlaBasicMisc::csv_explode(':', $field);

            if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout')
                return '<p>unknown field/layout command "' . $field . '" should be like: "layout:' . $pair[1] . '".</p>';

            $Layouts = new Layouts($ct);
            $layoutCode = $Layouts->getLayout($pair[1]);

            if ($layoutCode == '')
                return '<p>layout "' . $pair[1] . '" not found or is empty.</p>';

            $htmlResult .= '<table style="border:none;" id="sqljoin_table_' . $control_name . '">';
            $i = 0;
            foreach ($ct->Records as $row) {
                $htmlResult .= '<tr><td>';

                if ($selectorPair[0] == 'multi' or $selectorPair[0] == 'checkbox') {
                    $htmlResult .= '<input type="checkbox"'
                        . ' name="' . $control_name . '[]"'
                        . ' id="' . $control_name . '_' . $i . '"'
                        . ' value="' . $row[$ct->Table->realidfieldname] . '"'
                        . ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked"' : '')
                        . ' data-type="records" />';
                } elseif ($selectorPair[0] == 'single' or $selectorPair[0] == 'radio') {
                    $htmlResult .= '<input type="radio" '
                        . ' name="' . $control_name . '"'
                        . ' id="' . $control_name . '_' . $i . '"'
                        . ' value="' . $row[$ct->Table->realidfieldname] . '"'
                        . ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked"' : '')
                        . ' data-type="records" />';
                } else
                    return '<p>Incorrect selector</p>';

                $htmlResult .= '</td>';

                $htmlResult .= '<td>';

                //process layout
                $htmlResult .= '<label for="' . $control_name . '_' . $i . '">';

                if ($ct->Env->legacySupport) {
                    $LayoutProc = new LayoutProcessor($ct);
                    $LayoutProc->layout = $layoutCode;
                    $layoutcode_tmp = $LayoutProc->fillLayout($row);
                } else
                    $layoutcode_tmp = $layoutCode;

                $twig = new TwigProcessor($ct, $layoutcode_tmp);
                $htmlResult .= $twig->process($row);
                if ($twig->errorMessage !== null)
                    $ct->errors[] = $twig->errorMessage;

                $htmlResult .= '</label>';

                $htmlResult .= '</td></tr>';
                $i++;
            }
            $htmlResult .= '</table>'
                . '<input type="hidden"'
                . ' id="' . $control_name . '_off" '
                . ' name="' . $control_name . '_off" '
                . 'value="1" >';
        }
        return $htmlResult;
    }

    static protected function getCT($tableName, $filter, $allowUnpublished, $sortByField, $field): ?CT
    {
        $menuParams = self::prepareParams($tableName, $filter, $allowUnpublished, $sortByField, $field);

        $ct = new CT;
        $ct->setParams($menuParams, true);

        // -------------------- Table

        $ct->getTable($ct->Params->tableName);

        if ($ct->Table->tablename === null) {
            $ct->errors[] = 'Catalog View: Table not selected.';
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
        $htmlResult = '';

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
            $htmlResult .= LinkJoinFilters::getFilterBox($tableName, $dynamic_filter, $control_name, $filterValue, $control_name_postfix);
        }

        $htmlResult_options = '';

        if (!str_contains($control_name, '_selector'))
            $htmlResult_options .= '<option value="">- ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT') . ' ' . $place_holder . '</option>';

        if ($value == '' or $value == ',' or $value == ',,')
            $valueFound = true;
        else
            $valueFound = false;

        foreach ($ct->Records as $row) {
            if (in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) {
                $htmlResult_options .= '<option value="' . $row[$ct->Table->realidfieldname] . '" SELECTED ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';
                $valueFound = true;
            } else
                $htmlResult_options .= '<option value="' . $row[$ct->Table->realidfieldname] . '" ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';

            $v = JoomlaBasicMisc::processValue($field, $ct, $row);
            $htmlResult_options .= htmlspecialchars($v ?? '');

            if ($dynamic_filter != '') {
                $elements[] = $v;
                $elementsID[] = $row[$ct->Table->realidfieldname];
                $elementsFilter[] = $row[$ct->Env->field_prefix . $dynamic_filter];
                $elementsPublished[] = (int)$row['listing_published'];
            }
            $htmlResult_options .= '</option>';
        }

        if ($value != '' and $value != ',' and $value != ',,' and !$valueFound) {
            //_noFilter - add all elements, don't remember why, probably if value is not in the list after the filter
            //workaround in case the value not found

            foreach ($ct_noFilter->Records as $row) {
                if (in_array($row[$ct_noFilter->Table->realidfieldname], $valueArray) and count($valueArray) > 0) {
                    $htmlResult_options .= '<option value="' . $row[$ct_noFilter->Table->realidfieldname] . '" SELECTED ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';
                } else
                    $htmlResult_options .= '<option value="' . $row[$ct_noFilter->Table->realidfieldname] . '" ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';

                $v = JoomlaBasicMisc::processValue($field, $ct_noFilter, $row);
                $htmlResult_options .= htmlspecialchars($v ?? '');

                if ($dynamic_filter != '') {
                    $elements[] = $v;
                    $elementsID[] = $row[$ct_noFilter->Table->realidfieldname];
                    $elementsFilter[] = $row[$ct_noFilter->Env->field_prefix . $dynamic_filter];
                    $elementsPublished[] = (int)$row['listing_published'];
                }
                $htmlResult_options .= '</option>';
            }
        }

        $htmlResult .= '<SELECT name="' . $control_name . '" id="' . $control_name . $control_name_postfix . '"'
            . ($style != '' ? ' style="' . $style . '"' : '')
            . ($cssClass != '' ? ' class="' . $cssClass . '"' : '')
            . ($attribute != '' ? ' ' . $attribute : '')
            . ' data-label="' . $place_holder . '"'
            . ' data-type="records" />';

        $htmlResult .= $htmlResult_options;

        $htmlResult .= '</SELECT>';

        if ($dynamic_filter != '') {
            $htmlResultJS .= '
			<div id="' . $control_name . $control_name_postfix . '_elements" style="display:none;">' . json_encode($elements) . '</div>
			<div id="' . $control_name . $control_name_postfix . '_elementsID" style="display:none;">' . implode(',', $elementsID) . '</div>
			<div id="' . $control_name . $control_name_postfix . '_elementsFilter" style="display:none;">' . implode(';', $elementsFilter) . '</div>
			<div id="' . $control_name . $control_name_postfix . '_elementsPublished" style="display:none;">' . implode(',', $elementsPublished) . '</div>
			';
            $htmlResult = $htmlResultJS . $htmlResult;
        }
        return $htmlResult;
    }

    static protected function getMultiBox(CT &$ct, &$ct_noFilter, $valueArray, $field,
                                             $control_name, $style, $cssClass, $attribute, $tableName, $dynamic_filter, $langPostfix = '', $place_holder = ''): string
    {
        $real_field_row = Fields::getFieldRowByName($field, null, $tableName);

        if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
            $real_field = $real_field_row->realfieldname . $langPostfix;
        else
            $real_field = $real_field_row->realfieldname;

        $ctInputBoxRecords_r = [];
        $ctInputBoxRecords_v = [];
        $ctInputBoxRecords_p = [];

        foreach ($ct->Records as $row) {

            if (in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) {
                $ctInputBoxRecords_r[] = $row[$ct->Table->realidfieldname]; //record ID

                if ($real_field_row->type == 'sqljoin') {
                    $layoutCode = '{{ ' . $real_field_row->fieldname . ' }}';
                    $twig = new TwigProcessor($ct, $layoutCode);
                    $ctInputBoxRecords_v[] = $twig->process($row);
                } else
                    $ctInputBoxRecords_v[] = $row[$real_field]; //Value string

                $ctInputBoxRecords_p[] = (int)$row['listing_published']; //record published status
            }
        }

        $htmlResult = '
		<script>
			//Field value
			ctInputBoxRecords_r["' . $control_name . '"] = ' . json_encode($ctInputBoxRecords_r) . ';
			ctInputBoxRecords_v["' . $control_name . '"] = ' . json_encode($ctInputBoxRecords_v) . ';
			ctInputBoxRecords_p["' . $control_name . '"] = ' . json_encode($ctInputBoxRecords_p) . ';
		</script>
		';

        $single_box = JHTMLESRecords::getSingle($ct, $ct_noFilter, $valueArray, $field,
            $control_name, '_selector', $style, $cssClass, $attribute, '', $tableName, $dynamic_filter, $place_holder);

        $icon_path = CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/';
        $htmlResult .= '<div style="padding-bottom:20px;"><div style="width:90%;" id="' . $control_name . '_box"></div>'
            . '<div style="height:30px;">'
            . '<div id="' . $control_name . '_addButton" style="visibility: visible;"><img src="' . $icon_path . 'new.png" alt="Add" title="Add" style="cursor: pointer;" '
            . 'onClick="ctInputBoxRecords_addItem(\'' . $control_name . '\',\'_selector\')" /></div>'
            . '<div id="' . $control_name . '_addBox" style="visibility: hidden;">'
            . '<div style="float:left;">' . $single_box . '</div>'
            . '<img src="' . $icon_path . 'plus.png" '
            . 'alt="Add" title="Add" '
            . 'style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;width:16px;height:16px;" '
            . 'onClick="ctInputBoxRecords_DoAddItem(\'' . $control_name . '\',\'_selector\')" />'
            . '<img src="' . $icon_path . 'cancel.png" alt="Cancel" title="Cancel" '
            . 'style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;width:16px;height:16px;" '
            . 'onClick="ctInputBoxRecords_cancel(\'' . $control_name . '\',\'_selector\')" />'

            . '</div>'
            . '</div>'
            . '<div style="visibility: hidden;"><select name="' . $control_name . '[]" id="' . $control_name . '" MULTIPLE ></select></div>'
            . '</div>

		<script>
			ctInputBoxRecords_showMultibox("' . $control_name . '","_selector");
		</script>
		';

        return $htmlResult;
    }
}
