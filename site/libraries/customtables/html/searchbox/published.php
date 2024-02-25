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

class Search_published extends BaseSearch
{
	function __construct(CT &$ct, Field $field, string $moduleName, array $attributes, int $index, string $where, string $whereList, string $objectName)
	{
		parent::__construct($ct, $field, $moduleName, $attributes, $index, $where, $whereList, $objectName);
		BaseInputBox::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);
	}

	function render($value): string
	{
		$result = '';

		$published = common::translate('COM_CUSTOMTABLES_PUBLISHED');
		$unpublished = common::translate('COM_CUSTOMTABLES_UNPUBLISHED');
		$any = $published . ' ' . common::translate('COM_CUSTOMTABLES_AND') . ' ' . $unpublished;
		$translations = array($any, $published, common::translate('COM_CUSTOMTABLES_UNPUBLISHED'));
		$this->getOnChangeAttributeString();

		$result .= '<select'
			. ' id="' . $this->objectName . '"'
			. ' name="' . $this->objectName . '"'
			. BaseInputBox::attributes2String($this->attributes) . '>'
			. '<option value="" ' . ($value == '' ? 'SELECTED' : '') . '>' . $translations[0] . '</option>'
			. '<option value="1" ' . ($value == '1' ? 'SELECTED' : '') . '>' . $translations[1] . '</option>'
			. '<option value="0" ' . ($value == '0' ? 'SELECTED' : '') . '>' . $translations[2] . '</option>'
			. '</select>';

		return $result;
	}
}