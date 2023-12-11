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

use ESTables;
use Exception;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use JoomlaBasicMisc;

class InputBox_tablejoin extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	static public function renderTableJoinSelectorJSON(CT &$ct, $key, $obEndClean = true): ?string
	{
		$index = common::inputGetInt('index');

		$selectors = (array)$ct->app->getUserState($key);

		if ($index < 0 or $index >= count($selectors))
			die(json_encode(['error' => 'Index out of range.' . $key]));

		$additional_filter = common::inputGetCmd('filter', '');
		$subFilter = common::inputGetCmd('subfilter');
		$search = common::inputGetString('search');

		return self::renderTableJoinSelectorJSON_Process($ct, $selectors, $index, $additional_filter, $subFilter, $search, $obEndClean);
	}

	protected static function renderTableJoinSelectorJSON_Process(CT &$ct, $selectors, $index, $additional_filter, $subFilter, ?string $search = null, $obEndClean = true): ?string
	{
		$selector = $selectors[$index];

		$tableName = $selector[0];
		if ($tableName === null) {
			if ($obEndClean)
				die(json_encode(['error' => 'Table not selected']));
			else
				return 'Table not selected';
		}

		$ct->getTable($tableName);
		if (is_null($ct->Table->tablename))
			die(json_encode(['error' => 'Table "' . $tableName . '"not found']));

		$fieldName_or_layout = $selector[1];
		if ($fieldName_or_layout === null or $fieldName_or_layout == '')
			$fieldName_or_layout = $ct->Table->fields[0]['fieldname'];//Get first field if not specified

		//$showPublished = 0 - show published
		//$showPublished = 1 - show unpublished
		//$showPublished = 2 - show any
		$showPublishedString = $selector[2] ?? '';
		$showPublished = ($showPublishedString == 'true' ? 2 : 0); //$selector[2] can be "" or "true" or "false"

		$filter = $selector[3] ?? '';
		$filterOverride = common::inputGetString('where');
		if ($filterOverride !== null) {
			if ($filter != '')
				$filter .= ' and ';

			$filter .= base64_decode($filterOverride);
		}

		$additional_where = '';
		//Find the field name that has a join to the parent (index-1) table
		foreach ($ct->Table->fields as $fld) {
			if ($fld['type'] == 'sqljoin' or $fld['type'] == 'records') {
				$type_params = JoomlaBasicMisc::csv_explode(',', $fld['typeparams']);

				$join_tableName = $type_params[0];
				$join_to_tableName = $selector[5];

				if ($additional_filter != '') {
					if ($join_tableName == $join_to_tableName)
						$filter .= ' and ' . $fld['fieldname'] . '=' . $additional_filter;

				} else {
					//Check if this table has self-parent field - the TableJoin field linked with the same table.
					if ($join_tableName == $tableName) {
						if ($subFilter == '')
							$additional_where = '(' . $fld['realfieldname'] . ' IS NULL OR ' . $fld['realfieldname'] . '="")';
						else
							$additional_where = $fld['realfieldname'] . '=' . database::quote($subFilter);
					}
				}
			}
		}

		$ct->setFilter($filter, $showPublished);
		if ($additional_where != '')
			$ct->Filter->where[] = $additional_where;

		if ($search !== null and $search != '') {
			foreach ($ct->Table->fields as $fld) {
				if ($fieldName_or_layout == $fld['fieldname']) {
					$ct->Filter->where[] = 'INSTR(' . $fld['realfieldname'] . ',' . database::quote($search) . ')';
				}
			}
		}

		$orderBy = $selector[4] ?? '';

		//sorting
		$ct->Ordering->ordering_processed_string = $orderBy;
		$ct->Ordering->parseOrderByString();

		$ct->getRecords();

		if (!str_contains($fieldName_or_layout, '{{') and !str_contains($fieldName_or_layout, 'layout')) {
			$fieldName_or_layout_tag = '{{ ' . $fieldName_or_layout . ' }}';
		} else {
			$pair = explode(':', $fieldName_or_layout);

			if (count($pair) == 2) {
				$layout_mode = true;
				if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout')
					die(json_encode(['error' => common::translate('COM_CUSTOMTABLES_ERROR_UNKNOWN_FIELD_LAYOUT') . ' inputbox_tablejoin.php' . $fieldName_or_layout . '"']));

				$Layouts = new Layouts($ct);
				$fieldName_or_layout_tag = $Layouts->getLayout($pair[1]);

				if (!isset($fieldName_or_layout_tag) or $fieldName_or_layout_tag == '') {
					$result_js = ['error' => common::translate(
							'COM_CUSTOMTABLES_ERROR_LAYOUT_NOT_FOUND') . ' inputbox_tablejoin.php' . $pair[1] . '"'];
					return json_encode($result_js);
				}
			} else
				$fieldName_or_layout_tag = $fieldName_or_layout;
		}

		$selector1 = JoomlaBasicMisc::generateRandomString();
		$selector2 = JoomlaBasicMisc::generateRandomString() . '*';

		$itemLayout = '{{ record.id }}' . $selector1 . $fieldName_or_layout_tag . $selector2;
		$pageLayoutContent = '{% block record %}' . $itemLayout . '{% endblock %}';

		$paramsArray['establename'] = $tableName;

		$params = new Registry;
		$params->loadArray($paramsArray);
		$ct->setParams($params);

		$pathViews = CUSTOMTABLES_LIBRARIES_PATH
			. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

		require_once($pathViews . 'json.php');

		$jsonOutput = new ViewJSON($ct);
		$output = $jsonOutput->render($pageLayoutContent, false);
		$outputList = JoomlaBasicMisc::csv_explode($selector2, $output, '"', false);
		$outputArray = [];
		foreach ($outputList as $outputListItems) {
			$items = JoomlaBasicMisc::csv_explode($selector1, $outputListItems, '"', false);
			if ($items[0] != '')
				$outputArray[] = ["value" => $items[0], "label" => $items[1]];
		}

		$outputString = json_encode($outputArray);

		if ($obEndClean) {
			if (ob_get_contents()) ob_end_clean();
			header('Content-Type: application/json; charset=utf-8');
			header("Pragma: no-cache");
			header("Expires: 0");

			die($outputString);
		}

		return $outputString;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(?string $value, ?string $defaultValue): string
	{
		//$this->option_list[0] - CSS Class
		//$this->option_list[1] - Optional Attributes
		//$this->option_list[2] - Parent Selector - Array
		//$this->option_list[3] - Custom Title Layout

		if ($value === null) {
			$value = common::inputGetInt($this->ct->Env->field_prefix . $this->field->fieldname, 0);
			if ($value == 0)
				$value = $defaultValue;
		}

		$listing_id = ($this->row !== null ? $this->row[$this->ct->Table->realidfieldname] : null);
		return $this->doRender($this->attributes['id'], $listing_id, $value);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function doRender($control_name, $listing_id, $value): string
	{
		$params = new Registry;
		//$params->loadArray([]);
		$ct = new CT($params, true);

		//Check if Value exists
		if ($value !== null and $value !== '' and $value !== 0) {
			$tableName = $this->field->params[0];
			$tableRow = ESTables::getTableRowByNameAssoc($tableName);
			$ct->setTable($tableRow);
			if (!$ct->Table->isRecordExists($value))
				$value = null;
		}

		//Prepare the input box
		$filter = [];
		$parent_filter_table_and_field = self::parseTagArguments($this->option_list, $filter);
		$parent_filter_table_name = $parent_filter_table_and_field[0] ?? '';
		$parent_filter_field_name = $parent_filter_table_and_field[1] ?? '';

		$params_filter = [];
		if ($parent_filter_table_name == '' and $parent_filter_field_name == '') {
			self::parseTypeParams($this->field, $params_filter, $parent_filter_table_name, $parent_filter_field_name);
			$params_filter = array_reverse($params_filter);
			if (count($params_filter) > 0 and isset($option_list[3]) and $option_list[3] != "") {
				$params_filter[0][1] = 'layout:' . $option_list[3];
			}
		}

		$filter = array_merge($filter, $params_filter);

		//Get initial table filters based on the value
		$js_filters = [];
		$js_filters_FieldName = [];
		$parent_id = $value;
		self::processValue($filter, $parent_id, $js_filters, $js_filters_FieldName);

		if (count($js_filters) == 0)
			$js_filters[] = $value;

		$key = JoomlaBasicMisc::generateRandomString();
		$ct->app->setUserState($key, $filter);

		$cssClass = $option_list[0] ?? '';
		$improved = ($option_list[5] ?? '');// '','improved' or 'virtualselect'

		if ($improved == 'improved')
			$cssClass .= ($cssClass == '' ? '' : ' ') . ' ct_improved_selectbox';
		elseif ($improved == 'virtualselect')
			$cssClass .= ($cssClass == '' ? '' : ' ') . ' ct_virtualselect_selectbox';

		$data = [];
		$data[] = 'data-key="' . $key . '"';
		$data[] = 'data-fieldname="' . $this->field->fieldname . '"';
		$data[] = 'data-controlname="' . $control_name . '"';
		$data[] = 'data-valuefilters="' . base64_encode(json_encode($js_filters)) . '"';
		$data[] = 'data-valuefiltersnames="' . base64_encode(json_encode($js_filters_FieldName)) . '"';
		$data[] = 'data-onchange="' . base64_encode($this->attributes['onchange']) . '"';
		$data[] = 'data-listing_id="' . $listing_id . '"';
		$data[] = 'data-value="' . htmlspecialchars($value ?? '') . '"';
		$data[] = 'data-cssclass="' . htmlspecialchars($cssClass ?? '') . '"';

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
				$formID .= $this->field->ct->Params->ModuleId;
			}
		}

		$data[] = 'data-formname="' . $formID . '"';
		$Placeholder = $this->field->title;

		$attributesString = self::attributes2String($this->attributes);

		return '<input type="hidden" id="' . $control_name . '" name="' . $control_name . '" value="' . htmlspecialchars($value ?? '') . '" '
			. 'data-type="sqljoin" '
			. 'data-tableid="' . $this->field->ct->Table->tableid . '" '
			. $attributesString . '/>'
			. '<div id="' . $control_name . 'Wrapper" ' . implode(' ', $data) . '>'
			. self::ctUpdateTableJoinLink($ct, $control_name, 0, 0, "", $formID, $attributesString, $this->attributes['onchange'],
				$filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder)
			. '</div>';
	}

	public static function parseTagArguments($option_list, &$filter): array
	{
		//Preselects
		//example: city.edit("cssclass","attributes",[["province","name",true,"active=1","name"],["city","name",false,"active=1","name"],["streets","layout:TheStreetName",false,"active=1","streetname"]])
		//parameter 3 can be 1 or 2 dimensional array.
		//One dimensional array will be converted to 2 dimensional array.
		//$cssclass = $option_list[0]; // but it's having been already progressed
		//$attribute = $option_list[1]; // but it's having been already progressed

		//Twig teg example:
		//{{ componentid.edit("mycss","readonly",[["grades","grade"],["classes","class"]]) }}
		//{{ componentid.edit("mycss","readonly",["grades","grade"]) }}
		//{{ componentid.edit("mycss","readonly",["grades","grade"],"gradesTitleLayout") }}

		$parent_filter_table_name = '';
		$parent_filter_field_name = '';

		if (isset($option_list[2])) {
			$option = $option_list[2];
			if (is_array($option)) {
				if (count($option) > 0) {
					if (is_array($option[0])) {

						$previousTableName = null;

						foreach ($option as $optionFilter) {
							$tableName = $optionFilter[0];
							$fieldName = $optionFilter[1];
							$allow_unpublished = $optionFilter[2] ?? 'published';
							$whereFilter = $optionFilter[3] ?? '';
							$orderBy = $optionFilter[4] ?? null;

							if ($parent_filter_field_name == '' and isset($optionFilter[5])) {
								$parent_filter_table_name = $optionFilter[0];
								$parent_filter_field_name = $optionFilter[5];
							}

							if (count($filter) == 0) {
								$dynamicFilter = null;
							} else {
								$dynamicFilter = self::findDynamicFilter($tableName, $previousTableName);
							}
							$filter[] = [$tableName, $fieldName, $allow_unpublished, $whereFilter, $orderBy, $parent_filter_table_name, $parent_filter_field_name, $dynamicFilter];
							$parent_filter_table_name = $optionFilter[0];
							$parent_filter_field_name = $optionFilter[1];
							$previousTableName = $tableName;
						}
					} else {

						//Example: "cssclass","attributes", [table_name, field_name, allow_unpublished, filter, order_by]
						$tableName = $option[0];
						$fieldName = $option[1] ?? null;
						if ($fieldName === null) {
							echo 'Table Field or Layout not specified.';
							return [];
						}

						$allow_unpublished = $option[2] ?? null;
						$whereFilter = $option[3] ?? null;
						$orderBy = $option[4] ?? null;

						if (isset($option[5])) {
							$parent_filter_table_name = $option[0];
							$parent_filter_field_name = $option[5];
						}

						$filter[] = [$tableName, $fieldName, $allow_unpublished, $whereFilter, $orderBy, $parent_filter_table_name, $parent_filter_field_name];
						$parent_filter_table_name = $tableName;
						$parent_filter_field_name = $fieldName;
					}
				} else
					return [];
			} else {
				echo 'Table Join field: wrong option_list format - Parent Selector must be an array';
				return [];
			}
		}
		return [$parent_filter_table_name, $parent_filter_field_name];
	}

	public static function findDynamicFilter(string $tableName, $previousTableName): ?string
	{
		$ct = new CT();
		$ct->getTable($tableName);
		if (is_null($ct->Table->tablename))
			die(json_encode(['error' => 'Table "' . $tableName . '"not found']));

		//Find the field name that has a join to the parent (index-1) table
		foreach ($ct->Table->fields as $fld) {
			if ($fld['type'] == 'sqljoin' or $fld['type'] == 'records') {
				$type_params = JoomlaBasicMisc::csv_explode(',', $fld['typeparams']);

				$join_tableName = $type_params[0];

				if ($join_tableName == $previousTableName)
					return $fld['fieldname'];
			}
		}
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function parseTypeParams($field, &$filter, &$parent_filter_table_name, &$parent_filter_field_name): bool
	{
		$params = new Registry;
		$temp_ct = new CT($params, true);

		//Table Join
		//Example: table_name, field_name, where_filter, dynamic_filter, order_by, allow_unpublished
		if ($field->type == 'sqljoin')
			$dynamicFilter = $field->params[3] ?? null;
		elseif ($field->type == 'records')
			$dynamicFilter = $field->params[4] ?? null;
		else
			return false;

		$tableName = $field->params[0];
		$temp_ct->getTable($tableName);

		//Dynamic filter,
		if (!is_null($dynamicFilter) and $dynamicFilter != '') {

			if ($temp_ct->Table->tablename === null) {
				$temp_ct->errors[] = 'Dynamic filter field "' . $dynamicFilter . '" : Table "' . $temp_ct->Table->tablename . '" not found.';
				return false;
			}

			//Find dynamic filter field
			foreach ($temp_ct->Table->fields as $fld) {
				if ($fld['fieldname'] == $dynamicFilter) {

					$tempField = new Field($temp_ct, $fld);

					$parent_filter_table_name = $tempField->params[0];
					$parent_filter_field_name = $tempField->params[1];

					$filter[] = self::mapJoinTypeParams($field, $parent_filter_table_name, $parent_filter_field_name, $dynamicFilter);

					$parent_filter_table_name = null;
					$parent_filter_field_name = null;

					self::parseTypeParams($tempField, $filter, $parent_filter_table_name, $parent_filter_field_name);
					break;
				}
			}
		} else {

			$selfParentField = Fields::getSelfParentField($temp_ct);
			if ($selfParentField !== null) {
				$parent_filter_table_name = $temp_ct->Table->tablename;
				$parent_filter_field_name = $selfParentField['fieldname'];//it was 6
			}

			$filter[] = self::mapJoinTypeParams($field, $parent_filter_table_name, $parent_filter_field_name, null);
		}
		return true;
	}

	protected static function mapJoinTypeParams(Field $field, $parent_filter_table_name, $parent_filter_field_name, ?string $dynamicFilter): ?array
	{
		if ($field->type = 'sqljoin') {
			$tableName = $field->params[0];
			$fieldName = $field->params[1];
			$where_filter = $field->params[2] ?? null;
			//$dynamicFilter = $field->params[3] ?? null;
			$orderBy = $field->params[4] ?? null;
			$allowUnpublished = $field->params[5] ?? null;
		} elseif ($field->type = 'records') {
			$tableName = $field->params[0];
			$fieldName = $field->params[1];
			$where_filter = $field->params[3] ?? null;
			//$dynamicFilter = $field->params[4] ?? null;
			$orderBy = $field->params[5] ?? null;
			$allowUnpublished = $field->params[6] ?? null;
		} else
			return null;

		return [$tableName, $fieldName, $allowUnpublished, $where_filter, $orderBy, $parent_filter_table_name, $parent_filter_field_name, $dynamicFilter];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function processValue($filter, &$parent_id, &$js_filters, &$js_filters_FieldName): void
	{
		for ($i = count($filter) - 1; $i >= 0; $i--) {
			$flt = $filter[$i];
			$tableName = $flt[0];

			$temp_ct = new CT;
			$temp_ct->getTable($tableName);

			$temp_js_filters = null;
			$join_to_tableName = $flt[5];

			$parent_id = self::getParentFilterID($temp_ct, $parent_id, $join_to_tableName);
			$temp_js_filters = $parent_id;

			//Check if this table has self-parent field - the TableJoin field linked with the same table.
			$selfParentFieldProvided = $flt[0] == $flt[5];

			if ($selfParentFieldProvided) {
				$selfParent_filters = [];
				while ($parent_id !== null) {
					$selfParent_filters[] = $parent_id;
					$parent_id = self::getParentFilterID($temp_ct, $parent_id, $join_to_tableName);
				}

				$selfParent_filters[] = "";

				if (count($selfParent_filters) > 0)
					$temp_js_filters = array_reverse($selfParent_filters);
			}
			$js_filters[] = $temp_js_filters;
			$js_filters_FieldName[] = $flt[7];
		}

		if (count($js_filters) > 0) {
			$js_filters = array_reverse($js_filters);
			$js_filters_FieldName = array_reverse($js_filters_FieldName);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function getParentFilterID($temp_ct, $parent_id, $join_to_tablename)
	{
		$join_realfieldname = '';
		$where = '';

		foreach ($temp_ct->Table->fields as $fld) {
			if ($fld['type'] == 'sqljoin' or $fld['type'] == 'records') {
				$type_params = JoomlaBasicMisc::csv_explode(',', $fld['typeparams']);
				$join_tablename = $type_params[0];

				if ($join_tablename == $join_to_tablename) {
					$join_realfieldname = $fld['realfieldname'];

					//if ($fld['type'] == 'sqljoin')
					$where = $temp_ct->Table->realidfieldname . '=' . database::quote($parent_id);
					//else
					//$where = 'INSTR(' . $temp_ct->Table->realidfieldname . ',",' . (int)$parent_id . ',")';

					break;
				}
			}
		}

		if ($join_realfieldname == '')
			return null;

		$query = 'SELECT ' . $join_realfieldname . ' FROM ' . $temp_ct->Table->realtablename . ' WHERE ' . $where . ' LIMIT 1';
		$rows = database::loadAssocList($query);
		if (count($rows) == 0)
			return null;

		return $rows[0][$join_realfieldname];
	}

	public static function ctUpdateTableJoinLink(CT &$ct, $control_name, $index, $sub_index, $object_id, $formId, $attributes, $onchange, $filter,
	                                                $js_filters, $js_filters_FieldName, $value, ?string $addRecordMenuAlias = null, string $cssClass = '', string $Placeholder = '')
	{
		$subFilter = '';
		$additional_filter = '';

		if (is_array($js_filters[$index])) {
			//Self Parent field
			if ($js_filters[$index][$sub_index] != '')
				$subFilter = $js_filters[$index][$sub_index];
		} else if ($js_filters[$index] != '')
			$additional_filter = $js_filters[$index];

		$result = self::renderTableJoinSelectorJSON_Process($ct, $filter, $index, $additional_filter, $subFilter, null, false);

		if ($result == '')
			return '';

		$resultJSON_encoded = @json_decode($result, null, 512, JSON_INVALID_UTF8_IGNORE);

		if (isset($resultJSON_encoded->error))
			return $resultJSON_encoded->error;

		if (!is_array($resultJSON_encoded)) {
			echo 'ctUpdateTableJoinLink: $resultJSON_encoded is not an array';
		}

		$resultJSON = [];
		foreach ($resultJSON_encoded as $j) {
			$j->label = html_entity_decode($j->label);
			$resultJSON[] = $j;
		}

		if (!is_array($resultJSON))
			return 'Table Join - Corrupted or not supported encoding.';

		return self::ctRenderTableJoinSelectBox($ct, $control_name, $resultJSON, $index, $sub_index, $object_id, $formId, $attributes, $onchange,
			$filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder);
	}

	protected static function ctRenderTableJoinSelectBox(CT      &$ct, $control_name, $r, int $index, int $sub_index, $parent_object_id, $formId,
	                                                             $attributes, $onchange, $filter, $js_filters, $js_filters_FieldName, ?string $value,
	                                                     ?string $addRecordMenuAlias = null, string $cssClass = '', string $Placeholder = '')
	{
		$next_index = $index;
		$next_sub_index = $sub_index;

		if (is_array($js_filters[$index])) {
			//Self Parent field
			$next_sub_index += 1;
			if ($next_sub_index == count($js_filters[$index])) {
				$val = null;
			} else {
				$val = $js_filters[$next_index][$next_sub_index];
			}
		} else {
			$next_index += 1;
			if (count($js_filters) > $next_index) {
				$val = $js_filters[$next_index];
			} else
				$val = null;
		}

		$parentElementNotSelected = false;

		if (is_null($val)) {

			if ($index == count($js_filters) - 1) {
				$val = $value;
			} else {
				if ($value != '') {
					$parentElementNotSelected = true;
					$js_filters[$next_index] = 'NULL';
				}
			}
		}

		if (isset($r->error) and $r->error)
			return $r->error;

		if ($ct->Env->version < 4) {
			if (!str_contains($cssClass, 'inputbox'))
				$cssClass .= ($cssClass == '' ? '' : ' ') . 'inputbox';
		} else {
			if (!str_contains($cssClass, 'form-select'))
				$cssClass .= ($cssClass == '' ? '' : ' ') . 'form-select';

			if (!str_contains($cssClass, 'valid'))
				$cssClass .= ' valid';

			if (!str_contains($cssClass, 'form-control-success'))
				$cssClass .= ' form-control-success';
		}

		if (count($r) == 0) {
			if (isset($js_filters[$next_index]) and is_array($js_filters[$next_index])) {

				if ($next_index + 2 < count($js_filters)) {
					$next_index += 1;
					$next_sub_index = 0;
					$result = self::ctUpdateTableJoinLink($ct, $control_name, $next_index, $next_sub_index, $parent_object_id, $formId, $attributes,
						$onchange, $filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder);
					$result .= '<div id="' . $control_name . 'Selector' . $next_index . '_' . $next_sub_index . '"></div>';
					return $result;

				} else
					return '<div id="' . $control_name . 'Selector' . $index . '_' . $sub_index . '"></div>';

			} else
				return common::translate("COM_CUSTOMTABLES_SELECT_NOTHING");
		}

		$result = '<div id="' . $control_name . 'Selector' . $index . '_' . $sub_index . '">';

		//Add select box
		$current_object_id = $control_name . $index . (is_array($js_filters[$index]) ? '_' . $sub_index : '');

		if (count($r) > 0) {

			$updateValueString = ($index + 1 == count($js_filters) ? 'true' : 'false');
			$onChangeAttribute = 'ctUpdateTableJoinLink(\'' . $control_name . '\', ' . $next_index . ', false, ' . $next_sub_index
				. ',\'' . $current_object_id . '\', \'' . $formId . '\', ' . $updateValueString . ',null,\'\');';

			$onChangeAttribute .= $onchange;

			if (str_contains($cssClass, ' ct_improved_selectbox'))
				HTMLHelper::_('formbehavior.chosen', '.ct_improved_selectbox');

			if ($parentElementNotSelected) {
				$result .= '<div>Selected value does not have a parent.</div>';
			} else {
				$childTableField = $js_filters_FieldName[$next_index];

				if (str_contains($cssClass, ' ct_virtualselect_selectbox')) {

					$selectBoxParams = [];
					$selectBoxParams [] = 'id="' . $current_object_id . '"';
					$selectBoxParams [] = 'title="' . $Placeholder . '"';
					$selectBoxParams [] = 'placeholder="' . $Placeholder . '"';
					//$selectBoxParams [] = 'onChange="' . $onChangeAttribute . '"';
					$selectBoxParams [] = 'data-wrapper="' . $control_name . 'Wrapper"';
					$selectBoxParams [] = 'data-fieldname="' . $control_name . '"';
					//$selectBoxParams [] = 'class="' . $cssClass . '"';
					$selectBoxParams [] = 'data-childtablefield="' . $childTableField . '"';
					$selectBoxParams [] = 'data-js_filters_FieldName="' . $js_filters_FieldName[$index] . '"';

					$selectedValue = null;
					$options = [];
					$maxLimitCount = 0;

					for ($i = 0; $i < count($r); $i++) {
						$label = htmlspecialchars_decode($r[$i]->label ?? '', ENT_HTML5);

						if ($r[$i]->value == $val or str_contains($val, ',' . $r[$i]->value . ',')) {
							$selectedValue = $r[$i]->value;
							$options [] = '{ label: "' . strip_tags(html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '", value: "' . $r[$i]->value . '" },';
						} elseif ($maxLimitCount < 5)
							$options [] = '{ label: "' . strip_tags(html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '", value: "' . $r[$i]->value . '" },';

						$maxLimitCount += 1;
					}

					$VirtualSelectParams = [];
					$VirtualSelectParams [] = 'ele: "#' . $current_object_id . '"';
					$VirtualSelectParams [] = 'options: [' . implode(',', $options) . ']';
					$VirtualSelectParams [] = 'dropboxWrapper: "' . $current_object_id . '"';

					if ($selectedValue !== null)
						$VirtualSelectParams [] = 'selectedValue: "' . $selectedValue . '"';

					$VirtualSelectParams [] = 'search: true';
					$VirtualSelectParams [] = 'onServerSearch: onCTVirtualSelectServerSearch';
					$current_object_id_tmp = $current_object_id . '_Tmp';
					$result .= '<input id="' . $current_object_id_tmp . '" value="" type="hidden" /><div ' . implode(' ', $selectBoxParams) . '></div>'
						. '<script>VirtualSelect.init({' . implode(',', $VirtualSelectParams) . '})

                        document.querySelector("#' . $current_object_id . '").addEventListener("change", function() {
                        document.getElementById("' . $current_object_id_tmp . '").value = this.value;
                        ctUpdateTableJoinLink("' . $control_name . '",' . $next_index . ', false, ' . $next_sub_index . ',"' . $current_object_id_tmp
						. '", "' . $formId . '", ' . $updateValueString . ',null,"")
});
</script>';
				} else {
					$selectBoxParams = [];
					$selectBoxParams [] = 'id="' . $current_object_id . '"';
					$selectBoxParams [] = 'title="' . $Placeholder . '"';
					$selectBoxParams [] = 'placeholder="' . $Placeholder . '"';
					$selectBoxParams [] = 'onChange="' . $onChangeAttribute . '"';
					$selectBoxParams [] = 'class="' . $cssClass . '"';
					$selectBoxParams [] = 'data-childtablefield="' . $childTableField . '"';

					$result .= '<select ' . implode(' ', $selectBoxParams) . ' >';
					$result .= '<option value="">- ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

					if ($addRecordMenuAlias !== null)
						$result .= '<option value=" % addRecord % ">- ' . common::translate('COM_CUSTOMTABLES_ADD') . '</option>';

					for ($i = 0; $i < count($r); $i++) {
						$label = htmlspecialchars_decode($r[$i]->label ?? '', ENT_HTML5);

						if ($r[$i]->value == $val)
							$result .= '<option value="' . $r[$i]->value . '" selected="selected">' . $label . '</option>';
						elseif (str_contains($val, ',' . $r[$i]->value . ','))
							$result .= '<option value="' . $r[$i]->value . '" selected="selected">' . $label . '</option>';
						else
							$result .= '<option value="' . $r[$i]->value . '">' . $r[$i]->label . '</option>';
					}
					$result .= '</select>';
				}
			}

			//Prepare the space for next elements
			if ($next_index < count($js_filters) && $val !== null)// and !$value_found)
			{
				if (is_array($js_filters[$index])) {

					if ($next_sub_index < count($js_filters[$index]))//TODO: check this part
						$result .= self::ctUpdateTableJoinLink($ct, $control_name, $next_index, $next_sub_index, null, $formId,
							$attributes, $onchange, $filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder);
					else
						$result .= '<div id="' . $control_name . 'Selector' . $next_index . '_' . $next_sub_index . '"></div>';
				} else {
					$result .= self::ctUpdateTableJoinLink($ct, $control_name, $next_index, $next_sub_index, null, $formId,
						$attributes, $onchange, $filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder);
				}
			} else {

				if ($next_index < count($js_filters) && $value !== null) {

					if (is_array($js_filters[$index])) {

						if ($next_sub_index < count($js_filters[$index]))
							$result .= self::ctUpdateTableJoinLink($ct, $control_name, $next_index, $next_sub_index, null, $formId,
								$attributes, $onchange, $filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder);
						else
							$result .= '<div id="' . $control_name . 'Selector' . $next_index . '_' . $next_sub_index . '"></div>';
					} else {
						$result .= self::ctUpdateTableJoinLink($ct, $control_name, $next_index, $next_sub_index, null, $formId,
							$attributes, $onchange, $filter, $js_filters, $js_filters_FieldName, $value, $addRecordMenuAlias, $cssClass, $Placeholder);
					}
				}

				$result .= '<div id="' . $control_name . 'Selector' . $next_index . '_' . $next_sub_index . '"></div>';
			}
		}
		$result .= '</div>';
		return $result;
	}
}