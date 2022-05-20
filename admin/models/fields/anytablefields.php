<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use Joomla\CMS\Factory;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldAnyTableFields extends JFormFieldList
{
	protected $type = 'anytablefields';
	
	//Returns the Options object with the list of any table (specified by table id in url)
	
	protected function getOptions()
	{
		$options = array();
		$options[] = JHtml::_('select.option', '', JText::_('COM_CUSTOMTABLES_FIELDS_SELECT_LABEL'));
		
		$app = Factory::getApplication();
		$tableid=$app->input->getInt('tableid',0);
		if($tableid!=0)
		{
			$table_row = ESTables::getTableRowByID($tableid);
			if($table_row->customtablename!='')
			{
				$fields = Fields::getExistingFields($table_row->customtablename,false);

				foreach($fields as $field)
					$options[] = JHtml::_('select.option', $field['column_name'], $field['column_name'].' ('.$field['data_type'].')');
            }
        }
        return $options;
	}
}
