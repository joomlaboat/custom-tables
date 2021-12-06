<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;
 
// no direct access
defined('_JEXEC') or die('Restricted access');

use \LayoutProcessor;
use \JoomlaBasicMisc;
use \Joomla\CMS\Factory;
use \CustomTables\Twig_Field_Tags;
use \CustomTables\Forms;


class TwigProcessor
{
	var $ct;
	var $loaded = false;
	var $twig;
	var $variables = [];

	public function __construct(&$ct, $htmlresult)
	{
		$file = JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'
			. DIRECTORY_SEPARATOR. 'twig' . DIRECTORY_SEPARATOR . 'vendor'. DIRECTORY_SEPARATOR .'autoload.php';
			
		$this->loaded = false;
		if(!file_exists($file))
		{
			Factory::getApplication()->enqueueMessage(
				JoomlaBasicMisc::JTextExtended('Twig library not found.' ), 'Error');
					
			return false;
		}
		
		$this->ct = $ct;
		
		require_once ($file);
		$this->loaded = true;

		$loader = new \Twig\Loader\ArrayLoader([
			'index' => $htmlresult,
		]);
		
		$this->twig = new \Twig\Environment($loader);
			
		$this->twig->addGlobal('fields', new Twig_Field_Tags($ct) );
		$this->twig->addGlobal('user', new Twig_User_Tags($ct) );
		$this->twig->addGlobal('url', new Twig_Url_Tags($ct) );
		$this->twig->addGlobal('html', new Twig_Html_Tags($ct) );
		$this->twig->addGlobal('document', new Twig_Document_Tags($ct) );
		
		$this->variables = [];
		
		if(isset($ct->Table))
		{
			$this->variables['table'] = [
			'id'=>$ct->Table->tableid,
			'name' => $ct->Table->tablename,
			'title' => $ct->Table->tabletitle,
			'description'=>$ct->Table->tablerow['description'.$ct->Table->Languages->Postfix]
			];
		}

		if(isset($ct->Table->fields))
		{
			$index=0;
			foreach($ct->Table->fields as $field)
			{
	
				$function = new \Twig\TwigFunction($field['fieldname'], function () use (&$ct, $index) {
					
					// Process value
					$rfn = $ct->Table->fields[$index]['realfieldname'];
					return $ct->Table->record[$rfn];
					
					
				});
				
				$this->twig->addFunction($function);
			
				$this->variables[$field['fieldname']] = new fieldObject($ct,$field);
				
				$index++;
			}
		}
	}
	
	public function process($row = array())
	{
		if(!$this->loaded)
			return null;
		
		$this->ct->Table->record = $row;
		
		return @$this->twig->render('index', $this->variables);
	}
}

class fieldObject
{
	var $ct;
	var $field;

	function __construct(&$ct, &$field)
	{
		$this->ct = $ct;
		$this->field = $field;
	}
	
	public function __toString()
    {
		$rfn = $this->field['realfieldname'];
        return strval($this->ct->Table->record[$rfn]);
    }
	
	public function __call($name, $arguments)
    {
		//for jsl join fields
        return '*'.$name.'*['.$arguments[0].']';
    }
	
	public function _name()
    {
        return $this->field['fieldname'];
    }
	
	public function _title()
    {
		if(!array_key_exists('fieldtitle'.$this->ct->Languages->Postfix,$this->field))
		{
			Factory::getApplication()->enqueueMessage(
					JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
            return '*fieldtitle'.$this->ct->Languages->Postfix.' - not found*';
		}
        else
			return $this->field['fieldtitle'.$this->ct->Languages->Postfix];
    }
	
	public function _label()
    {
        return Forms::renderFieldLabel($ct, $this->field);
    }
	
	public function _description()
    {
		if(!array_key_exists('description'.$this->ct->Languages->Postfix,$this->field))
		{
			Factory::getApplication()->enqueueMessage(
					JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
            return '*description'.$this->ct->Languages->Postfix.' - not found*';
		}
        else
			return $this->field['description'.$this->ct->Languages->Postfix];
    }
	
	public function _type()
    {
        return $this->field['type'];
    }
	
	public function _params()
    {
        return $this->field['typeparams'];
    }
}
