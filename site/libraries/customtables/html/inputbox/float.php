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
defined('_JEXEC') or die();

class InputBox_float extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
		self::inputBoxAddCSSClass($this->attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetFloat($this->ct->Table->fieldPrefix . $this->field->fieldname);
			if ($value === null)
				$value = (float)$defaultValue;
		}

		$this->attributes['type'] = 'text';

		$decimals = (($this->field->params !== null and count($this->field->params) > 0) ? intval($this->field->params[0]) : 0);
		if ($decimals < 0)
			$decimals = 0;

		if (isset($this->field->params[2]) and $this->field->params[2] == 'smart')
			$this->attributes['onkeypress'] = 'ESsmart_float(this,event,' . $decimals . ')';

		$this->attributes['value'] = htmlspecialchars($value ?? '');

		return '<input ' . self::attributes2String($this->attributes) . ' />';
	}
}