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

class InputBox_url extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
		self::inputBoxAddCSSClass($this->attributes, $this->ct->Env->version);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetString($this->ct->Env->field_prefix . $this->field->fieldname, '');
			//https://stackoverflow.com/questions/58265286/remove-all-special-characters-from-string-to-make-it-a-valid-email-but-keep-%C3%A4%C3%B6%C3%BC
			$value = preg_replace('/[^\p{L}\d\-.;@_]/u', '', $value);

			if ($value == '')
				$value = $defaultValue;
		}

		$filters = array();
		$filters[] = 'url';

		if (isset($this->field->params[1]) and $this->field->params[1] == 'true')
			$filters[] = 'https';

		if (isset($this->field->params[2]) and $this->field->params[2] != '')
			$filters[] = 'domain:' . $this->field->params[2];

		$this->attributes['type'] = 'text';
		$this->attributes['value'] = htmlspecialchars($value ?? '');
		$this->attributes['maxlength'] = 1024;
		$this->attributes['data-sanitizers'] = 'trim';
		$this->attributes['data-filters'] = implode(',', $filters);

		return '<input ' . self::attributes2String($this->attributes) . ' />';
	}
}