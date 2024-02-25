<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC')) die('Restricted access');

use CustomTables\Inputbox;

class ESInputBox
{
	var string $requiredLabel = '';
	var CustomTables\CT $ct;

	function __construct(CustomTables\CT &$ct)
	{
		$this->ct = &$ct;
		$this->requiredLabel = 'COM_CUSTOMTABLES_REQUIREDLABEL';
	}

	function renderFieldBox(array $fieldrow, ?array $row, array $option_list, string $onchange = ''): ?string
	{
		$Inputbox = new Inputbox($this->ct, $fieldrow, $option_list, false, $onchange);

		/*

		$realFieldName = $fieldrow['realfieldname'];

		if ($this->ct->Env->frmt == 'json') {
			//This is the field options for JSON output

			$shortFieldObject = Fields::shortFieldObject($fieldrow, ($row[$realFieldName] ?? null), $option_list);

			if ($fieldrow['type'] == 'sqljoin') {
				$typeParams = CTMiscHelper::csv_explode(',', $fieldrow['typeparams']);

				if (isset($option_list[2]) and $option_list[2] != '')
					$typeParams[2] = $option_list[2];//Overwrites field type filter parameter.

				$typeParams[6] = 'json'; // to get the Object instead of the HTML element.

				$attributes_ = '';
				$value = '';
				$place_holder = '';
				$class = '';

				$list_of_values = HTMLHelper::_('ESSQLJoin.render',
					$typeParams,
					$value,
					false,
					$this->ct->Languages->Postfix,
					$this->ct->Env->field_input_prefix . $fieldrow['fieldname'],
					$place_holder,
					$class,
					$attributes_);

				$shortFieldObject['value_options'] = $list_of_values;
			}

			return $shortFieldObject;
		}
		*/

		$value = $Inputbox->getDefaultValueIfNeeded($row);

		return $Inputbox->render($value, $row);
	}
}
