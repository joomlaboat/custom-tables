<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
use CustomTables\database;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'catalog.php');

class JHTMLESUserView
{
    public static function render($value, $field = '')
    {
        $db = Factory::getDBO();

        if ($field == 'online') {
            $query = 'SELECT userid FROM #__session WHERE userid=' . (int)$value . ' LIMIT 1';
            $options = database::loadAssocList($query);
            if (count($options) == 0)
                return 0;
            else
                return 1;
        } elseif ($field == 'usergroups') {
            $selects = '(SELECT title FROM #__usergroups AS g WHERE g.id = m.group_id LIMIT 1) AS group_title';
            $query = 'SELECT ' . $selects . ' FROM #__user_usergroup_map AS m WHERE user_id=' . (int)$value;
            $groups = database::loadObjectList($query);
            $group_list = [];

            foreach ($groups as $group)
                $group_list[] = $group->group_title;

            return implode(',', $group_list);
        } else {
            $allowedFields = array('id', 'name', 'email', 'username', 'registerdate', 'lastvisitdate');

            $field = strtolower($field);
            if ($field == '')
                $field = 'name';
            elseif (!in_array($field, $allowedFields))
                return 'wrong field "' . $field . '" !';

            $query = 'SELECT id, name, username, email, registerDate,lastvisitDate FROM #__users WHERE id=' . (int)$value . ' LIMIT 1';
            $db = Factory::getDBO();
            if (count($rows) != 0) {
                $row = $rows[0];
                if (($field == 'registerDate' or $field == 'lastvisitDate') and $row[$field] == '0000-00-00 00:00:00')
                    return 'Never';

                if ($field == 'registerdate')
                    return $row['registerDate'];
                elseif ($field == 'lastvisitdate')
                    return $rec['lastvisitDate'];
                else
                    return $rec[$field];
            } else {
                if ((int)$value != 0)
                    return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FIELDS_USER_NOT_FOUND');
            }
        }
        return '';
    }
}
