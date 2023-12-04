<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;


if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class TypeView_TableJoinList extends TypeView
{
	public static function resolveRecordTypeValue(Field &$field, string $layoutcode, ?string $rowValue, string $showPublishedString = '', ?string $separatorCharacter = ','): string
	{
		if ($rowValue === null)
			return '';

		if ($separatorCharacter === null)
			$separatorCharacter = ',';

		$ct = new CT;
		$ct->getTable($field->params[0]);

		if (count($field->params) < 3)
			return 'selector not specified';

		$filter = $field->params[3] ?? '';

		//$showpublished = 0 - show published
		//$showpublished = 1 - show unpublished
		//$showpublished = 2 - show any

		if (($showPublishedString === null or $showPublishedString == '') and isset($field->params[6]))
			$showPublishedString = $field->params[6];

		if ($showPublishedString == 'published')
			$showpublished = 0;
		elseif ($showPublishedString == 'unpublished')
			$showpublished = 1;
		else
			$showpublished = 2;

		//this is important because it has been selected somehow.
		$ct->setFilter($filter, $showpublished);
		$ct->Filter->where[] = 'INSTR(' . database::quote($rowValue) . ',' . $ct->Table->realidfieldname . ')';
		$ct->getRecords();

		return self::processRecordRecords($ct, $layoutcode, $rowValue, $ct->Records, $separatorCharacter);
	}

	protected static function processRecordRecords(CT &$ct, $layoutcode, ?string $rowValue, &$records, string $separatorCharacter = ',')
	{
		$valueArray = explode(',', $rowValue);

		$number = 1;

		//To make sure that records belong to the value
		$CleanSearchResult = array();
		foreach ($records as $row) {
			if (in_array($row[$ct->Table->realidfieldname], $valueArray))
				$CleanSearchResult[] = $row;
		}

		$htmlresult = '';

		foreach ($CleanSearchResult as $row) {
			$row['_number'] = $number;
			$row['_islast'] = $number == count($CleanSearchResult);

			$twig = new TwigProcessor($ct, $layoutcode);

			if ($htmlresult != '')
				$htmlresult .= $separatorCharacter;

			$htmlresult .= $twig->process($row);

			if ($twig->errorMessage !== null)
				$ct->errors[] = $twig->errorMessage;

			$number++;
		}

		return $htmlresult;
	}
}