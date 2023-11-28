<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\DataTypes\Tree;
use DateTime;
use ESTables;
use JoomlaBasicMisc;
use LayoutProcessor;

//Joomla 3 support
use JHTML;

//Joomla 4+ support
use Joomla\CMS\HTML\HtmlHelper;

class Filtering
{
	var CT $ct;
	var array $PathValue;
	var array $where;
	var int $showPublished;

	function __construct(CT $ct, int $showPublished = 0)
	{
		if (defined('_JEXEC')) {
			if ($ct->Env->version < 4)
				HTMLHelper::_addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'helpers');
			else
				HtmlHelper::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'helpers');
		}

		$this->ct = $ct;
		$this->PathValue = [];
		$this->where = [];
		$this->showPublished = $showPublished;

		if ($this->ct->Table->published_field_found) {

			//TODO: Fix this mess by replacing the state with a text code like 'published','unpublished','everything','any','trash'
			//$showPublished = 0 - show published
			//$showPublished = 1 - show unpublished
			//$showPublished = 2 - show everything
			//$showPublished = -1 - show published and unpublished
			//$showPublished = -2 - show trashed

			if ($this->showPublished == 0)
				$this->where[] = $this->ct->Table->realtablename . '.published=1';
			if ($this->showPublished == 1)
				$this->where[] = $this->ct->Table->realtablename . '.published=0';
			if ($this->showPublished == -1)
				$this->where[] = '(' . $this->ct->Table->realtablename . '.published=0 OR ' . $this->ct->Table->realtablename . '.published=1)';
			if ($this->showPublished == -2)
				$this->where[] = $this->ct->Table->realtablename . '.published=-2';
		}
	}

	function addQueryWhereFilter(): void
	{
		if (common::inputGetBase64('where')) {
			$decodedURL = common::inputGetBase64('where', '');
			$decodedURL = urldecode($decodedURL);
			$decodedURL = str_replace(' ', '+', $decodedURL);
			$filter_string = $this->sanitizeAndParseFilter(base64_decode($decodedURL));

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
				$paramWhere = JoomlaBasicMisc::applyContentPlugins($paramWhere);
		}

		$paramWhere = str_ireplace('*', '=', $paramWhere);
		$paramWhere = str_ireplace('\\', '', $paramWhere);
		$paramWhere = str_ireplace('drop ', '', $paramWhere);
		$paramWhere = str_ireplace('select ', '', $paramWhere);
		$paramWhere = str_ireplace('delete ', '', $paramWhere);
		$paramWhere = str_ireplace('update ', '', $paramWhere);
		$paramWhere = str_ireplace('grant ', '', $paramWhere);
		return str_ireplace('insert ', '', $paramWhere);
	}

	function addWhereExpression(?string $param): void
	{
		if ($param === null or $param == '')
			return;

		$param = $this->sanitizeAndParseFilter($param, true);
		$wheres = [];
		$items = common::ExplodeSmartParams($param);
		$logic_operator = '';

		foreach ($items as $item) {
			$logic_operator = $item[0];
			$comparison_operator_str = $item[1];
			$comparison_operator = '';
			$multi_field_where = [];

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
					$whr = JoomlaBasicMisc::csv_explode($comparison_operator, $comparison_operator_str, '"', false);

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
									'fieldname' => '_id',
									'type' => '_id',
									'typeparams' => '',
									'realfieldname' => $this->ct->Table->realidfieldname,
								);
							} elseif ($fieldname == '_published') {
								$fieldrow = array(
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

							if (!is_null($fieldrow)) {
								$w = $this->processSingleFieldWhereSyntax($fieldrow, $comparison_operator, $fieldname, $value, $field_extra_param);
								if ($w !== null and $w != '')
									$multi_field_where[] = $w;
							}
						}
					}
				}
			}

			if (count($multi_field_where) == 1)
				$wheres[] = implode(' OR ', $multi_field_where);
			elseif (count($multi_field_where) > 1)
				$wheres[] = '(' . implode(' OR ', $multi_field_where) . ')';
		}

		if ($logic_operator == '') {
			$this->ct->errors[] = common::translate('Search parameter "' . $param . '" is incorrect');
			return;
		}

		if (count($wheres) > 0) {
			if ($logic_operator == 'or' and count($wheres) > 1)
				$this->where[] = '(' . implode(' ' . $logic_operator . ' ', $wheres) . ')';
			else
				$this->where[] = implode(' ' . $logic_operator . ' ', $wheres);
		}
	}

	function processSingleFieldWhereSyntax(array $fieldrow, string $comparison_operator, string $fieldname_, string $value, string $field_extra_param = ''): ?string
	{
		//Check if it's a range filter
		$fieldNameParts = explode('_r_', $fieldname_);
		$isRange = count($fieldNameParts) == 2;
		$fieldname = $fieldNameParts[0];
		$c = '';

		switch ($fieldrow['type']) {
			case '_id':

				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$vList = explode(',', $value);
				$cArr = array();
				foreach ($vList as $vL) {
					$cArr[] = $this->ct->Table->realtablename . '.' . $this->ct->Table->realidfieldname . $comparison_operator . database::quote($vL);

					$this->PathValue[] = 'ID ' . $comparison_operator . ' ' . $vL;
				}
				if (count($cArr) == 1)
					$c = $cArr[0];
				else
					$c = '(' . implode(' OR ', $cArr) . ')';

				break;

			case '_published':

				if ($this->ct->Table->published_field_found) {
					if ($comparison_operator == '==')
						$comparison_operator = '=';

					$c = $this->ct->Table->realtablename . '.published' . $comparison_operator . (int)$value;
					$this->PathValue[] = 'Published ' . $comparison_operator . ' ' . (int)$value;
				}

				break;

			case 'userid':
			case 'user':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$c = $this->Search_User($value, $fieldrow, $comparison_operator, $field_extra_param);
				break;

			case 'usergroup':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$c = $this->Search_UserGroup($value, $fieldrow, $comparison_operator);
				break;

			case 'float':
			case 'viewcount':
			case 'image':
			case 'int':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$c = $this->Search_Number($value, $fieldrow, $comparison_operator);
				break;

			case 'checkbox':

				$vList = explode(',', $value);
				$cArr = array();
				foreach ($vList as $vL) {

					if ($vL == 'true' or $vL == '1') {
						$cArr[] = $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . '=1';
						$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];
					} else {
						$cArr[] = $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . '=0';

						$this->PathValue[] = common::translate('COM_CUSTOMTABLES_NOT') . ' ' . $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];
					}
				}
				if (count($cArr) == 1)
					$c = $cArr[0];
				else
					$c = '(' . implode(' OR ', $cArr) . ')';

				break;

			case 'range':

				$c = $this->getRangeWhere($fieldrow, $value);
				break;

			case 'email':
			case 'url':
			case 'string':
			case 'phponchange':
			case 'text':
			case 'phponadd':
			case 'radio':

				$c = $this->Search_String($value, $fieldrow, $comparison_operator);
				break;

			case 'md5':
			case 'alias':

				$c = $this->Search_Alias($value, $fieldrow, $comparison_operator);
				break;

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

				$c = $this->Search_String($value, $fieldrow, $comparison_operator, true);
				break;

			case 'customtables':

				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$vList = explode(',', $value);
				$cArr = array();
				foreach ($vList as $vL) {
					//--------

					$v = trim($vL);
					if ($v != '') {

						//to fix the line
						if ($v[0] != ',')
							$v = ',' . $v;

						if ($v[strlen($v) - 1] != '.')
							$v .= '.';

						if ($comparison_operator == '=') {
							$cArr[] = 'instr(' . $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . ',' . database::quote($v) . ')';

							$vTitle = Tree::getMultyValueTitles($v, $this->ct->Languages->Postfix, 1, ' - ');
							$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ': ' . implode(',', $vTitle);
						} elseif ($comparison_operator == '!=') {
							$cArr[] = '!instr(' . $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . ',' . database::quote($v) . ')';

							$vTitle = Tree::getMultyValueTitles($v, $this->ct->Languages->Postfix, 1, ' - ');
							$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ': ' . implode(',', $vTitle);
						}
					}
				}

				if (count($cArr) == 1)
					$c = $cArr[0];
				else
					$c = '(' . implode(' OR ', $cArr) . ')';

				break;

			case 'records':

				$vList = explode(',', $this->getString_vL($value));
				$cArr = array();
				foreach ($vList as $vL) {
					// Filter Title
					$typeParamsArray = JoomlaBasicMisc::csv_explode(',', $fieldrow['typeparams'], '"', false);

					$filterTitle = '';
					if (count($typeParamsArray) < 1)
						$filterTitle .= 'table not specified';

					if (count($typeParamsArray) < 2)
						$filterTitle .= 'field or layout not specified';

					if (count($typeParamsArray) < 3)
						$filterTitle .= 'selector not specified';

					$esr_table = $typeParamsArray[0];
					$esr_table_full = $this->ct->Table->realtablename;
					$esr_field = $typeParamsArray[1];
					$esr_selector = $typeParamsArray[2];

					if (count($typeParamsArray) > 3)
						$esr_filter = $typeParamsArray[3];
					else
						$esr_filter = '';

					$filterTitle .= HTMLHelper::_('ESRecordsView.render',
						$vL,
						$esr_table,
						$esr_field,
						$esr_selector,
						$esr_filter);

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

						if ($comparison_operator == '!=')
							$cArr[] = '!instr(' . $esr_table_full . '.' . $fieldrow['realfieldname'] . ',' . database::quote(',' . $valueNew . ',') . ')';
						elseif ($comparison_operator == '!==')
							$cArr[] = $esr_table_full . '.' . $fieldrow['realfieldname'] . '!=' . database::quote(',' . $valueNew . ',');//not exact value
						elseif ($comparison_operator == '=')
							$cArr[] = 'instr(' . $esr_table_full . '.' . $fieldrow['realfieldname'] . ',' . database::quote(',' . $valueNew . ',') . ')';
						elseif ($comparison_operator == '==')
							$cArr[] = $esr_table_full . '.' . $fieldrow['realfieldname'] . '=' . database::quote(',' . $valueNew . ',');//exact value
						else
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
				if (count($cArr) == 1)
					$c = $cArr[0];
				elseif (count($cArr) > 1)
					$c = '(' . implode(' OR ', $cArr) . ')';

				break;

			case 'sqljoin':

				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$vList = explode(',', $this->getString_vL($value));
				$cArr = array();

				// Filter Title
				$typeParamsArray = explode(',', $fieldrow['typeparams']);
				$filterTitle = '';

				if (count($typeParamsArray) < 2)
					$filterTitle = 'field or layout not specified';

				if (count($typeParamsArray) < 1)
					$filterTitle = 'table not specified';

				$esr_table = $typeParamsArray[0];
				$esr_table_full = $this->ct->Table->realtablename;
				$esr_field = $typeParamsArray[1];
				$esr_filter = $typeParamsArray[2] ?? '';

				if (count($typeParamsArray) >= 2) {
					foreach ($vList as $vL) {
						$valueNew = $vL;

						$filterTitle .= HTMLHelper::_('ESSQLJoinView.render',
							$vL,
							$esr_table,
							$esr_field,
							$esr_filter,
							$this->ct->Languages->Postfix);

						if ($valueNew != '') {
							if ($comparison_operator == '!=') {
								$opt_title = common::translate('COM_CUSTOMTABLES_NOT');

								$cArr[] = $esr_table_full . '.' . $fieldrow['realfieldname'] . '!=' . database::quote($valueNew);
								$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix]
									. ' '
									. $opt_title
									. ' '
									. $filterTitle;
							} elseif ($comparison_operator == '=') {
								$opt_title = ':';

								$integerValueNew = $valueNew;
								if ($integerValueNew == 0 or $integerValueNew == -1) {
									$cArr[] = '(' . $esr_table_full . '.' . $fieldrow['realfieldname'] . '=0 OR ' . $esr_table_full . '.' . $fieldrow['realfieldname'] . '="" OR '
										. $esr_table_full . '.' . $fieldrow['realfieldname'] . ' IS NULL)';
								} else
									$cArr[] = $esr_table_full . '.' . $fieldrow['realfieldname'] . '=' . database::quote($valueNew);

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

				if (count($cArr) == 1)
					$c = $cArr[0];
				elseif (count($cArr) > 1)
					$c = '(' . implode(' OR ', $cArr) . ')';

				break;

			case 'virtual':
				if ($comparison_operator == '==')
					$comparison_operator = '=';

				$field = new Field($this->ct, $fieldrow);
				$storage = $field->params[1] ?? null;

				if ($storage == 'storedstring')
					$isNumber = false;
				elseif ($storage == 'storedintegersigned' or $storage == 'storedintegerunsigned')
					$isNumber = true;
				else {
					$this->PathValue[] = 'Virtual not stored fields cannot be used in filters';
					return '';
				}

				if ($isNumber)
					$c = $this->Search_Number($value, $fieldrow, $comparison_operator);
				else
					$c = $this->Search_String($value, $fieldrow, $comparison_operator);

				break;
		}
		return $c;
	}

	function Search_User($value, $fieldrow, $comparison_operator, $field_extra_param = '')
	{
		$v = $this->getString_vL($value);

		$vList = explode(',', $v);
		$cArr = array();

		if ($field_extra_param == 'usergroups') {
			foreach ($vList as $vL) {
				if ($vL != '') {
					$select1 = '(SELECT title FROM #__usergroups AS g WHERE g.id = m.group_id LIMIT 1)';
					$cArr[] = '(SELECT m.group_id FROM #__user_usergroup_map AS m WHERE user_id=' . $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . ' AND '
						. $select1 . $comparison_operator . database::quote($v) . ')';

					$filterTitle = HTMLHelper::_('ESUserView.render', $vL);
					$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
				}
			}
		} else {
			foreach ($vList as $vL) {
				if ($vL != '') {
					if ((int)$vL == 0 and $comparison_operator == '=')
						$cArr[] = '(' . $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . '=0 OR ' . $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . ' IS NULL)';
					else
						$cArr[] = $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . $comparison_operator . (int)$vL;

					$filterTitle = HTMLHelper::_('ESUserView.render', $vL);
					$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
				}
			}
		}

		if (count($cArr) == 0)
			return '';
		elseif (count($cArr) == 1)
			return $cArr[0];
		else
			return '(' . implode(' AND ', $cArr) . ')';
	}

	function getString_vL($vL): string
	{
		if (str_contains($vL, '$get_')) {
			$getPar = str_replace('$get_', '', $vL);
			$v = (string)preg_replace("/[^\p{L}\d.,_-]/u", "", common::inputGetString($getPar));
			//$v = (string)preg_replace('/[^A-Z\d_.,-]/i', '', common::inputGetString($getPar));
		} else
			$v = $vL;

		$v = str_replace('$', '', $v);
		$v = str_replace('"', '', $v);
		$v = str_replace("'", '', $v);
		$v = str_replace('/', '', $v);
		$v = str_replace('\\', '', $v);
		return str_replace('&', '', $v);
	}

	function Search_UserGroup($value, $fieldrow, $comparison_operator)
	{
		$v = $this->getString_vL($value);

		$vList = explode(',', $v);
		$cArr = array();
		foreach ($vList as $vL) {
			if ($vL != '') {
				$cArr[] = $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . $comparison_operator . (int)$vL;
				$filterTitle = HTMLHelper::_('ESUserGroupView.render', $vL);
				$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
			}
		}

		if (count($cArr) == 0)
			return '';
		elseif (count($cArr) == 1)
			return $cArr[0];
		else
			return '(' . implode(' AND ', $cArr) . ')';
	}

	function Search_Number($value, $fieldrow, $comparison_operator)
	{
		if ($comparison_operator == '==')
			$comparison_operator = '=';

		$v = $this->getString_vL($value);

		$vList = explode(',', $v);
		$cArr = array();
		foreach ($vList as $vL) {
			if ($vL != '') {
				$cArr[] = $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . $comparison_operator . (int)$vL;

				$opt_title = ' ' . $comparison_operator;
				if ($comparison_operator == '=')
					$opt_title = ':';

				$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . (int)$vL;
			}
		}

		if (count($cArr) == 0)
			return '';

		if (count($cArr) == 1)
			return $cArr[0];
		else
			return '(' . implode(' OR ', $cArr) . ')';
	}

	function getRangeWhere($fieldrow, $value): string
	{
		$fieldTitle = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];

		if ($fieldrow['typeparams'] == 'date')
			$valueArr = explode('-to-', $value);
		else
			$valueArr = explode('-', $value);

		if ($valueArr[0] == '' and $valueArr[1] == '')
			return '';

		$range = explode('_r_', $fieldrow['fieldname']);
		if (count($range) == 1)
			return '';

		$valueTitle = '';
		$rangeWhere = '';

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
			return '';

		if ($fieldrow['typeparams'] == 'date') {
			$v_min = database::quote($valueArr[0]);
			$v_max = database::quote($valueArr[1]);
		} else {
			$v_min = (float)$valueArr[0];
			$v_max = (float)$valueArr[1];
		}

		if ($valueArr[0] != '' and $valueArr[1] != '')
			$rangeWhere = '(es_' . $from_field . '>=' . $v_min . ' AND es_' . $to_field . '<=' . $v_max . ')';
		elseif ($valueArr[0] != '' and $valueArr[1] == '')
			$rangeWhere = '(es_' . $from_field . '>=' . $v_min . ')';
		elseif ($valueArr[1] != '' and $valueArr[0] == '')
			$rangeWhere = '(es_' . $from_field . '<=' . $v_max . ')';

		if ($rangeWhere == '')
			return '';

		if ($valueArr[0] != '')
			$valueTitle .= common::translate('COM_CUSTOMTABLES_FROM') . ' ' . $valueArr[0] . ' ';

		if ($valueArr[1] != '')
			$valueTitle .= common::translate('COM_CUSTOMTABLES_TO') . ' ' . $valueArr[1];

		$this->PathValue[] = $fieldTitle . ': ' . $valueTitle;

		return $rangeWhere;
	}

	function Search_String($value, $fieldrow, $comparison_operator, $isMultilingual = false): string
	{
		$realfieldname = $fieldrow['realfieldname'] . ($isMultilingual ? $this->ct->Languages->Postfix : '');
		$v = $this->getString_vL($value);
		$serverType = database::getServerType();

		if ($comparison_operator == '=' and $v != "") {
			$PathValue = [];

			$vList = explode(',', $v);
			$cArr = array();
			foreach ($vList as $vL) {
				//this method breaks search sentence to words and creates the LIKE where filter
				$new_v_list = array();
				$v_list = explode(' ', $vL);
				foreach ($v_list as $vl) {

					if ($serverType == 'postgresql')
						$new_v_list[] = 'CAST ( ' . $this->ct->Table->realtablename . '.' . $realfieldname . ' AS text ) LIKE ' . database::quote('%' . $vl . '%');
					else
						$new_v_list[] = $this->ct->Table->realtablename . '.' . $realfieldname . ' LIKE ' . database::quote('%' . $vl . '%');

					$PathValue[] = $vl;
				}

				if (count($new_v_list) > 1)
					$cArr[] = '(' . implode(' AND ', $new_v_list) . ')';
				else
					$cArr[] = implode(' AND ', $new_v_list);
			}

			$opt_title = ':';
			$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . implode(', ', $PathValue);

			if (count($cArr) > 1)
				return '(' . implode(' OR ', $cArr) . ')';
			else
				return implode(' OR ', $cArr);

		} else {
			//search exactly what requested
			if ($comparison_operator == '==')
				$comparison_operator = '=';

			if ($v == '' and $comparison_operator == '=')
				$where = '(' . $this->ct->Table->realtablename . '.' . $realfieldname . ' IS NULL OR ' . database::quoteName($realfieldname) . '=' . database::quote('') . ')';
			elseif ($v == '' and $comparison_operator == '!=')
				$where = '(' . $this->ct->Table->realtablename . '.' . $realfieldname . ' IS NOT NULL AND ' . database::quoteName($realfieldname) . '!=' . database::quote('') . ')';
			else
				$where = $this->ct->Table->realtablename . '.' . $realfieldname . $comparison_operator . database::quote($v);

			$opt_title = ' ' . $comparison_operator;
			if ($comparison_operator == '=')
				$opt_title = ':';

			$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . ($v == '' ? 'NOT SELECTED' : $v);
			return $where;
		}
	}

	function Search_Alias($value, $fieldrow, $comparison_operator)
	{
		if ($comparison_operator == '==')
			$comparison_operator = '=';

		$v = $this->getString_vL($value);

		$vList = explode(',', $v);
		$cArr = array();
		foreach ($vList as $vL) {
			if ($vL == "null" and $comparison_operator == '=')
				$cArr[] = '(' . $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . '=' . database::quote('') . ' OR ' . $fieldrow['realfieldname'] . ' IS NULL)';
			else
				$cArr[] = $this->ct->Table->realtablename . '.' . $fieldrow['realfieldname'] . $comparison_operator . database::quote($vL);

			$opt_title = ' ' . $comparison_operator;
			if ($comparison_operator == '=')
				$opt_title = ':';

			$this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . $vL;
		}

		if (count($cArr) == 1)
			return $cArr[0];
		else
			return '(' . implode(' AND ', $cArr) . ')';
	}

	function Search_DateRange(string $fieldname, string $valueRaw): ?string
	{
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
				$valueStart = database::quote($startDateTime->format($dateFormat));
				$titleStart = $startDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				$fieldrowStart = Fields::FieldRowByName($valueStart, $this->ct->Table->fields);
				$answer = $this->processDateSearchTags($valueStart, $fieldrowStart, $this->ct->Table->realtablename);
				$valueStart = $answer['query'];
				$titleStart = $answer['caption'];
			}
		}

		if ($valueEnd) {
			$endDateTime = DateTime::createFromFormat($dateFormat, $valueEnd);

			if ($endDateTime !== false) {
				$valueEnd = database::quote($endDateTime->format($dateFormat));
				$titleEnd = $endDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				$fieldrowEnd = Fields::FieldRowByName($valueEnd, $this->ct->Table->fields);
				$answer = $this->processDateSearchTags($valueEnd, $fieldrowEnd, $this->ct->Table->realtablename);
				$valueEnd = $answer['query'];
				$titleEnd = $answer['caption'];
			}
		}

		if ($valueStart and $valueEnd) {
			//Breadcrumbs
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_DATE_FROM') . ' ' . $titleStart . ' '
				. common::translate('COM_CUSTOMTABLES_DATE_TO') . ' ' . $titleEnd;

			return '(' . $fieldrow1['realfieldname'] . '>=' . $valueStart . ' AND ' . $fieldrow1['realfieldname'] . '<=' . $valueEnd . ')';
		} elseif ($valueStart and $valueEnd === null) {
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_FROM') . ' ' . $titleStart;

			return $fieldrow1['realfieldname'] . '>=' . $valueStart;
		} elseif ($valueStart === null and $valueEnd) {
			$this->PathValue[] = $title1 . ' '
				. common::translate('COM_CUSTOMTABLES_TO') . ' ' . $valueEnd;

			return $fieldrow1['realfieldname'] . '<=' . $valueEnd;
		}
		return null;
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
			$fList = JoomlaBasicMisc::getListToReplace('now', $options, $value, '{}');

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

	function Search_Date(string $fieldname, string $valueRaw, string $comparison_operator): ?string
	{
		//field 1
		$fieldrow1 = Fields::FieldRowByName($fieldname, $this->ct->Table->fields);
		$answer = $this->processDateSearchTags($fieldname, $fieldrow1, $this->ct->Table->realtablename);
		$value1 = $answer['query'];
		$title1 = $answer['caption'];

		//field 2
		// Sanitize and validate date format
		$dateFormat = 'Y-m-d'; // Adjust the format according to your needs

		if ($valueRaw) {
			$valueDateTime = DateTime::createFromFormat($dateFormat, $valueRaw);

			if ($valueDateTime !== false) {
				$value = $valueDateTime->format($dateFormat);
			} else {
				// Invalid date format, handle the error or set a default value
				return null;
			}
		} else
			return null;

		$fieldrow2 = Fields::FieldRowByName($value, $this->ct->Table->fields);
		$answer = $this->processDateSearchTags($value, $fieldrow2, $this->ct->Table->realtablename);
		$value2 = $answer['query'];
		$title2 = $answer['caption'];

		//Breadcrumbs
		$this->PathValue[] = $title1 . ' ' . $comparison_operator . ' ' . $title2;

		//Query condition
		if ($value2 == 'NULL' and $comparison_operator == '=')
			$query = $value1 . ' IS NULL';
		elseif ($value2 == 'NULL' and $comparison_operator == '!=')
			$query = $value1 . ' IS NOT NULL';
		else
			$query = $value1 . ' ' . $comparison_operator . ' ' . $value2;

		return $query;
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

}//end class

