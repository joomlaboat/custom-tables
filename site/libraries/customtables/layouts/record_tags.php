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
use Joomla\CMS\Router\Route;

class Twig_Record_Tags
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
	function id()
	{
		if (!isset($this->ct->Table))
			throw new Exception('{{ record.id }} - Table not loaded.');

		if (is_null($this->ct->Table->record))
			return '';

		return $this->ct->Table->record[$this->ct->Table->realidfieldname];
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.2.2
	 */
	function label($allowSortBy = false)
	{
		$forms = new Forms($this->ct);
		$fieldRow = ['id' => 0, 'type' => '_id', 'fieldname' => '_id', 'fieldtitle' => '#', 'description' => '', 'isrequired' => false, 'realfieldname' => $this->ct->Table->realidfieldname];
		$field = new Field($this->ct, $fieldRow);
		return $forms->renderFieldLabel($field, $allowSortBy);
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
			throw new Exception('record.link tag cannot be used on empty record.');

		$menu_item_id = 0;
		$view_link = '';

		if ($menu_item_alias != "") {
			$menu_item = CTMiscHelper::FindMenuItemRowByAlias($menu_item_alias);//Accepts menu Itemid and alias
			if ($menu_item !== null) {
				$menu_item_id = (int)$menu_item['id'];
				$link = $menu_item['link'];

				if ($link != '')
					$view_link = CTMiscHelper::deleteURLQueryOption($link, 'view');
			}
		}

		$listing_id = $this->ct->Table->record[$this->ct->Table->realidfieldname];

		if (defined('_JEXEC')) {
			if ($view_link == '')
				$view_link = 'index.php?option=com_customtables&amp;view=details';

			//if (!empty($this->ct->Params->ModuleId))
			//$view_link .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

			if ($this->ct->Table->alias_fieldname != '') {
				$alias = $this->ct->Table->record[$this->ct->Table->fieldPrefix . $this->ct->Table->alias_fieldname] ?? '';
				if ($alias != '') {
					$view_link .= '&amp;alias=' . $alias;
				} else {
					$view_link = CTMiscHelper::deleteURLQueryOption($view_link, 'listing_id');
					$view_link .= '&amp;listing_id=' . $listing_id;
				}

			} else {
				$view_link = CTMiscHelper::deleteURLQueryOption($view_link, 'listing_id');
				$view_link .= '&amp;listing_id=' . $listing_id;
			}

			$view_link .= '&amp;Itemid=' . ($menu_item_id == 0 ? $this->ct->Params->ItemId : $menu_item_id);
			//$view_link .= (is_null($this->ct->Params->ModuleId) ? '' : '&amp;ModuleId=' . $this->ct->Params->ModuleId);


			$view_link = CTMiscHelper::deleteURLQueryOption($view_link, 'returnto');

			if ($add_returnto) {
				if ($custom_not_base64_returnto)
					$returnToEncoded = common::makeReturnToURL($custom_not_base64_returnto);
				else
					$returnToEncoded = common::makeReturnToURL($this->ct->Env->current_url . '#a' . $listing_id);

				$view_link .= ($returnToEncoded != '' ? '&amp;returnto=' . $returnToEncoded : '');
			}

			return Route::_($view_link);
		} else {
			$link = common::curPageURL();
			$link = CTMiscHelper::deleteURLQueryOption($link, 'view' . $this->ct->Table->tableid);
			$link = CTMiscHelper::deleteURLQueryOption($link, 'listing_id');

			$link .= (str_contains($link, '?') ? '&amp;' : '?') . 'view' . $this->ct->Table->tableid . '=details';
			$link .= '&amp;listing_id=' . $listing_id;
			if (!empty($this->ct->Env->encoded_current_url))
				$link .= '&amp;returnto=' . $this->ct->Env->encoded_current_url;

			return $link;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function published(string $type = '', string $customTextPositive = "Published", string $customTextNegative = "Unpublished")
	{
		if (!isset($this->ct->Table))
			throw new Exception('{{ record.published }} - Table not loaded.');

		if (!isset($this->ct->Table->record))
			throw new Exception('{{ record.published }} - Record not loaded.');

		if ($type == 'bool' or $type == 'boolean')
			return ((int)$this->ct->Table->record['listing_published'] ? 'true' : 'false');
		elseif ($type == 'number')
			return (int)$this->ct->Table->record['listing_published'];
		elseif ($type == 'custom')
			return $this->ct->Table->record['listing_published'] == 1 ? $customTextPositive : $customTextNegative;
		else
			return (int)$this->ct->Table->record['listing_published'] == 1 ? common::translate('COM_CUSTOMTABLES_YES') : common::translate('COM_CUSTOMTABLES_NO');
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function number(): ?int
	{
		if (!isset($this->ct->Table))
			throw new Exception('{{ record.number }} - Table not loaded.');

		if (!isset($this->ct->Table->record))
			throw new Exception('{{ record.number }} - Record not loaded.');

		if (!isset($this->ct->Table->record['_number']))
			throw new Exception('{{ record.number }} - Record number not set.');

		return (int)$this->ct->Table->record['_number'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function joincount(string $join_table = '', string $filter = ''): ?int
	{
		if ($join_table == '')
			throw new Exception('{{ record.joincount("' . $join_table . '") }} - Table not specified.');

		if (!isset($this->ct->Table))
			throw new Exception('{{ record.joincount("' . $join_table . '") }} - Parent table not loaded.');

		$join_ct = new CT([], true);
		$join_ct->getTable($join_table);
		$join_table_fields = $join_ct->Table->fields;

		if (count($join_table_fields) == 0)
			throw new Exception('{{ record.joincount("' . $join_table . '") }} - Table not found or it has no fields.');

		foreach ($join_table_fields as $join_table_field) {
			if ($join_table_field['type'] == 'sqljoin') {
				$typeParams = CTMiscHelper::csv_explode(',', $join_table_field['typeparams']);
				$join_table_join_to_table = $typeParams[0];
				if ($join_table_join_to_table == $this->ct->Table->tablename)
					return intval($this->advancedJoin('count', $join_table, '_id', $join_table_field['fieldname'], '_id', $filter));
			}
		}

		throw new Exception('{{ record.joincount("' . $join_table . '") }} - Table found but the field that links to this table not found.');
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function advancedJoin($sj_function, $sj_tablename, $field1_findWhat, $field2_lookWhere, $field3_readValue = '_id', $filter = '',
						  $order_by_option = '', $value_option_list = [])
	{
		if ($sj_tablename === null or $sj_tablename == '') return '';

		$newCt = new CT([], true);
		$newCt->getTable($sj_tablename);
		if ($newCt->Table === null)
			return '';

		$field_details = $this->join_getRealFieldName($field1_findWhat, $this->ct->Table);
		if ($field_details === null) return '';
		$field1_findWhat_realName = $field_details[0];
		$field1_type = $field_details[1];

		$field_details = $this->join_getRealFieldName($field2_lookWhere, $newCt->Table);
		if ($field_details === null) return '';
		$field2_lookWhere_realName = $field_details[0];
		$field2_type = $field_details[1];

		$field_details = $this->join_getRealFieldName($field3_readValue, $newCt->Table);
		if ($field_details === null) return '';
		$field3_readValue_realName = $field_details[0];

		$f = new Filtering($newCt, 2);
		$f->addWhereExpression($filter);

		if ($order_by_option != '') {
			$field_details = $this->join_getRealFieldName($order_by_option, $newCt->Table);
			$order_by_option_realName = $field_details[0] ?? '';
		} else
			$order_by_option_realName = '';

		try {
			$rows = $this->join_buildQuery($sj_function, $newCt->Table, $field1_findWhat_realName, $field1_type, $field2_lookWhere_realName,
				$field2_type, $field3_readValue_realName, $f->whereClause, $order_by_option_realName);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if (count($rows) == 0) {
			$vlu = 'no records found';
		} else {
			$row = $rows[0];

			if ($sj_function == 'smart') {
				//TODO: review smart advanced join
				$vlu = $row['vlu'];

				foreach ($newCt->Table->fields as $fieldRow) {
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
	protected function join_getRealFieldName(string $fieldName, Table $table): ?array
	{
		if ($fieldName == '_id') {
			return [$table->realidfieldname, '_id'];
		} elseif ($fieldName == '_published') {
			if ($table->published_field_found)
				return ['listing_published', '_published'];
			else
				throw new Exception('{{ record.join... }} - Table does not have "published" field.');
		} else {
			$field1_row = $table->getFieldByName($fieldName);

			if (is_array($field1_row)) {
				return [$field1_row['realfieldname'], $field1_row['type']];
			} else
				throw new Exception('{{ record.join... }} - Field "' . $fieldName . '" not found.');
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function join_buildQuery($sj_function, Table $table, $field1_findWhat, $field1_type, $field2_lookWhere,
									   $field2_type, $field3_readValue, MySQLWhereClause $whereClauseAdditional, $order_by_option): array
	{
		$whereClause = new MySQLWhereClause();
		$selects = [];

		if ($sj_function == 'count')
			$selects[] = ['COUNT', $table->realtablename, $field3_readValue];
		elseif ($sj_function == 'sum')
			$selects[] = ['SUM', $table->realtablename, $field3_readValue];
		elseif ($sj_function == 'avg')
			$selects[] = ['AVG', $table->realtablename, $field3_readValue];
		elseif ($sj_function == 'min')
			$selects[] = ['VALUE', $table->realtablename, $field3_readValue];
		elseif ($sj_function == 'max')
			$selects[] = ['MAX', $table->realtablename, $field3_readValue];
		else {
			//need to resolve record value if it's "records" type
			$selects[] = ['VALUE', $table->realtablename, $field3_readValue];
		}

		$sj_tablename = $table->tablename;
		$leftJoin = '';

		if ($this->ct->Table->tablename != $sj_tablename) {
			// Join not needed when we are in the same table
			$leftJoin = ' LEFT JOIN `' . $table->realtablename . '` ON ';

			if ($field1_type == 'records') {
				if ($field2_type == 'records') {
					$leftJoin .= '1==2'; //todo
				} else {
					$leftJoin .= 'INSTR(`' . $this->ct->Table->realtablename . '`.`' . $field1_findWhat . '`,CONCAT(",",`' . $table->realtablename . '`.`' . $field2_lookWhere . '`,","))';
				}
			} else {
				if ($field2_type == 'records') {
					$leftJoin .= 'INSTR(`' . $table->realtablename . '`.`' . $field2_lookWhere . '`'
						. ',  CONCAT(",",`' . $this->ct->Table->realtablename . '`.`' . $field1_findWhat . '`,","))';
				} else {
					$leftJoin .= ' `' . $this->ct->Table->realtablename . '`.`' . $field1_findWhat . '` = '
						. ' `' . $table->realtablename . '`.`' . $field2_lookWhere . '`';
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
			($order_by_option != '' ? $table->realtablename . '.' . $order_by_option : null), null, 1);
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
		if ($join_table == '')
			throw new Exception('{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Table not specified.');

		if ($value_field == '')
			throw new Exception('{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Value field not specified.');

		if (!isset($this->ct->Table))
			throw new Exception('{{ ' . $tag . '() }} - Table not loaded.');

		$tempCT = new CT([], true);
		$tempCT->getTable($join_table);
		$join_table_fields = $tempCT->Table->fields;

		if (count($join_table_fields) == 0)
			throw new Exception('{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Table "' . $join_table . '" not found or it has no fields.');

		$value_field_found = false;
		foreach ($join_table_fields as $join_table_field) {
			if ($join_table_field['fieldname'] == $value_field) {
				$value_field_found = true;
				break;
			}
		}

		if (!$value_field_found)
			throw new Exception('{{ ' . $tag . '("' . $join_table . '","' . $value_field . '") }} - Value field "' . $value_field . '" not found.');

		foreach ($join_table_fields as $join_table_field) {
			if ($join_table_field['type'] == 'sqljoin') {
				$typeParams = CTMiscHelper::csv_explode(',', $join_table_field['typeparams']);
				$join_table_join_to_table = $typeParams[0];
				if ($join_table_join_to_table == $this->ct->Table->tablename)
					return $this->advancedJoin($function, $join_table, '_id', $join_table_field['fieldname'], $value_field, $filter);
			}
		}

		throw new Exception('{{ ' . $tag . '("' . $join_table . '") }} - Table found but the field that links to this table not found.');
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

		if ($layoutname == '')
			throw new Exception('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.');

		$layouts = new Layouts($this->ct);

		$pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table
		if ($layouts->tableId === null)
			throw new Exception('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.');

		$layoutsCT = new CT([], true);
		$layoutsCT->getTable($layouts->tableId);
		$join_table_fields = $layoutsCT->Table->fields;

		$complete_filter = $filter;

		foreach ($join_table_fields as $join_table_field) {
			if ($join_table_field['type'] == 'sqljoin') {
				$typeParams = CTMiscHelper::csv_explode(',', $join_table_field['typeparams']);
				$join_table_join_to_table = $typeParams[0];
				if ($join_table_join_to_table == $this->ct->Table->tablename) {
					$complete_filter = $join_table_field['fieldname'] . '=' . $this->ct->Table->record[$this->ct->Table->realidfieldname];
					if ($filter != '')
						$complete_filter .= ' and ' . $filter;
					break;
				}
			}
		}

		$join_ct = new CT([], true);
		$join_ct->getTable($layouts->tableId);
		if ($join_ct->Table === null)
			throw new Exception('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Table "' . $layouts->tableId . ' not found.');

		$join_ct->setFilter($complete_filter, CUSTOMTABLES_SHOWPUBLISHED_ANY);
		if ($join_ct->getRecords(false, $limit, $orderby)) {

			try {
				$twig = new TwigProcessor($join_ct, $pageLayout);
				$value = $twig->process();
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			return $value;
		}

		throw new Exception('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - LCould not load records.');
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function min(string $tableName = '', string $value_field = '', string $filter = ''): ?int
	{
		if ($value_field == '')
			throw new Exception('{{ record.min(table_name,value_field) }} - Value Field not specified.');

		return $this->countOrSumRecords('min', $tableName, $value_field, $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function countOrSumRecords(string $function, string $tableName, string $fieldName, string $filter = ''): ?int
	{
		if ($tableName == '')
			throw new Exception('countOrSumRecords - Table not specified.');

		$newCT = new CT([], true);
		$newCT->getTable($tableName);
		if ($newCT->Table === null)
			throw new Exception('{{ record.count("' . $tableName . '") }} - Table not found.');

		if ($fieldName == '')
			throw new Exception('countOrSumRecords - Field not specified.');

		if (!isset($this->ct->Table))
			throw new Exception('{{ record.count("' . $tableName . '","' . $fieldName . '","' . $filter . '") }} - Parent table not loaded.');

		if ($fieldName == '_id') {
			$fieldRealFieldName = $newCT->Table->realidfieldname;
		} elseif ($fieldName == '_published') {
			$fieldRealFieldName = 'listing_published';
		} else {

			if (count($newCT->Table->fields) == 0)
				throw new Exception('{{ record.count("' . $tableName . '") }} - Table not found or it has no fields.');

			$field = null;
			foreach ($newCT->Table->fields as $tableField) {
				if ($tableField['fieldname'] == $fieldName) {
					$field = new Field($this->ct, $tableField);
					break;
				}
			}

			if ($field === null)
				throw new Exception('{{ record.count("' . $tableName . '") }} - Table found but the field that links to this table not found.');

			$fieldRealFieldName = $field->realfieldname;
		}


		$f = new Filtering($newCT, 2);
		$f->addWhereExpression($filter);

		try {
			$rows = $this->count_buildQuery($function, $newCT->Table->realtablename, $fieldRealFieldName, $f->whereClause);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if (count($rows) == 0)
			return null;
		else {

			if (!key_exists('vlu', $rows[0]))
				throw new Exception('countOrSumRecords: vlu key not found.');

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
		if ($value_field == '')
			throw new Exception('{{ record.max(table_name,value_field) }} - Value Field not specified.');

		return $this->countOrSumRecords('max', $tableName, $value_field, $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function avg(string $tableName = '', string $value_field = '', string $filter = ''): ?int
	{
		if ($value_field == '')
			throw new Exception('{{ record.avg(table_name,value_field) }} - Value Field not specified.');

		return $this->countOrSumRecords('avg', $tableName, $value_field, $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function sum(string $tableName = '', string $value_field = '', string $filter = ''): ?int
	{
		if ($value_field == '')
			throw new Exception('{{ record.sum(table_name,value_field) }} - Value Field not specified.');

		return $this->countOrSumRecords('sum', $tableName, $value_field, $filter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function count(string $tableName = '', string $filter = ''): ?int
	{
		if ($tableName == '')
			throw new Exception('{{ record.min(table_name) }} - Table Name not specified.');

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

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function isLast(): bool
	{
		if (!isset($this->ct->Table))
			throw new Exception('{{ record.islast }} - Table not loaded.');

		if (!isset($this->ct->Table->record))
			throw new Exception('{{ record.islast }} - Record not loaded.');

		if (!isset($this->ct->Table->record['_islast']))
			throw new Exception('{{ record.islast }} - Record number not set.');

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

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
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

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function description()
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			throw new Exception('Table not selected');

		if (isset($this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix])
			and $this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix] !== '') {
			return $this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix];
		} else
			return $this->ct->Table->tablerow['description'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function title(): string
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			throw new Exception('Table not selected');

		return $this->ct->Table->tabletitle;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function name(): ?string
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			throw new Exception('Table not selected');

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

