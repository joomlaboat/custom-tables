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
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Field;
use CustomTables\Fields;
use CustomTables\Inputbox;

class JHTMLCTTableJoin
{
    static public function render($control_name, Field $field, $value, $option_list, $attributes): string
    {
        $params = new JRegistry;
        $params->loadArray([]);
        $ct = new CT($params, true);

        $filter = [];

        $parent_filter_field_name = JHTMLCTTableJoin::parseTagArguments($option_list, $filter);
        JHTMLCTTableJoin::parseTypeParams($ct, $field, $filter, $parent_filter_field_name);

        //Get initial table filters based on the value

        $js_filters = [];
        $js_filters_selfParent = [];
        $parent_id = $value;
        JHTMLCTTableJoin::processValue($filter, $parent_id, $js_filters, $js_filters_selfParent);

        $js_filters[] = $value;

        $key = JoomlaBasicMisc::generateRandomString();
        $ct->app->setUserState($key, $filter);

        $data = [];
        $data[] = 'data-key="' . $key . '"';
        $data[] = 'data-fieldname="' . $field->fieldname . '"';
        $data[] = 'data-controlname="' . $control_name . '"';
        $data[] = 'data-valuefilters="' . base64_encode(json_encode($js_filters)) . '"';
        $data[] = 'data-value="' . $value . '"';

        if ($ct->app->getName() == 'administrator')   //since   3.2
            $formID = 'adminForm';
        else
            $formID = 'eseditForm';

        $formID .= $field->ct->Params->ModuleId;

        return '<div id="' . $control_name . 'Wrapper" ' . implode(' ', $data) . '><div id="' . $control_name . 'Selector0_0">'
            . self::ctUpdateTableJoinLink($ct, $control_name, 0, 0, "", $formID, $attributes, $filter, $js_filters)
            . '</div></div>';

        /*
                return '<div id="' . $control_name . 'Wrapper" ' . implode(' ', $data) . '><div id="' . $control_name . 'Selector0_0"></div></div>
                    <script>
                        ctUpdateTableJoinLink("' . $control_name . '",0,true,0,"","' . $formID . '","' . base64_encode($attributes) . '");
                    </script>
        ';
        */
    }

    protected static function parseTagArguments($option_list, &$filter)
    {
        //Preselects
        //example: city.edit("cssclass","attributes",[["province","name",true,"active=1","name"],["city","name",false,"active=1","name"],["streets","layout:TheStreetName",false,"active=1","streetname"]])
        //parameter 3 can be 1 or 2 dimensional array.
        //One dimensional array will be converted to 2 dimensional array.
        //$cssclass = $option_list[0]; // but it's have been already progressed
        //$attribute = $option_list[1]; // but it's have been already progressed

        //Twig teg example:
        //{{ componentid.edit("mycss","readyonly",[["grades","grade"],["classes","class"]]) }}
        //{{ componentid.edit("mycss","readyonly",["grades","grade"]) }}
        //{{ componentid.edit("mycss","readyonly","grades","grade") }}

        $parent_filter_field_name = '';

        if (isset($option_list[2])) {
            $option = $option_list[2];
            if (is_array($option)) {
                if (is_array($option[0])) {
                    foreach ($option as $optionFilter) {
                        $optionFilter[5] = $parent_filter_field_name;
                        $filter[] = $optionFilter;
                        $parent_filter_field_name = $optionFilter[0];
                    }
                } else {
                    $filter[] = [$option[0], $option[1], $option[2], $option[3], $option[4], $parent_filter_field_name];
                    $parent_filter_field_name = $option[0];
                }
            } else {
                //$filter[] = [table_name, field_name, allow_unpublished, filter, order_by];
                $filter[] = [$option, $option_list[3], $option_list[4], $option_list[5], $option_list[6], $parent_filter_field_name];
                $parent_filter_field_name = $option;
            }
        }

        return $parent_filter_field_name;
    }

