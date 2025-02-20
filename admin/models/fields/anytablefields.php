<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\TableHelper;
use Joomla\CMS\HTML\HTMLHelper;

JFormHelper::loadFieldClass('list');

class JFormFieldAnyTableFields extends JFormFieldList
{
	protected $type = 'anytablefields';

	//Returns the Options object with the list of any table (specified by table id in url)

	protected function getOptions()
	{
		$options = array();
		$options[] = HTMLHelper::_('select.option', '', common::translate('COM_CUSTOMTABLES_FIELDS_SELECT_LABEL'));
		$tableid = common::inputGetInt('tableid', 0);
		if ($tableid != 0) {
			$table_row = TableHelper::getTableRowByID($tableid);
			if (!empty($table_row->customtablename)) {
				$fields = database::getExistingFields($table_row->customtablename, false);

				foreach ($fields as $field)
					$options[] = HTMLHelper::_('select.option', $field['column_name'], $field['column_name'] . ' (' . $field['data_type'] . ')');
			}
		}
		return $options;
	}
}
