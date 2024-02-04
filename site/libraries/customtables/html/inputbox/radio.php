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
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

class InputBox_radio extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		$result = '<ul>';
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


			$this->attributes['value'] = $v;

			if ($value == $v)
				$this->attributes['checked'] = 'checked';

			//$this->attributes['id'] = $element_id . '_' . $i;

			$result .= '<input id="' . $element_id . '_' . $i . '" ' . self::attributes2String($this->attributes) . ' />'
				. '<label for="' . $element_id . '_' . $i . '">' . $v . '</label></li>';

			$i++;
		}
		$result .= '</ul>';

		return $result;
	}
}