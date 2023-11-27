<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Field;
use Joomla\Registry\Registry;

class JHTMLCTTableMultiJoin
{
	static public function render($control_name, Field $field, $listing_is, $value, $option_list, $onchange, $attributes): string
	{
		$params = new Registry;
		$ct = new CT($params, true);

		$filter = [];
		$parent_filter_table_and_field = JHTMLCTTableJoin::parseTagArguments($option_list, $filter);
		$parent_filter_table_name = $parent_filter_table_and_field[0] ?? '';
		$parent_filter_field_name = $parent_filter_table_and_field[1] ?? '';

		$params_filter = [];
		if ($parent_filter_table_name == '' and $parent_filter_field_name == '') {
			JHTMLCTTableJoin::parseTypeParams($field, $params_filter, $parent_filter_table_name, $parent_filter_field_name);
			$params_filter = array_reverse($params_filter);
			if (count($params_filter) > 0 and isset($option_list[3])) {
				$params_filter[0][1] = 'layout:' . $option_list[3];
			}
		}

		$filter = array_merge($filter, $params_filter);
		//Get initial table filters based on the value
		$js_filters = [];
		$js_filters_FieldName = [];
		$parent_id = $value;

		JHTMLCTTableJoin::processValue($filter, $parent_id, $js_filters, $js_filters_FieldName);

		if (count($js_filters) == 0)
			$js_filters[] = $value;

		$key = JoomlaBasicMisc::generateRandomString();
		$ct->app->setUserState($key, $filter);

		$cssClass = '';

		$data = [];
		$data[] = 'data-key="' . $key . '"';
		$data[] = 'data-fieldname="' . $field->fieldname . '"';
		$data[] = 'data-controlname="' . $control_name . '"';
		$data[] = 'data-valuefilters="' . base64_encode(json_encode($js_filters)) . '"';
		$data[] = 'data-valuefiltersnames="' . base64_encode(json_encode($js_filters_FieldName)) . '"';
		$data[] = 'data-onchange="' . base64_encode($onchange) . '"';
		$data[] = 'data-listing_id="' . $listing_is . '"';
		$data[] = 'data-value="' . htmlspecialchars($value ?? '') . '"';

		$addRecordMenuAlias = $option_list[4] ?? null;
		if ($addRecordMenuAlias == '')
			$addRecordMenuAlias = null;

		if ($addRecordMenuAlias !== null)
			$data[] = 'data-addrecordmenualias="' . $addRecordMenuAlias . '"';

		if ($ct->app->getName() == 'administrator')   //since   3.2
			$formID = 'adminForm';
		else {

			if ($ct->Env->isModal)
				$formID = 'ctEditModalForm';
			else {
				$formID = 'ctEditForm';
				$formID .= $field->ct->Params->ModuleId;
			}
		}

		$data[] = 'data-formname="' . $formID . '"';

		$Placeholder = $field->title;

		return '<input type="hidden" id="' . $control_name . '" name="' . $control_name . '" value="' . htmlspecialchars($value ?? '') . '" ' . $attributes . '/>'
			. '<div id="' . $control_name . 'Wrapper" ' . implode(' ', $data) . '>'
			. JHTMLCTTableJoin::ctUpdateTableJoinLink($ct, $control_name, 0, 0, "", $formID, $attributes, $onchange,
				$filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder)
			. '</div>';
	}
}