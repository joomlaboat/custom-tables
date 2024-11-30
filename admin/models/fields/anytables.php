<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

//Returns the Options object with the list of tables (specified by table id in url)

if (!CUSTOMTABLES_JOOMLA_MIN_4) {

    JFormHelper::loadFieldClass('list');

    class JFormFieldAnyTables extends JFormFieldList
    {

        protected $type = 'anytables';

        protected function getOptions(): array
        {
            $options = array();
            $options[] = HTMLHelper::_('select.option', '', ' - ' . common::translate('COM_CUSTOMTABLES_SELECT'));

            $tables = $this->getListOfExistingTables();

            foreach ($tables as $table)
                $options[] = HTMLHelper::_('select.option', $table, $table);

            $options[] = HTMLHelper::_('select.option', '-new-', '- Create New Table');

            return $options;
        }

        protected function getListOfExistingTables(): array
        {
            $whereClause = new MySQLWhereClause();

            $prefix = database::getDBPrefix();
            $serverType = database::getServerType();

            if ($serverType == 'postgresql') {
                $whereClause->addCondition('table_type', 'BASE TABLE');
                $whereClause->addCondition('table_schema NOT IN (\'pg_catalog\', \'information_schema\')', null);
                $whereClause->addCondition('POSITION(\'' . $prefix . 'customtables_\' IN table_name)', 1, '!=');
                $whereClause->addCondition('table_name', $prefix . 'user_keys', '!=');
                $whereClause->addCondition('table_name', $prefix . 'user_usergroup_map', '!=');
                $whereClause->addCondition('table_name', $prefix . 'usergroups', '!=');
                $whereClause->addCondition('table_name', $prefix . 'users', '!=');
                $rows = database::loadAssocList('information_schema.tables', ['table_name'], $whereClause);
            } else {
                $database = database::getDataBaseName();
                $whereClause->addCondition('table_schema', $database);
                $whereClause->addCondition('INSTR(TABLE_NAME,\'' . $prefix . 'customtables_\')', 'false', '=', true);
                $whereClause->addCondition('TABLE_NAME', $prefix . 'user_keys', '!=');
                $whereClause->addCondition('TABLE_NAME', $prefix . 'user_usergroup_map', '!=');
                $whereClause->addCondition('TABLE_NAME', $prefix . 'usergroups', '!=');
                $whereClause->addCondition('TABLE_NAME', $prefix . 'users', '!=');
                $rows = database::loadAssocList('information_schema.tables', ['TABLE_NAME AS table_name'], $whereClause);
            }
            $list = array();

            foreach ($rows as $row)
                $list[] = $row['table_name'];

            return $list;
        }
    }
} else {

    class JFormFieldAnyTables extends FormField
    {
        protected $type = 'anytables';
        protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

        /**
         * @throws Exception
         * @since 3.2.2
         */
        protected function getInput(): string
        {
            $data = $this->getLayoutData();
            $data['options'] = $this->getOptions();
            return $this->getRenderer($this->layout)->render($data);
        }

        /**
         * @throws Exception
         * @since 3.2.2
         */
        protected function getOptions($add_empty_option = true): array
        {
            $tables = $this->getListOfExistingTables();

            $options = array();
            if ($tables) {
                if ($add_empty_option)
                    $options[] = ['value' => '', 'text' => ' - ' . common::translate('COM_CUSTOMTABLES_SELECT')];

                foreach ($tables as $table)
                    $options[] = ['value' => $table, 'text' => $table];
            }
            return $options;
        }

        /**
         * @throws Exception
         * @since 3.2.2
         */
        protected function getListOfExistingTables(): array
        {
            $prefix = database::getDBPrefix();
            $serverType = database::getServerType();

            $whereClause = new MySQLWhereClause();

            if ($serverType == 'postgresql') {
                $whereClause->addCondition('table_type', 'BASE TABLE');
                $whereClause->addCondition('table_schema NOT IN (\'pg_catalog\', \'information_schema\')', null);
                $whereClause->addCondition('POSITION(\'' . $prefix . 'customtables_\' IN table_name)', 1, '!=');
                $whereClause->addCondition('table_name', $prefix . 'user_keys', '!=');
                $whereClause->addCondition('table_name', $prefix . 'user_usergroup_map', '!=');
                $whereClause->addCondition('table_name', $prefix . 'usergroups', '!=');
                $whereClause->addCondition('table_name', $prefix . 'users', '!=');
                $rows = database::loadAssocList('information_schema.tables', ['table_name'], $whereClause);
            } else {
                $database = database::getDataBaseName();
                $whereClause->addCondition('table_schema', $database);
                $whereClause->addCondition('INSTR(TABLE_NAME,\'' . $prefix . 'customtables_\')', 'false', '=', true);
                $whereClause->addCondition('TABLE_NAME', $prefix . 'user_keys', '!=');
                $whereClause->addCondition('TABLE_NAME', $prefix . 'user_usergroup_map', '!=');
                $whereClause->addCondition('TABLE_NAME', $prefix . 'usergroups', '!=');
                $whereClause->addCondition('TABLE_NAME', $prefix . 'users', '!=');
                $rows = database::loadAssocList('information_schema.tables', ['TABLE_NAME AS table_name'], $whereClause);
            }

            $list = array();

            foreach ($rows as $row)
                $list[] = $row['table_name'];

            return $list;
        }
    }
}