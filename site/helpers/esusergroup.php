<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

class JHTMLESUserGroup
{
    static public function render($control_name, $value,$style,$cssclass, $attribute='',$mysqlwhere='',$mysqljoin='')
    {
		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('#__usergroups.id AS id, #__usergroups.title AS name');
	 	$query->from('#__usergroups');

		if($mysqljoin!='')
			$query->join('INNER', $mysqljoin);

		if($mysqlwhere!='')
			$query->where($mysqlwhere);

		$query->order('#__usergroups.title');

		$db->setQuery($query);

		$options=$db->loadObjectList();
		$options=array_merge(array(array('id'=>'','name'=>'- '.JText ::_( 'COM_CUSTOMTABLES_SELECT' ))),$options);

		return JHTML::_('select.genericlist', $options, $control_name, $cssclass.' style="'.$style.'" '.$attribute.' ', 'id', 'name', $value,$control_name);
    }
}
