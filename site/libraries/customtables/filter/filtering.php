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

if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use DateTime;
use Exception;
use LayoutProcessor;

class Filtering
{
	var CT $ct;
	var array $PathValue;
	var MySQLWhereClause $whereClause;
	var int $showPublished;

	function __construct(CT $ct, int $showPublished = 0)
	{
		$this->ct = $ct;
		$this->PathValue = [];
		$this->whereClause = new MySQLWhereClause();
		$this->showPublished = $showPublished;

		if ($this->ct->Table->published_field_found) {

			//TODO: Fix this mess by replacing the state with a text code like 'published','unpublished','everything','any','trash'
			//$showPublished = 0 - show published
			//$showPublished = 1 - show unpublished
			//$showPublished = 2 - show everything
			//$showPublished = -1 - show published and unpublished
			//$showPublished = -2 - show trashed

			if ($this->showPublished == 0) {
				$this->whereClause->addCondition($this->ct->Table->realtablename . '.published', 1);
				//$this->where[] = $this->ct->Table->realtablename . '.published=1';
				//$this->whereData[$this->ct->Table->realtablename . '.published'] = 1;
			}
			if ($this->showPublished == 1) {
				$this->whereClause->addCondition($this->ct->Table->realtablename . '.published', 0);
				//$this->where[] = $this->ct->Table->realtablename . '.published=0';
				//$this->whereData[$this->ct->Table->realtablename . '.published'] = 0;
			}
			if ($this->showPublished == -1) {
				$this->whereClause->addOrCondition($this->ct->Table->realtablename . '.published', 0);
				$this->whereClause->addOrCondition($this->ct->Table->realtablename . '.published', 1);
				//$this->where[] = '(' . $this->ct->Table->realtablename . '.published=0 OR ' . $this->ct->Table->realtablename . '.published=1)';
				//$this->whereData[$this->ct->Table->realtablename . '.published'] = [0, 1];
			}
			if ($this->showPublished == -2) {
				$this->whereClause->addCondition($this->ct->Table->realtablename . '.published', -2);
				//$this->where[] = $this->ct->Table->realtablename . '.published=-2';
				//$this->whereData[$this->ct->Table->realtablename . '.published'] = -2;
			}
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function addQueryWhereFilter(): void
	{
		if (common::inputGetBase64('where')) {
			$decodedURL = common::inputGetString('where', '');
			//$decodedURL = urldecode($decodedURL);
			//$decodedURL = str_replace(' ', '+', $decodedURL);
			$filter_string = $this->sanitizeAndParseFilter(urldecode($decodedURL));//base64_decode

			if ($filter_string != '')
				$this->addWhereExpression($filter_string);
		}
	}

	function sanitizeAndParseFilter($paramWhere, $parse = false): string
	{
		if ($parse) {
			//Parse using layout, has no effect to layout itself
			if ($this->ct->Env->legacySupport) {

				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'layout.php');

				$LayoutProc = new LayoutProcessor($this->ct);
				$LayoutProc->layout = $paramWhere;
				$paramWhere = $LayoutProc->fillLayout();
			}

			$twig = new TwigProcessor($this->ct, $paramWhere);
			$paramWhere = $twig->process();

			if ($twig->errorMessage !== null)
				$this->ct->errors[] = $twig->errorMessage;

			if ($this->ct->Params->allowContentPlugins)
				$paramWhere = CTMiscHelper::applyContentPlugins($paramWhere);
		}

		//This is old and probably not needed any more because we use MySQLWhereClause class that sanitize individual values.
		//I leave it here just in case
		$paramWhere = str_ireplace('*', '=', $paramWhere);
		$paramWhere = str_ireplace('\\', '', $paramWhere);
		$paramWhere = str_ireplace('drop ', '', $paramWhere);
		$paramWhere = str_ireplace('select ', '', $paramWhere);
		$paramWhere = str_ireplace('delete ', '', $paramWhere);
		$paramWhere = str_ireplace('update ', '', $paramWhere);
		$paramWhere = str_ireplace('grant ', '', $paramWhere);
		return str_ireplace('insert ', '', $paramWhere);
	}

	/**
	 * @throws Exception
	 * @since 3.1.9
	 */
	function addWhereExpression(?string $param): void
	{
		if ($param === null or $param == '')
			return;

		$param = $this->sanitizeAndParseFilter($param, true);
		$items = common::ExplodeSmartParams($param);
		$logic_operator = '';

		foreach ($items as $item) {
			$logic_operator = $item[0];
			$comparison_operator_str = $item[1];
			$comparison_operator = '';
			$whereClauseTemp = new MySQLWhereClause();
			//$multi_field_where = [];

			if ($logic_operator == 'or' or $logic_operator == 'and') {
				if (!(!str_contains($comparison_operator_str, '<=')))
					$comparison_operator = '<=';
				elseif (!(!str_contains($comparison_operator_str, '>=')))
					$comparison_operator = '>=';
				elseif (str_contains($comparison_operator_str, '!=='))
					$comparison_operator = '!==';
				elseif (!(!str_contains($comparison_operator_str, '!=')))
					$comparison_operator = '!=';
				elseif (str_contains($comparison_operator_str, '=='))
					$comparison_operator = '==';
				elseif (str_contains($comparison_operator_str, '='))
					$comparison_operator = '=';
				elseif (!(!str_contains($comparison_operator_str, '<')))
					$comparison_operator = '<';
				elseif (!(!str_contains($comparison_operator_str, '>')))
					$comparison_operator = '>';

				if ($comparison_operator != '') {
					$whr = CTMiscHelper::csv_explode($comparison_operator, $comparison_operator_str, '"', false);

					if (count($whr) == 2) {
						$fieldNamesString = trim(preg_replace("/[^a-zA-Z\d,:\-_;]/", "", trim($whr[0])));

						$fieldNames = explode(';', $fieldNamesString);
						$value = trim($whr[1]);

						if ($this->ct->Env->legacySupport) {

							require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables'
								. DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');
							$LayoutProc = new LayoutProcessor($this->ct);
							$LayoutProc->layout = $value;
							$value = $LayoutProc->fillLayout();
						}

						$twig = new TwigProcessor($this->ct, $value);
						$value = $twig->process();

						if ($twig->errorMessage !== null) {
							$this->ct->errors[] = $twig->errorMessage;
							return;
						}

						foreach ($fieldNames as $fieldname_) {
							$fieldname_parts = explode(':', $fieldname_);
							$fieldname = $fieldname_parts[0];
							$field_extra_param = '';
							if (isset($fieldname_parts[1]))
								$field_extra_param = $fieldname_parts[1];

							if ($fieldname == '_id') {
								$fieldrow = array(
									'id' => 0,
									'fieldname' => '_id',
									'type' => '_id',
									'typeparams' => '',
									'realfieldname' => $this->ct->Table->realidfieldname,
								);
							} elseif ($fieldname == '_published') {
								$fieldrow = array(
									'id' => 0,
									'fieldname' => '_published',
									'type' => '_published',
									'typeparams' => '',
									'realfieldname' => 'listing_published'
								);
							} else {
								//Check if it's a range filter
								$fieldNameParts = explode('_r_', $fieldname);
								$fieldrow = Fields::FieldRowByName($fieldNameParts[0], $this->ct->Table->fields);
							}

							if (!is_null($fieldrow) and array_key_exists('type', $fieldrow)) {
								$w = $this->processSingleFieldWhereSyntax($fieldrow, $comparison_operator, $fieldname, $value, $field_extra_param);

								if ($w->hasConditions()) {
									$whereClauseTemp->addNestedOrCondition($w);
								}
							}
						}
					}
				}
			}

			if ($whereClauseTemp->hasConditions()) {
				if ($logic_operator == 'or')
					$this->whereClause->addNestedOrCondition($whereClauseTemp);//'(' . implode(' ' . $logic_operator . ' ', $wheres) . ')';
				else
					$this->whereClause->addNestedCondition($whereClauseTemp);//'(' . implode(' ' . $logic_operator . ' ', $wheres) . ')';
			}
		}

		if ($logic_operator == '') {
			$this->ct->errors[] = common::translate('Search parameter "' . $param . '" is incorrect');
		}
	}

	/**
	 * @throws Exception
	 * @since 3.1.9
	 */
	function processSingleFieldWhereSyntax(array $fieldrow, string $comparison_operator, string $fieldname_, string $value, string $field_extra_param = ''): MySQLWhereClause
	{
		if (!array_key_exists('type', $fieldrow)) {
			throw new Exception('processSingleFieldWhereSyntax: Field not set');
		}

		$field = new Field($this->ct, $fieldrow);
		//Check if it's a range filter
		$fieldNameParts = explode('_r_', $fieldname_);
		$isRange = count($fieldNameParts) == 2;
		$fieldname = $fieldNameParts[0];

		$whereClause = new MySQLWhereClause();

		switch ($fieldrow['type']) {
			case '_id':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$vList = explode(',', $value);

				foreach ($vList as $vL) {
					$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->realidfieldname, $vL, $comparison_operator);
					$this->PathValue[] = 'ID ' . $comparison_operator . ' ' . $vL;
				}

				return $whereClause;

			case '_published':
				if ($this->ct->Table->published_field_found) {
					if ($comparison_operator == '==')
						$comparison_operator = '=';

					$whereClause->addCondition($this->ct->Table->realtablename . '.published', (int)$value, $comparison_operator);
					$this->PathValue[] = 'Published ' . $comparison_operator . ' ' . (int)$value;
				}
				return $whereClause;

			case 'userid':
			case 'user':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				return $this->Search_User($value, $fieldrow, $comparison_operator, $field_extra_param);

			case 'usergroup':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				return $this->Search_UserGroup($value, $fieldrow, $comparison_operator);

			case 'viewcount':
			case 'id':
			case 'image':
			case 'int':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				return $this->Search_Number($value, $fieldrow, $comparison_operator);

			case 'float':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				return $this->Search_Number($value, $fieldrow, $comparison_operator, true);

			case 'checkbox':
				$vList = explode(',', $value);

				foreach ($vList as $vL) {

					if ($vL == 'true' or $vL == '1') {
						$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], 1);
						$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];
					} else {
						$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], 0);
						$this->PathValue[] = common::translate('COM_CUSTOMTABLES_NOT') . ' ' . $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];
					}
				}
				return $whereClause;

			case 'range':
				return $this->getRangeWhere($fieldrow, $value);

			case 'email':
			case 'url':
			case 'string':
			case 'phponchange':
			case 'text':
			case 'phponadd':
			case 'radio':
				return $this->Search_String($value, $fieldrow, $comparison_operator);

			case 'md5':
			case 'alias':
				return $this->Search_Alias($value, $fieldrow, $comparison_operator);

			case 'lastviewtime':
			case 'changetime':
			case 'creationtime':
			case 'date':
				if ($isRange)
					return $this->Search_DateRange($fieldname, $value);
				else
					return $this->Search_Date($fieldname, $value, $comparison_operator);

			case 'multilangtext':
			case 'multilangstring':
				return $this->Search_String($value, $fieldrow, $comparison_operator, true);

			case 'records':

				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
					. DIRECTORY_SEPARATOR . 'tablejoinlist.php');

				$vList = explode(',', $this->getString_vL($value));

				foreach ($vList as $vL) {
					// Filter Title
					$typeParamsArray = CTMiscHelper::csv_explode(',', $fieldrow['typeparams'], '"', false);

					$filterTitle = '';
					if (count($typeParamsArray) < 1)
						$filterTitle .= 'table not specified';

					if (count($typeParamsArray) < 2)
						$filterTitle .= 'field or layout not specified';

					if (count($typeParamsArray) < 3)
						$filterTitle .= 'selector not specified';

					//$esr_table = $typeParamsArray[0];
					$esr_table_full = $this->ct->Table->realtablename;
					//$esr_field = $typeParamsArray[1];
					$esr_selector = $typeParamsArray[2];

					/*
					if (count($typeParamsArray) > 3)
						$esr_filter = $typeParamsArray[3];
					else
						$esr_filter = '';
					*/

					$filterTitle .= Value_tablejoinlist::renderTableJoinListValue($field, $vL);

					$opt_title = '';

					if ($esr_selector == 'multi' or $esr_selector == 'checkbox' or $esr_selector == 'multibox') {
						if ($comparison_operator == '!=')
							$opt_title = common::translate('COM_CUSTOMTABLES_NOT_CONTAINS');
						elseif ($comparison_operator == '=')
							$opt_title = common::translate('COM_CUSTOMTABLES_CONTAINS');
						elseif ($comparison_operator == '==')
							$opt_title = common::translate('COM_CUSTOMTABLES_IS');
						elseif ($comparison_operator == '!==')
							$opt_title = common::translate('COM_CUSTOMTABLES_ISNOT');
						else
							$opt_title = common::translate('COM_CUSTOMTABLES_UNKNOWN_OPERATION');
					} elseif ($esr_selector == 'radio' or $esr_selector == 'single')
						$opt_title = ':';

					$valueNew = $this->getInt_vL($vL);

					if ($valueNew !== '') {

						if ($comparison_operator == '!=') {
							$whereClause->addCondition($esr_table_full . '.' . $fieldrow['realfieldname'], $valueNew, '!=');
							$whereClause->addCondition($esr_table_full . '.' . $fieldrow['realfieldname'], ',' . $valueNew . ',', 'NOT INSTR');
						} elseif ($comparison_operator == '!==') {
							$whereClause->addCondition($esr_table_full . '.' . $fieldrow['realfieldname'], $valueNew, '!=');
							$whereClause->addCondition($esr_table_full . '.' . $fieldrow['realfieldname'], ',' . $valueNew . ',', '!=');//exact not value
						} elseif ($comparison_operator == '=') {
							$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], $valueNew);
							$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], ',' . $valueNew . ',', 'INSTR');
						} elseif ($comparison_operator == '==') {
							$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], $valueNew);
							$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], ',' . $valueNew . ',');//exact value
						} else
							$opt_title = common::translate('COM_CUSTOMTABLES_UNKNOWN_OPERATION');

						if ($comparison_operator == '!=' or $comparison_operator == '=') {
							$this->PathValue[] = $fieldrow['fieldtitle'
								. $this->ct->Languages->Postfix]
								. ' '
								. $opt_title
								. ' '
								. $filterTitle;
						}
					}
				}

				return $whereClause;

			case 'sqljoin':

				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
					. DIRECTORY_SEPARATOR . 'tablejoin.php');

				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$vList = explode(',', $this->getString_vL($value));

				// Filter Title
				$typeParamsArray = CTMiscHelper::csv_explode(',', $fieldrow['typeparams']);
				$filterTitle = '';

				if (count($typeParamsArray) < 2)
					$filterTitle = 'field or layout not specified';

				if (count($typeParamsArray) < 1)
					$filterTitle = 'table not specified';

				$esr_table_full = $this->ct->Table->realtablename;
				$esr_field_name = $typeParamsArray[1];

				if (count($typeParamsArray) >= 2) {
					foreach ($vList as $vL) {
						$valueNew = $vL;

						$filterTitle .= Value_tablejoin::renderTableJoinValue($field, '{{ ' . $esr_field_name . ' }}', $valueNew);

						if ($valueNew != '') {
							if ($comparison_operator == '!=') {
								$opt_title = common::translate('COM_CUSTOMTABLES_NOT');
								$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], $valueNew, '!=');
								$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix]
									. ' '
									. $opt_title
									. ' '
									. $filterTitle;
							} elseif ($comparison_operator == '=') {
								$opt_title = ':';

								$integerValueNew = $valueNew;
								if ($integerValueNew == 0 or $integerValueNew == -1) {
									$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], null, 'NULL');
									$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], '');
									$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], 0);
								} else
									$whereClause->addOrCondition($esr_table_full . '.' . $fieldrow['realfieldname'], $valueNew);

								$this->PathValue[] = $fieldrow['fieldtitle'
									. $this->ct->Languages->Postfix]
									. ''
									. $opt_title
									. ' '
									. $filterTitle;
							}
						}
					}
				}
				return $whereClause;

			case 'virtual':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$storage = $field->params[1] ?? null;

				if ($storage == 'storedstring')
					$isNumber = false;
				elseif ($storage == 'storedintegersigned' or $storage == 'storedintegerunsigned')
					$isNumber = true;
				else {
					$this->PathValue[] = 'Virtual not stored fields cannot be used in filters';
					return $whereClause;
				}

				if ($isNumber)
					return $this->Search_Number($value, $fieldrow, $comparison_operator);
				else
					return $this->Search_String($value, $fieldrow, $comparison_operator);
		}
		return $whereClause;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function Search_User($value, $fieldrow, $comparison_operator, $field_extra_param = ''): MySQLWhereClause
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'user.php');

		$v = $this->getString_vL($value);

		$vList = explode(',', $v);
		$whereClause = new MySQLWhereClause();
		//$cArr = array();

		if ($field_extra_param == 'usergroups') {
			foreach ($vList as $vL) {
				if ($vL != '') {
					$whereClause->addOrCondition('(SELECT title FROM #__usergroups AS g WHERE g.id = m.group_id LIMIT 1)', $v, $comparison_operator);
					$whereClause->addOrCondition('(SELECT m.group_id FROM #__user_usergroup_map AS m WHERE user_id='
						. $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . ' LIMIT 1)', $v, $comparison_operator);

					require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
						. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'user.php');

					$filterTitle = Value_user::renderUserValue($vL);
					$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
				}
			}
		} else {
			foreach ($vList as $vL) {
				if ($vL != '') {
					if ((int)$vL == 0 and $comparison_operator == '=') {
						$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], 0);
						$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], null, 'NULL');
					} else {
						$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], (int)$vL, $comparison_operator);
					}

					$filterTitle = Value_user::renderUserValue($vL);
					$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
				}
			}
		}
		return $whereClause;
	}

	function getString_vL($vL): string
	{
		if (str_contains($vL, '$get_')) {
			$getPar = str_replace('$get_', '', $vL);
			$v = (string)preg_replace("/[^\p{L}\d.,_-]/u", "", common::inputGetString($getPar));
		} else
			$v = $vL;

		$v = str_replace('$', '', $v);
		$v = str_replace('"', '', $v);
		$v = str_replace("'", '', $v);
		$v = str_replace('/', '', $v);
		$v = str_replace('\\', '', $v);
		return str_replace('&', '', $v);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function Search_UserGroup($value, $fieldrow, $comparison_operator): MySQLWhereClause
	{
		$v = $this->getString_vL($value);
		$vList = explode(',', $v);
		$whereClause = new MySQLWhereClause();

		//$cArr = array();
		foreach ($vList as $vL) {
			if ($vL != '') {
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], (int)$vL, $comparison_operator);
				//$cArr[] = $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . $comparison_operator . (int)$vL;
				$filterTitle = CTUser::showUserGroup((int)$vL);
				$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
			}
		}

		return $whereClause;
		//if (count($cArr) == 0)
		//	return '';
		//elseif (count($cArr) == 1)
		//	return $cArr[0];
		//else
		//	return '(' . implode(' AND ', $cArr) . ')';
	}

	function Search_Number($value, array $fieldrow, string $comparison_operator, bool $isFloat = false): MySQLWhereClause
	{
		if ($comparison_operator == '==')
			$comparison_operator = '=';

		$v = $this->getString_vL($value);
		$vList = explode(',', $v);
		$whereClause = new MySQLWhereClause();

		foreach ($vList as $vL) {
			if ($vL != '') {

				if ($isFloat)
					$cleanValue = floatval($vL);
				else
					$cleanValue = intval($vL);

				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], $cleanValue, $comparison_operator);

				$opt_title = ' ' . $comparison_operator;
				if ($comparison_operator == '=')
					$opt_title = ':';

				$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . $cleanValue;
			}
		}

		return $whereClause;
		/*
		if (count($cArr) == 0)
			return '';

		if (count($cArr) == 1)
			return $cArr[0];
		else
			return '(' . implode(' OR ', $cArr) . ')';
		*/
	}

	function getRangeWhere($fieldrow, $value): MySQLWhereClause
	{
		$whereClause = new MySQLWhereClause();

		$fieldTitle = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];

		if ($fieldrow['typeparams'] == 'date')
			$valueArr = explode('-to-', $value);
		else
			$valueArr = explode('-', $value);

		if ($valueArr[0] == '' and $valueArr[1] == '')
			return $whereClause;

		$range = explode('_r_', $fieldrow['fieldname']);
		if (count($range) == 1)
			return $whereClause;

		$valueTitle = '';
		//$rangeWhere = '';

		$from_field = '';
		$to_field = '';
		if (isset($range[0])) {
			$from_field = $range[0];
			if (isset($range[1]) and $range[1] != '')
				$to_field = $range[1];
			else
				$to_field = $from_field;
		}

		if ($from_field == '' and $to_field == '')
			return $whereClause;

		if ($fieldrow['typeparams'] == 'date') {
			$v_min = $valueArr[0];
			$v_max = $valueArr[1];
		} else {
			$v_min = (float)$valueArr[0];
			$v_max = (float)$valueArr[1];
		}

		if ($valueArr[0] != '' and $valueArr[1] != '') {
			$whereClause->addCondition('es_' . $from_field, $v_min, '>=');
			$whereClause->addCondition('es_' . $from_field, $v_max, '<=');
			//$rangeWhere = '(es_' . $from_field . '>=' . $v_min . ' AND es_' . $to_field . '<=' . $v_max . ')';
		} elseif ($valueArr[0] != '' and $valueArr[1] == '')
			$whereClause->addCondition('es_' . $from_field, $v_min, '>=');
		elseif ($valueArr[1] != '' and $valueArr[0] == '')
			$whereClause->addCondition('es_' . $from_field, $v_max, '<=');

		if (!$whereClause->hasConditions())
			return $whereClause;

		if ($valueArr[0] != '')
			$valueTitle .= common::translate('COM_CUSTOMTABLES_FROM') . ' ' . $valueArr[0] . ' ';

		if ($valueArr[1] != '')
			$valueTitle .= common::translate('COM_CUSTOMTABLES_TO') . ' ' . $valueArr[1];

		$this->PathValue[] = $fieldTitle . ': ' . $valueTitle;

		return $whereClause;
	}

	function Search_String($value, $fieldrow, $comparison_operator, $isMultilingual = false): MySQLWhereClause
	{
		$whereClause = new MySQLWhereClause();
		$realfieldname = $fieldrow['realfieldname'] . ($isMultilingual ? $this->ct->Languages->Postfix : '');
		$v = $this->getString_vL($value);
		$serverType = database::getServerType();

		if ($comparison_operator == '=' and $v != "") {
			$PathValue = [];

			$vList = explode(',', $v);
			$parentWhereClause = new MySQLWhereClause();

			foreach ($vList as $vL) {
				//this method breaks search sentence to words and creates the LIKE where filter
				$nestedWhereClause = new MySQLWhereClause();

				$v_list = explode(' ', $vL);
				foreach ($v_list as $vl) {

					if ($serverType == 'postgresql') {
						$nestedWhereClause->addOrCondition(
							'CAST ( ' . $this->ct->Table->realtablename . '.' . $realfieldname . ' AS text )',
							'%' . $vl . '%',
							'LIKE',
							true
						);
					} else {
						$nestedWhereClause->addOrCondition(
							$this->ct->Table->realtablename . '.' . $realfieldname,
							'%' . $vl . '%',
							'LIKE',
							true
						);
					}

					$PathValue[] = $vl;
				}
				if ($nestedWhereClause->hasConditions())//if (count($new_v_list) > 1)
					$parentWhereClause->addNestedCondition($nestedWhereClause);
			}

			$opt_title = ':';
			$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . implode(', ', $PathValue);

			if ($parentWhereClause->hasConditions())
				$whereClause->addNestedCondition($parentWhereClause);

			return $whereClause;

		} else {
			//search exactly what requested
			if ($comparison_operator == '==')
				$comparison_operator = '=';

			if ($v == '' and $comparison_operator == '=') {
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $realfieldname, null);
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $realfieldname, '');
			} elseif ($v == '' and $comparison_operator == '!=') {
				$whereClause->addCondition($this->ct->Table->realtablename . '.' . $realfieldname, null, 'NOT NULL');
				$whereClause->addCondition($this->ct->Table->realtablename . '.' . $realfieldname, '', '!=');
			} else {
				$whereClause->addCondition($this->ct->Table->realtablename . '.' . $realfieldname, $v, $comparison_operator);
			}

			$opt_title = ' ' . $comparison_operator;
			if ($comparison_operator == '=')
				$opt_title = ':';

			$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . ($v == '' ? 'NOT SELECTED' : $v);
			return $whereClause;
		}
	}

	function Search_Alias($value, $fieldrow, $comparison_operator): MySQLWhereClause
	{
		if ($comparison_operator == '==')
			$comparison_operator = '=';

		$v = $this->getString_vL($value);
		$vList = explode(',', $v);
		$whereClause = new MySQLWhereClause();

		//$cArr = array();
		foreach ($vList as $vL) {
			if ($vL == "null" and $comparison_operator == '=') {
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], '', $comparison_operator);
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], null, 'NULL');
			} else {
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'], $vL, $comparison_operator);
			}

			$opt_title = ' ' . $comparison_operator;
			if ($comparison_operator == '=')
				$opt_title = ':';

			$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . $vL;
		}
		return $whereClause;
	}

	function Search_DateRange(string $fieldname, string $valueRaw): MySQLWhereClause
	{
		$titleStart = '';
		$whereClause = new MySQLWhereClause();

		$fieldrow1 = Fields::FieldRowByName($fieldname, $this->ct->Table->fields);

		if (!is_null($fieldrow1)) {
			$title1 = $fieldrow1['fieldtitle' . $this->ct->Languages->Postfix];
		} else
			$title1 = $fieldname;

		$valueParts = explode('-to-', $valueRaw);

		$valueStart = isset($valueParts[0]) ? trim($valueParts[0]) : null;
		if ($valueStart === '')
			$valueStart = null;

		$valueEnd = isset($valueParts[1]) ? trim($valueParts[1]) : null;
		if ($valueEnd === '')
			$valueEnd = null;

		// Sanitize and validate date format
		$dateFormat = 'Y-m-d'; // Adjust the format according to your needs

		if ($valueStart) {
			$startDateTime = DateTime::createFromFormat($dateFormat, $valueStart);

			if ($startDateTime !== false) {
				$valueStart = $startDateTime->format($dateFormat);
				$titleStart = $startDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				$fieldrowStart = Fields::FieldRowByName($valueStart, $this->ct->Table->fields);
				//$answer = $valueStart;//$this->processDateSearchTags($valueStart, $fieldrowStart, $this->ct->Table->realtablename);
				$valueStart = $valueStart;//$answer['query'];
				$titleStart = $fieldrowStart['fieldtitle' . $this->ct->Languages->Postfix];//$answer['caption'];
			}
		}

		if ($valueEnd) {
			$endDateTime = DateTime::createFromFormat($dateFormat, $valueEnd);

			if ($endDateTime !== false) {
				$valueEnd = $endDateTime->format($dateFormat);
				$titleEnd = $endDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				$fieldrowEnd = Fields::FieldRowByName($valueEnd, $this->ct->Table->fields);
				//$answer = $valueEnd;//$this->processDateSearchTags($valueEnd, $fieldrowEnd, $this->ct->Table->realtablename);
				$valueEnd = $valueEnd;//$answer['query'];
				$titleEnd = $fieldrowEnd['fieldtitle' . $this->ct->Languages->Postfix];//$answer['caption'];
			}
		}

		if ($valueStart and $valueEnd) {
			//Breadcrumbs
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_DATE_FROM') . ' ' . $titleStart . ' '
				. common::translate('COM_CUSTOMTABLES_DATE_TO') . ' ' . $titleEnd;

			$whereClause->addCondition($fieldrow1['realfieldname'], $valueStart, '>=');
			$whereClause->addCondition($fieldrow1['realfieldname'], $valueEnd, '<=');
			//return '(' . $fieldrow1['realfieldname'] . '>=' . $valueStart . ' AND ' . $fieldrow1['realfieldname'] . '<=' . $valueEnd . ')';
		} elseif ($valueStart and $valueEnd === null) {
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_FROM') . ' ' . $titleStart;

			$whereClause->addCondition($fieldrow1['realfieldname'], $valueStart, '>=');
			//return $fieldrow1['realfieldname'] . '>=' . $valueStart;
		} elseif ($valueStart === null and $valueEnd) {
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_TO') . ' ' . $valueEnd;

			$whereClause->addCondition($fieldrow1['realfieldname'], $valueEnd, '<=');
			//return $fieldrow1['realfieldname'] . '<=' . $valueEnd;
		}
		return $whereClause;
	}

	function Search_Date(string $fieldname, string $valueRaw, string $comparison_operator): MySQLWhereClause
	{
		$whereClause = new MySQLWhereClause();

		//field 1
		$fieldrow1 = Fields::FieldRowByName($fieldname, $this->ct->Table->fields);
		if ($fieldrow1 !== null) {
			//$answer = $this->processDateSearchTags($fieldname, $fieldrow1, $this->ct->Table->realtablename);
			$value1 = $fieldrow1['realfieldname'];//$answer['query'];
			$title1 = $fieldrow1['fieldtitle' . $this->ct->Languages->Postfix];//$answer['caption'];
		} else {
			$value1 = $fieldname;
			$title1 = $fieldname;
		}

		//field 2
		// Sanitize and validate date format
		$dateFormat = 'Y-m-d'; // Adjust the format according to your needs

		if ($valueRaw) {
			$valueDateTime = DateTime::createFromFormat($dateFormat, $valueRaw);

			if ($valueDateTime !== false) {
				$value = $valueDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				return $whereClause;
			}
		} else
			return $whereClause;

		$fieldrow2 = Fields::FieldRowByName($value, $this->ct->Table->fields);
		if ($fieldrow2 !== null) {
			//$answer = $this->processDateSearchTags($value, $fieldrow2, $this->ct->Table->realtablename);
			$value2 = $value;
			$title2 = $fieldrow2['fieldtitle' . $this->ct->Languages->Postfix];//$answer['caption'];$answer['caption'];
		} else {
			$value2 = $value;
			$title2 = $value;
		}

		//Breadcrumbs
		$this->PathValue[] = $title1 . ' ' . $comparison_operator . ' ' . $title2;

		//Query condition
		if ($value2 == 'NULL' and $comparison_operator == '=')
			$whereClause->addCondition($value1, null, 'NULL');
		//$query = $value1 . ' IS NULL';
		elseif ($value2 == 'NULL' and $comparison_operator == '!=')
			$whereClause->addCondition($value1, null, 'NOT NULL');
		//$query = $value1 . ' IS NOT NULL';
		else
			$whereClause->addCondition($value1, $value2, $comparison_operator);
		//$query = $value1 . ' ' . $comparison_operator . ' ' . $value2;
		return $whereClause;
	}

	function getInt_vL($vL)
	{
		if (str_contains($vL, '$get_')) {
			$getPar = str_replace('$get_', '', $vL);
			$a = common::inputGetCmd($getPar, '');
			if ($a == '')
				return '';
			return common::inputGetInt($getPar);
		}
		return $vL;
	}

	function getCmd_vL($vL)
	{
		if (str_contains($vL, '$get_')) {
			$getPar = str_replace('$get_', '', $vL);
			return common::inputGetCmd($getPar, '');
		}

		return $vL;
	}

	protected function processDateSearchTags(string $value, ?array $fieldrow, $esr_table_full): array
	{
		$v = str_replace('"', '', $value);
		$v = str_replace("'", '', $v);
		$v = str_replace('/', '', $v);
		$v = str_replace('\\', '', $v);
		$value = str_replace('&', '', $v);

		if ($fieldrow) {
			//field
			$options = explode(':', $value);

			if (isset($options[1]) and $options[1] != '') {
				$option = trim(preg_replace("/[^a-zA-Z-+% ,_]/", "", $options[1]));
				//https://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html#function_date-format
				return ['query' => 'DATE_FORMAT(' . $esr_table_full . '.' . $fieldrow['realfieldname'] . ', ' . database::quote($option) . ')',
					'caption' => $fieldrow['fieldtitle' . $this->ct->Languages->Postfix]];//%m/%d/%Y %H:%i
			} else {
				return ['query' => $esr_table_full . '.' . $fieldrow['realfieldname'],
					'caption' => $fieldrow['fieldtitle' . $this->ct->Languages->Postfix]];
			}
		} else {
			//value
			if ($value == '{year}') {
				return ['query' => 'year()',
					'caption' => common::translate('COM_CUSTOMTABLES_THIS_YEAR')];
			}

			if ($value == '{month}') {
				return ['query' => 'month()',
					'caption' => common::translate('COM_CUSTOMTABLES_THIS_MONTH')];
			}

			if ($value == '{day}') {
				return ['query' => 'day()',
					'caption' => common::translate('COM_CUSTOMTABLES_THIS_DAY')];
			}

			if (trim(strtolower($value)) == 'null') {
				return ['query' => 'NULL',
					'caption' => common::translate('COM_CUSTOMTABLES_DATE_NOT_SET')];
			}

			$options = array();
			$fList = CTMiscHelper::getListToReplace('now', $options, $value, '{}');

			if (count($fList) == 0) {
				return ['query' => database::quote($value),
					'caption' => $value];
			}

			$option = trim(preg_replace("/[^a-zA-Z-+% ,_]/", "", $options[0]));

			//https://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html#function_date-format
			if ($option != '') {
				//%m/%d/%Y %H:%i
				return ['query' => 'DATE_FORMAT(now(), ' . database::quote($option) . ')',
					'caption' => common::translate('COM_CUSTOMTABLES_DATE_NOW') . ' (' . $option . ')'];
			} else {
				return ['query' => 'now()',
					'caption' => common::translate('COM_CUSTOMTABLES_DATE_NOW')];
			}
		}
	}

}//end class

