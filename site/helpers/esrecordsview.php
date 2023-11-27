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
use CustomTables\Layouts;
use CustomTables\TwigProcessor;

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'catalog.php');

class JHTMLESRecordsView
{
	public static function render($value, $tableName, $field, $selector, $filter, $sortByField = "", string $separator = ","): ?string
	{
		if ($value == '' or $value == ',' or $value == ',,')
			return '';

		$htmlresult = '';
		$value_where_filter = 'INSTR(",' . $value . ',",id)';

		$paramsArray = array();
		$paramsArray['limit'] = 0;
		$paramsArray['establename'] = $tableName;
		$paramsArray['filter'] = $filter;
		$paramsArray['showpublished'] = 2;//0 - published only; 1 - hidden only;
		$paramsArray['showpagination'] = 0;
		$paramsArray['groupby'] = '';
		$paramsArray['shownavigation'] = 0;
		$paramsArray['sortby'] = $sortByField;

		$_params = new JRegistry($paramsArray);

		$ct = new CT;
		$ct->setParams($_params, true);

		// -------------------- Table

		$ct->getTable($ct->Params->tableName);

		if ($ct->Table->tablename === null) {
			$ct->errors[] = 'Catalog View: Table not selected.';
			return null;
		}

		// --------------------- Filter
		$ct->setFilter($ct->Params->filter, $ct->Params->showPublished);
		$ct->Filter->where[] = $value_where_filter;

		// --------------------- Sorting
		$ct->Ordering->parseOrderByParam();

		// --------------------- Limit
		$ct->applyLimits();
		$ct->getRecords();

		if (is_null($ct->Records)) {
			return 'Records not loaded';
		}

		if (!str_contains($field, ':')) {
			//without layout
			$valueArray = explode(',', $value);

			$vArray = array();

			foreach ($ct->Records as $row) {
				if (in_array($row[$ct->Table->realidfieldname], $valueArray))
					$vArray[] = JoomlaBasicMisc::processValue($field, $ct, $row);
			}
			$htmlresult .= implode($separator, $vArray);
		} else {
			$pair = JoomlaBasicMisc::csv_explode(':', $field);

			if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout')
				return '<p>unknown field/layout command "' . $field . '" should be like: "layout:' . $pair[1] . '".</p>';

			$isTableLess = false;
			if ($pair[0] == 'tablelesslayout')
				$isTableLess = true;

			if (isset($pair[1]))
				$layoutname = $pair[1];
			else
				return '<p>unknown field/layout command "' . $field . '" should be like: "layout:' . $pair[1] . '".</p>';

			if (isset($pair[2]))
				$columns = (int)$pair[2];
			else
				$columns = 0;

			$Layouts = new Layouts($ct);
			$layoutcode = $Layouts->getLayout($layoutname);

			if ($layoutcode == '')
				return '<p>layout "' . $layoutname . '" not found or is empty.</p>';

			$valueArray = explode(',', $value);

			if ($isTableLess)
				$htmlresult .= self::renderResultsAsTableLess($ct, $valueArray, $layoutcode, $separator);
			else
				$htmlresult .= self::renderResultsAsTable($ct, $valueArray, $columns, $layoutcode);
		}
		return $htmlresult;
	}

	protected static function renderResultsAsTableLess(CT &$ct, array $valueArray, string $layoutcode, string $separator = ','): string
	{
		$vArray = array();
		$number = 1;

		$CleanSearchResult = [];
		foreach ($ct->Records as $row) {
			if (in_array($row[$ct->Table->realidfieldname], $valueArray))
				$CleanSearchResult[] = $row;
		}

		foreach ($CleanSearchResult as $row) {

			//process layout
			$row['_number'] = $number;
			$row['_islast'] = $number == count($CleanSearchResult);

			if ($ct->Env->legacySupport) {
				$LayoutProc = new LayoutProcessor($ct);
				$LayoutProc->layout = $layoutcode;
				$vlu = $LayoutProc->fillLayout($row);
			} else
				$vlu = $layoutcode;

			$twig = new TwigProcessor($ct, $vlu);
			$vlu = $twig->process($row);
			if ($twig->errorMessage !== null)
				$ct->errors[] = $twig->errorMessage;

			$vArray[] = $vlu;
			$number++;
		}
		return implode($separator, $vArray);
	}

	protected static function renderResultsAsTable(CT &$ct, array $valueArray, int $columns, string $layoutcode): string
	{
		$htmlresult = '<table style="border:none;">';

		$number = 1;
		$tr = 0;

		$CleanSearchResult = [];
		foreach ($ct->Records as $row) {
			if (in_array($row[$ct->Table->realidfieldname], $valueArray))
				$CleanSearchResult[] = $row;
		}

		foreach ($CleanSearchResult as $row) {
			if ($tr == $columns)
				$tr = 0;

			$htmlresult .= '<tr>';

			//process layout
			$row['_number'] = $number;
			$row['_islast'] = $number == count($CleanSearchResult);

			if ($ct->Env->legacySupport) {
				$LayoutProc = new LayoutProcessor($ct);
				$LayoutProc->layout = $layoutcode;
				$vlu = $LayoutProc->fillLayout($row);
			} else
				$vlu = $layoutcode;

			$twig = new TwigProcessor($ct, $vlu);
			$vlu = $twig->process($row);
			if ($twig->errorMessage !== null) {
				$ct->errors[] = $twig->errorMessage;
				return '';
			}

			$htmlresult .= '<td style="border:none;">' . $vlu . '</td>';

			$tr++;
			if ($tr == $columns)
				$htmlresult .= '</tr>';

			$number++;
		}

		$htmlresult .= '</tr>';

		return $htmlresult . '</table>';
	}
}
