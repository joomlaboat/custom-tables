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

use CustomTables\CT;
use CustomTables\Field;
use CustomTables\TwigProcessor;
use Joomla\CMS\HTML\HTMLHelper;

class CT_FieldTypeTag_sqljoin_UNUSED
{
	//New function
	/*
	public static function resolveSQLJoinTypeValue(Field &$field, string $layoutcode, $listing_id): string
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
	*/

	//Old function
	/*
	public static function resolveSQLJoinType($listing_id, $typeParams, $option_list): string
	{
		if ($listing_id == '')
			return '';

		if (count($typeParams) < 1)
			return 'table not specified';

		if (count($typeParams) < 2)
			return 'field or layout not specified';

		$esr_table = $typeParams[0];

		if (isset($option_list[0]) and $option_list[0] != '')
			$esr_field = $option_list[0];
		else
			$esr_field = $typeParams[1];

		//this is important because it has been selected somehow.
		//$esr_filter='';

		if (count($typeParams) > 2)
			$esr_filter = $typeParams[2];
		else
			$esr_filter = '';

		//Old method - slow
		$result = HTMLHelper::_('ESSQLJoinView.render', $listing_id, $esr_table, $esr_field, $esr_filter);

		//New method - fast and secure
		$join_ct = new CT;
		$join_ct->getTable($typeParams[0]);

		$row = $join_ct->Table->loadRecord($listing_id);

		$twig = new TwigProcessor($join_ct, $result);

		$value = $twig->process($row);

		if ($twig->errorMessage !== null)
			$join_ct->errors[] = $twig->errorMessage;

		return $value;
	}
	*/
}
