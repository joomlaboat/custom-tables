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

class InputBox_gps extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		$elementId = $this->attributes['id'];

		if ($value === null) {
			$value = common::inputGetCmd($this->ct->Table->fieldPrefix . $this->field->fieldname, '');
			if ($value == '')
				$value = $defaultValue;
		}

		$html = [];
		$html[] = '<div class="input-group has-success">';
		$html[] = '<input type="text" class="form-control valid form-control-success" id="' . $elementId . '" name="' . $elementId . '" value="' . htmlspecialchars($value ?? '') . '" />';

		$class = common::convertClassString('btn btn-primary');
		$onClick = 'ctInputbox_googlemapcoordinates(\'' . $elementId . '\')';
		$dataInputField = $this->ct->Table->fieldInputPrefix . $elementId;
		$dataButton = $this->ct->Table->fieldInputPrefix . $elementId . '_btn';

		$html[] = '<button type="button" class="' . $class . '" onclick="' . $onClick
			. '" data-inputfield="' . $dataInputField . '" data-button="' . $dataButton . '">&nbsp;...&nbsp;</button>';

		$html[] = '</div>';
		$html[] = '<div id="' . $elementId . '_map" style="width: 480px; height: 540px;display:none;"></div>';

		return implode("\n", $html);
	}
}