<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
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

use CustomTables\database;
use Joomla\CMS\Language\Text;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldAnyTables extends JFormFieldList
{

    protected $type = 'anytables';

    //Returns the Options object with the list of tables (specified by table id in url)

    protected function getOptions()
    {
        $options = array();
        $options[] = JHtml::_('select.option', '', Text::_('COM_CUSTOMTABLES_SELECT'));

        $tables = $this->getListOfExistingTables();

        foreach ($tables as $table)
            $options[] = JHtml::_('select.option', $table, $table);

        $options[] = JHtml::_('select.option', '-new-', '- Create New Table');

        return $options;
    }

    protected function getListOfExistingTables()
    {
        $prefix = database::getDBPrefix();
        $serverType = database::getServerType();
        if ($serverType == 'postgresql') {
            $wheres = array();
            $wheres[] = 'table_type = \'BASE TABLE\'';
            $wheres[] = 'table_schema NOT IN (\'pg_catalog\', \'information_schema\')';
            $wheres[] = 'POSITION(\'' . $prefix . 'customtables_\' IN table_name)!=1';
            $wheres[] = 'table_name!=\'' . $prefix . 'user_keys\'';
            $wheres[] = 'table_name!=\'' . $prefix . 'user_usergroup_map\'';
            $wheres[] = 'table_name!=\'' . $prefix . 'usergroups\'';
            $wheres[] = 'table_name!=\'' . $prefix . 'users\'';

            $query = 'SELECT table_name FROM information_schema.tables WHERE ' . implode(' AND ', $wheres) . ' ORDER BY table_name';
        } else {
            $database = database::getDataBaseName();

            $wheres = array();
            $wheres[] = 'table_schema=\'' . $database . '\'';
            $wheres[] = '!INSTR(TABLE_NAME,\'' . $prefix . 'customtables_\')';
            $wheres[] = 'TABLE_NAME!=\'' . $prefix . 'user_keys\'';
            $wheres[] = 'TABLE_NAME!=\'' . $prefix . 'user_usergroup_map\'';
            $wheres[] = 'TABLE_NAME!=\'' . $prefix . 'usergroups\'';
            $wheres[] = 'TABLE_NAME!=\'' . $prefix . 'users\'';

            $query = 'SELECT TABLE_NAME AS table_name FROM information_schema.tables WHERE ' . implode(' AND ', $wheres) . ' ORDER BY TABLE_NAME';
        }

        $list = array();
        $rows = database::loadAssocList($query);

        foreach ($rows as $row)
            $list[] = $row['table_name'];

        return $list;
    }
}
