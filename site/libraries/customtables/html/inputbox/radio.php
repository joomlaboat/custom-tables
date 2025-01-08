<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
use Exception;

defined('_JEXEC') or die();

class InputBox_radio extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function getOptions(?string $value): array
	{
		$options = [];

		foreach ($this->field->params as $radioValue) {
			$v = trim($radioValue);
			$option = ["value" => $v, "label" => $v];

			if ($v === $value)
				$option['selected'] = true;

			$options[] = $option;
		}
		return $options;
	}

	/**
	 * @throws Exception
	 * @since 3.3.5
	 */
	function render(?string $value, ?string $defaultValue): string
	{
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			return $this->render_joomla4($value, $defaultValue);
		else
			return $this->render_joomla3($value, $defaultValue);
	}

	/**
	 * @throws Exception
	 * @since 3.3.5
	 */
	function render_joomla4(?string $value, ?string $defaultValue): string
	{
		$result = '<div class="' . (empty($this->attributes['class']) ? 'radio' : $this->attributes['class']) . '">';
		$i = 0;

		if ($value === null) {
			$value = common::inputGetString($this->ct->Table->fieldPrefix . $this->field->fieldname, '');
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
			self::addCSSClass($attributes, 'form-check-input');

			if ($value == $v)
				$attributes['checked'] = 'checked';

			$result .= '<div class="form-check' . ($value == $v ? ' has-success' : '') . '">'
				. '<input id="' . $element_id . '_' . $i . '" ' . self::attributes2String($attributes) . ' />'
				. '<label for="' . $element_id . '_' . $i . '">' . $v . '</label>'
				. '</div>';

			$i++;
		}
		$result .= '</div>';

		return $result;
	}

	/**
	 * @throws Exception
	 * @since 3.3.5
	 */
	function render_joomla3(?string $value, ?string $defaultValue): string
	{
		$result = '<ul class="' . (empty($this->attributes['class']) ? 'list-unstyled' : $this->attributes['class']) . '">';
		$i = 0;

		if ($value === null) {
			$value = common::inputGetString($this->ct->Table->fieldPrefix . $this->field->fieldname, '');
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