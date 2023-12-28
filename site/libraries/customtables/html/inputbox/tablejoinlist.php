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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;
use Joomla\Registry\Registry;
use JoomlaBasicMisc;
use LayoutProcessor;


class InputBox_TableJoinList extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function renderNew($control_name, Field $field, $listing_is, $value, $option_list, $onchange, $attributes): string
	{
		$params = new Registry;
		$ct = new CT($params, true);

		$filter = [];
		$parent_filter_table_and_field = InputBox_tablejoin::parseTagArguments($option_list, $filter);
		$parent_filter_table_name = $parent_filter_table_and_field[0] ?? '';
		$parent_filter_field_name = $parent_filter_table_and_field[1] ?? '';

		$params_filter = [];
		if ($parent_filter_table_name == '' and $parent_filter_field_name == '') {
			InputBox_tablejoin::parseTypeParams($field, $params_filter, $parent_filter_table_name, $parent_filter_field_name);
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

		InputBox_tablejoin::processValue($filter, $parent_id, $js_filters, $js_filters_FieldName);

		if (count($js_filters) == 0)
			$js_filters[] = $value;

		$key = common::generateRandomString();
		$ct->app->setUserState($key, $filter);

		$cssClass = '';

		$data = [];
		$data[] = 'data-key="' . $key . '"';
		$data[] = 'data-fieldname="' . $field->fieldname . '"';
		$data[] = 'data-controlname="' . $control_name . '"';
		$data[] = 'data-valuefilters="' . base64_encode(common::ctJsonEncode($js_filters)) . '"';
		$data[] = 'data-valuefiltersnames="' . base64_encode(common::ctJsonEncode($js_filters_FieldName)) . '"';
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
			. InputBox_tablejoin::ctUpdateTableJoinLink($ct, $control_name, 0, 0, "", $formID, $attributes, $onchange,
				$filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder)
			. '</div>';
	}

	function render(?string $value, ?string $defaultValue): string
	{
		$result = '';
		//$this->option_list[0] - CSS Class
		//$this->option_list[1] - Optional Attributes
		//$this->option_list[2] - Parent Selector - Array
		//$this->option_list[3] - Custom Title Layout

		if ($value === null) {
			$value = common::inputGetInt($this->ct->Env->field_prefix . $this->field->fieldname, 0);
			if ($value == 0)
				$value = $defaultValue;
		}

		//records : table, [fieldname || layout:layoutname], [selector: multi || single], filter, |dataLength|

		//Check minimum requirements
		if (count($this->field->params) < 1)
			$result .= 'table not specified';

		if (count($this->field->params) < 2)
			$result .= 'field or layout not specified';

		if (count($this->field->params) < 3)
			$result .= 'selector not specified';

		$esr_table = $this->field->params[0];

		$advancedOption = null;
		if (isset($this->option_list[2]) and is_array($this->option_list[2]))
			$advancedOption = $this->option_list[2];

		if (isset($this->option_list[3])) {
			$esr_field = 'layout:' . $this->option_list[3];
		} else {
			if ($advancedOption and isset($advancedOption[1]) and $advancedOption[1] and $advancedOption[1] != "")
				$esr_field = $advancedOption[1];
			else
				$esr_field = $this->field->params[1] ?? '';
		}

		$esr_selector = $this->field->params[2] ?? '';

		if (isset($this->option_list[5])) {
			//To back-support old style
			$esr_filter = $this->option_list[5];
		} elseif ($advancedOption and isset($advancedOption[3]) and $advancedOption[3] and $advancedOption[3] != "") {
			$esr_filter = $advancedOption[3];
		} elseif (count($this->field->params) > 3)
			$esr_filter = $this->field->params[3];
		else
			$esr_filter = '';

		$dynamic_filter = $this->field->params[4] ?? '';

		if ($advancedOption and isset($advancedOption[4]) and $advancedOption[4] and $advancedOption[4] != "")
			$sortByField = $advancedOption[4];
		else
			$sortByField = $this->field->params[5] ?? '';

		/*
		$records_attributes = ($this->attributes != '' ? ' ' : '')
			. 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
			. 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
			. 'data-type="filelink"';
		*/

		if ($value === null) {
			$value = SaveFieldQuerySet::get_record_type_value($this->field);
			common::inputGetInt($this->ct->Env->field_prefix . $this->field->fieldname);
			if ($value == '')
				$value = $defaultValue;
		}

		if ($this->attributes['class'] == '')
			$this->attributes['class'] = 'ct_improved_selectbox';

		self::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);

		$result .= $this->renderOld(
			$this->field->params,
			$value,
			$esr_table,
			$esr_field,
			$esr_selector,
			$esr_filter,
			$dynamic_filter,
			$sortByField
		);
		return $result;
	}

	public function renderOld(array  $typeParams, ?string $value, $tableName, string $theField, string $selector, $filter,
	                          string $dynamic_filter = '', string $sortByField = ''): string
	{
		$htmlResult = '';
		$fieldArray = explode(';', $theField);
		$field = $fieldArray[0];
		$selectorPair = explode(':', $selector);

		if (isset($typeParams[6]) and $typeParams[6] == 'true')
			$allowUnpublished = true;
		else
			$allowUnpublished = false;

		$ct = self::getCT($tableName, $filter, $allowUnpublished, $sortByField, $field);

		if ($ct == null)
			return '<p>Table not selected</p>';

		$ct_noFilter = null;

		if ($selectorPair[0] == 'single' or $selectorPair[0] == 'multibox')
			$ct_noFilter = self::getCT($tableName, '', $allowUnpublished, $sortByField, $field);

		$valueArray = explode(',', $value);

		if (!str_contains($field, ':')) {
			//without layout
			$real_field_row = Fields::getFieldRowByName($field, null, $tableName);

			switch ($selectorPair[0]) {

				case 'single' :

					self::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);
					$htmlResult .= self::getSingle($ct, $ct_noFilter, $valueArray, $field, '', $this->attributes, $value, $tableName, $dynamic_filter);
					break;

				case 'multi' :
					$attributes = $this->attributes;
					if (count($selectorPair) > 1)
						$attributes['size'] = $selectorPair[1];

					$htmlResult .= self::getMulti($ct, $valueArray, $attributes, $real_field_row);
					break;

				case 'radio' :

					$htmlResult .= '<table style="border:none;" id="sqljoin_table_' . $this->attributes['id'] . '">';
					$i = 0;
					foreach ($ct->Records as $row) {
						$htmlResult .= '<tr><td>'
							. '<input type="radio"'
							. ' name="' . $this->attributes['id'] . '"'
							. ' id="' . $this->attributes['id'] . '_' . $i . '"'
							. ' value="' . $row[$ct->Table->realidfieldname] . '"'
							. ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked"' : '')
							. ($this->attributes['class'] != '' ? ' class="' . $this->attributes['class'] . '"' : '')
							. ' data-type="records" />'
							. '</td>'
							. '<td>'
							. '<label for="' . $this->attributes['id'] . '_' . $i . '">' . $row[$real_field_row->realfieldname] . '</label>'
							. '</td></tr>';
						$i++;
					}
					$htmlResult .= '</table>';
					break;

				case 'checkbox' :

					if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
						$real_field = $real_field_row->realfieldname . $ct->Languages->Postfix;
					else
						$real_field = $real_field_row->realfieldname;

					$htmlResult .= '<table style="border:none;">';
					$i = 0;
					foreach ($ct->Records as $row) {
						$htmlResult .= '<tr><td>'
							. '<input type="checkbox"'
							. ' name="' . $this->attributes['id'] . '[]"'
							. ' id="' . $this->attributes['id'] . '_' . $i . '"'
							. ' value="' . $row[$ct->Table->realidfieldname] . '"'
							. ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked" ' : '')
							. ($this->attributes['class'] != '' ? ' class="' . $this->attributes['class'] . '"' : '')
							. ' data-type="records" />'
							. '</td>'
							. '<td>'
							. '<label for="' . $this->attributes['id'] . '_' . $i . '">' . $row[$real_field] . '</label>'
							. '</td></tr>';

						$i++;
					}
					$htmlResult .= '</table>'
						. '<input type="hidden"'
						. ' id="' . $this->attributes['id'] . '_off" '
						. ' name="' . $this->attributes['id'] . '_off" '
						. 'value="1" >';

					break;

				case 'multibox' :
					$htmlResult .= $this->getMultiBox($ct, $ct_noFilter, $valueArray, $field,
						$this->attributes['id'], $tableName);
					break;

				default:
					return '<p>Incorrect (unknown) selector</p>';
			}
		} else {
			//with layout
			$pair = JoomlaBasicMisc::csv_explode(':', $field);

			if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout')
				return '<p>unknown field/layout command "' . $field . '" should be like: "layout:' . $pair[1] . '".</p>';

			$Layouts = new Layouts($ct);
			$layoutCode = $Layouts->getLayout($pair[1]);

			if ($layoutCode == '')
				return '<p>layout "' . $pair[1] . '" not found or is empty.</p>';

			$htmlResult .= '<table style="border:none;" id="sqljoin_table_' . $this->attributes['id'] . '">';
			$i = 0;
			foreach ($ct->Records as $row) {
				$htmlResult .= '<tr><td>';

				if ($selectorPair[0] == 'multi' or $selectorPair[0] == 'checkbox') {
					$htmlResult .= '<input type="checkbox"'
						. ' name="' . $this->attributes['id'] . '[]"'
						. ' id="' . $this->attributes['id'] . '_' . $i . '"'
						. ' value="' . $row[$ct->Table->realidfieldname] . '"'
						. ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked"' : '')
						. ' data-type="records" />';
				} elseif ($selectorPair[0] == 'single' or $selectorPair[0] == 'radio') {
					$htmlResult .= '<input type="radio" '
						. ' name="' . $this->attributes['id'] . '"'
						. ' id="' . $this->attributes['id'] . '_' . $i . '"'
						. ' value="' . $row[$ct->Table->realidfieldname] . '"'
						. ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' checked="checked"' : '')
						. ' data-type="records" />';
				} else
					return '<p>Incorrect selector</p>';

				$htmlResult .= '</td>';

				$htmlResult .= '<td>';

				//process layout
				$htmlResult .= '<label for="' . $this->attributes['id'] . '_' . $i . '">';

				if ($ct->Env->legacySupport) {
					$LayoutProc = new LayoutProcessor($ct);
					$LayoutProc->layout = $layoutCode;
					$layoutcode_tmp = $LayoutProc->fillLayout($row);
				} else
					$layoutcode_tmp = $layoutCode;

				$twig = new TwigProcessor($ct, $layoutcode_tmp);
				$htmlResult .= $twig->process($row);
				if ($twig->errorMessage !== null)
					$ct->errors[] = $twig->errorMessage;

				$htmlResult .= '</label>';

				$htmlResult .= '</td></tr>';
				$i++;
			}
			$htmlResult .= '</table>'
				. '<input type="hidden"'
				. ' id="' . $this->attributes['id'] . '_off" '
				. ' name="' . $this->attributes['id'] . '_off" '
				. 'value="1" >';
		}
		return $htmlResult;
	}

	static protected function getCT($tableName, $filter, $allowUnpublished, $sortByField, $field): ?CT
	{
		$menuParams = self::prepareParams($tableName, $filter, $allowUnpublished, $sortByField, $field);

		$ct = new CT;
		$ct->setParams($menuParams);

		// -------------------- Table

		$ct->getTable($ct->Params->tableName);

		if ($ct->Table->tablename === null) {
			$ct->errors[] = 'Catalog View: Table not selected.';
			return null;
		}

		// --------------------- Filter
		$ct->setFilter($ct->Params->filter, $ct->Params->showPublished);

		// --------------------- Sorting
		$ct->Ordering->parseOrderByParam();

		// --------------------- Limit
		$ct->applyLimits();

		$ct->getRecords();

		return $ct;
	}

	static protected function prepareParams($tableName, $filter, $allowUnpublished, $sortByField, $field): Registry
	{
		$paramsArray = array();
		$paramsArray['limit'] = 10000;
		$paramsArray['establename'] = $tableName;
		$paramsArray['filter'] = str_replace('****quote****', '"', $filter);

		if ($allowUnpublished)//0 - published only; 1 - hidden only; 2 - Any
			$paramsArray['showpublished'] = 2;
		else
			$paramsArray['showpublished'] = 0;

		$paramsArray['groupby'] = '';

		if ($sortByField != '')
			$paramsArray['forcesortby'] = $sortByField;
		elseif (!str_contains($field, ':')) //cannot sort by layout only by field name
			$paramsArray['forcesortby'] = $field;

		return new Registry($paramsArray);
	}

	static protected function getSingle(CT &$ct, CT &$ct_noFilter, $valueArray,
	                                       $field, $control_name_postfix, array $attributes, ?string $value,
	                                       $tableName, $dynamic_filter = ''): string
	{
		$htmlResult = '';

		if ($dynamic_filter != '') {
			$htmlResultJS = '';
			$elements = array();
			$elementsID = array();
			$elementsFilter = array();
			$elementsPublished = array();

			$filterValue = '';
			foreach ($ct_noFilter->Records as $row) {
				if ($row[$ct_noFilter->Table->realidfieldname] == $value) {
					$filterValue = $row[$ct_noFilter->Env->field_prefix . $dynamic_filter];
					break;
				}
			}
			$htmlResult .= LinkJoinFilters::getFilterBox($tableName, $dynamic_filter, $attributes['id'], $filterValue, $control_name_postfix);
		}

		$htmlResult_options = '';

		if ($control_name_postfix != '_selector')
			$htmlResult_options .= '<option value="">- ' . common::translate('COM_CUSTOMTABLES_SELECT') . ' ' . $attributes['data-label'] . '</option>';

		if ($value == '' or $value == ',' or $value == ',,')
			$valueFound = true;
		else
			$valueFound = false;

		foreach ($ct->Records as $row) {
			if (in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) {
				$htmlResult_options .= '<option value="' . $row[$ct->Table->realidfieldname] . '" SELECTED ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';
				$valueFound = true;
			} else
				$htmlResult_options .= '<option value="' . $row[$ct->Table->realidfieldname] . '" ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';

			$v = JoomlaBasicMisc::processValue($field, $ct, $row);
			$htmlResult_options .= htmlspecialchars($v ?? '');

			if ($dynamic_filter != '') {
				$elements[] = $v;
				$elementsID[] = $row[$ct->Table->realidfieldname];
				$elementsFilter[] = $row[$ct->Env->field_prefix . $dynamic_filter];
				$elementsPublished[] = (int)$row['listing_published'];
			}
			$htmlResult_options .= '</option>';
		}

		if ($value != '' and $value != ',' and $value != ',,' and !$valueFound) {
			//_noFilter - add all elements, don't remember why, probably if value is not in the list after the filter
			//workaround in case the value not found

			foreach ($ct_noFilter->Records as $row) {
				if (in_array($row[$ct_noFilter->Table->realidfieldname], $valueArray) and count($valueArray) > 0) {
					$htmlResult_options .= '<option value="' . $row[$ct_noFilter->Table->realidfieldname] . '" SELECTED ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';
				} else
					$htmlResult_options .= '<option value="' . $row[$ct_noFilter->Table->realidfieldname] . '" ' . ($row['listing_published'] == 0 ? ' disabled="disabled"' : '') . '>';

				$v = JoomlaBasicMisc::processValue($field, $ct_noFilter, $row);
				$htmlResult_options .= htmlspecialchars($v ?? '');

				if ($dynamic_filter != '') {
					$elements[] = $v;
					$elementsID[] = $row[$ct_noFilter->Table->realidfieldname];
					$elementsFilter[] = $row[$ct_noFilter->Env->field_prefix . $dynamic_filter];
					$elementsPublished[] = (int)$row['listing_published'];
				}
				$htmlResult_options .= '</option>';
			}
		}

		$attributes['id'] = $attributes['id'] . $control_name_postfix;
		$attributesString = self::localAttributes2String($attributes);

		$htmlResult .= '<SELECT ' . $attributesString . ' />';

		$htmlResult .= $htmlResult_options;

		$htmlResult .= '</SELECT>';

		if ($dynamic_filter != '') {
			$htmlResultJS .= '
			<div id="' . $attributes['id'] . '_elements" style="display:none;">' . common::ctJsonEncode($elements) . '</div>
			<div id="' . $attributes['id'] . '_elementsID" style="display:none;">' . implode(',', $elementsID) . '</div>
			<div id="' . $attributes['id'] . '_elementsFilter" style="display:none;">' . implode(';', $elementsFilter) . '</div>
			<div id="' . $attributes['id'] . '_elementsPublished" style="display:none;">' . implode(',', $elementsPublished) . '</div>
			';
			$htmlResult = $htmlResultJS . $htmlResult;
		}
		return $htmlResult;
	}

	protected static function localAttributes2String(array $attributes): string
	{
		$result = '';
		foreach ($attributes as $key => $attr) {
			$result .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($attr) . '"';
		}
		return $result;
	}

	static protected function getMulti(CT $ct, $valueArray, array $attributes, $real_field_row): string
	{
		$htmlResult = '';

		if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
			$real_field = $real_field_row->realfieldname . $ct->Languages->Postfix;
		else
			$real_field = $real_field_row->realfieldname;

		$attributes['name'] = $attributes['name'] . '[]';
		$attributesString = self::localAttributes2String($attributes);

		$htmlResult .= '<SELECT ' . $attributesString . ' MULTIPLE>';

		foreach ($ct->Records as $row) {
			if ($row['listing_published'] == 0)
				$style = 'style="color:red"';
			else
				$style = '';

			$htmlResult .= '<option value="' . $row[$ct->Table->realidfieldname] . '" '
				. ((in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) ? ' SELECTED ' : '')
				. ' ' . $style . '>';

			$htmlResult .= htmlspecialchars($row[$real_field] ?? '') . '</option>';
		}
		$htmlResult .= '</SELECT>';
		return $htmlResult;
	}

	protected function getMultiBox(CT &$ct, &$ct_noFilter, $valueArray, $field, $tableName, $dynamic_filter): string
	{
		$real_field_row = Fields::getFieldRowByName($field, null, $tableName);

		if ($real_field_row->type == "multilangstring" or $real_field_row->type == "multilangtext")
			$real_field = $real_field_row->realfieldname . $ct->Languages->Postfix;
		else
			$real_field = $real_field_row->realfieldname;

		$ctInputBoxRecords_r = [];
		$ctInputBoxRecords_v = [];
		$ctInputBoxRecords_p = [];

		foreach ($ct->Records as $row) {

			if (in_array($row[$ct->Table->realidfieldname], $valueArray) and count($valueArray) > 0) {
				$ctInputBoxRecords_r[] = $row[$ct->Table->realidfieldname]; //record ID

				if ($real_field_row->type == 'sqljoin') {
					$layoutCode = '{{ ' . $real_field_row->fieldname . ' }}';
					$twig = new TwigProcessor($ct, $layoutCode);
					$ctInputBoxRecords_v[] = $twig->process($row);
				} else
					$ctInputBoxRecords_v[] = $row[$real_field]; //Value string

				$ctInputBoxRecords_p[] = (int)$row['listing_published']; //record published status
			}
		}

		$htmlResult = '
		<script>
			//Field value
			ctInputBoxRecords_r["' . $this->attributes['id'] . '"] = ' . common::ctJsonEncode($ctInputBoxRecords_r) . ';
			ctInputBoxRecords_v["' . $this->attributes['id'] . '"] = ' . common::ctJsonEncode($ctInputBoxRecords_v) . ';
			ctInputBoxRecords_p["' . $this->attributes['id'] . '"] = ' . common::ctJsonEncode($ctInputBoxRecords_p) . ';
		</script>
		';

		$single_box = self::getSingle($ct, $ct_noFilter, $valueArray, $field, '_selector', $this->attributes, '', $tableName, $dynamic_filter);

		$icon_path = CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/';
		$htmlResult .= '<div style="padding-bottom:20px;"><div style="width:90%;" id="' . $this->attributes['id'] . '_box"></div>'
			. '<div style="height:30px;">'
			. '<div id="' . $this->attributes['id'] . '_addButton" style="visibility: visible;"><img src="' . $icon_path . 'new.png" alt="Add" title="Add" style="cursor: pointer;" '
			. 'onClick="ctInputBoxRecords_addItem(\'' . $this->attributes['id'] . '\',\'_selector\')" /></div>'
			. '<div id="' . $this->attributes['id'] . '_addBox" style="visibility: hidden;">'
			. '<div style="float:left;">' . $single_box . '</div>'
			. '<img src="' . $icon_path . 'plus.png" '
			. 'alt="Add" title="Add" '
			. 'style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;width:16px;height:16px;" '
			. 'onClick="ctInputBoxRecords_DoAddItem(\'' . $this->attributes['id'] . '\',\'_selector\')" />'
			. '<img src="' . $icon_path . 'cancel.png" alt="Cancel" title="Cancel" '
			. 'style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;width:16px;height:16px;" '
			. 'onClick="ctInputBoxRecords_cancel(\'' . $this->attributes['id'] . '\',\'_selector\')" />'

			. '</div>'
			. '</div>'
			. '<div style="visibility: hidden;"><select name="' . $this->attributes['id'] . '[]" id="' . $this->attributes['id'] . '" MULTIPLE ></select></div>'
			. '</div>

		<script>
			ctInputBoxRecords_showMultibox("' . $this->attributes['id'] . '","_selector");
		</script>
		';

		return $htmlResult;
	}
}