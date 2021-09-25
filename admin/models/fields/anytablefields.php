<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldAnyTableFields extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 *  
	 */
	protected $type = 'anytablefields';
	
	//Returns the Options object with the list of any table (specified by table id in url)
	
	protected function getOptions()
	{
		$options = array();
		$options[] = JHtml::_('select.option', '', JText::_('COM_CUSTOMTABLES_FIELDS_SELECT_LABEL'));
		
		$app = JFactory::getApplication();
		$tableid=$app->input->getInt('tableid',0);
		if($tableid!=0)
		{
			$table_row = ESTables::getTableRowByID($tableid);
			if($table_row->customtablename!='')
			{
				$fields = Fields::getExistingFields($table_row->customtablename,false);
				
				$db = JFactory::getDBO();
									
				foreach($fields as $field)
					$options[] = JHtml::_('select.option', $field['column_name'], $field['column_name'].' ('.$field['data_type'].')');
            }
        }
		
        $options = array_merge(parent::getOptions(), $options);
        return $options;

	}
}
