<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

class OrderingHTML
{
	public static function getOrderBox(Ordering &$ordering): string
	{
		$lists = $ordering->getSortByFields();
		$order_values = $lists->values;
		$order_list = $lists->titles;

		$moduleIDString = $ordering->Params->ModuleId === null ? 'null' : $ordering->Params->ModuleId;

		$result = '<select name="esordering" id="esordering" onChange="ctOrderChanged(this.value, ' . $moduleIDString . ');" class="inputbox">' . PHP_EOL;

		for ($i = 0; $i < count($order_values); $i++) {
			$result .= '<option value="' . $order_values[$i] . '" ' . ($ordering->ordering_processed_string == $order_values[$i] ? ' selected ' : '') . '>'
				. htmlspecialchars($order_list[$i] ?? '')
				. '</option>' . PHP_EOL;
		}

		$result .= '</select>' . PHP_EOL;
		return $result;
	}
}