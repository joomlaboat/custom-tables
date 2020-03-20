 <?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @version 1.8.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );


class JHTMLESFields
{
        public static function fields($tableid, $currentfieldid, $control_name, $value)
        {
				
				$db = JFactory::getDBO();

				$query = 'SELECT id, fieldname '
						. ' FROM #__customtables_fields '
						. ' WHERE published=1 AND tableid='.(int)$tableid.' AND id!='.(int)$currentfieldid
						. ' AND type="checkbox"'
						. ' ORDER BY fieldname'
						;
				$db->setQuery( $query );
				$fields = $db->loadAssocList( );
				if(!$fields) $fields= array();
		
				$fields[]=array('id'=>'0','fieldname'=>'- ROOT');
				
				return JHTML::_('select.genericlist',  $fields, $control_name, 'class="inputbox"', 'id', 'fieldname', $value);
		
				
        }
	
}
