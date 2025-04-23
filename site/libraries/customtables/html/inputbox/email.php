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

class InputBox_email extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
		self::inputBoxAddCSSClass($this->attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetString($this->ct->Table->fieldPrefix . $this->field->fieldname, '');
			//https://stackoverflow.com/questions/58265286/remove-all-special-characters-from-string-to-make-it-a-valid-email-but-keep-%C3%A4%C3%B6%C3%BC
			$value = preg_replace('/[^\p{L}\d\-.;@_]/u', '', $value);

			if ($value == '')
				$value = $defaultValue;
		}

		$this->attributes['type'] = 'text';
		$this->attributes['value'] = $value ?? '';
		$this->attributes['maxlength'] = 255;
		$this->attributes['data-filters'] = 'email';
		return '<input ' . self::attributes2String($this->attributes) . ' />';
	}
}