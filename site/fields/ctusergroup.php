<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

//require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');

class JFormFieldCTUserGroup extends JFormFieldList
{
	public $type = 'CTUserGroup';

	protected function getOptions()//$name, $value, &$node, $control_name)	
    {
		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('id,title');
	 	$query->from('#__usergroups');
		$query->order('title');

		$db->setQuery($query);

		$records=$db->loadObjectList();
		
		if($records)
        {
            foreach($records as $record) 
                $options[] = JHtml::_('select.option', $record->id, $record->title);
        }
		return $options;
    }
}
