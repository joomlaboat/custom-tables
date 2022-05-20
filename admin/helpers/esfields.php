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
 use Joomla\CMS\Factory;

 defined('_JEXEC') or die( 'Restricted access' );

class JHTMLCTFields
{
        public static function fields($tableid, $currentfieldid, $control_name, $value)
        {
				$db = Factory::getDBO();

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
