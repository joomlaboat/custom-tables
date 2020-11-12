<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

class JHTMLESArticle
{


	static public function render($control_name, $value,$cssclass, $TypeParams, $attribute='')
        {
		$p=explode(',',$TypeParams);
		if(isset($p[0]))
			$catid=(int)$p[0];
		else
			return '<p style="color:white;background-color:red;"> CustomTables: Article Category ID not set. </p>';


		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('id, title');
		$query->where('catid='.$catid);
	 	$query->from('#__content');
		$query->order('title');
		$db->setQuery($query);
		//if (!$db->query())    die( $db->stderr());
		$options=$db->loadObjectList();
		$options=array_merge(array(array('id'=>'','title'=>'- '.JText ::_( 'COM_CUSTOMTABLES_SELECT' ))),$options);

		return JHTML::_('select.genericlist', $options, $control_name, 'class="'.$cssclass.'" '.$attribute.' ', 'id', 'title', $value,$control_name);

        }

}

?>
