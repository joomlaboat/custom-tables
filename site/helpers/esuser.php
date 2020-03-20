<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @version 1.6.1
 * @author Ivan Komlev< <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

class JHTMLESUser
{


        static public function render($control_name, $value,$style,$cssclass, $usergroup='', $attribute='',$mysqlwhere='',$mysqljoin='')
        {
				$db = JFactory::getDBO();

				$query = $db->getQuery(true);
				$query->select('#__users.id AS id, #__users.name AS name');
	 			$query->from('#__users ');

				if($usergroup!='')
				{
						$query->join('INNER', '#__user_usergroup_map ON user_id=id ');
						$query->join('INNER', '#__usergroups ON #__usergroups.id = #__user_usergroup_map.group_id ');

						$ug=explode(",",$usergroup);
						$w=array();
						foreach($ug as $u)
							$w[]='#__usergroups.title="'.$u.'"';


						if(count($w)>0)
							$query->where(' '.implode(' OR ',$w).' ');
				}

				if($mysqljoin!='')
						$query->join('INNER', $mysqljoin);

				if($mysqlwhere!='')
						$query->where($mysqlwhere);

				$query->group('#__users.id');
				$query->order('#__users.name');

				$db->setQuery($query);
				if (!$db->query())    die( $db->stderr());

				$options=$db->loadObjectList();

				$options=array_merge(array(array('id'=>'','name'=>'- '.JText ::_( 'COM_CUSTOMTABLES_SELECT' ))),$options);

				return JHTML::_('select.genericlist', $options, $control_name, $cssclass.' style="'.$style.'" '.$attribute.' ', 'id', 'name', $value,$control_name);

        }


}

?>
