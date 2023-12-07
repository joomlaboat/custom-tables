<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\DataTypes;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\database;

class Tree
{
	public static function CleanLink($newParams, $deleteWhat)
	{
		$i = 0;
		do {
			$npv = substr($newParams[$i], 0, strlen($deleteWhat));
			if (str_contains($npv, $deleteWhat)) {
				unset($newParams[$i]);
				$newParams = array_values($newParams);
				if (count($newParams) == 0) return $newParams;
				$i = 0;

			} else
				$i++;

		} while ($i < count($newParams));
		return $newParams;
	}
}
