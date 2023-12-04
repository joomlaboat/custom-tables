<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

abstract class BaseInputBox
{
	protected CT $ct;
	protected Field $field;
	protected ?array $row;
	protected array $attributes;
	protected array $option_list;

	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		$this->ct = $ct;
		$this->field = $field;
		$this->row = $row;
		$this->option_list = $option_list;
		$this->attributes = $attributes;
	}

	function selectBoxAddCSSClass(): void
	{
		if (isset($this->attributes['class'])) {
			$classes = explode(' ', $this->attributes['class']);
			if ($this->ct->Env->version < 4) {
				if (!in_array('inputbox', $classes))
					$this->attributes['class'] .= ' inputbox';
			} else {
				if (!in_array('inputbox', $classes))
					$this->attributes['class'] = ' form-select';
			}
		} else {
			if ($this->ct->Env->version < 4)
				$this->attributes['class'] = 'inputbox';
			else
				$this->attributes['class'] = 'form-select';
		}
	}

	function attributes2String(): string
	{
		$result = '';
		foreach ($this->attributes as $key => $attr) {
			$result .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($attr) . '"';
		}
		return $result;
	}
}

class InputBox_Language extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render_language(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetString($this->ct->Env->field_prefix . $this->field->fieldname, '');
			if ($value == '') {
				if ($defaultValue === null or $defaultValue === '') {
					//If it's a new record then current language will be used.
					$langObj = Factory::getLanguage();
					$value = $langObj->getTag();
				} else
					$value = $defaultValue;
			}
		}

		$this->selectBoxAddCSSClass();

		$lang = new Languages();

		// Start building the select element with attributes
		$select = '<select ' . $this->attributes2String() . '>';

		// Optional default option
		$select .= '<option value="">' . common::translate('COM_CUSTOMTABLES_SELECT_LANGUAGE') . '</option>';

		// Generate options for each file in the folder
		foreach ($lang->LanguageList as $language) {
			$selected = ($language->id === (int)$value) ? ' selected' : '';
			$select .= '<option value="' . $language->id . '" ' . $selected . '>' . $language->caption . '</option>';

		}
		$select .= '</select>';
		return $select;
	}
}