class LinkJoinFilters
{
	static public function getFilterBox($tableName, $dynamicFilterFieldName, $control_name, $filterValue, $control_name_postfix = ''): string
	{
		$fieldRow = Fields::getFieldRowByName($dynamicFilterFieldName, null, $tableName);

		if ($fieldRow === null)
			return '';

		if ($fieldRow->type == 'sqljoin' or $fieldRow->type == 'records')
			return LinkJoinFilters::getFilterElement_SqlJoin($fieldRow->typeparams, $control_name, $filterValue, $control_name_postfix);

		return '';
	}

	static protected function getFilterElement_SqlJoin($typeParams, $control_name, $filterValue, $control_name_postfix = ''): string
	{
		$result = '';
		$pair = JoomlaBasicMisc::csv_explode(',', $typeParams, '"', false);

		$tablename = $pair[0];
		if (isset($pair[1]))
			$field = $pair[1];
		else
			return '<p style="color:white;background-color:red;">sqljoin: field not set</p>';

		$tableRow = ESTables::getTableRowByNameAssoc($tablename);
		if (!is_array($tableRow))
			return '<p style="color:white;background-color:red;">sqljoin: table "' . $tablename . '" not found</p>';

		$fieldrow = Fields::getFieldRowByName($field, $tableRow['id']);
		if (!is_object($fieldrow))
			return '<p style="color:white;background-color:red;">sqljoin: field "' . $field . '" not found</p>';

		$selects = [];
		$selects[] = $tableRow['realtablename'] . '.' . $tableRow['realidfieldname'];

		$where = '';
		if ($tableRow['published_field_found']) {
			$selects[] = $tableRow['realtablename'] . '.published AS listing_published';
			$where = 'WHERE ' . $tableRow['realtablename'] . '.published=1';
		} else {
			$selects[] = '1 AS listing_published';
		}

		$selects[] = $tableRow['realtablename'] . '.' . $fieldrow->realfieldname;

		$query = 'SELECT ' . implode(',', $selects) . ' FROM ' . $tableRow['realtablename'] . ' ' . $where . ' ORDER BY ' . $fieldrow->realfieldname;
		$rows = database::loadAssocList($query);

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
