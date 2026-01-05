<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

class Search_checkbox extends BaseSearch
{
	function __construct(CT &$ct, Field $field, string $moduleName, array $attributes, int $index, string $where, string $whereList, string $objectName)
	{
		parent::__construct($ct, $field, $moduleName, $attributes, $index, $where, $whereList, $objectName);
		BaseInputBox::selectBoxAddCSSClass($this->attributes);
	}

	function render($value): string
	{
		$translations = array(common::translate('COM_CUSTOMTABLES_ANY'), common::translate('COM_CUSTOMTABLES_YES'), common::translate('COM_CUSTOMTABLES_NO'));
		//$this->getOnChangeAttributeString();

		return '<select'
			. ' id="' . $this->objectName . '"'
			. ' name="' . $this->objectName . '"'
			. BaseInputBox::attributes2String($this->attributes) . '>'
			. '<option value="" ' . ($value == '' ? 'SELECTED' : '') . '>' . $this->field->title . ' - ' . $translations[0] . '</option>'
			. '<option value="1" ' . (($value == '1' or $value == 'true') ? 'SELECTED' : '') . '>' . $this->field->title . ' - ' . $translations[1] . '</option>'
			. '<option value="0" ' . (($value == '0' or $value == 'false') ? 'SELECTED' : '') . '>' . $this->field->title . ' - ' . $translations[2] . '</option>'
			. '</select>';
	}
}