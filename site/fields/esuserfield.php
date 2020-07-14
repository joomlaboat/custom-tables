<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldESTable extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 *  
	 */
	protected $type = 'estable';
	
	protected function getOptions()//$name, $value, &$node, $control_name)
	{
	
        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id,tablename');
        $query->from('#__customtables_tables');
		$query->order('tablename');
		
        $db->setQuery((string)$query);
        $messages = $db->loadObjectList();
        $options = array();
        if ($messages)
        {
            foreach($messages as $message) 
            {
                $options[] = JHtml::_('select.option', $message->tablename, $message->tablename);
                                
            }
        }
        $options = array_merge(parent::getOptions(), $options);
        return $options;

	}
}

?>
