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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Joomla\CMS\HTML\HTMLHelper;

//Probably unused

class Search_range extends BaseSearch
{
	function __construct(CT &$ct, Field $field, string $moduleName, array $attributes, int $index, string $where, string $whereList, string $objectName)
	{
		parent::__construct($ct, $field, $moduleName, $attributes, $index, $where, $whereList, $objectName);
		BaseInputBox::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);
		BaseInputBox::inputBoxAddCSSClass($this->attributes, $this->ct->Env->version);
	}

	function render($value): string
	{
		$result = '';

		if ($this->ct->Env->version < 4)
			$default_class = 'inputbox';
		else
			$default_class = 'form-control';

		//$value_min = ''; //TODO: Check this
		$value_max = '';

		if ($this->field->type == 'date' or $this->field->type == 'range')
			$d = '-to-';
		elseif ($this->field->type == 'int' or $this->field->type == 'float')
			$d = '-';
		else
			return 'Cannot search by "' . $this->field->type . '"';

		$values = explode($d, $value);
		$value_min = $values[0];

		if (isset($values[1]))
			$value_max = $values[1];

		if ($value_min == '')
			$value_min = common::inputPostString($this->objectName . '_min');

		if ($value_max == '')
			$value_max = common::inputPostString($this->objectName . '_max');

		//header function

		$js = '
	function Update' . $this->objectName . 'Values()
	{
		var o=document.getElementById("' . $this->objectName . '");
		var v_min=document.getElementById("' . $this->objectName . '_min").value
		var v_max=document.getElementById("' . $this->objectName . '_max").value;
		o.value=v_min+"' . $d . '"+v_max;

		//' . $this->moduleName . '_onChange(' . $this->index . ',v_min+"' . $d . '"+v_max,"' . $this->field->fieldname . '","' . urlencode($this->where) . '","' . urlencode($this->whereList) . '");
	}
';
		$this->ct->document->addCustomTag('<script>' . $js . '</script>');
		//end of header function

		$attribs = 'onChange="Update' . $this->objectName . 'Values()" class="' . $default_class . '" ';

		$result .= '<input type="hidden"'
			. ' id="' . $this->objectName . '" '
			. ' name="' . $this->objectName . '" '
			. ' value="' . $value_min . $d . $value_max . '" '
			. ' onkeypress="es_SearchBoxKeyPress(event)"'
			. ' data-type="range" />';

		$result .= '<table class="es_class_min_range_table" style="border: none;" class="' . $this->attributes['class'] . '" ><tbody><tr><td style="vertical-align: middle;">';

		//From
		if ($this->field->params[0] ?? '' == 'date') {
			$result .= HTMLHelper::calendar($value_min, $this->objectName . '_min', $this->objectName . '_min', '%Y-%m-%d', $attribs);
		} else {
			$result .= '<input type="text"'
				. ' id="' . $this->objectName . '_min" '
				. ' name="' . $this->objectName . '_min" '
				. 'value="' . $value_min . '" '
				. ' onkeypress="es_SearchBoxKeyPress(event)" '
				. ' ' . str_replace('class="', 'class="es_class_min_range ', $attribs)
				. ' data-type="range" />';
		}

		$result .= '</td><td style="text-align:center;">-</td><td style="text-align:left;vertical-align: middle;width: 140px;">';

		//TODO: check if this is correct
		if ($this->field->params[0] ?? '' == 'date') {
			$result .= HTMLHelper::calendar($value_max, $this->objectName . '_max', $this->objectName . '_max', '%Y-%m-%d', $attribs);
		} else {
			$result .= '<input type="text"'
				. ' id="' . $this->objectName . '_max"'
				. ' name="' . $this->objectName . '_max"'
				. ' value="' . $value_max . '"'
				. ' onkeypress="es_SearchBoxKeyPress(event)"'
				. ' ' . str_replace('class="', 'class="es_class_min_range ', $attribs)
				. ' data-type="range" />';
		}
		return $result . '</td></tr></tbody></table>';
	}
}