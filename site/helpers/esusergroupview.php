<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\database;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class JHTMLESUserGroupView
{
	public static function render($value, $field = '')
	{
		$query = 'SELECT #__usergroups.title AS name FROM #__usergroups WHERE id=' . (int)$value . ' LIMIT 1';
		$options = database::loadObjectList($query);

		if (count($options) == 0)
			return '';

		return $options[0]->name;
	}
}
