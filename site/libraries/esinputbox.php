<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\Inputbox;

class ESInputBox
{
	var string $requiredLabel = '';
	var CustomTables\CT $ct;

	function __construct(CustomTables\CT &$ct)
	{
		$this->ct = &$ct;
		$this->requiredLabel = 'COM_CUSTOMTABLES_REQUIREDLABEL';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function renderFieldBox(array $fieldRow, ?array $row, array $option_list, string $onchange = ''): ?string
	{
		$Inputbox = new Inputbox($this->ct, $fieldRow, $option_list, false, $onchange);
		$value = $Inputbox->getDefaultValueIfNeeded($row);
		return $Inputbox->render($value, $row);
	}
}
