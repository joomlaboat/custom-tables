<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
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