    protected static function parseTypeParams(CT $temp_ct, $field, &$filter, &$parent_filter_field_name): bool
    {
        if (count($field->params) > 6 or (isset($field->params[7]) and ($field->params[7] == 'addforignkey' or $field->params[7] == 'noforignkey'))) {
            //Dynamic filter,
            if ($field->params[3] != null and $field->params[3] != '') {
                $temp_ct->getTable($field->params[0]);

                if ($temp_ct->Table->tablename == '') {
                    $temp_ct->app->enqueueMessage('Dynamic filter field "' . $field->params[3] . '" : Table "' . $temp_ct->Table->tablename . '" not found.', 'error');
                    return false;
                }

                //Find dynamic filter field
                foreach ($temp_ct->Table->fields as $fld) {
                    if ($fld['fieldname'] == $field->params[3]) {
                        //Add dynamic filter parameters
                        $temp_type_params = JoomlaBasicMisc::csv_explode(',', $fld['typeparams']);
                        $filter[] = [$temp_type_params[0], $temp_type_params[1], $temp_type_params[5], $temp_type_params[2], $temp_type_params[4], $parent_filter_field_name];

                        $parent_filter_field_name = $temp_type_params[0];
                        break;
                    }
                }
            }

            $filter[] = [$field->params[0], $field->params[1], $field->params[5], $field->params[2], $field->params[4], $parent_filter_field_name];
            $parent_filter_field_name = $field->params[0];
        } else {
            $filter[] = [$field->params[0], $field->params[1], $field->params[2], $field->params[3], $field->params[4], $parent_filter_field_name];
        }
        return true;
    }

    protected static function processValue(&$filter, &$parent_id, &$js_filters, &$js_filters_selfParent): void
    {
        for ($i = count($filter) - 1; $i >= 0; $i--) {
            $flt = $filter[$i];
            $tablename = $flt[0];
            $temp_ct = new CT;
            $temp_ct->getTable($tablename);

            if ($i > 0)//No need to filter first select element values
            {
                $join_to_tablename = $flt[5];
                $parent_id = JHTMLCTTableJoin::getParentFilterID($temp_ct, $parent_id, $join_to_tablename);
                $js_filters[] = $parent_id;
            }

            //Check if this table has self-parent field - the TableJoin field linked with the same table.
            $selfParentField = Fields::getSelfParentField($temp_ct);
            if ($selfParentField != null) {
                $selfParent_type_params = JoomlaBasicMisc::csv_explode(',', $selfParentField['typeparams']);

                if ($filter[$i][3] == '')
                    $filter[$i][3] = $selfParent_type_params[2];

                if ($filter[$i][4] == '')
                    $filter[$i][4] = $selfParent_type_params[4];

                $filter[$i][6] = $selfParentField['fieldname'];
                $js_filters_selfParent[] = 1;

                $join_to_tablename = $filter[$i][0];

                $selfParent_filters = [];
                while ($parent_id != null) {
                    $parent_id = JHTMLCTTableJoin::getParentFilterID($temp_ct, $parent_id, $join_to_tablename);
                    if ($parent_id != null)
                        $selfParent_filters[] = $parent_id;
                }
                $selfParent_filters[] = "";
                $js_filters[] = array_reverse($selfParent_filters);
            }
        }

        if (!is_array(end($js_filters)))
            $js_filters[] = "";

        $js_filters = array_reverse($js_filters);
    }

    protected static function getParentFilterID($temp_ct, $parent_id, $join_to_tablename)
    {
        $join_realfieldname = '';

        foreach ($temp_ct->Table->fields as $fld) {
            if ($fld['type'] == 'sqljoin') {
                $type_params = JoomlaBasicMisc::csv_explode(',', $fld['typeparams']);
                $join_tablename = $type_params[0];

                if ($join_tablename == $join_to_tablename) {
                    $join_realfieldname = $fld['realfieldname'];
                    break;
                }
            }
        }

        if ($join_realfieldname == '')
            return null;

        $query = 'SELECT ' . $join_realfieldname . ' FROM ' . $temp_ct->Table->realtablename . ' WHERE '
            . $temp_ct->Table->realidfieldname . '=' . $temp_ct->db->quote($parent_id) . ' LIMIT 1';

        $temp_ct->db->setQuery($query);
        $recs = $temp_ct->db->loadAssocList();
        if (count($recs) == 0)
            return null;

        return $recs[0][$join_realfieldname];
    }

