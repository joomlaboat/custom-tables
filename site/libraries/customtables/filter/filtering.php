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

defined('_JEXEC') or die();

use DateTime;
use Exception;

class Filtering
{
	var CT $ct;
	var array $PathValue;
	var MySQLWhereClause $whereClause;
	var int $showPublished;

	function __construct(CT $ct, int $showPublished = CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY)
	{
		$this->ct = $ct;
		$this->PathValue = [];
		$this->whereClause = new MySQLWhereClause();
		$this->showPublished = $showPublished;

		if ($this->ct->Table !== null and $this->ct->Table->published_field_found) {

			//CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY = 0 - show published
			//CUSTOMTABLES_SHOWPUBLISHED_UNPUBLISHED_ONLY = 1 - show unpublished
			//CUSTOMTABLES_SHOWPUBLISHED_ANY = 2 - show everything
			//CUSTOMTABLES_SHOW_NOT_TRASHED = -1 - show published and unpublished
			//CUSTOMTABLES_SHOW_TRASHED = -2 - show trashed

			if ($this->showPublished == CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY) {
				$this->whereClause->addCondition($this->ct->Table->realtablename . '.published', 1);
			}
			if ($this->showPublished == CUSTOMTABLES_SHOWPUBLISHED_UNPUBLISHED_ONLY) {
				$this->whereClause->addCondition($this->ct->Table->realtablename . '.published', 0);
			}
			if ($this->showPublished == CUSTOMTABLES_SHOW_NOT_TRASHED) {
				$this->whereClause->addOrCondition($this->ct->Table->realtablename . '.published', 0);
				$this->whereClause->addOrCondition($this->ct->Table->realtablename . '.published', 1);
			}
			if ($this->showPublished == CUSTOMTABLES_SHOW_TRASHED) {
				$this->whereClause->addCondition($this->ct->Table->realtablename . '.published', -2);
			}
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function addQueryWhereFilter(): void
	{
		if ($this->ct->Params->blockExternalVars)
			return;

		if (common::inputGetString('where')) {
			$decodedURL = common::inputGetString('where', '');
			$filter_string = $this->sanitizeAndParseFilter(urldecode($decodedURL));

			if ($filter_string != '')
				$this->addWhereExpression($filter_string);
		}

		if (common::inputGetString('filter')) {
			$decodedURL = common::inputGetString('filter', '');
			$filter_string = $this->sanitizeAndParseFilter(urldecode($decodedURL));

			if ($filter_string != '')
				$this->addWhereExpression($filter_string);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function sanitizeAndParseFilter($paramWhere, $parse = false): string
	{
		if ($parse) {
			//Parse using layout, has no effect to layout itself

			try {
				$twig = new TwigProcessor($this->ct, $paramWhere);
				$paramWhere = $twig->process();
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			if ($this->ct->Params->allowContentPlugins)
				$paramWhere = CTMiscHelper::applyContentPlugins($paramWhere);
		}

		//This is old and probably not needed any more because we use MySQLWhereClause class that sanitize individual values.
		//I leave it here just in case
		$paramWhere = str_ireplace('*', '%', $paramWhere);
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
	public function addWhereExpression(?string $param): void
	{
		if ($param === null or $param == '')
			return;

		$param = $this->sanitizeAndParseFilter($param, true);
		$items = CTMiscHelper::ExplodeSmartParamsArray($param);
		$whereClauseExpression = new MySQLWhereClause();

		foreach ($items as $item) {

			$whereClauseTemp = new MySQLWhereClause();
			$fieldNames = explode(';', $item['field']);
			$value = $item['value'];

			try {
				$twig = new TwigProcessor($this->ct, $value);
				$value = $twig->process();
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			foreach ($fieldNames as $fieldname_) {
				$fieldname_parts = explode(':', $fieldname_);
				$fieldname = $fieldname_parts[0];
				$field_extra_param = '';
				if (isset($fieldname_parts[1]))
					$field_extra_param = $fieldname_parts[1];

				if ($fieldname == '_id') {
					$fieldRow = array(
						'id' => 0,
						'fieldname' => '_id',
						'type' => '_id',
						'typeparams' => '',
						'realfieldname' => $this->ct->Table->realidfieldname,
					);
				} elseif ($fieldname == '_published') {
					$fieldRow = array(
						'id' => 0,
						'fieldname' => '_published',
						'type' => '_published',
						'typeparams' => '',
						'realfieldname' => 'listing_published'
					);
				} else {
					//Check if it's a range filter
					$fieldNameParts = explode('_r_', $fieldname);
					$fieldRow = $this->ct->Table->getFieldByName($fieldNameParts[0]);
				}

				if (!is_null($fieldRow) and array_key_exists('type', $fieldRow)) {

					$w = $this->processSingleFieldWhereSyntax($fieldRow, $item['comparison'], $fieldname, $value, $field_extra_param, count($fieldNames) > 1);

					if ($w->hasConditions())
						$whereClauseTemp->addNestedOrCondition($w);
				}
			}

			if ($whereClauseTemp->hasConditions()) {
				if ($item['logic'] == 'or')
					$whereClauseExpression->addNestedOrCondition($whereClauseTemp);
				else
					$whereClauseExpression->addNestedCondition($whereClauseTemp);
			}
		}

		if ($whereClauseExpression->hasConditions())
			$this->whereClause->addNestedCondition($whereClauseExpression);

		//echo 'This what we have: ' . $this->whereClause . '<br/>';
	}

	/**
	 * @throws Exception
	 * @since 3.1.9
	 */
	protected function processSingleFieldWhereSyntax(array $fieldRow, string $comparison_operator, string $fieldname_, string $value, string $field_extra_param = '', bool $asString = false): MySQLWhereClause
	{
		if (!array_key_exists('type', $fieldRow)) {
			throw new Exception('processSingleFieldWhereSyntax: Field not set');
		}

		$field = new Field($this->ct, $fieldRow);
		//Check if it's a range filter
		$fieldNameParts = explode('_r_', $fieldname_);
		$isRange = count($fieldNameParts) == 2;
		$fieldname = $fieldNameParts[0];
		$whereClause = new MySQLWhereClause();

		switch ($fieldRow['type']) {
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

				return $this->Search_User($value, $fieldRow, $comparison_operator, $field_extra_param, $asString);

			case 'usergroup':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				return $this->Search_UserGroup($value, $fieldRow, $comparison_operator);

			case 'viewcount':
			case 'id':
			case 'image':
			case 'int':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				return $this->Search_Number($value, $fieldRow, $comparison_operator);

			case 'float':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				return $this->Search_Number($value, $fieldRow, $comparison_operator, true);

			case 'checkbox':
				$vList = explode(',', $value);

				foreach ($vList as $vL) {

					if ($vL == 'true' or $vL == '1') {
						$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], 1);
						$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix];
					} else {
						$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], 0);
						$this->PathValue[] = common::translate('COM_CUSTOMTABLES_NOT') . ' ' . $fieldRow['fieldtitle' . $this->ct->Languages->Postfix];
					}
				}
				return $whereClause;

			case 'range':
				return $this->getRangeWhere($fieldRow, $value);

			case 'email':
			case 'url':
			case 'string':
			case 'phponchange':
			case 'text':
			case 'phponadd':
			case 'radio':
				return $this->Search_String($value, $fieldRow, $comparison_operator);

			case 'md5':
			case 'alias':
				return $this->Search_Alias($value, $fieldRow, $comparison_operator);

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
				return $this->Search_String($value, $fieldRow, $comparison_operator, true);

			case 'records':

				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
					. DIRECTORY_SEPARATOR . 'tablejoinlist.php');

				$vList = explode(',', $this->getString_vL($value));

				foreach ($vList as $vL) {
					// Filter Title
					$typeParamsArray = CTMiscHelper::csv_explode(',', $fieldRow['typeparams'], '"', false);

					$filterTitle = '';
					if (count($typeParamsArray) < 1)
						$filterTitle .= 'table not specified';

					if (count($typeParamsArray) < 2)
						$filterTitle .= 'field or layout not specified';

					if (count($typeParamsArray) < 3)
						$filterTitle .= 'selector not specified';

					$esr_table_full = $this->ct->Table->realtablename;
					$esr_selector = $typeParamsArray[2];
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
						if ($asString) {
							$tempCT = new CT([], true);
							if ($typeParamsArray[0] != '') {
								$tempCT->getTable($typeParamsArray[0]);
								if ($tempCT->Table === null) {
									throw new Exception('processSingleFieldWhereSyntax: Table not found.');
								}
							} else {
								throw new Exception('processSingleFieldWhereSyntax: Table not set.');
							}

							$joinRealFieldName = null;
							foreach ($tempCT->Table->fields as $field) {
								if ($field['fieldname'] == $typeParamsArray[1]) {
									$joinRealFieldName = $field['realfieldname'];
									break;
								}
							}

							if ($joinRealFieldName === null)
								throw new Exception('processSingleFieldWhereSyntax: field not found.');

							$operator = null;

							if ($comparison_operator == '!=')
								$operator = 'MULTI_FIELD_SEARCH_TABLEJOINLIST_NOT_CONTAIN';
							elseif ($comparison_operator == '!==')
								$operator = 'MULTI_FIELD_SEARCH_TABLEJOINLIST_NOT_EQUAL';
							elseif ($comparison_operator == '=')
								$operator = 'MULTI_FIELD_SEARCH_TABLEJOINLIST_CONTAIN';
							elseif ($comparison_operator == '==')
								$operator = 'MULTI_FIELD_SEARCH_TABLEJOINLIST_EQUAL';
							else
								$opt_title = common::translate('COM_CUSTOMTABLES_UNKNOWN_OPERATION');

							if ($operator !== null) {
								$whereClause->addOrCondition(
									$esr_table_full . '.' . $fieldRow['realfieldname'],
									$valueNew,
									$operator,
									false,
									$tempCT->Table->realtablename,
									$tempCT->Table->realidfieldname,
									$joinRealFieldName
								);
							}
						} else {
							if ($comparison_operator == '!=') {
								//Does not contain
								$whereClause->addCondition($esr_table_full . '.' . $fieldRow['realfieldname'], $valueNew, '!=');
								$whereClause->addCondition($esr_table_full . '.' . $fieldRow['realfieldname'], ',' . $valueNew . ',', 'NOT INSTR');
							} elseif ($comparison_operator == '!==') {
								//Not equal
								$whereClause->addCondition($esr_table_full . '.' . $fieldRow['realfieldname'], $valueNew, '!=');
								$whereClause->addCondition($esr_table_full . '.' . $fieldRow['realfieldname'], ',' . $valueNew . ',', '!=');//exact not value
							} elseif ($comparison_operator == '=') {
								//Contain
								$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], $valueNew);
								$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], ',' . $valueNew . ',', 'INSTR');
							} elseif ($comparison_operator == '==') {
								//Equal
								$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], $valueNew);
								$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], ',' . $valueNew . ',');//exact value
							} else
								$opt_title = common::translate('COM_CUSTOMTABLES_UNKNOWN_OPERATION');
						}

						if ($comparison_operator == '!=' or $comparison_operator == '=') {
							$this->PathValue[] = $fieldRow['fieldtitle'
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
				$typeParamsArray = CTMiscHelper::csv_explode(',', $fieldRow['typeparams']);
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

						$esr_field_name_parts = explode(':', $esr_field_name);
						if (count($esr_field_name_parts) == 2 and ($esr_field_name_parts[0] == 'tablelesslayout' or $esr_field_name_parts[0] == 'layout'))
							$filterTitle .= Value_tablejoin::renderTableJoinValue($field, '{{ document.layout("' . $esr_field_name_parts[1] . '") }}', $valueNew);
						else
							$filterTitle .= Value_tablejoin::renderTableJoinValue($field, '{{ ' . $esr_field_name . ' }}', $valueNew);

						if ($valueNew != '') {
							if ($comparison_operator == '!=') {
								$opt_title = common::translate('COM_CUSTOMTABLES_NOT');
								$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], $valueNew, '!=');
								$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix]
									. ' '
									. $opt_title
									. ' '
									. $filterTitle;
							} elseif ($comparison_operator == '=') {
								$opt_title = ':';

								$integerValueNew = $valueNew;
								if ($integerValueNew == 0 or $integerValueNew == -1) {
									$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], null, 'NULL');
									$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], '');
									$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], 0);
								} else
									$whereClause->addOrCondition($esr_table_full . '.' . $fieldRow['realfieldname'], $valueNew);

								$this->PathValue[] = $fieldRow['fieldtitle'
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
					return $this->Search_Number($value, $fieldRow, $comparison_operator);
				else
					return $this->Search_String($value, $fieldRow, $comparison_operator);
		}
		return $whereClause;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function Search_User($value, $fieldRow, $comparison_operator, $field_extra_param = '', bool $asString = false): MySQLWhereClause
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'user.php');

		$v = $this->getString_vL($value);

		$vList = explode(',', $v);
		$whereClause = new MySQLWhereClause();

		if ($field_extra_param == 'usergroups') {
			foreach ($vList as $vL) {
				if ($vL != '') {
					$whereClause->addOrCondition('(SELECT title FROM #__usergroups AS g WHERE g.id = m.group_id LIMIT 1)', $v, $comparison_operator);
					$whereClause->addOrCondition('(SELECT m.group_id FROM #__user_usergroup_map AS m WHERE user_id='
						. $this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'] . ' LIMIT 1)', $v, $comparison_operator);

					require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
						. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'user.php');

					$filterTitle = Value_user::renderUserValue($vL);
					$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
				}
			}
		} else {
			foreach ($vList as $vL) {
				if ($vL != '') {

					if ($asString) {

						$operator = null;

						if ($comparison_operator == '!=') {
							$operator = 'MULTI_FIELD_SEARCH_TABLEJOIN_NOT_CONTAIN';
							$opt_title = common::translate('COM_CUSTOMTABLES_NOT_CONTAINS');
						} elseif ($comparison_operator == '!==') {
							$operator = 'MULTI_FIELD_SEARCH_TABLEJOIN_NOT_EQUAL';
							$opt_title = common::translate('COM_CUSTOMTABLES_ISNOT');
						} elseif ($comparison_operator == '=') {
							$operator = 'MULTI_FIELD_SEARCH_TABLEJOIN_CONTAIN';
							$opt_title = common::translate('COM_CUSTOMTABLES_CONTAINS');
						} elseif ($comparison_operator == '==') {
							$operator = 'MULTI_FIELD_SEARCH_TABLEJOIN_EQUAL';
							$opt_title = common::translate('COM_CUSTOMTABLES_IS');
						} else
							$opt_title = common::translate('COM_CUSTOMTABLES_UNKNOWN_OPERATION');

						$esr_table_full = $this->ct->Table->realtablename;

						if ($operator !== null) {
							$whereClause->addOrCondition(
								$esr_table_full . '.' . $fieldRow['realfieldname'],
								$vL,
								$operator,
								false,
								'#__users',
								'id',
								'name'
							);

							$whereClause->addOrCondition(
								$esr_table_full . '.' . $fieldRow['realfieldname'],
								$vL,
								$operator,
								false,
								'#__users',
								'id',
								'username'
							);

							$whereClause->addOrCondition(
								$esr_table_full . '.' . $fieldRow['realfieldname'],
								$vL,
								$operator,
								false,
								'#__users',
								'id',
								'email'
							);
						}

						$this->PathValue[] = $fieldRow['fieldtitle'
							. $this->ct->Languages->Postfix]
							. ' '
							. $opt_title
							. ' '
							. $vL;

					} else {
						if ((int)$vL == 0 and $comparison_operator == '=') {
							$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], 0);
							$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], null, 'NULL');
						} else {
							$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], (int)$vL, $comparison_operator);
						}

						$filterTitle = Value_user::renderUserValue((int)$vL);
						$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
					}
				}
			}
		}
		return $whereClause;
	}

	protected function getString_vL($vL): string
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
	protected function Search_UserGroup($value, $fieldRow, $comparison_operator): MySQLWhereClause
	{
		$v = $this->getString_vL($value);
		$vList = explode(',', $v);
		$whereClause = new MySQLWhereClause();

		foreach ($vList as $vL) {
			if ($vL != '') {
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], (int)$vL, $comparison_operator);
				$filterTitle = CTUser::showUserGroup((int)$vL);
				$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
			}
		}

		return $whereClause;
	}

	protected function Search_Number($value, array $fieldRow, string $comparison_operator, bool $isFloat = false): MySQLWhereClause
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

				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], $cleanValue, $comparison_operator);

				$opt_title = ' ' . $comparison_operator;
				if ($comparison_operator == '=')
					$opt_title = ':';

				$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . $cleanValue;
			}
		}

		return $whereClause;
	}

	protected function getRangeWhere($fieldRow, $value): MySQLWhereClause
	{
		$whereClause = new MySQLWhereClause();

		$fieldTitle = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix];

		if ($fieldRow['typeparams'] == 'date')
			$valueArr = explode('-to-', $value);
		else
			$valueArr = explode('-', $value);

		if ($valueArr[0] == '' and $valueArr[1] == '')
			return $whereClause;

		$range = explode('_r_', $fieldRow['fieldname']);
		if (count($range) == 1)
			return $whereClause;

		$valueTitle = '';
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

		if ($fieldRow['typeparams'] == 'date') {
			$v_min = $valueArr[0];
			$v_max = $valueArr[1];
		} else {
			$v_min = (float)$valueArr[0];
			$v_max = (float)$valueArr[1];
		}

		if ($valueArr[0] != '' and $valueArr[1] != '') {
			$whereClause->addCondition($this->ct->Table->fieldPrefix . $from_field, $v_min, '>=');
			$whereClause->addCondition($this->ct->Table->fieldPrefix . $from_field, $v_max, '<=');
		} elseif ($valueArr[0] != '' and $valueArr[1] == '')
			$whereClause->addCondition($this->ct->Table->fieldPrefix . $from_field, $v_min, '>=');
		elseif ($valueArr[1] != '' and $valueArr[0] == '')
			$whereClause->addCondition($this->ct->Table->fieldPrefix . $from_field, $v_max, '<=');

		if (!$whereClause->hasConditions())
			return $whereClause;

		if ($valueArr[0] != '')
			$valueTitle .= common::translate('COM_CUSTOMTABLES_FROM') . ' ' . $valueArr[0] . ' ';

		if ($valueArr[1] != '')
			$valueTitle .= common::translate('COM_CUSTOMTABLES_TO') . ' ' . $valueArr[1];

		$this->PathValue[] = $fieldTitle . ': ' . $valueTitle;

		return $whereClause;
	}

	protected function Search_String($value, array $fieldRow, $comparison_operator, $isMultilingual = false): MySQLWhereClause
	{
		$whereClause = new MySQLWhereClause();
		$realfieldname = $fieldRow['realfieldname'] . ($isMultilingual ? $this->ct->Languages->Postfix : '');
		$v = $this->getString_vL($value);
		$serverType = database::getServerType();

		if ($comparison_operator == '=' and $v != "") {
			$PathValue = [];

			//this method breaks search sentence to words and creates the LIKE where filter
			$v = str_replace(' ', ',', $v);
			$vList = explode(',', $v);
			$nestedWhereClause = new MySQLWhereClause();

			foreach ($vList as $vL) {

				if ($serverType == 'postgresql') {
					$nestedWhereClause->addOrCondition(
						'CAST ( ' . $this->ct->Table->realtablename . '.' . $realfieldname . ' AS text )',
						'%' . $vL . '%',
						'LIKE',
						true
					);
				} else {
					$nestedWhereClause->addOrCondition(
						$this->ct->Table->realtablename . '.' . $realfieldname,
						'%' . $vL . '%',
						'LIKE',
						true
					);
				}
				$PathValue[] = $vL;
			}

			$opt_title = ':';
			$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . implode(', ', $PathValue);

			if ($nestedWhereClause->hasConditions())
				$whereClause->addNestedCondition($nestedWhereClause);

			return $whereClause;

		} else {
			//search exactly what requested
			if ($comparison_operator == '==')
				$comparison_operator = '=';

			$vList = explode(',', $v);
			$nestedWhereClause = new MySQLWhereClause();

			foreach ($vList as $vL) {

				if ($vL == '' and $comparison_operator == '=') {

					$nestedWhereClause2 = new MySQLWhereClause();
					$nestedWhereClause2->addOrCondition($this->ct->Table->realtablename . '.' . $realfieldname, null);
					$nestedWhereClause2->addOrCondition($this->ct->Table->realtablename . '.' . $realfieldname, '');
					$nestedWhereClause->addNestedCondition($nestedWhereClause2);

				} elseif ($vL == '' and $comparison_operator == '!=') {
					$nestedWhereClause->addOrCondition($this->ct->Table->realtablename . '.' . $realfieldname, null, 'NOT NULL');
					$nestedWhereClause->addOrCondition($this->ct->Table->realtablename . '.' . $realfieldname, '', '!=');
				} else {

					if ($comparison_operator == '=' and str_contains($vL, '%')) {
						$nestedWhereClause->addOrCondition($this->ct->Table->realtablename . '.' . $realfieldname, $vL, 'LIKE');
					} else {
						$nestedWhereClause->addOrCondition($this->ct->Table->realtablename . '.' . $realfieldname, $vL, $comparison_operator);
					}
				}

				$opt_title = ' ' . $comparison_operator;
				if ($comparison_operator == '=')
					$opt_title = ':';

				$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . ($vL == '' ? 'NOT SELECTED' : $v);
			}

			if ($nestedWhereClause->hasConditions())
				$whereClause->addNestedCondition($nestedWhereClause);

			return $whereClause;
		}
	}

	protected function Search_Alias($value, $fieldRow, $comparison_operator): MySQLWhereClause
	{
		if ($comparison_operator == '==')
			$comparison_operator = '=';

		$v = $this->getString_vL($value);
		$vList = explode(',', $v);
		$whereClause = new MySQLWhereClause();

		foreach ($vList as $vL) {
			if ($vL == "null" and $comparison_operator == '=') {
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], '', $comparison_operator);
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], null, 'NULL');
			} else {
				$whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $fieldRow['realfieldname'], $vL, $comparison_operator);
			}

			$opt_title = ' ' . $comparison_operator;
			if ($comparison_operator == '=')
				$opt_title = ':';

			$this->PathValue[] = $fieldRow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . $vL;
		}
		return $whereClause;
	}

	protected function Search_DateRange(string $fieldname, string $valueRaw): MySQLWhereClause
	{
		$titleStart = '';
		$whereClause = new MySQLWhereClause();

		$fieldRow1 = $this->ct->Table->getFieldByName($fieldname);

		if (!is_null($fieldRow1)) {
			$title1 = $fieldRow1['fieldtitle' . $this->ct->Languages->Postfix];
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
				$fieldRowStart = $this->ct->Table->getFieldByName($valueStart);
				$titleStart = $fieldRowStart['fieldtitle' . $this->ct->Languages->Postfix];
			}
		}

		if ($valueEnd) {
			$endDateTime = DateTime::createFromFormat($dateFormat, $valueEnd);

			if ($endDateTime !== false) {
				$valueEnd = $endDateTime->format($dateFormat);
				$titleEnd = $endDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				$fieldRowEnd = $this->ct->Table->getFieldByName($valueEnd);
				$titleEnd = $fieldRowEnd['fieldtitle' . $this->ct->Languages->Postfix];
			}
		}

		if ($valueStart and $valueEnd) {
			//Breadcrumbs
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_DATE_FROM') . ' ' . $titleStart . ' '
				. common::translate('COM_CUSTOMTABLES_DATE_TO') . ' ' . $titleEnd;

			$whereClause->addCondition($fieldRow1['realfieldname'], $valueStart, '>=');
			$whereClause->addCondition($fieldRow1['realfieldname'], $valueEnd, '<=');
		} elseif ($valueStart and $valueEnd === null) {
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_FROM') . ' ' . $titleStart;

			$whereClause->addCondition($fieldRow1['realfieldname'], $valueStart, '>=');
		} elseif ($valueStart === null and $valueEnd) {
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_TO') . ' ' . $valueEnd;

			$whereClause->addCondition($fieldRow1['realfieldname'], $valueEnd, '<=');
		}
		return $whereClause;
	}

	protected function Search_Date(string $fieldname, string $valueRaw, string $comparison_operator): MySQLWhereClause
	{
		$whereClause = new MySQLWhereClause();

		//field 1
		$fieldRow1 = $this->ct->Table->getFieldByName($fieldname);
		if ($fieldRow1 !== null) {
			$value1 = $fieldRow1['realfieldname'];
			$title1 = $fieldRow1['fieldtitle' . $this->ct->Languages->Postfix];
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

		$fieldRow2 = $this->ct->Table->getFieldByName($value);
		if ($fieldRow2 !== null) {
			$value2 = $value;
			$title2 = $fieldRow2['fieldtitle' . $this->ct->Languages->Postfix];
		} else {
			$value2 = $value;
			$title2 = $value;
		}

		//Breadcrumbs
		$this->PathValue[] = $title1 . ' ' . $comparison_operator . ' ' . $title2;

		//Query condition
		if ($value2 == 'NULL' and $comparison_operator == '=')
			$whereClause->addCondition($value1, null, 'NULL');
		elseif ($value2 == 'NULL' and $comparison_operator == '!=')
			$whereClause->addCondition($value1, null, 'NOT NULL');
		else
			$whereClause->addCondition($value1, $value2, $comparison_operator);
		return $whereClause;
	}

	protected function getInt_vL($vL)
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

	protected function getCmd_vL($vL)
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
	static public function getFilterBox(CT $ct, $dynamicFilterFieldName, $control_name, $filterValue, $control_name_postfix = ''): string
	{
		$fieldRow = $ct->Table->getFieldByName($dynamicFilterFieldName);

		if ($fieldRow === null)
			return '';

		if ($fieldRow['type'] == 'sqljoin' or $fieldRow['type'] == 'records')
			return LinkJoinFilters::getFilterElement_SqlJoin($fieldRow['typeparams'], $control_name, $filterValue, $control_name_postfix);

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

		$ct = new CT([], true);
		$ct->getTable($tablename);

		if ($ct->Table === null)
			return '<p style="color:white;background-color:red;">sqljoin: table "' . $tablename . '" not found</p>';

		$fieldRow = $ct->Table->getFieldByName($field);
		if (!is_array($fieldRow))
			return '<p style="color:white;background-color:red;">sqljoin: field "' . $field . '" not found</p>';

		$selects = [];
		$selects[] = $ct->Table->realtablename . '.' . $ct->Table->realidfieldname;
		$whereClause = new MySQLWhereClause();

		if ($ct->Table->published_field_found) {
			$selects[] = 'LISTING_PUBLISHED';
			$whereClause->addCondition($ct->Table->realtablename . '.published', 1);
		} else {
			$selects[] = 'LISTING_PUBLISHED_1';
		}

		$selects[] = $ct->Table->realtablename . '.' . $fieldRow['realfieldname'];
		$rows = database::loadAssocList($ct->Table->realtablename, $selects, $whereClause, $fieldRow['realfieldname']);

		$result .= '<select id="' . $control_name . 'SQLJoinLink" class="' . common::convertClassString('form-select') . '" onchange="ctInputbox_UpdateSQLJoinLink(\'' . $control_name . '\',\'' . $control_name_postfix . '\')">';
		$result .= '<option value="">- ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

		foreach ($rows as $row) {
			if ($row[$ct->Table->realidfieldname] == $filterValue or str_contains($filterValue, ',' . $row[$ct->Table->realidfieldname] . ','))
				$result .= '<option value="' . $row[$ct->Table->realidfieldname] . '" selected>' . htmlspecialchars($row[$fieldRow['realfieldname']] ?? '') . '</option>';
			else
				$result .= '<option value="' . $row[$ct->Table->realidfieldname] . '">' . htmlspecialchars($row[$fieldRow['realfieldname']] ?? '') . '</option>';
		}
		$result .= '</select>
';
		return $result;
	}
}
