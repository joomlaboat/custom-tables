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

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;

class Value_tablejoinlist extends BaseValue
{
	function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
	{
		parent::__construct($ct, $field, $rowValue, $option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(): string
	{
		return self::renderTableJoinListValue($this->field, $this->rowValue, $this->option_list);
	}

	public static function renderTableJoinListValue(Field &$field, ?string $rowValue, array $option_list = []): string
	{
		if ($rowValue === null)
			return '';

		$ct = new CT;
		$ct->getTable($field->params[0]);

		if (count($option_list) == 0)
			$fieldName = $field->params[1];
		else
			$fieldName = $option_list[0];

		$fieldNameParts = explode(':', $fieldName);

		if (count($fieldNameParts) == 2) {
			//It's not a fieldname but layout. Example: tablelesslayout:PersonName
			if ($fieldNameParts[0] == 'tablelesslayout' or $fieldNameParts[0] == 'layout') {
				$Layouts = new Layouts($ct);
				$layoutCode = $Layouts->getLayout($fieldNameParts[1]);

				if ($layoutCode == '')
					throw new Exception('Table Join List value layout not found. (' . $fieldName . ')');
			} else
				throw new Exception('Table Join List value layout syntax invalid. (' . $fieldName . ')');
		} else
			$layoutCode = '{{ ' . $fieldName . ' }}';

		if (($options[2] ?? '') != '')
			$separatorCharacter = $options[1];
		else
			$separatorCharacter = ',';

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
			. DIRECTORY_SEPARATOR . 'tablejoinlist.php');

		return Value_tablejoinlist::resolveRecordTypeValue($field, $layoutCode, $rowValue, '', $separatorCharacter);
	}

	/**
	 * @throws Exception
	 * @since 3.4.5
	 */
	public static function resolveRecordTypeValue(Field   &$field, string $layoutcode, ?string $rowValue, string $showPublishedString = '',
	                                              ?string $separatorCharacter = ','): string
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
		$ct->Filter->whereClause->addCondition('"' . $rowValue . '"', $ct->Table->realidfieldname, 'INSTR', true);

		try {
			$ct->getRecords();
		} catch (Exception $e) {
			return $e->getMessage();
		}

		return self::processRecordRecords($ct, $layoutcode, $rowValue, $ct->Records, $separatorCharacter);
	}

	protected static function processRecordRecords(CT &$ct, $layoutcode, ?string $rowValue, &$records, string $separatorCharacter = ','): string
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