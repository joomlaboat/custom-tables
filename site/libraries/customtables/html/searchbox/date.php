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

use DateTime;

class Search_date extends BaseSearch
{
	function __construct(CT &$ct, Field $field, string $moduleName, array $attributes, int $index, string $where, string $whereList, string $objectName)
	{
		parent::__construct($ct, $field, $moduleName, $attributes, $index, $where, $whereList, $objectName);
		BaseInputBox::selectBoxAddCSSClass($this->attributes);
		BaseInputBox::inputBoxAddCSSClass($this->attributes);
	}

	function render($value): string
	{
		common::loadJQueryUI();

		$js = '

jQuery(document).ready(function($) {
    $("#' . $this->objectName . '_start").datepicker({
        dateFormat: "yy-mm-dd",
        onSelect: function(selectedDate) {
            $("#' . $this->objectName . '_end").datepicker("option", "minDate", selectedDate);
        }
    });

    $("#' . $this->objectName . '_end").datepicker({
        dateFormat: "yy-mm-dd",
        onSelect: function(selectedDate) {
            $("#' . $this->objectName . '_start").datepicker("option", "maxDate", selectedDate);
        }
    });
});

';

		$this->ct->LayoutVariables['script'] .= $js;

		$valueParts = explode('-to-', $value);

		$valueStart = isset($valueParts[0]) ? trim($valueParts[0]) : '';
		$valueEnd = isset($valueParts[1]) ? trim($valueParts[1]) : '';

		// Sanitize and validate date format
		$dateFormat = 'Y-m-d'; // Adjust the format according to your needs

		if ($valueStart) {
			$startDateTime = DateTime::createFromFormat($dateFormat, $valueStart);

			if ($startDateTime !== false) {
				$valueStart = $startDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				$valueStart = ''; // Set to default or perform error handling
			}
		}

		if ($valueEnd) {
			$endDateTime = DateTime::createFromFormat($dateFormat, $valueEnd);

			if ($endDateTime !== false) {
				$valueEnd = $endDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				$valueEnd = ''; // Set to default or perform error handling
			}
		}

		$jsOnChange = 'ctSearchBarDateRangeUpdate(\'' . $this->field->fieldname . '\')';

		$hidden = '<input type="hidden" name="' . $this->objectName . '" id="' . $this->objectName . '" value="' . $valueStart . '-to-' . $valueEnd . '">';

		$start = '<input onblur="' . $jsOnChange . '" onchange="' . $jsOnChange . '" value="' . $valueStart . '" type="text"'
			. ' class="' . ($this->attributes['class'] ?? '') . '" id="' . $this->objectName . '_start"'
			. ' placeholder="' . $this->field->title . ' - ' . common::translate('COM_CUSTOMTABLES_START') . '"'
			. ' style="display:inline-block;width:49%;margin-left:0;margin-right:0;float:left;">';

		$end = '<input onblur="' . $jsOnChange . '" onchange="' . $jsOnChange . '" value="' . $valueEnd . '" type="text"'
			. ' class="' . ($this->attributes['class'] ?? '') . '" id="' . $this->objectName . '_end"'
			. ' placeholder="' . $this->field->title . ' - ' . common::translate('COM_CUSTOMTABLES_END') . '"'
			. ' style="display:inline-block;width:49%;margin-left:0;margin-right:0;float:right;">';

		return $hidden . '<div style="position: relative;">' . $start . $end . '</div>';
	}
}