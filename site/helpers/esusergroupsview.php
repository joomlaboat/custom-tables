<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev< <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

//require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');
//require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

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
				//if (!$db->query())    die( $db->stderr());
				
				$options=$db->loadObjectList();
				
				if(count($options)==0)
					return '';
				
				$groups=array();
				foreach($options as $opt)
					$groups[]=$opt->name;
				
				return implode(',',$groups);
				

        }
}

?>
