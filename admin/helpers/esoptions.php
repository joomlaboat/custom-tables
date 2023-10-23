<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
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


class JHTMLESOptions
{

    public static function options($currentOptionId, $control_name, $value)
    {
        $query = 'SELECT id, optionname '
            . ' FROM #__customtables_options '
            . ' WHERE id!=' . (int)$currentOptionId
            . ' ORDER BY optionname';
        $optionlist = database::loadAssocList($query);
        if (!$optionlist) $optionlist = array();

        $optionlist[] = array('id' => '0', 'optionname' => '- ROOT');

        return JHTML::_('select.genericlist', $optionlist, $control_name, 'class="inputbox"', 'id', 'optionname', $value);
    }
}
