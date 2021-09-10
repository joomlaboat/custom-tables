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

class JHTMLESUserView
{
        public static function render($value,$field='')
        {
				$db = JFactory::getDBO();
				
				if($field=='online')
				{
						$query = 'SELECT userid FROM #__session WHERE userid='.(int)$value.' LIMIT 1';
						$db->setQuery($query);
				
						$options=$db->loadAssocList();
						if(count($options)==0)
								return 0;
						else
								return 1;
				}
				else
				{
						$allowedFields=array('id','name','email','username','registerDate','lastvisitDate');
						
						if($field=='')
							$field='name';
						elseif(!in_array($field, $allowedFields))
							return 'wrong field "'.$field.'" !';

						
						$query = 'SELECT id, name, username, email, registerDate,lastvisitDate FROM #__users WHERE id='.(int)$value.' LIMIT 1';
						
						$db->setQuery($query);
				
						$options=$db->loadAssocList();
						if(count($options)!=0)
						{
							$rec=$options[0];
							if(($field=='registerDate' or $field=='lastvisitDate') and $rec[$field]=='0000-00-00 00:00:00')
								return 'Never';
							else
								return $rec[$field];
						}
				}
				return '';
        }
}
