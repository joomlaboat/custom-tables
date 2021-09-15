<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class JHTMLESUserGroupsView
{
    public static function render($valuearray_str,$field='')
    {
		
		$db = JFactory::getDBO();
				
		$query = $db->getQuery(true);
		$query->select('#__usergroups.title AS name');
	 	$query->from('#__usergroups');
				
		$where=array();
		$valuearray=explode(',',$valuearray_str);
		
		foreach($valuearray as $value)
		{
			if($value!='')
			{
				$where[]='id='.(int)$value;
			}
		}
				
		$query->where(implode(' OR ',$where));
		$query->orderby('title');
				
		$db->setQuery($query);
				
		$options=$db->loadObjectList();
		
		if(count($options)==0)
			return '';
				
		$groups=array();
		foreach($options as $opt)
			$groups[]=$opt->name;
				
		return implode(',',$groups);
    }
}
