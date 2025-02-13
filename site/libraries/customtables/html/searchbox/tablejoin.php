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
use Joomla\CMS\HTML\HTMLHelper;

//use LayoutProcessor;
//use tagProcessor_Value;

//use CustomTables\ProInputBoxTableJoin;

class Search_tablejoin extends BaseSearch
{
	function __construct(CT &$ct, Field $field, string $moduleName, array $attributes, int $index, string $where, string $whereList, string $objectName)
	{
		parent::__construct($ct, $field, $moduleName, $attributes, $index, $where, $whereList, $objectName);
		BaseInputBox::selectBoxAddCSSClass($this->attributes);
	}

	/*
	protected static function processValue($field, &$ct, $row)
	{
		$p = strpos($field, '->');
		if (!($p === false)) {
			$field = substr($field, 0, $p);
		}

		//get options
		$options = '';
		$p = strpos($field, '(');

		if ($p !== false) {
			$e = strpos($field, '(', $p);
			if ($e === false)
				return 'syntax error';

			$options = substr($field, $p + 1, $e - $p - 1);
			$field = substr($field, 0, $p);
		}

		//getting filed row (we need field typeparams, to render formatted value)
		if ($field == '_id' or $field == '_published') {
			$htmlresult = $row[str_replace('_', '', $field)];
		} else {
			$fieldrow = Fields::FieldRowByName($field, $ct->Table->fields);
			if (!is_null($fieldrow)) {

				$options_list = explode(',', $options);

				$v = tagProcessor_Value::getValueByType($ct,
					$fieldrow,
					$row,
					$options_list,
				);

				$htmlresult = $v;
			} else {
				$htmlresult = 'Field "' . $field . '" not found.';
			}
		}
		return $htmlresult;
	}
	*/

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render($value): string
	{
		//if (str_contains($this->attributes['onchange'] ?? '', 'onkeypress='))
		$this->attributes['onkeypress'] = 'es_SearchBoxKeyPress(event)';

		if (is_array($value))
			$value = implode(',', $value);

		BaseInputBox::selectBoxAddCSSClass($this->attributes);

		if ($this->field->layout !== null)
			$this->field->params[1] = 'tablelesslayout:' . $this->field->layout;

		return $this->doRender($value, $this->objectName);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function doRender($value, $control_name, $addNoValue = false): string
	{
		$typeParams = $this->field->params;

		if (count($typeParams) < 1) {
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED'));
			return common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED');
		}

		if (count($typeParams) < 2) {
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_UNKNOWN_FIELD_LAYOUT'));
			return common::translate('COM_CUSTOMTABLES_ERROR_UNKNOWN_FIELD_LAYOUT');
		}

		$tableName = $typeParams[0];

		if (empty($tableName)) {
			common::enqueueMessage('Table not set.');
			return 'Table not set.';
		}

		$value_field = $typeParams[1] ?? '';
		$filter = $typeParams[2] ?? '';
		$dynamic_filter = $typeParams[3] ?? '';
		$order_by_field = $typeParams[4] ?? '';

		if (isset($typeParams[5]) and $typeParams[5] == 'true')
			$allowUnpublished = true;
		else
			$allowUnpublished = false;

		if (TableHelper::getTableID($tableName) == '') {
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND'));
			return common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND');
		}

		if ($order_by_field == '')
			$order_by_field = $value_field;

		//Get Database records
		$ct = new CT([], true);
		$this->getSearchResult($ct, $filter, $tableName, $order_by_field, $allowUnpublished);

		//Process records depending on field type and layout
		$list_values = $this->get_List_Values($ct, $value_field, $dynamic_filter);

		$htmlResult = self::renderDynamicFilter($ct, $value, $dynamic_filter, $control_name);
		$htmlResult .= self::renderDropdownSelector_Box($list_values, (string)$value, $control_name, $dynamic_filter, $addNoValue);

		return $htmlResult;
	}

	/**
	 * @throws Exception
	 * @since 3.2.0
	 */
	static protected function getSearchResult(CT $ct, $filter, $tableName, $order_by_field, $allowUnpublished): bool
	{
		$paramsArray = array();

		$paramsArray['limit'] = 0;
		$paramsArray['establename'] = $tableName;
		if ($allowUnpublished)
			$paramsArray['showpublished'] = CUSTOMTABLES_SHOWPUBLISHED_ANY;//0 - published only; 1 - hidden only; 2 - Any
		else
			$paramsArray['showpublished'] = CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY;//0 - published only; 1 - hidden only; 2 - Any

		$paramsArray['groupby'] = '';

		if (!str_contains($order_by_field, ':')) //cannot sort by layout only by field name
			$paramsArray['forcesortby'] = $order_by_field;

		if ($filter != '')
			$paramsArray['filter'] = str_replace('|', ',', str_replace('****quote****', '"', $filter));
		else
			$paramsArray['filter'] = ''; //!IMPORTANT - NO FILTER

		$ct->Params->setParams($paramsArray);

		// -------------------- Table
		$ct->getTable($ct->Params->tableName);

		if ($ct->Table === null) {
			$ct->errors[] = 'Catalog View: Table "' . $ct->Params->tableName . '" not found.';
			echo 'Catalog View: Table "' . $ct->Params->tableName . '" not found.';
			return false;
		}

		// --------------------- Filter
		$ct->setFilter($ct->Params->filter, $ct->Params->showPublished);

		// --------------------- Sorting
		$ct->Ordering->parseOrderByParam();

		// --------------------- Limit
		$ct->applyLimits();
		$ct->getRecords();

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static protected function get_List_Values(CT &$ct, $field, $dynamic_filter): array
	{
		if ($ct->Records === null)
			return [];

		$layout_mode = false;
		$layoutcode = '';
		$pair = explode(':', $field);
		if (count($pair) == 2) {
			$layout_mode = true;
			if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout') {
				common::enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_UNKNOWN_FIELD_LAYOUT') . ' search_tablejoin.php' . $field . '"');
				return array();
			}

			$Layouts = new Layouts($ct);
			$layoutcode = $Layouts->getLayout($pair[1]);

			if (!isset($layoutcode) or $layoutcode == '') {
				common::enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_LAYOUT_NOT_FOUND') . ' search_tablejoin.php' . $pair[1] . '"');
				return array();
			}
		}

		$list_values = [];

		foreach ($ct->Records as $row) {
			if ($layout_mode) {

				$v = $layoutcode;

				try {
					$twig = new TwigProcessor($ct, $v);
					$v = $twig->process($row);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}

			} else {

				try {
					$twig = new TwigProcessor($ct, '{{ ' . $field . ' }}');
					$v = $twig->process($row);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}

			if ($dynamic_filter != '')
				$d = $row[$ct->Table->fieldPrefix . $dynamic_filter];
			else
				$d = '';

			$list_values[] = [$row[$ct->Table->realidfieldname], $v, (int)$row['listing_published'], $d];
		}

		return $list_values;
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.0.0
	 */
	static protected function renderDynamicFilter(CT $ct, $value, $dynamic_filter, $control_name): string
	{
		$htmlResult = '';

		if ($dynamic_filter != '') {
			$filterValue = '';
			foreach ($ct->Records as $row) {
				if ($row[$ct->Table->realidfieldname] == $value) {
					$filterValue = $row[$ct->Table->fieldPrefix . $dynamic_filter];
					break;
				}
			}
			$htmlResult .= LinkJoinFilters::getFilterBox($ct, $dynamic_filter, $control_name, $filterValue);
		}
		return $htmlResult;
	}

	protected function renderDropdownSelector_Box($list_values, string $current_value, $control_name, $dynamic_filter, $addNoValue = false): string
	{
		if (str_contains(($this->attributes['class'] ?? ''), ' ct_improved_selectbox'))
			return self::renderDropdownSelector_Box_improved($list_values, $current_value, $control_name, $dynamic_filter);
		else
			return self::renderDropdownSelector_Box_simple($list_values, $current_value, $control_name, $dynamic_filter, $addNoValue);
	}

	protected function renderDropdownSelector_Box_improved($list_values, string $current_value, $control_name, $dynamic_filter, $addNoValue = false): string
	{
		if (defined('WPINC')) {
			return 'renderDropdownSelector_Box_improved not yet supported by WordPress version of the Custom Tables.';
		}

		HTMLHelper::_('formbehavior.chosen', '.ct_improved_selectbox');
		return $this->renderDropdownSelector_Box_simple($list_values, (string)$current_value, $control_name, $dynamic_filter, $addNoValue);
	}

	protected function renderDropdownSelector_Box_simple($list_values, string $current_value, $control_name, $dynamic_filter, $addNoValue = false): string
	{
		$htmlResult = '';

		$this->attributes['name'] = $control_name;
		$this->attributes['id'] = $control_name;
		$this->attributes['data-type'] = 'sqljoin';

		if (str_contains(($this->attributes['class'] ?? ''), ' ct_virtualselect_selectbox'))
			$this->attributes['data-search'] = true;

		$htmlResult_select = '<SELECT ' . BaseInputBox::attributes2String($this->attributes) . '>';

		$htmlResult_select .= '<option value="">- ' . common::translate('COM_CUSTOMTABLES_SELECT') . ' ' . $this->attributes['data-label'] . '</option>';

		foreach ($list_values as $list_value) {
			if ($list_value[2] == 0)//if unpublished
				$style = ' style="color:red"';
			else
				$style = '';

			if ($dynamic_filter == '') {
				$listValueString = (string)$list_value[0];
				$htmlResult_select .= '<option value="' . $listValueString . '"' . ($listValueString == $current_value ? ' selected="SELECTED"' : '') . $style . '>' . htmlspecialchars(common::ctStripTags($list_value[1] ?? '')) . '</option>';
			}
		}

		if ($addNoValue)
			$htmlResult_select .= '<option value="-1"' . ((int)$current_value == -1 ? ' selected="SELECTED"' : '') . '>- ' . common::translate('COM_CUSTOMTABLES_NOT_SPECIFIED') . '</option>';

		$htmlResult_select .= '</SELECT>';

		if ($dynamic_filter != '') {
			$elements = array();
			$elementsID = array();
			$elementsFilter = array();
			$elementsPublished = array();

			foreach ($list_values as $list_value) {
				$elementsID[] = $list_value[0];
				$elements[] = $list_value[1];
				$elementsPublished[] = $list_value[2];
				$elementsFilter[] = $list_value[3];
			}

			$htmlResult .= '
<script type="application/json" id="' . $control_name . '_elements">
    ' . common::ctJsonEncode($elements) . '
</script>
';

			$htmlResult .= '
			<div id="' . $control_name . '_elementsID" style="display:none;">' . implode(',', $elementsID) . '</div>
			<div id="' . $control_name . '_elementsFilter" style="display:none;">' . implode(';', $elementsFilter) . '</div>
			<div id="' . $control_name . '_elementsPublished" style="display:none;">' . implode(',', $elementsPublished) . '</div>
';

			$htmlResult .= $htmlResult_select;
			$htmlResult .= '<div id="' . $control_name . '_ctInputBoxRecords_current_value" style="display:none;">' . $current_value . '</div>';

			$htmlResult .= '
			<script>
				window.onload = function() {
			    	(function checkAndRun_ctInputbox_removeEmptyParents() {
    			    	if (typeof ctInputbox_removeEmptyParents === "function") {
                            ctInputbox_removeEmptyParents("' . $control_name . '","");
				            ctInputbox_UpdateSQLJoinLink("' . $control_name . '","");            
';
			if (str_contains(($this->attributes['class'] ?? ''), ' ct_virtualselect_selectbox'))
				$htmlResult .= '
                        VirtualSelect.init({ ele: "' . $control_name . '" });';

			$htmlResult .= '
					    } else {
					        console.error("Waiting for ctInputbox_removeEmptyParents");
						    setTimeout(checkAndRun_ctInputbox_removeEmptyParents, 100);
					    }
				    })();
                };
			</script>
';
		} else {
			$htmlResult .= $htmlResult_select;
		}
		return $htmlResult;
	}
}