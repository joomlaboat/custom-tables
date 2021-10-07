<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

class OrderingHTML
{
	public static function getOrderBox(&$ordering)//$SelectedCategory
	{
		$lists = $ordering->getSortByFields();
		$order_values = $lists->values;
		$order_list = $lists->titles;
		
		$result='<select name="esordering" id="esordering" onChange="ctOrderChanged(this);" class="inputbox">
';
		for($i=0;$i<count($order_values);$i++)
		{
			$result.='<option value="'.$order_values[$i].'" '.($ordering->ordering_processed_string==$order_values[$i] ? ' selected ' : '').'>'.$order_list[$i].'</option>
';
		}

		$result.='</select>
';
		return $result;
	}
}