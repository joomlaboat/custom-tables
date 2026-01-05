<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class Value_tablejoin extends BaseValue
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
		if (count($this->option_list) == 0)
			$fieldName = $this->field->params[1];
		else
			$fieldName = $this->option_list[0];

		$fieldNameParts = explode(':', $fieldName);

		if (count($fieldNameParts) == 2) {
			//It's not a fieldname but layout. Example: tablelesslayout:PersonName
			if ($fieldNameParts[0] == 'tablelesslayout' or $fieldNameParts[0] == 'layout') {
				$Layouts = new Layouts($this->ct);
				$layoutCode = $Layouts->getLayout($fieldNameParts[1]);

				if ($layoutCode == '')
					throw new Exception('TableJoin value layout not found. (' . $fieldName . ')');
			} else
				throw new Exception('TableJoin value layout syntax invalid. (' . $fieldName . ')');
		} else
			$layoutCode = '{{ ' . $fieldName . ' }}';

		return self::renderTableJoinValue($this->field, $layoutCode, $this->rowValue);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function renderTableJoinValue(Field $field, string $layoutcode, $listing_id): string
	{
		if (empty($field->params[0]))
			throw new Exception('Table not selected.');

		$ct = new CT([], true);
		$ct->getTable($field->params[0]);

		//TODO: add selector to the output box
		//$selector = $field->params[6] ?? 'dropdown';

		if ($ct->Table === null)
			throw new Exception('Table Join: Table not found.');

		if (!empty($listing_id)) {
			$ct->Params->listing_id = $listing_id;
			$ct->getRecord();
		}

		try {
			$twig = new TwigProcessor($ct, $layoutcode);
			$value = $twig->process($ct->Table->record);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		return $value;
	}
}