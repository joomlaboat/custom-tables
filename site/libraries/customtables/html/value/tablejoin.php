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
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

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

	//Value_tablejoin::renderTableJoinValue
	public static function renderTableJoinValue(Field $field, string $layoutcode, $listing_id): string
	{
		$ct = new CT;
		$ct->getTable($field->params[0]);

		//TODO: add selector to the output box
		//$selector = $field->params[6] ?? 'dropdown';

		$row = $ct->Table->loadRecord($listing_id);
		$twig = new TwigProcessor($ct, $layoutcode);
		$value = $twig->process($row);

		if ($twig->errorMessage !== null)
			$ct->errors[] = $twig->errorMessage;

		return $value;
	}
}