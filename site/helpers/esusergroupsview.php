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

class JHTMLESUserGroupsView
{
    public static function render($valuearray_str, $field = '')
    {
        $query = 'SELECT #__usergroups.title AS name FROM #__usergroups';
        $where = [];
        $valueArray = explode(',', $valuearray_str);

        foreach ($valueArray as $value) {
            if ($value != '') {
                $where[] = 'id=' . (int)$value;
            }
        }

        $query .= ' WHERE ' . implode(' OR ', $where) . ' ORDER BY title';
        $options = database::loadObjectList($query);

        if (count($options) == 0)
            return '';

        $groups = array();
        foreach ($options as $opt)
            $groups[] = $opt->name;

        return implode(',', $groups);
    }
}
