<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use Exception;

class Forms
{
    var CT $ct;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
    }

    /**
     * Render an HTML select box populated with options from a database query.
     *
     * @param string $objectId The HTML element's ID and name attribute.
     * @param string $tableName The name of the database table to query.
     * @param array $selects An array of fields to select from the table.
     * @param MySQLWhereClause $whereClause An optional array of conditions to apply in the WHERE clause.
     * @param string|null $orderBy An optional field to use for sorting the results.
     *
     * @return string The HTML select box element.
     *
     * @throws Exception If there is an error in the database query.
     * @since 3.2.2
     */

    public static function renderHTMLSelectBoxFromDB(string $objectId, ?int $value, bool $addSelectOption, string $tableName, array $selects, MySQLWhereClause $whereClause, ?string $orderBy = null, array $arguments = []): string
    {
        //$whereClause = new MySQLWhereClause();
        //$whereClause->addCondition($tableName, $selects);
        /*
        $sql = 'SELECT ' . implode(',', $selects) . ' FROM '
            . $tableName;

        if ($where !== null and count($where) > 0)
            $sql .= ' WHERE ' . implode(' AND ', $where);
*/
        $options = database::loadAssocList($tableName, $selects, $whereClause, ($orderBy !== null ? $orderBy : null));

        $selectBoxOptions = [];

        if (count($options) > 0) {
            $keys = [];
            foreach ($options[0] as $key => $opt)
                $keys[] = $key;

            if ($addSelectOption)
                $selectBoxOptions[] = '<option value=""' . ($value === null ? ' selected="selected"' : '') . '>- Select</option>';

            foreach ($options as $option) {
                $selectBoxOptions[] = '<option value="' . $option[$keys[0]] . '"' . ((int)$option[$keys[0]] === $value ? ' selected="selected"' : '') . '>' . $option[$keys[1]] . '</option>';
            }
        }

        return '<select name="' . $objectId . '" id="' . $objectId . '" ' . implode(' ', $arguments) . '>' . implode('', $selectBoxOptions) . '</select>';
    }

    function renderFieldLabel($field, $allowSortBy = false)
    {
        $OrderingField = null;
        $OrderingDirection = null;

        if ($this->ct->Ordering->ordering_processed_string !== null) {
            $OrderingStringPair = explode(' ', $this->ct->Ordering->ordering_processed_string);
            $OrderingField = $OrderingStringPair[0];
            $OrderingDirection = $OrderingStringPair[1] ?? '';
        }

        if ($field->type == 'dummy')
            return $field->title;

        $field_label = '<label id="' . $this->ct->Env->field_input_prefix . $field->fieldname . '-lbl" for="' . $this->ct->Env->field_input_prefix . $field->fieldname . '" ';
        $class = ($field->description != '' ? 'hasPopover' : '') . ($field->isrequired == 1 ? ' required' : '');

        if ($class != '')
            $field_label .= ' class="' . $class . '"';

        $field_label .= ' title="' . $field->title . '"';

        if ($field->description != "")
            $field_label .= ' data-content="' . $field->description . '"';

        if ($this->ct->Ordering->ordering_processed_string !== null and $allowSortBy) {
            $field_label .= ' style="cursor:pointer"';
            $field_label .= ' onClick="ctOrderChanged(\'' . $field->fieldname . ($OrderingField == $field->fieldname ? ($OrderingDirection == 'desc' ? '' : ' desc') : '') . '\')"';
        }

        $field_label .= ' data-original-title="' . $field->title . '">';

        if (!$allowSortBy or $field->type != 'ordering')
            $field_label .= $field->title;

        if ($this->ct->Ordering->ordering_processed_string !== null and $allowSortBy) {
            if ($OrderingField == $field->fieldname) {
                if ($OrderingDirection == 'desc')
                    $field_label .= '<span class="ms-1 icon-caret-down" aria-hidden="true"></span>';
                else
                    $field_label .= '<span class="ms-1 icon-caret-up" aria-hidden="true"></span>';
            } else
                $field_label .= '<span class="ms-1 icon-sort" aria-hidden="true"></span>';
        }

        if ($field->isrequired == 1 and isset($this->ct->LayoutVariables['layout_type']) and $this->ct->LayoutVariables['layout_type'] == 2)
            $field_label .= '<span class="star" aria-hidden="true">&nbsp;*</span>';

        $field_label .= '</label>';
        return $field_label;
    }
}