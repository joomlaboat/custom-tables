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

use Exception;

class InputBox_language extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function getOptions(?string $value): array
	{
		$options = [];
		$lang = new Languages();

		foreach ($lang->LanguageList as $language) {

			$option = ["value" => $language->language, "label" => $language->caption];

			if ($language->language === $value)
				$option['selected'] = true;

			$options[] = $option;
		}
		return $options;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null or $value === '') {
			$value = common::inputGetString($this->ct->Table->fieldPrefix . $this->field->fieldname);
			if ($value === null) {
				if ($defaultValue === null or $defaultValue === '') {
					//If it's a new record then current language will be used.
					$value = $this->ct->Languages->tag;
				} else
					$value = $defaultValue;
			}
		}

		self::selectBoxAddCSSClass($this->attributes);
		$lang = new Languages();

		// Start building the select element with attributes
		$select = '<select ' . self::attributes2String($this->attributes) . '>';

		// Optional default option
		$selected = (0 === $value) ? ' selected' : '';
		$select .= '<option value=""' . $selected . '> - ' . common::translate('COM_CUSTOMTABLES_SELECT_LANGUAGE') . '</option>';

		// Generate options for each file in the folder
		foreach ($lang->LanguageList as $language) {
			$selected = ($language->language == $value) ? ' selected' : '';
			$select .= '<option value="' . $language->language . '" ' . $selected . '>' . $language->caption . '</option>';

		}
		$select .= '</select>';
		return $select;
	}
}