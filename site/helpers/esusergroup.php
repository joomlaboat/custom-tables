<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\database;
use Joomla\CMS\Language\Text;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'catalog.php');

class JHTMLESUserGroup
{
    static public function render($control_name, $value, $style, $cssclass, $attribute = '', $mysqlwhere = '', $mysqljoin = '')
    {
        $query = 'SELECT #__usergroups.id AS id, #__usergroups.title AS name FROM #__usergroups';

        if ($mysqljoin != '')
            $query .= ' INNER ' . $mysqljoin;

        if ($mysqlwhere != '')
            $query .= 'WHERE ' . $mysqlwhere;

        $query .= '#__usergroups.title';
        $options = database::loadObjectList($query);
        $att = ['id' => '', 'data-type' => 'usergroup', 'name' => '- ' . Text::_('COM_CUSTOMTABLES_SELECT')];
        $options = array_merge(array($att), $options);
        return JHTML::_('select.genericlist', $options, $control_name, $cssclass . ' style="' . $style . '" ' . $attribute . ' ', 'id', 'name', $value, $control_name);
    }
}
