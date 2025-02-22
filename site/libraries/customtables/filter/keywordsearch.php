<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\MySQLWhereClause;

defined('_JEXEC') or die();

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ordering.php');

class CustomTablesKeywordSearch
{
	var CT $ct;
	var array $PathValue;
	var string $groupby;
	var string $esordering;

	function __construct($ct)
	{
		$this->ct = $ct;
		$this->PathValue = [];

		$this->groupby = '';
		$this->esordering = '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getRowsByKeywords($keywords, &$record_count, $limit, $limitstart): array
	{
		$result_rows = array();

		if (!common::inputGetString('esfieldlist', ''))
			return $result_rows;

		if ($keywords == '')
			return $result_rows;

		$keywords = trim(preg_replace("/[^a-zA-Z\dáéíóúýñÁÉÍÓÚÝÑ [:punct:]]/", "", $keywords));
		$keywords = str_replace('\\', '', $keywords);
		$mod_fieldlist = explode(',', common::inputGetString('esfieldlist', ''));

		//Strict (all words in a search must be there)
		$result_rows = $this->getRowsByKeywords_Processor($keywords, $mod_fieldlist, 'AND');

		//At least one word is match
		if (count($result_rows) == 0)
			$result_rows = $this->getRowsByKeywords_Processor($keywords, $mod_fieldlist, 'OR');

		$record_count = count($result_rows);

		//Process Limit
		return $this->processLimit($result_rows, $limit, $limitstart);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getRowsByKeywords_Processor(string $keywords, array $mod_fieldlist, string $AndOrOr): array
	{
		$keyword_arr = explode(' ', $keywords);
		$count = 0;
		$result_rows = array();
		$listing_ids = array();

		$AndOrOr_text = 'UNKNOWN';

		if ($AndOrOr == 'OR')
			$AndOrOr_text = common::translate('COM_CUSTOMTABLES_OR');

		if ($AndOrOr == 'AND')
			$AndOrOr_text = common::translate('COM_CUSTOMTABLES_AND');

		foreach ($mod_fieldlist as $mod_field) {
			$inner = '';

			$fieldrow = null;
			foreach ($this->ct->Table->fields as $f) {
				if ($f['fieldname'] == trim($mod_field)) {
					$fieldrow = $f;
					break;
				}
			}

			//exact match
			if (isset($fieldrow['type']) and isset($fieldrow['fieldname'])) {
				$whereClause = $this->getRowsByKeywords_ProcessTypes($fieldrow['type'], $fieldrow['fieldname'], $fieldrow['typeparams'],
					'[[:<:]]' . $keywords . '[[:>:]]', $inner);

				if ($whereClause->hasConditions())
					$this->getKeywordSearch($inner, $whereClause, $result_rows, $count, $listing_ids);
			}

			$this->PathValue[] = common::translate('COM_CUSTOMTABLES_CONTAINS') . ' "' . $keywords . '"';

			if (count($keyword_arr) > 1) //Do not search because there is only one keyword, and it's already checked
			{
				$inner = '';
				$inner_arr = array();
				$kw_text_array = array();
				$whereClause = new MySQLWhereClause();

				foreach ($keyword_arr as $kw) {
					$inner = '';
					$whereClauseTemp = $this->getRowsByKeywords_ProcessTypes($fieldrow['type'], $fieldrow['fieldname'], $fieldrow['typeparams'], '[[:<:]]' . $kw . '[[:>:]]', $inner);
					if ($whereClauseTemp->hasConditions()) {

						$whereClause->addNestedCondition($whereClauseTemp);

						if (!in_array($inner, $inner_arr)) {
							$inner_arr[] = $inner;
							$kw_text_array[] = $kw;
						}
					}
				}

				$inner = implode(' ', $inner_arr);

				if ($whereClause->hasConditions())
					$this->getKeywordSearch($inner, $whereClause, $result_rows, $count, $listing_ids);

				$this->PathValue[] = common::translate('COM_CUSTOMTABLES_CONTAINS') . ' "' . implode('" ' . $AndOrOr_text . ' "', $kw_text_array) . '"';
			}

			$inner = '';
			$whereClause = new MySQLWhereClause();
			$inner_arr = array();
			$kw_text_array = array();

			foreach ($keyword_arr as $kw) {
				$inner = '';

				if (isset($fieldrow['type']) and isset($fieldrow['fieldname'])) {
					$whereClauseTemp = $this->getRowsByKeywords_ProcessTypes($fieldrow['type'], $fieldrow['fieldname'], $fieldrow['typeparams'], '[[:<:]]' . $kw, $inner);

					if ($whereClauseTemp->hasConditions()) {
						$whereClause->addNestedCondition($whereClauseTemp);

						if (!in_array($inner, $inner_arr)) {
							$inner_arr[] = $inner;
							$kw_text_array[] = $kw;
						}
					}
				}
			}

			$inner = implode(' ', $inner_arr);

			if ($whereClause->hasConditions())
				$this->getKeywordSearch($inner, $whereClause, $result_rows, $count, $listing_ids);

			$this->PathValue[] = common::translate('COM_CUSTOMTABLES_CONTAINS') . ' "' . implode('" ' . $AndOrOr_text . ' "', $kw_text_array) . '"';
		}

		$whereClause = new MySQLWhereClause();

		foreach ($mod_fieldlist as $mod_field) {

			$fieldrow = null;
			foreach ($this->ct->Table->fields as $f) {
				if ($f['fieldname'] == trim($mod_field)) {
					$fieldrow = $f;
					break;
				}
			}

			//any
			$keyword_arr = explode(' ', $keywords);
			$inner = '';
			$inner_arr = array();
			$kw_text_array = array();

			foreach ($keyword_arr as $kw) {
				$kw_text_array[] = $kw;
				$t = '';
				if (isset($fieldrow['type']))
					$t = $fieldrow['type'];

				switch ($t) {
					case 'url':
					case 'string':
					case 'phponadd':
					case 'phponchange':
					case 'text':
					case 'email':
						$whereClause->addCondition($fieldrow['realfieldname'], $kw, 'INSTR');
						break;

					case 'multilangtext':
					case 'multilangstring':
						$whereClause->addCondition($fieldrow['realfieldname'] . $this->ct->Languages->Postfix, $kw, 'INSTR');
						break;

					case 'records':
						$typeParamsArrayy = explode(',', $fieldrow['typeparams']);
						$esr_table = '#__customtables_table_' . $typeParamsArrayy[0];
						$esr_field = $typeParamsArrayy[1];

						$inner = 'INNER JOIN ' . $esr_table . ' ON instr(#__customtables_table_' . $this->ct->Table->tablename . $fieldrow['realfieldname'] . ',concat(",",' . $esr_table . '.' . $this->ct->Table->realidfieldname . ',","))';//TODO
						if (!in_array($inner, $inner_arr))
							$inner_arr[] = $inner;

						$whereClause->addCondition($esr_table . '.es_' . $esr_field, $kw, 'INSTR');
						break;

					case 'sqljoin':
						common::enqueueMessage('Search box not ready yet.', 'notice');

						$typeParamsArrayy = explode(',', $fieldrow['typeparams']);
						$esr_table = '#__customtables_table_' . $typeParamsArrayy[0];
						$esr_field = $typeParamsArrayy[1];

						$inner = 'INNER JOIN ' . $esr_table . ' ON instr(#__customtables_table_' . $this->ct->Table->tablename . $fieldrow['realfieldname'] . ',concat(",",' . $esr_table . '.' . $this->ct->Table->realidfieldname . ',","))';
						if (!in_array($inner, $inner_arr))
							$inner_arr[] = $inner;

						$whereClause->addCondition($esr_table . '.es_' . $esr_field, $kw, 'INSTR');
						break;

					case 'userid':
					case 'user':
						$inner = 'INNER JOIN #__users ON #__users.id=#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldrow['fieldname'];
						if (!in_array($inner, $inner_arr))
							$inner_arr[] = $inner;

						$whereClause->addCondition('#__users.name', $kw, 'REGEXP');
						break;
				}
			}

			$inner = implode(' ', $inner_arr);

			if ($whereClause->hasConditions())
				$this->getKeywordSearch($inner, $whereClause, $result_rows, $count, $listing_ids);

			$this->PathValue[] = common::translate('COM_CUSTOMTABLES_CONTAINS') . ' "' . implode('" ' . $AndOrOr_text . ' "', $kw_text_array) . '"';
		}
		return $result_rows;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getRowsByKeywords_ProcessTypes($fieldType, $fieldname, $typeParams, $regExpression, &$inner): MySQLWhereClause
	{
		$whereClause = new MySQLWhereClause();
		$inner = '';

		switch ($fieldType) {
			case 'phponchange':
			case 'text':
			case 'phponadd':
			case 'string':
				$whereClause->addCondition($this->ct->Table->fieldPrefix . $fieldname, $regExpression, 'REGEXP');
				break;

			case 'multilangtext':
			case 'multilangstring':
				$whereClause->addCondition($this->ct->Table->fieldPrefix . $fieldname . $this->ct->Languages->Postfix, $regExpression, 'REGEXP');
				break;

			case 'records':

				$typeParamsArray = explode(',', $typeParams);

				if (count($typeParamsArray) < 3)
					return $whereClause;

				$esr_table = '#__customtables_table_' . $typeParamsArray[0];
				$esr_field = $typeParamsArray[1];

				$inner = 'INNER JOIN ' . $esr_table . ' ON instr(#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldname . ',concat(",",' . $esr_table . '.id,","))';//TODO
				$whereClause->addCondition($esr_table . '.es_' . $esr_field, $regExpression, 'REGEXP');
				break;

			case 'sqljoin':
				common::enqueueMessage('Search box not ready yet.', 'notice');
				break;

			case 'userid':
			case 'user':
				$inner = 'INNER JOIN #__users ON #__users.id=#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldname;
				$whereClause->addCondition('#__users.name', $regExpression, 'REGEXP');
				break;
		}
		return $whereClause;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getKeywordSearch($inner_str, MySQLWhereClause $whereClause, &$result_rows, &$count, &$listing_ids): void
	{
		$selects = [
			'*',
			$this->ct->Table->realtablename . '.' . $this->ct->Table->realidfieldname . ' AS listing_id'
		];

		$inner = array($inner_str);
		if ($this->ct->Table->published_field_found)
			$selects[] = $this->ct->Table->realtablename . '.published As listing_published';

		$ordering = array();

		if ($this->groupby != '')
			$ordering[] = $this->ct->Table->fieldPrefix . $this->groupby;

		$from = $this->ct->Table->realtablename . (count($inner) != '' ? ' ' . implode(' ', $inner) : '');
		$rows = database::loadAssocList($from, $selects,
			$whereClause, (count($ordering) > 0 ? implode(',', $ordering) : null), null, null, null, 'listing_id');

		foreach ($rows as $row) {
			if (in_array($row[$this->ct->Table->realidfieldname], $listing_ids))
				$exist = true;
			else
				$exist = false;

			if (!$exist) {
				$result_rows[] = $row;
				$listing_ids[] = $row[$this->ct->Table->realidfieldname];
				$count++;
			}
		}
	}

	function processLimit($result_rows, $limit, $limitStart): array
	{
		$result_rows_new = array();
		for ($i = $limitStart; $i < $limitStart + $limit; $i++)
			$result_rows_new[] = $result_rows[$i];

		return $result_rows_new;
	}
}
