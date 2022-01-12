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
		$this->ct = $ct;

		$loader = new \Twig\Loader\ArrayLoader([
			'index' => $htmlresult,
		]);
		
		$this->twig = new \Twig\Environment($loader);
			
		$this->twig->addGlobal('fields', new Twig_Field_Tags($ct) );
		$this->twig->addGlobal('user', new Twig_User_Tags($ct) );
		$this->twig->addGlobal('url', new Twig_Url_Tags($ct) );
		$this->twig->addGlobal('html', new Twig_Html_Tags($ct) );
		$this->twig->addGlobal('document', new Twig_Document_Tags($ct) );
		$this->twig->addGlobal('record', new Twig_Record_Tags($ct) );
		
		$this->variables = [];
		
		if(isset($ct->Table))
		{
			$description = $ct->Table->tablerow['description'.$ct->Table->Languages->Postfix];
						
			$this->variables['table'] = [
			'id'=>$ct->Table->tableid,
			'name' => $ct->Table->tablename,
			'title' => $ct->Table->tabletitle,
			'description'=> new \Twig\Markup($description, 'UTF-8' )
			];
		}

		if(isset($ct->Table->fields))
		{
			$index=0;
			foreach($ct->Table->fields as $field)
			{
	
				$function = new \Twig\TwigFunction($field['fieldname'], function () use (&$ct, $index) 
				{
					//This function will process record values with field typeparams and with optional arguments
					//Example:
					//{{ price }}  - will return 35896.14 if field type parameter is 2,20 (2 decimals)
					//{{ price(3,",") }}  - will return 35,896.140 if field type parameter is 2,20 (2 decimals) but extra 0 added
					
					$args = func_get_args();	
					
					
					$valueProcessor = new Value($this->ct);
					$vlu = strval($valueProcessor->renderValue($ct->Table->fields[$index],$this->ct->Table->record,$args));
					return $vlu;
					//return new \Twig\Markup($vlu, 'UTF-8' ); //doesnt work because it cannot be converted to int or string
				});
				
				$this->twig->addFunction($function);
			
				$this->variables[$field['fieldname']] = new fieldObject($ct,$field);
				
				$index++;
			}
		}
	}
	
	public function process($row = null)
	{
		if($row !== null)
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
		//$args = func_get_args();
		$valueProcessor = new Value($this->ct);
		$vlu = $valueProcessor->renderValue($this->field,$this->ct->Table->record,[]);
		return strval($vlu);
		//return new \Twig\Markup($vlu, 'UTF-8' ); //doesnt work because it cannot be converted to int or string
		//return strval(new \Twig\Markup($vlu, 'UTF-8'));
    }
	
	public function __call($name, $arguments)
    {
		//if($name == 'title')
			//return $this->title();
		
		if($name == 'edit')
		{
			return 'object:'.$name.':['.$arguments[0].']';
		}
		
		//for jsl join fields
        return 'unknown';
    }
	
	public function fieldname()
    {
        return $this->field['fieldname'];
    }
	
	public function v()
    {
		return $this->value();
	}
	
	public function value()
    {
		$rfn = $this->field['realfieldname'];
		return $this->ct->Table->record[$rfn];
	}
	
	public function t()
    {
		return $this->title();
	}
	
	public function title()
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
	
	public function label()
    {
		$forms = new Forms($this->ct);
        return $forms->renderFieldLabel($this->field);
    }

	public function description()
    {
		if(!array_key_exists('description'.$this->ct->Languages->Postfix,$this->field))
			$vlu = $this->field['description'];
        else
			$vlu = $this->field['description'.$this->ct->Languages->Postfix];
		
		return new \Twig\Markup($vlu, 'UTF-8' );
    }
	
	public function type()
    {
        return $this->field['type'];
    }
	
	public function params()
    {
        return $this->field['typeparams'];
    }
	
	public function edit()
    {
		$args = func_get_args();
		
		$value = '';
		if($this->field['type']!='multilangstring' and $this->field['type']!='multilangtext' and $this->field['type']!='multilangarticle')
		{
			$rfn = $this->field['realfieldname'];
			$value = isset($this->ct->Table->record[$rfn]) ? $this->ct->Table->record[$rfn] : null;
		}
		
		if($this->ct->isEditForm)
		{
			$Inputbox = new Inputbox($this->ct, $this->field, $args);
			return new \Twig\Markup($Inputbox->render($value, $this->ct->Table->record), 'UTF-8' );
		}
		else
		{
			$postfix='';
            $ajax_prefix = 'com_'.$this->ct->Table->record['listing_id'].'_';//example: com_153_es_fieldname or com_153_ct_fieldname

			if($this->field['type']=='multilangstring')
			{
				if(isset($args[4]))
				{
					//multilang field specific language
                    $firstlanguage=true;
                    foreach($this->ct->Languages->LanguageList as $lang)
					{
						if($lang->sef==$value_option_list[4])
                        {
							$postfix=$lang->sef;
                            break;
						}
                    }
				}
			}
			
			//Deafult style (borderless)
			if(isset($args[0]) or $args[0] != '')
				$class_str = $args[0];
			else
				$class_str = '';
			
			if($class_str == '' or strpos($class_str,':')!==false)//its a style, change it to attribute
				$div_arg=' style="'.$class_str.'"';
			else
				$div_arg=' class="'.$class_str.'"';

			// Default attribute - action to save the value
			$args[0] = 'border:none !important;width:auto;box-shadow:none;';
			
			$onchange='ct_UpdateSingleValue(\''.$this->ct->Env->WebsiteRoot.'\','.$this->ct->Env->Itemid.',\''
				.$this->field['fieldname'].'\','.$this->ct->Table->record['listing_id'].',\''.$postfix.'\');';
								
            $attributes='onchange="'.$onchange.'"'.$style;
								
			if(isset($value_option_list[1]))
				$args[1] .= ' '.$attributes;
			else
				$args[1] = $attributes;

			$Inputbox = new Inputbox($this->ct, $this->field, $args, true);
			
			$edit_box = '<div '.$div_arg.'id="'.$ajax_prefix.$this->field['fieldname'].$postfix.'_div">'
                            .$Inputbox->render($value, $this->ct->Table->record)
						.'</div>';
			
			return new \Twig\Markup($edit_box, 'UTF-8' );
		}
    }
}
