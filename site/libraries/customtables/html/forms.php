<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

class Forms
{
    var CT $ct;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
    }

    function renderFieldLabel($field, $allowSortBy = false)
    {
        $OrderingStringPair = explode(' ', $this->ct->Ordering->ordering_processed_string);
        $OrderingField = $OrderingStringPair[0];
        $OrderingDirection = $OrderingStringPair[1] ?? '';

        if ($field->type == 'dummy')
            return $field->title;

        $field_label = '<label id="' . $this->ct->Env->field_input_prefix . $field->fieldname . '-lbl" for="' . $this->ct->Env->field_input_prefix . $field->fieldname . '" ';
        $class = ($field->description != '' ? 'hasPopover' : '') . ($field->isrequired ? ' required' : '');

        if ($class != '')
            $field_label .= ' class="' . $class . '"';

        $field_label .= ' title="' . $field->title . '"';

        if ($field->description != "")
            $field_label .= ' data-content="' . $field->description . '"';

        if ($allowSortBy) {
            $field_label .= ' style="cursor:pointer"';
            $field_label .= ' onClick="ctOrderChanged(\'' . $field->fieldname . ($OrderingField == $field->fieldname ? ($OrderingDirection == 'desc' ? '' : ' desc') : '') . '\')"';
        }

        $field_label .= ' data-original-title="' . $field->title . '">';

        if (!$allowSortBy or $field->type != 'ordering')
            $field_label .= $field->title;

        if ($allowSortBy) {
            if ($OrderingField == $field->fieldname) {
                if ($OrderingDirection == 'desc')
                    $field_label .= '<span class="ms-1 icon-caret-down" aria-hidden="true"></span>';
                else
                    $field_label .= '<span class="ms-1 icon-caret-up" aria-hidden="true"></span>';

            } else
                $field_label .= '<span class="ms-1 icon-sort" aria-hidden="true"></span>';
        }

        if ($field->isrequired and isset($this->ct->LayoutVariables['layout_type']) and $this->ct->LayoutVariables['layout_type'] == 2)
            $field_label .= '<span class="star">&#160;*</span>';

        $field_label .= '</label>';

        return $field_label;
    }


}