    protected static function ctUpdateTableJoinLink(CT &$ct, $control_name, $index, $sub_index, $object_id, $formId, $attributes, $filter, $js_filters)
    {
        $subFilter = '';
        $additional_filter = '';

        if (is_array($js_filters[$index])) {
            //Self Parent field
            if ($js_filters[$index][$sub_index] != '')
                $subFilter = $js_filters[$index][$sub_index];
        } else if ($js_filters[$index] != '')
            $additional_filter = $js_filters[$index];

        $result = Inputbox::renderTableJoinSelectorJSON_Process($ct, $filter, $index, $additional_filter, $subFilter, false);
        $resultJSON = json_decode($result);

        return self::ctRenderTableJoinSelectBox($ct, $control_name, $resultJSON, $index, $sub_index, $object_id, $formId, $attributes, $filter, $js_filters);
    }

    protected static function ctRenderTableJoinSelectBox(CT &$ct, $control_name, $r, $index, $sub_index, $parent_object_id, $formId, $attributes, $filter, $js_filters)
    {
        $next_index = $index;
        $next_sub_index = $sub_index;

        if (is_array($js_filters[$index])) {
            //Self Parent field
            $next_sub_index += 1;
            if ($next_sub_index == count($js_filters[$index])) {
                $val = null;
            } else
                $val = $js_filters[$next_index][$next_sub_index];
        } else {
            $next_index += 1;
            $val = $js_filters[$next_index];
        }

        if ($r->error)
            return $r->error;

        if (count($r) == 0) {
            if (is_array($js_filters[$next_index])) {
                $next_sub_index = 0;
                $next_index += 1;

                if ($next_index + 1 < count($js_filters)) {
                    $result = '<div id="' . $control_name . 'Selector' . $next_index . '_' . $next_sub_index . '"></div>';

                    return self::ctUpdateTableJoinLink($ct, $control_name, $next_index, $next_sub_index, $parent_object_id, $formId, $attributes, $filter, $js_filters) . $result;

                } else
                    return "No items to select..";
            } else {
                return "No items to select";
            }
        }

        $result = '';

        $cssClass = 'form-select valid form-control-success';
        if ($ct->Env->version < 4)
            $cssClass = 'inputbox';

        if ($next_index + 1 < count($js_filters)) {
            //Add select box
            $current_object_id = $control_name + $index;

            if (is_array($js_filters[$index]))
                $current_object_id .= '_' . $sub_index;

            $onChangeAttribute = ' onChange="ctUpdateTableJoinLink(\'' . $control_name . '\', ' . $next_index . ', false, ' . $next_sub_index . ',\'' . $current_object_id . '\', \'' . $formId . '\', \'' . $attributes . '\')"';
            $result .= '<select id="' . $current_object_id . '"' . $onChangeAttribute . ' class="' . $cssClass . '">';
        } else
            $result .= '<select id="' . $control_name . '" name="' . $control_name . '" class="' . $cssClass . '"' . $attributes . '>';

        $result .= '<option value="">- Select</option>';

        for ($i = 0; $i < count($r); $i++)
            $result .= '<option value="' . $r[$i]->id . '"' . ($r[$i]->id == $val ? ' selected="selected"' : '') . '>' . $r[$i]->label . '</option>';

        $result .= '</select>';

        //Prepare the space for next elements
        $result .= '<div id="' . $control_name . 'Selector' . $next_index . '_' . $next_sub_index . '"></div>';

        if ($next_index + 1 < count($js_filters) && $val != null)
            $result .= self::ctUpdateTableJoinLink($ct, $control_name, $next_index, $next_sub_index, null, $formId, $attributes, $filter, $js_filters);

        return $result;
    }
}
