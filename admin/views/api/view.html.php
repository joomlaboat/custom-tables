<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com 
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		view.html.php
	@author			Ivan Komlev <https://joomlaboat.com>	
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Customtables View class for the Listoftables
 */
class CustomtablesViewAPI extends JViewLegacy
{
	/**
	 * Listoftables view display method
	 * @return void
	 */
	function display($tpl = null)
	{
		if (ob_get_contents()) ob_end_clean();
		$jinput = JFactory::getApplication()->input;
		
		$task=$jinput->getCmd('task', '');
		$frmt=$jinput->getCmd('frmt', '');
		
		$result=array();
		switch($task)
		{
			case 'getfields':
				
				$tableid=$jinput->getInt('tableid', 0);
				if($tableid==0)
				{
					$result=array('error'=>'tableid not set');
				}
				else
				{
					require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fields.php');
					
					$result=ESFields::getFields($tableid,true);
				}
				
				
				break;
			
			default:
				$result=array('error'=>'unknown task');
				break;
		}
		
		
		if($frmt=='json')
		{
			header('Content-Type: application/json');
			echo json_encode($result);
		}
		elseif($frmt=='xml')
		{
			header('Content-Type: text/xml');
			$xml_data = new SimpleXMLElement('<?xml version="1.0"?><data></data>');

			// function call to convert array to xml
			$this->array_to_xml($result,$xml_data);

			//saving generated xml file; 
			echo $xml_data->asXML();
		}
		else
			echo 'error:unknown format';
		
		die;
	}
	
	
	function array_to_xml( $data, &$xml_data )
	{
		foreach( $data as $key => $value )
		{
			if( is_numeric($key) )
			{
			    $key = 'item'.$key; //dealing with <0/>..<n/> issues
			}
        
			if( is_array($value) )
			{
				$subnode = $xml_data->addChild($key);
				array_to_xml($value, $subnode);
			}
			else
			{
				$xml_data->addChild("$key",htmlspecialchars("$value"));
			}
		}
	}
	
}
