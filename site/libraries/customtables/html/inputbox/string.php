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

// no direct access
defined('_JEXEC') or die();

use Exception;

class InputBox_string extends BaseInputBox
{
    function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
    {
        parent::__construct($ct, $field, $row, $option_list, $attributes);
        self::inputBoxAddCSSClass($this->attributes, $this->ct->Env->version);
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function render(?string $value, ?string $defaultValue): string
    {
        if ($value === null) {
            $value = common::inputGetString($this->ct->Table->fieldPrefix . $this->field->fieldname, '');
            if ($value == '')
                $value = $defaultValue;
        }

        $dataset = '';
        if (isset($this->option_list[2]) and $this->option_list[2] == 'autocomplete') {
            $this->attributes['list'] = $this->attributes['id'] . '_datalist';

            $whereClause = new MySQLWhereClause();
            $records = database::loadObjectList($this->ct->Table->realtablename, [$this->field->realfieldname], $whereClause,
                $this->field->realfieldname, null, null, null, 'OBJECT', $this->field->realfieldname);

            $dataset = '<datalist id="' . $this->attributes['id'] . '_datalist">'
                . (count($records) > 0 ? '<option value="' . implode('"><option value="', $records) . '">' : '')
                . '</datalist>';
        }
        $this->attributes['type'] = 'text';
        $this->attributes['value'] = htmlspecialchars($value ?? '');
        $this->attributes['maxlength'] = (($this->field->params !== null and count($this->field->params) > 0 and (int)$this->field->params[0] > 0) ? (int)$this->field->params[0] : 255);

        $input = '<input ' . self::attributes2String($this->attributes) . ' />';

        return $input . $dataset;
    }
}