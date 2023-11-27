<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\CT;
use CustomTables\DataTypes\Tree;

require_once('render_html.php');
require_once('render_xlsx.php');
require_once('render_csv.php');
require_once('render_json.php');
require_once('render_image.php');

use CustomTables\Fields;
use CustomTables\TwigProcessor;

class tagProcessor_Catalog
{
	use render_html;
	use render_xlsx;
	use render_csv;
	use render_json;
	use render_image;

	public static function process(CT &$ct, $layoutType, &$pageLayout, $itemLayout, $newReplaceItCode): string
	{
		$vlu = '';
		$options = array();
		$fList = JoomlaBasicMisc::getListToReplace('catalog', $options, $pageLayout, '{}');

		$i = 0;
		foreach ($fList as $fItem) {
			$pair = JoomlaBasicMisc::csv_explode(',', $options[$i]);

			$tableClass = $pair[0];
			$notable = $pair[1] ?? '';
			$separator = $pair[2] ?? '';

			if ($ct->Env->frmt == 'csv') {
				$vlu .= self::get_CatalogTable_singleline_CSV($ct, $layoutType, $itemLayout);
			} elseif ($ct->Env->frmt == 'json') {
				$vlu = self::get_CatalogTable_singleline_JSON($ct, $layoutType, $itemLayout);
			} elseif ($ct->Env->frmt == 'image')
				self::get_CatalogTable_singleline_IMAGE($ct, $layoutType, $pageLayout);
			elseif ($notable == 'notable')
				$vlu .= self::get_Catalog($ct, $layoutType, $itemLayout, $tableClass, false, $separator);
			else
				$vlu .= self::get_Catalog($ct, $layoutType, $itemLayout, $tableClass, true, $separator);

			$pageLayout = str_replace($fItem, $newReplaceItCode, $pageLayout);
			$i++;
		}
		return $vlu;
	}

	protected static function get_Catalog(CT &$ct, $layoutType, $itemLayout, $tableClass, $showTable = true, $separator = ''): string
	{
		$catalogResult = '';

		if (is_null($ct->Records))
			return '';

		$CatGroups = array();
		$twig = new TwigProcessor($ct, $itemLayout);

		//Grouping
		if ($ct->Params->groupBy != '')
			$groupBy = Fields::getRealFieldName($ct->Params->groupBy, $ct->Table->fields);
		else
			$groupBy = '';

		if ($groupBy == '') {
			$number = 1 + $ct->LimitStart;
			$RealRows = [];

			foreach ($ct->Records as $row) {
				$row['_number'] = $number;
				$row['_islast'] = $number == count($ct->Records);
				$RealRows[] = tagProcessor_Item::RenderResultLine($ct, $layoutType, $twig, $row); //3ed parameter is to show record HTML anchor or not
				$number++;
			}
			$CatGroups[] = array('', $RealRows);
		} else {
			//Group Results
			$FieldRow = Fields::FieldRowByName($ct->Params->groupBy, $ct->Table->fields);

			$RealRows = array();
			$lastGroup = '';

			$number = 1 + $ct->LimitStart;
			foreach ($ct->Records as $row) {

				if ($lastGroup != $row[$ct->Params->groupBy] and $lastGroup != '') {
					if ($FieldRow['type'] == 'customtables')
						$GroupTitle = implode(',', Tree::getMultyValueTitles($lastGroup, $ct->Languages->Postfix, 1, ' - '));
					else {
						$row['_number'] = $number;
						$row['_islast'] = $number == count($ct->Records);
						$option = array();
						$GroupTitle = tagProcessor_Value::getValueByType($ct, $FieldRow, $row, $option);
					}

					$CatGroups[] = array($GroupTitle, $RealRows);
					$RealRows = array();
				}
				$RealRows[] = tagProcessor_Item::RenderResultLine($ct, $layoutType, $twig, $row); //3ed parameter is to show record HTML anchor or not
				$lastGroup = $row[$ct->Params->groupBy];
				$number++;
			}
			if (count($RealRows) > 0) {
				if ($FieldRow['type'] == 'customtables')
					$GroupTitle = implode(',', Tree::getMultyValueTitles($lastGroup, $ct->Languages->Postfix, 1, ' - '));
				else {
					$galleryRows = array();
					$FileBoxRows = array();
					$option = array();

					$row = $RealRows[0];

					$GroupTitle = tagProcessor_Value::getValueByType($ct, $FieldRow, $row, $option, $galleryRows, $FileBoxRows);
				}
				$CatGroups[] = array($GroupTitle, $RealRows);
			}
		}

		$CatGroups = self::reorderCatGroups($CatGroups);

		if ($showTable) {
			$catalogResult .= '
    <table' . (($tableClass != '' ? ' class="' . $tableClass . '"' : '')) . ' cellpadding="0" cellspacing="0">
        <tbody>
';
		}

		$number_of_columns = 3;

		foreach ($CatGroups as $cGroup) {
			$tr = 0;
			$RealRows = $cGroup[1];

			if ($showTable) {
				if ($cGroup[0] != '')
					$catalogResult .= '<tr><td colspan="' . ($number_of_columns) . '"><h2>' . $cGroup[0] . '</h2></td></tr>';
			} else {
				if ($cGroup[0] != '')
					$catalogResult .= '<h2>' . $cGroup[0] . '</h2>';
			}

			$i = 0;

			foreach ($RealRows as $row) {
				if ($separator != '' and $i > 0)
					$catalogResult .= $separator;

				if ($tr == 0 and $showTable)
					$catalogResult .= '<tr>';

				if ($showTable) {
					if (isset($row[$ct->Table->realidfieldname]))
						$catalogResult .= '<td><a name="a' . $row[$ct->Table->realidfieldname] . '"></a>' . $row . '</td>';
					else
						$catalogResult .= '<td>' . $row . '</td>';
				} else
					$catalogResult .= $row;

				$tr++;
				if ($tr == $number_of_columns) {
					if ($showTable)
						$catalogResult .= '</tr>';

					$tr = 0;
				}

				$i += 1;
			}

			if ($tr > 0 and $showTable)
				$catalogResult .= '<td colspan="' . ($number_of_columns - $tr) . '>&nbsp;</td></tr>';

			if ($showTable)
				$catalogResult .= '<tr><td colspan="' . ($number_of_columns) . '"><hr/></td></tr>';
		}

		if ($showTable) {
			$catalogResult .= '</tbody>
    </table>';
		}

		return $catalogResult;
	}

	protected static function reorderCatGroups($CatGroups): array
	{
		$newCat = array();
		$names = array();
		foreach ($CatGroups as $c)
			$names[] = $c[0];

		sort($names);

		foreach ($names as $n) {
			foreach ($CatGroups as $c) {
				if ($n == $c[0]) {
					$newCat[] = $c;
					break;
				}
			}
		}
		return $newCat;
	}
}