class LinkJoinFilters
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function getFilterBox($tableName, $dynamicFilterFieldName, $control_name, $filterValue, $control_name_postfix = ''): string
	{
		$fieldRow = Fields::getFieldRowByName($dynamicFilterFieldName, null, $tableName);

		if ($fieldRow === null)
			return '';

		if ($fieldRow->type == 'sqljoin' or $fieldRow->type == 'records')
			return LinkJoinFilters::getFilterElement_SqlJoin($fieldRow->typeparams, $control_name, $filterValue, $control_name_postfix);

		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static protected function getFilterElement_SqlJoin($typeParams, $control_name, $filterValue, $control_name_postfix = ''): string
	{
		$result = '';
		$pair = CTMiscHelper::csv_explode(',', $typeParams, '"', false);

		$tablename = $pair[0];
		if (isset($pair[1]))
			$field = $pair[1];
		else
			return '<p style="color:white;background-color:red;">sqljoin: field not set</p>';

		$tableRow = TableHelper::getTableRowByNameAssoc($tablename);
		if (!is_array($tableRow))
			return '<p style="color:white;background-color:red;">sqljoin: table "' . $tablename . '" not found</p>';

		$fieldrow = Fields::getFieldRowByName($field, $tableRow['id']);
		if (!is_object($fieldrow))
			return '<p style="color:white;background-color:red;">sqljoin: field "' . $field . '" not found</p>';

		$selects = [];
		$selects[] = $tableRow['realtablename'] . '.' . $tableRow['realidfieldname'];

		$whereClause = new MySQLWhereClause();

		//$where = '';
		if ($tableRow['published_field_found']) {
			$selects[] = 'LISTING_PUBLISHED';
			$whereClause->addCondition($tableRow['realtablename'] . '.published', 1);
		} else {
			$selects[] = 'LISTING_PUBLISHED_1';
		}

		$selects[] = $tableRow['realtablename'] . '.' . $fieldrow->realfieldname;

		$rows = database::loadAssocList($tableRow['realtablename'], $selects, $whereClause, $fieldrow->realfieldname);

		$result .= '
		<script>
			ctTranslates["COM_CUSTOMTABLES_SELECT"] = "- ' . common::translate('COM_CUSTOMTABLES_SELECT') . '";
			ctInputBoxRecords_current_value["' . $control_name . '"]="";
		</script>
		';

		$result .= '<select id="' . $control_name . 'SQLJoinLink" onchange="ctInputbox_UpdateSQLJoinLink(\'' . $control_name . '\',\'' . $control_name_postfix . '\')">';
		$result .= '<option value="">- ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

		foreach ($rows as $row) {
			if ($row[$tableRow['realidfieldname']] == $filterValue or str_contains($filterValue, ',' . $row[$tableRow['realidfieldname']] . ','))
				$result .= '<option value="' . $row[$tableRow['realidfieldname']] . '" selected>' . htmlspecialchars($row[$fieldrow->realfieldname] ?? '') . '</option>';
			else
				$result .= '<option value="' . $row[$tableRow['realidfieldname']] . '">' . htmlspecialchars($row[$fieldrow->realfieldname] ?? '') . '</option>';
		}
		$result .= '</select>
';
		return $result;
	}
}
