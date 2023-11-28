<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
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

use CustomTables\common;
use CustomTables\database;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Version;

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

//Returns the Options object with the list of tables (specified by table id in url)

if ($version < 4) {

	//jimport('joomla.form.helper');
	JFormHelper::loadFieldClass('list');

	class JFormFieldAnyTables extends JFormFieldList
	{

		protected $type = 'anytables';

		protected function getOptions()
		{
			$options = array();
			$options[] = HTMLHelper::_('select.option', '', common::translate('COM_CUSTOMTABLES_SELECT'));

			$tables = $this->getListOfExistingTables();

			foreach ($tables as $table)
				$options[] = HTMLHelper::_('select.option', $table, $table);

			$options[] = HTMLHelper::_('select.option', '-new-', '- Create New Table');

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
} else {

	class JFormFieldAnyTables extends FormField
	{
		protected $type = 'anytables';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = $this->getOptions();
			return $this->getRenderer($this->layout)->render($data);
		}

		protected function getOptions($add_empty_option = true)
		{
			$tables = $this->getListOfExistingTables();

			$options = array();
			if ($tables) {
				if ($add_empty_option)
					$options[] = ['value' => '', 'text' => common::translate('COM_CUSTOMTABLES_TABLES_CATEGORY_SELECT')];

				foreach ($tables as $table)
					$options[] = ['value' => $table, 'text' => $table];
			}
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
}