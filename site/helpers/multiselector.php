<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev< <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'multiselector.php');

class JHTMLMultiSelector
{
	
	//MultiSelector
	public static function render($prefix,$parentid,$parentname,$langpostfix, $establename,$esfieldname,$field_value, $attribute='',$place_holder='')
	{
		$ObjectName=$prefix.'esmulti_'.$establename.'_'.$esfieldname;
		
		
		$ms=new ESMultiSelector;
		
		$result='';
		
		
		$ItemList="";
		
		$count=0;
		$listhtml=$ms->getMultiSelector($parentid,$parentname,$langpostfix,$ObjectName,$ItemList,$count,$field_value,$place_holder);
		
		if($count>0)
			$result.=$listhtml;
		
		return $result;
	}

	
	
	
}
