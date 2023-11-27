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
use CustomTables\database;
use CustomTables\Layouts;
use CustomTables\TwigProcessor;

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'catalog.php');

class JHTMLESSQLJoinView
{
	public static function render($value, $tableName, $field, $filter): ?string
	{
		if ($value == 0 or $value == '')
			return '';

		$paramsArray = array();
		$paramsArray['limit'] = 0;
		$paramsArray['establename'] = $tableName;
		$paramsArray['filter'] = $filter;
		$paramsArray['showpublished'] = 0;
		$paramsArray['showpagination'] = 0;
		$paramsArray['groupby'] = '';
		$paramsArray['shownavigation'] = 0;
		$paramsArray['sortby'] = '';

		$_params = new JRegistry($paramsArray);

		$ct = new CT($_params, true);

		// -------------------- Table
		$ct->getTable($ct->Params->tableName);

		if ($ct->Table->tablename === null) {
			$ct->errors[] = 'SQL Join field: Table no set.';
			return null;
		}

		$htmlresult = '';

		//Get Row
		$query = 'SELECT ' . implode(',', $ct->Table->selects) . ' FROM ' . $ct->Table->realtablename . ' WHERE '
			. $ct->Table->tablerow['realidfieldname'] . '=' . database::quote($value) . ' LIMIT 1';

		$records = database::loadAssocList($query);

		if (!str_contains($field, ':')) {
			//without layout
			if (count($records) == 1) {
				if ($records[0][$ct->Table->realidfieldname] == $value)
					$htmlresult .= JoomlaBasicMisc::processValue($field, $ct, $records[0]);
			}

		} else {
			$pair = explode(':', $field);

			if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout' and $pair[0] != 'value')
				return '<p>unknown field/layout command "' . $field . '" should be like: "layout:' . $pair[1] . '"..</p>';

			$isTableLess = false;
			if ($pair[0] == 'tablelesslayout' or $pair[0] == 'value')
				$isTableLess = true;

			if ($pair[0] == 'value') {
				$layoutcode = '[_value:' . $pair[1] . ']';
			} else {
				//load layout
				if (isset($pair[1]) or $pair[1] != '')
					$layout_pair[0] = $pair[1];
				else
					return '<p>unknown field/layout command "' . $field . '" should be like: "layout:' . $pair[1] . '".</p>';

				if (isset($pair[2]))
					$layout_pair[1] = $pair[2];
				else
					$layout_pair[1] = 0;

				$Layouts = new Layouts($ct);
				$layoutcode = $Layouts->getLayout($layout_pair[0]);

				if ($layoutcode == '')
					return '<p>layout "' . $layout_pair[0] . '" not found or is empty.</p>';
			}

			$valueArray = explode(',', $value);

			if (!$isTableLess)
				$htmlresult .= '<table style="border:none;">';

			$number = 1;
			if (isset($layout_pair[1]) and (int)$layout_pair[1] > 0)
				$columns = (int)$layout_pair[1];
			else
				$columns = 1;

			$tr = 0;

			$CleanSearchResult = array();
			foreach ($records as $row) {
				if (in_array($row[$ct->Table->realidfieldname], $valueArray))
					$CleanSearchResult[] = $row;
			}

			foreach ($CleanSearchResult as $row) {
				if ($tr == $columns)
					$tr = 0;

				if (!$isTableLess and $tr == 0)
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
					return null;
				}

				if ($isTableLess)
					$htmlresult .= $vlu;
				else
					$htmlresult .= '<td style="border:none;">' . $vlu . '</td>';

				$tr++;
				if (!$isTableLess and $tr == $columns)
					$htmlresult .= '</tr>';

				$number++;
			}

			if (!$isTableLess and $tr < $columns)
				$htmlresult .= '</tr>';

			if (!$isTableLess)
				$htmlresult .= '</table>';
		}

		if ($ct->Params->allowContentPlugins)
			$htmlresult = JoomlaBasicMisc::applyContentPlugins($htmlresult);

		return $htmlresult;
	}
}
