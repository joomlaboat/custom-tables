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

class InputBox_radio extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		$result = '<ul class="' . ($this->attributes['class'] == '' ? 'list-unstyled' : $this->attributes['class']) . '">';
		$i = 0;

		if ($value === null) {
			$value = common::inputGetString($this->ct->Env->field_prefix . $this->field->fieldname, '');
			$value = preg_replace("/[^A-Za-z\d\-]/", '', $value);
			if ($value == '')
				$value = $defaultValue;
		}

		$element_id = $this->attributes['id'];

		$this->attributes['type'] = 'radio';

		foreach ($this->field->params as $radioValue) {
			$v = trim($radioValue);

			$attributes = $this->attributes;
			$attributes['value'] = $v;

			if ($value == $v)
				$attributes['checked'] = 'checked';

			$result .= '<li><input id="' . $element_id . '_' . $i . '" ' . self::attributes2String($attributes) . ' />'
				. '<label for="' . $element_id . '_' . $i . '">' . $v . '</label></li>';

			$i++;
		}
		$result .= '</ul>';

		return $result;
	}
}