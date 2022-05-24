<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
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

        return $options;
    }

    protected function getListOfExistingTables()
    {
        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql') {
            $wheres = array();
            $wheres[] = 'table_type = \'BASE TABLE\'';
            $wheres[] = 'table_schema NOT IN (\'pg_catalog\', \'information_schema\')';
            $wheres[] = 'POSITION(\'' . $db->getPrefix() . 'customtables_\' IN table_name)!=1';
            $wheres[] = 'table_name!=\'' . $db->getPrefix() . 'user_keys\'';
            $wheres[] = 'table_name!=\'' . $db->getPrefix() . 'user_usergroup_map\'';
            $wheres[] = 'table_name!=\'' . $db->getPrefix() . 'usergroups\'';
            $wheres[] = 'table_name!=\'' . $db->getPrefix() . 'users\'';

            $query = 'SELECT table_name FROM information_schema.tables WHERE ' . implode(' AND ', $wheres) . ' ORDER BY table_name';
        } else {
            $conf = Factory::getConfig();
            $database = $conf->get('db');


            $wheres = array();
            $wheres[] = 'table_schema=\'' . $database . '\'';
            $wheres[] = '!INSTR(TABLE_NAME,\'' . $db->getPrefix() . 'customtables_\')';
            $wheres[] = 'TABLE_NAME!=\'' . $db->getPrefix() . 'user_keys\'';
            $wheres[] = 'TABLE_NAME!=\'' . $db->getPrefix() . 'user_usergroup_map\'';
            $wheres[] = 'TABLE_NAME!=\'' . $db->getPrefix() . 'usergroups\'';
            $wheres[] = 'TABLE_NAME!=\'' . $db->getPrefix() . 'users\'';

            $query = 'SELECT TABLE_NAME AS table_name FROM information_schema.tables WHERE ' . implode(' AND ', $wheres) . ' ORDER BY TABLE_NAME';
        }

        $list = array();

        $db->setQuery($query);
        $recs = $db->loadAssocList();

        foreach ($recs as $rec)
            $list[] = $rec['table_name'];

        return $list;
    }
}
