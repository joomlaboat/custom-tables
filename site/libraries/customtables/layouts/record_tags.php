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
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use Exception;
use Joomla\CMS\Router\Route;
use LayoutProcessor;

class Twig_Record_Tags
{
	var CT $ct;

	function __construct(&$ct)
	{
		$this->ct = &$ct;
	}

	function id()
	{
		if (!isset($this->ct->Table)) {
			return '{{ record.id }} - Table not loaded.';
		}

		if (is_null($this->ct->Table->record))
			return '';

		return $this->ct->Table->record[$this->ct->Table->realidfieldname];
	}

	function label($allowSortBy = false)
	{
		$forms = new Forms($this->ct);

		$field = ['type' => '_id', 'fieldname' => '_id', 'title' => '#', 'description' => '', 'isrequired' => false];
		return $forms->renderFieldLabel((object)$field, $allowSortBy);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function link($add_returnto = false, $menu_item_alias = '', $custom_not_base64_returnto = ''): ?string
	{
		if ($this->ct->Table->record === null)
			return '';

		if (count($this->ct->Table->record) == 0)
			return 'record.link tag cannot be used on empty record.';

		$menu_item_id = 0;
		$view_link = '';

		if ($menu_item_alias != "") {
			$menu_item = CTMiscHelper::FindMenuItemRowByAlias($menu_item_alias);//Accepts menu Itemid and alias
			if ($menu_item != 0) {
				$menu_item_id = (int)$menu_item['id'];
				$link = $menu_item['link'];

				if ($link != '')
					$view_link = CTMiscHelper::deleteURLQueryOption($link, 'view');
			}
		}

		if ($view_link == '')
			$view_link = 'index.php?option=com_customtables&amp;view=details';

		if (!is_null($this->ct->Params->ModuleId))
			$view_link .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

		if ($this->ct->Table->alias_fieldname != '') {
			$alias = $this->ct->Table->record[$this->ct->Env->field_prefix . $this->ct->Table->alias_fieldname] ?? '';
			if ($alias != '')
				$view_link .= '&amp;alias=' . $alias;
			else
				$view_link .= '&amp;listing_id=' . $this->ct->Table->record[$this->ct->Table->realidfieldname];
		} else
			$view_link .= '&amp;listing_id=' . $this->ct->Table->record[$this->ct->Table->realidfieldname];

		$view_link .= '&amp;Itemid=' . ($menu_item_id == 0 ? $this->ct->Params->ItemId : $menu_item_id);
		$view_link .= (is_null($this->ct->Params->ModuleId) ? '' : '&amp;ModuleId=' . $this->ct->Params->ModuleId);
		$view_link = CTMiscHelper::deleteURLQueryOption($view_link, 'returnto');

		if ($add_returnto) {
			if ($custom_not_base64_returnto)
				$returnToEncoded = common::makeReturnToURL($custom_not_base64_returnto);
			else
				$returnToEncoded = common::makeReturnToURL($this->ct->Env->current_url . '#a' . $this->ct->Table->record[$this->ct->Table->realidfieldname]);

			$view_link .= ($returnToEncoded != '' ? '&amp;returnto=' . $returnToEncoded : '');
		}

		if (defined('_JEXEC'))
			return Route::_($view_link);
		else
			return $view_link;
	}

	function published(string $type = '', string $customTextPositive = "Published", string $customTextNegative = "Unpublished")
	{
		if (!isset($this->ct->Table)) {
			$this->ct->errors[] = '{{ record.published }} - Table not loaded.';
			return null;
		}

		if (!isset($this->ct->Table->record)) {
			$this->ct->errors[] = '{{ record.published }} - Record not loaded.';
			return null;
		}

		if ($type == 'bool' or $type == 'boolean')
			return ((int)$this->ct->Table->record['listing_published'] ? 'true' : 'false');
		elseif ($type == 'number')
			return (int)$this->ct->Table->record['listing_published'];
		elseif ($type == 'custom')
			return $this->ct->Table->record['listing_published'] == 1 ? $customTextPositive : $customTextNegative;
		else
			return (int)$this->ct->Table->record['listing_published'] == 1 ? common::translate('COM_CUSTOMTABLES_YES') : common::translate('COM_CUSTOMTABLES_NO');
	}

	function number(): ?int
	{
		if (!isset($this->ct->Table)) {
			$this->ct->errors[] = '{{ record.number }} - Table not loaded.';
			return null;
		}

		if (!isset($this->ct->Table->record)) {
			$this->ct->errors[] = '{{ record.number }} - Record not loaded.';
			return null;
		}

		if (!isset($this->ct->Table->record['_number'])) {
			$this->ct->errors[] = '{{ record.number }} - Record number not set.';
			return null;
		}

		return (int)$this->ct->Table->record['_number'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function joincount(string $join_table = '', string $filter = ''): ?int
	{
		if ($join_table == '') {
			$this->ct->errors[] = '{{ record.joincount("' . $join_table . '") }} - Table not specified.';
			return null;
		}

		if (!isset($this->ct->Table)) {
			$this->ct->errors[] = '{{ record.joincount("' . $join_table . '") }} - Parent table not loaded.';
			return null;
		}

		$join_table_fields = Fields::getFields($join_table);

		if (count($join_table_fields) == 0) {
			$this->ct->errors[] = '{{ record.joincount("' . $join_table . '") }} - Table not found or it has no fields.';
			return null;
		}

		foreach ($join_table_fields as $join_table_field) {
			if ($join_table_field['type'] == 'sqljoin') {
				$typeParams = CTMiscHelper::csv_explode(',', $join_table_field['typeparams'], '"', false);
				$join_table_join_to_table = $typeParams[0];
				if ($join_table_join_to_table == $this->ct->Table->tablename)
					return intval($this->advancedJoin('count', $join_table, '_id', $join_table_field['fieldname'], '_id', $filter));
			}
		}

		$this->ct->errors[] = '{{ record.joincount("' . $join_table . '") }} - Table found but the field that links to this table not found.';
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function advancedJoin($sj_function, $sj_tablename, $field1_findWhat, $field2_lookWhere, $field3_readValue = '_id', $filter = '',
	                      $order_by_option = '', $value_option_list = [])
	{
		if ($sj_tablename === null or $sj_tablename == '') return '';

		$tableRow = TableHelper::getTableRowByNameAssoc($sj_tablename);

		if (!is_array($tableRow)) return '';

		$field_details = $this->join_getRealFieldName($field1_findWhat, $this->ct->Table->tablerow);
		if ($field_details === null) return '';
		$field1_findWhat_realName = $field_details[0];
		$field1_type = $field_details[1];

		$field_details = $this->join_getRealFieldName($field2_lookWhere, $tableRow);
		if ($field_details === null) return '';
		$field2_lookWhere_realName = $field_details[0];
		$field2_type = $field_details[1];

		$field_details = $this->join_getRealFieldName($field3_readValue, $tableRow);
		if ($field_details === null) return '';
		$field3_readValue_realName = $field_details[0];

		$newCt = new CT();
		$newCt->setTable($tableRow);
		$f = new Filtering($newCt, 2);
		$f->addWhereExpression($filter);
		//$additional_where = implode(' AND ', $f->where);

		if ($order_by_option != '') {
			$field_details = $this->join_getRealFieldName($order_by_option, $tableRow);
			$order_by_option_realName = $field_details[0] ?? '';
		} else
			$order_by_option_realName = '';

		try {
			$rows = $this->join_buildQuery($sj_function, $tableRow, $field1_findWhat_realName, $field1_type, $field2_lookWhere_realName,
				$field2_type, $field3_readValue_realName, $f->whereClause, $order_by_option_realName);
		} catch (Exception $e) {
			$this->ct->errors[] = $e->getMessage();
			return null;
		}

		if (count($rows) == 0) {
			$vlu = 'no records found';
		} else {
			$row = $rows[0];

			if ($sj_function == 'smart') {
				//TODO: review smart advanced join
				$vlu = $row['vlu'];
				$tempCTFields = Fields::getFields($tableRow['id']);

				foreach ($tempCTFields as $fieldRow) {
					if ($fieldRow['fieldname'] == $field3_readValue) {
						$fieldRow['realfieldname'] = 'vlu';
						$valueProcessor = new Value($this->ct);
						$vlu = $valueProcessor->renderValue($fieldRow, $row, $value_option_list);
						break;
					}
				}
			} else
				$vlu = $row['vlu'];
		}
		return $vlu;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function join_getRealFieldName(string $fieldName, array $tableRow): ?array
	{
		$tableId = (int)$tableRow['id'];

		if ($fieldName == '_id') {
			return [$tableRow['realidfieldname'], '_id'];
		} elseif ($fieldName == '_published') {
			if ($tableRow['published_field_found'])
				return ['listing_published', '_published'];
			else
				$this->ct->errors[] = '{{ record.join... }} - Table does not have "published" field.';
		} else {
			$field1_row = Fields::getFieldRowByName($fieldName, $tableId);

			if (is_object($field1_row)) {
				return [$field1_row->realfieldname, $field1_row->type];
			} else
				$this->ct->errors[] = '{{ record.join... }} - Field "' . $fieldName . '" not found.';
		}
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function join_buildQuery($sj_function, $tableRow, $field1_findWhat, $field1_type, $field2_lookWhere,
	                                   $field2_type, $field3_readValue, MySQLWhereClause $whereClauseAdditional, $order_by_option): array
	{
		$whereClause = new MySQLWhereClause();
		$selects = [];

		if ($sj_function == 'count')
			$selects[] = ['VALUE', $tableRow['realtablename'], $field3_readValue];
		elseif ($sj_function == 'sum')
			$selects[] = ['SUM', $tableRow['realtablename'], $field3_readValue];
		elseif ($sj_function == 'avg')
			$selects[] = ['AVG', $tableRow['realtablename'], $field3_readValue];
		elseif ($sj_function == 'min')
			$selects[] = ['VALUE', $tableRow['realtablename'], $field3_readValue];
		elseif ($sj_function == 'max')
			$selects[] = ['MAX', $tableRow['realtablename'], $field3_readValue];
		else {
			//need to resolve record value if it's "records" type
			$selects[] = ['VALUE', $tableRow['realtablename'], $field3_readValue];
		}

		$sj_tablename = $tableRow['tablename'];
		$leftJoin = '';

		if ($this->ct->Table->tablename != $sj_tablename) {
			// Join not needed when we are in the same table
			$leftJoin = ' LEFT JOIN `' . $tableRow['realtablename'] . '` ON ';

			if ($field1_type == 'records') {
				if ($field2_type == 'records') {
					$leftJoin .= '1==2'; //todo
				} else {
					$leftJoin .= 'INSTR(`' . $this->ct->Table->realtablename . '`.`' . $field1_findWhat . '`,CONCAT(",",`' . $tableRow['realtablename'] . '`.`' . $field2_lookWhere . '`,","))';
				}
			} else {
				if ($field2_type == 'records') {
					$leftJoin .= 'INSTR(`' . $tableRow['realtablename'] . '`.`' . $field2_lookWhere . '`'
						. ',  CONCAT(",",`' . $this->ct->Table->realtablename . '`.`' . $field1_findWhat . '`,","))';
				} else {
					$leftJoin .= ' `' . $this->ct->Table->realtablename . '`.`' . $field1_findWhat . '` = '
						. ' `' . $tableRow['realtablename'] . '`.`' . $field2_lookWhere . '`';
				}
			}
		}

		if ($this->ct->Table->tablename != $sj_tablename) {
			//don't attach to specific record when it is the same table, example : to find averages
			$whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], $this->ct->Table->record[$this->ct->Table->realidfieldname]);
		}

		if ($whereClauseAdditional->hasConditions())
			$whereClause->addNestedCondition($whereClauseAdditional);

		$from = $this->ct->Table->realtablename . ' ' . $leftJoin;

		return database::loadAssocList($from, $selects, $whereClause,
			($order_by_option != '' ? $tableRow['realtablename'] . '.' . $order_by_option : null), null, 1);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function joinavg(string $join_table = '', string $value_field = '', string $filter = '')
	{
		return $this->simple_join('avg', $join_table, $value_field, 'record.joinavg', $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function simple_join($function, $join_table, $value_field, $tag, string $filter = '')
	{
		if ($join_table == '') {
			$this->ct->errors[] = '{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Table not specified.';
			return '';
		}

		if ($value_field == '') {
			$this->ct->errors[] = '{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Value field not specified.';
			return '';
		}

		if (!isset($this->ct->Table)) {
			$this->ct->errors[] = '{{ ' . $tag . '() }} - Table not loaded.';
			return '';
		}

		$join_table_fields = Fields::getFields($join_table);

		if (count($join_table_fields) == 0) {
			$this->ct->errors[] = '{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Table "' . $join_table . '" not found or it has no fields.';
			return '';
		}

		$value_field_found = false;
		foreach ($join_table_fields as $join_table_field) {
			if ($join_table_field['fieldname'] == $value_field) {
				$value_field_found = true;
				break;
			}
		}

		if (!$value_field_found) {
			$this->ct->errors[] = '{{ ' . $tag . '("' . $join_table . '","' . $value_field . '") }} - Value field "' . $value_field . '" not found.';
			return '';
		}

		foreach ($join_table_fields as $join_table_field) {
			if ($join_table_field['type'] == 'sqljoin') {
				$typeParams = CTMiscHelper::csv_explode(',', $join_table_field['typeparams'], '"', false);
				$join_table_join_to_table = $typeParams[0];
				if ($join_table_join_to_table == $this->ct->Table->tablename)
					return $this->advancedJoin($function, $join_table, '_id', $join_table_field['fieldname'], $value_field, $filter);
			}
		}

		$this->ct->errors[] = '{{ ' . $tag . '("' . $join_table . '") }} - Table found but the field that links to this table not found.';
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function joinmin(string $join_table = '', string $value_field = '', string $filter = '')
	{
		return $this->simple_join('min', $join_table, $value_field, 'record.joinmin', $filter);
	}

	/* --------------------------- PROTECTED FUNCTIONS ------------------- */

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function joinmax(string $join_table = '', string $value_field = '', string $filter = '')
	{
		return $this->simple_join('max', $join_table, $value_field, 'record.joinmax', $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function joinsum(string $join_table = '', string $value_field = '', string $filter = '')
	{
		return $this->simple_join('sum', $join_table, $value_field, 'record.joinsum', $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function joinvalue(string $join_table = '', string $value_field = '', string $filter = '')
	{
		return $this->simple_join('value', $join_table, $value_field, 'record.joinvalue', $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function jointable($layoutname = '', $filter = '', $orderby = '', $limit = 0): string
	{
		//Example {{ record.tablejoin("InvoicesPage","_published=1","name") }}

		if ($layoutname == '') {
			$this->ct->errors[] = '{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.';
			return '';
		}

		$layouts = new Layouts($this->ct);

		$pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table
		if ($layouts->tableId === null) {
			$this->ct->errors[] = '{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.';
			return '';
		}

		$join_table_fields = Fields::getFields($layouts->tableId);

		$complete_filter = $filter;

		foreach ($join_table_fields as $join_table_field) {
			if ($join_table_field['type'] == 'sqljoin') {
				$typeParams = CTMiscHelper::csv_explode(',', $join_table_field['typeparams'], '"', false);
				$join_table_join_to_table = $typeParams[0];
				if ($join_table_join_to_table == $this->ct->Table->tablename) {
					$complete_filter = $join_table_field['fieldname'] . '=' . $this->ct->Table->record[$this->ct->Table->realidfieldname];
					if ($filter != '')
						$complete_filter .= ' and ' . $filter;
					break;
				}
			}
		}

		$join_ct = new CT;
		$tables = new Tables($join_ct);

		if ($tables->loadRecords($layouts->tableId, $complete_filter, $orderby, $limit)) {
			$twig = new TwigProcessor($join_ct, $pageLayout);

			$value = $twig->process();
			if ($twig->errorMessage !== null)
				$this->ct->errors[] = $twig->errorMessage;

			return $value;
		}

		$this->ct->errors[] = '{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - LCould not load records.';
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function min(string $tableName = '', string $value_field = '', string $filter = ''): ?int
	{
		return $this->countOrSumRecords('min', $tableName, $value_field, $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function countOrSumRecords(string $function = 'count', string $tableName = '', string $fieldName = '', string $filter = ''): ?int
	{
		if ($tableName == '') {
			$this->ct->errors[] = '{{ record.count("' . $tableName . '") }} - Table not specified.';
			return null;
		}

		$tableRow = TableHelper::getTableRowByNameAssoc($tableName);
		if (!is_array($tableRow)) {
			$this->ct->errors[] = '{{ record.count("' . $tableName . '") }} - Table not found.';
			return null;
		}

		if ($fieldName == '') {
			$this->ct->errors[] = '{{ record.count("' . $fieldName . '") }} - Field not specified.';
			return null;
		}

		if (!isset($this->ct->Table)) {
			$this->ct->errors[] = '{{ record.count("' . $tableName . '","' . $fieldName . '","' . $filter . '") }} - Parent table not loaded.';
			return null;
		}

		if ($fieldName == '_id') {
			$fieldRealFieldName = $tableRow['realidfieldname'];
		} elseif ($fieldName == '_published') {
			$fieldRealFieldName = 'listing_published';//$tableRow['published'];
		} else {
			$tableFields = Fields::getFields($tableName);

			if (count($tableFields) == 0) {
				$this->ct->errors[] = '{{ record.count("' . $tableName . '") }} - Table not found or it has no fields.';
				return null;
			}

			$field = null;
			foreach ($tableFields as $tableField) {
				if ($tableField['fieldname'] == $fieldName) {
					$field = new Field($this->ct, $tableField);
					break;
				}
			}

			if ($field === null) {
				$this->ct->errors[] = '{{ record.count("' . $tableName . '") }} - Table found but the field that links to this table not found.';
				return null;
			}
			$fieldRealFieldName = $field->realfieldname;
		}

		$newCt = new CT();
		$newCt->setTable($tableRow);

		$f = new Filtering($newCt, 2);
		$f->addWhereExpression($filter);

		try {
			$rows = $this->count_buildQuery($function, $tableRow['realtablename'], $fieldRealFieldName, $f->whereClause);
		} catch (Exception $e) {
			$this->ct->errors[] = $e->getMessage();
			return null;
		}

		if (count($rows) == 0)
			return null;
		else {

			if (!key_exists('vlu', $rows[0])) {
				echo '*************';
				print_r($rows);
			}
			return $rows[0]['vlu'];
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function count_buildQuery($sj_function, $realTableName, $realFieldName, MySQLWhereClause $whereClause): ?array
	{
		$selects = [];

		if ($sj_function == 'count')
			$selects[] = ['COUNT', $realTableName, $realFieldName];
		elseif ($sj_function == 'sum')
			$selects[] = ['SUM', $realTableName, $realFieldName];
		elseif ($sj_function == 'avg')
			$selects[] = ['AVG', $realTableName, $realFieldName];
		elseif ($sj_function == 'min')
			$selects[] = ['MIN', $realTableName, $realFieldName];
		elseif ($sj_function == 'max')
			$selects[] = ['MAX', $realTableName, $realFieldName];
		else {
			//need to resolve record value if it's "records" type
			$selects[] = ['VALUE', $realTableName, $realFieldName];
		}
		return database::loadAssocList($realTableName, $selects, $whereClause, null, null, 1);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function max(string $tableName = '', string $value_field = '', string $filter = ''): ?int
	{
		return $this->countOrSumRecords('max', $tableName, $value_field, $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function avg(string $tableName = '', string $value_field = '', string $filter = ''): ?int
	{
		return $this->countOrSumRecords('avg', $tableName, $value_field, $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function sum(string $tableName = '', string $value_field = '', string $filter = ''): ?int
	{
		return $this->countOrSumRecords('sum', $tableName, $value_field, $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function count(string $tableName = '', string $filter = ''): ?int
	{
		return $this->countOrSumRecords('count', $tableName, '_id', $filter);
	}

	function MissingFields($separator = ','): string
	{
		return implode($separator, $this->MissingFieldsList());
	}

	function MissingFieldsList(): array
	{
		if ($this->ct->Table->isRecordNull())
			return [];

		$fieldTitles = [];
		foreach ($this->ct->Table->fields as $field) {
			if ($field['published'] == 1 and $field['isrequired'] == 1 and !Fields::isVirtualField($field)) {
				$value = $this->ct->Table->record[$field['realfieldname']];
				if ($value === null or $value == '') {
					if (!array_key_exists('fieldtitle' . $this->ct->Languages->Postfix, $field)) {
						$fieldTitles[] = 'fieldtitle' . $this->ct->Languages->Postfix . ' - not found';
					} else {
						$vlu = $field['fieldtitle' . $this->ct->Languages->Postfix];
						if ($vlu == '')
							$fieldTitles[] = $field['fieldtitle'];
						else
							$fieldTitles[] = $vlu;
					}
				}
			}
		}
		return $fieldTitles;
	}

	function isLast(): bool
	{
		if (!isset($this->ct->Table)) {
			$this->ct->errors[] = '{{ record.islast }} - Table not loaded.';
			return false;
		}

		if (!isset($this->ct->Table->record)) {
			$this->ct->errors[] = '{{ record.islast }} - Record not loaded.';
			return false;
		}

		if (!isset($this->ct->Table->record['_islast'])) {
			$this->ct->errors[] = '{{ record.islast }} - Record number not set.';
			return false;
		}

		return (bool)$this->ct->Table->record['_islast'];
	}
}

class Twig_Table_Tags
{
	var CT $ct;

	function __construct(&$ct)
	{
		$this->ct = &$ct;
	}

	function recordstotal(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;//Table not selected

		$whereClause = new MySQLWhereClause();
		$count = $this->ct->getNumberOfRecords($whereClause);

		return $count === null ? -1 : $count;
	}

	function records(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return $this->ct->Table->recordcount;
	}

	function fields(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return count($this->ct->Table->fields);
	}

	function description()
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return 'Table not selected';

		if (isset($this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix])
			and $this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix] !== '') {
			return $this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix];
		} else
			return $this->ct->Table->tablerow['description'];
	}

	function title(): string
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return 'Table not selected';

		return $this->ct->Table->tabletitle;
	}

	function name(): ?string
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return 'Table not selected';

		return $this->ct->Table->tablename;
	}

	function id(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return $this->ct->Table->tableid;
	}

	function recordsperpage(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return $this->ct->Limit;
	}

	function recordpagestart(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return $this->ct->LimitStart;
	}
}

class Twig_Tables_Tags
{
	var CT $ct;

	function __construct(&$ct)
	{
		$this->ct = &$ct;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getvalue($table = '', $fieldname = '', $record_id_or_filter = '', $orderby = '')
	{
		$tag = 'tables.getvalue';
		if ($table == '') {
			$this->ct->errors[] = '{{ ' . $tag . '("' . $table . '",value_field_name) }} - Table not specified.';
			return '';
		}

		if ($fieldname == '') {
			$this->ct->errors[] = '{{ ' . $tag . '("' . $table . '",field_name) }} - Value field not specified.';
			return '';
		}

		$join_table_fields = Fields::getFields($table);

		$join_ct = new CT;
		$tables = new Tables($join_ct);
		$tableRow = TableHelper::getTableRowByNameAssoc($table);
		$join_ct->setTable($tableRow);

		if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
			try {
				$row = $tables->loadRecord($table, $record_id_or_filter);
				if ($row === null)
					return '';
			} catch (Exception $e) {
				$join_ct->errors[] = $e->getMessage();
				return '';
			}
		} else {
			try {
				if ($tables->loadRecords($table, $record_id_or_filter, $orderby, 1)) {
					if (count($join_ct->Records) > 0)
						$row = $join_ct->Records[0];
					else
						return '';
				} else
					return '';
			} catch (Exception $e) {
				$join_ct->errors[] = $e->getMessage();
				return '';
			}
		}

		if (Layouts::isLayoutContent($fieldname)) {

			$twig = new TwigProcessor($join_ct, $fieldname);
			$value = $twig->process($row);

			if ($twig->errorMessage !== null)
				$join_ct->errors[] = $twig->errorMessage;

			return $value;

		} else {
			$value_realfieldname = '';
			if ($fieldname == '_id')
				$value_realfieldname = $join_ct->Table->realidfieldname;
			elseif ($fieldname == '_published')
				if ($join_ct->Table->published_field_found) {
					$value_realfieldname = 'listing_published';
				} else {
					$this->ct->errors[] = '{{ ' . $tag . '("' . $table . '","published") }} - "published" does not exist in the table.';
					return '';
				}
			else {
				foreach ($join_table_fields as $join_table_field) {
					if ($join_table_field['fieldname'] == $fieldname) {
						$value_realfieldname = $join_table_field['realfieldname'];
						break;
					}
				}
			}

			if ($value_realfieldname == '') {
				$this->ct->errors[] = '{{ ' . $tag . '("' . $table . '","' . $fieldname . '") }} - Value field "' . $fieldname . '" not found.';
				return '';
			}
			return $row[$value_realfieldname];
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getrecord($layoutname = '', $record_id_or_filter = '', $orderby = ''): string
	{
		if ($layoutname == '') {
			$this->ct->errors[] = '{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout name not specified.';
			return '';
		}

		if ($record_id_or_filter == '') {
			$this->ct->errors[] = '{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Record id or filter not set.';
			return '';
		}

		$join_ct = new CT;
		$tables = new Tables($join_ct);

		$layouts = new Layouts($join_ct);
		$pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table

		if ($layouts->tableId === null) {
			$this->ct->errors[] = '{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.';
			return '';
		}

		if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
			$row = $tables->loadRecord($layouts->tableId, $record_id_or_filter);
			if ($row === null)
				return '';
		} else {
			if ($tables->loadRecords($layouts->tableId, $record_id_or_filter, $orderby, 1)) {
				if (count($join_ct->Records) > 0)
					$row = $join_ct->Records[0];
				else
					return '';
			} else
				return '';
		}

		$twig = new TwigProcessor($join_ct, $pageLayout);

		$value = $twig->process($row);
		if ($twig->errorMessage !== null)
			$join_ct->errors[] = $twig->errorMessage;

		return $value;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getrecords($layoutname = '', $filter = '', $orderby = '', $limit = 0): string
	{
		//Example {{ html.records("InvoicesPage","firstname=john","lastname") }}

		if ($layoutname == '') {
			$this->ct->errors[] = '{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.';
			return '';
		}

		$join_ct = new CT;
		$tables = new Tables($join_ct);
		$layouts = new Layouts($join_ct);
		$pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table
		if ($layouts->tableId === null) {
			$this->ct->errors[] = '{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.';
			return '';
		}

		if ($tables->loadRecords($layouts->tableId, $filter, $orderby, $limit)) {

			if ($join_ct->Env->legacySupport) {
				$LayoutProc = new LayoutProcessor($join_ct);
				$LayoutProc->layout = $pageLayout;
				$pageLayout = $LayoutProc->fillLayout();
			}

			$twig = new TwigProcessor($join_ct, $pageLayout);

			$value = $twig->process();

			if ($twig->errorMessage !== null)
				$join_ct->errors[] = $twig->errorMessage;

			return $value;
		}

		$this->ct->errors[] = '{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Could not load records.';
		return '';
	}
}