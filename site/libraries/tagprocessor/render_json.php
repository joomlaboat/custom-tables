<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\TwigProcessor;

trait render_json
{
	static public function get_CatalogTable_singleline_JSON(CT &$ct, int $layoutType, string $layout): string //TO DO
	{
		if (ob_get_contents())
			ob_clean();

		//Prepare line layout

		$layout = str_replace("\n", '', $layout);
		$layout = str_replace("\r", '', $layout);

		$twig = new TwigProcessor($ct, $layout);

		$records = [];

		foreach ($ct->Records as $row)
			$records[] = trim(common::ctStripTags(tagProcessor_Item::RenderResultLine($ct, $layoutType, $twig, $row)));

		return implode(',', $records);
	}

	protected static function get_CatalogTable_JSON(CT &$ct, $fields): string
	{
		$header_fields = [];
		$line_fields = [];
		$fields = str_replace("\n", '', $fields);
		$fields = str_replace("\r", '', $fields);
		$fieldArray = JoomlaBasicMisc::csv_explode(',', $fields, '"', true);
		foreach ($fieldArray as $field) {
			$fieldPair = JoomlaBasicMisc::csv_explode(':', $field);
			$header_fields[] = $fieldPair[0];//$result;//header

			$vlu = $fieldPair[1] ?? "";

			$line_fields[] = $vlu;//content
		}

		$number = 1 + $ct->LimitStart; //table row number, it maybe uses in the layout as {number}
		$records = array();

		$LayoutProc = new LayoutProcessor($ct);

		foreach ($ct->Records as $row) {
			$row['_number'] = $number;
			$row['_islast'] = $number == count($ct->Records);
			$i = 0;
			$vlu2 = array();
			foreach ($header_fields as $header_field) {
				$LayoutProc->layout = $line_fields[$i];
				$vlu2[$header_field] = $LayoutProc->fillLayout($row);
				$i++;
			}

			$records[] = $vlu2;
			$number++;
		}

		return common::ctJsonEncode($records);
	}

}
