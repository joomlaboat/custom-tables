<?php

/**
 * CustomTables Joomla! 3.x Native Component
 * @version 2.5.7
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldCTTable extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 *  
	 */
	protected $type = 'cttable';
	
	protected function getOptions()//$name, $value, &$node, $control_name)
	{

        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id,tabletitle');
        $query->from('#__customtables_tables');
		$query->order('tabletitle');
		$query->where('published=1');

		
		$db->setQuery((string)$query);
        $records = $db->loadObjectList();
		
		
        $options = array();
        if ($records)
        {
			$options[] = JHtml::_('select.option', '', JText::_('COM_CUSTOMTABLES_FIELDS_SELECT_LABEL'));
            foreach($records as $rec) 
            {
                $options[] = JHtml::_('select.option', $rec->id, $rec->tabletitle);
                                
            }
        }
        $options = array_merge(parent::getOptions(), $options);
        return $options;

	}
}
