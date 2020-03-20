<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');


class CustomTablesModelHome extends JModel {
        
	function __construct()
	{
		parent::__construct();
        }
	
	function getOption($parampair)
	{
		$paramarr=explode("-",$parampair);
		
		$id=$this->getOptionIdFull($paramarr[0]);
		
		if($id==0)return null;
		// get database handle
		$db = JFactory::getDBO();
		
		$query = 'SELECT * FROM #__customtables_options WHERE id='.$id.' LIMIT 1';
		$db->setQuery($query);
		if (!$db->query())    die( $db->stderr());
		
		$rows=$db->loadObjectList();
		//echo count($rows);
		if(count($rows)==1)
			return $rows[0];
			
		//echo 'ggg';
		return null;	
	}
	
	

	function getOptionIdFull($optionname)
	{
	$names=explode(".",$optionname);
	$parentid=0;
	
	foreach($names as $name)
	{
	    $parentid=$this->getOptionId($name,$parentid);
	}
    
	return $parentid;
	}
	
	
	function getOptionId($optionname,$parentid)
	{
	// get database handle
	$db = JFactory::getDBO();
		
	$query = 'SELECT id FROM #__customtables_options WHERE parentid='.$parentid.' AND optionname="'.$optionname.'" LIMIT 1';
		
	$db->setQuery($query);
        if (!$db->query())    die( $db->stderr());
                
	$rows=$db->loadObjectList();
	
	if(count($rows)!=1)return 0;
	
	return $rows[0]->id;
    }

}
