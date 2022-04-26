<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

class JHTMLCTArticle
{
	static public function render($control_name, $value,$cssclass, $params, $attribute='')
	{
		$catid=(int)$params[0];
		
		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('id, title');
		
		if($catid != 0)
			$query->where('catid='.$catid);
		
	 	$query->from('#__content');
		$query->order('title');
		$db->setQuery($query);
		$options=$db->loadObjectList();
		$options=array_merge(array(array('id'=>'','title'=>'- '.JText ::_( 'COM_CUSTOMTABLES_SELECT' ))),$options);

		return JHTML::_('select.genericlist', $options, $control_name, 'class="'.$cssclass.'" '.$attribute.' ', 'id', 'title', $value,$control_name);
    }
}
