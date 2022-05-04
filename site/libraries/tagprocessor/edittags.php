<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die('Restricted access');

use \CustomTables\Twig_Html_Tags;

/* All tags are implemented using Twig

Implemented:

{captcha} - {{ html.captcha }}
{button:} - {{ html.button("type") }}

*/

class tagProcessor_Edit
{
    public static function process(&$ct,&$pagelayout,&$row)
    {
		$ct_html = new Twig_Html_Tags($ct, false);
		
        if(isset($row[$ct->Table->realidfieldname]))
            $listing_id=(int)$row[$ct->Table->realidfieldname];
        else
        	$listing_id='';
        
        $captcha_found = tagProcessor_Edit::process_captcha($ct_html,$pagelayout); //Converted to Twig. Original replaced.
	
        $buttons = tagProcessor_Edit::process_button($ct_html,$pagelayout,$captcha_found,$listing_id);
        
        $fields = tagProcessor_Edit::process_fields($ct,$pagelayout,$row); //Converted to Twig. Original replaced.
		return ['fields' => $fields,'buttons' => $buttons];
    }
    
    protected static function process_fields(&$ct,&$pagelayout,&$row)
    {
        require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'esinputbox.php');
        
    	$esinputbox = new ESInputBox($ct);
		
		if($ct->Env->menu_params->get('requiredlabel')!='')
			$esinputbox->requiredlabel=$ct->Env->menu_params->get( 'requiredlabel' );	
        
        //Calendars of the child should be built again, because when Dom was ready they didn't exist yet.
        $calendars=array();
        $replaceitecode=JoomlaBasicMisc::generateRandomString();
		$items_to_replace=array();
        
		$field_objects = tagProcessor_Edit::renderFields($row,$pagelayout,0,$esinputbox,$calendars,'',$replaceitecode,$items_to_replace);
        
		foreach($items_to_replace as $item)
			$pagelayout=str_replace($item[0],$item[1],$pagelayout);
			
		return $field_objects;
    }

    protected static function process_captcha(&$ct_html,&$pagelayout)
    {
        $found=false;
        $options = [];
		$captchas = JoomlaBasicMisc::getListToReplace('captcha',$options,$pagelayout,'{}');

		foreach($captchas as $captcha)
		{
			$captcha_code = $ct_html->captcha();
			$pagelayout=str_replace($captcha,$captcha_code,$pagelayout);
		}
		
        return $found;
    }

    protected static function process_button(&$ct_html,&$pagelayout,$captcha_found,$listing_id)
    {
		$button_objects = [];
		
        $options = [];
		$buttons = JoomlaBasicMisc::getListToReplace('button',$options,$pagelayout,'{}');
		
		if(count($buttons) == 0)
			return;

		for($i=0;$i<count($buttons);$i++)
		{
			$option=JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

            if($option[0]!='')
				$type=$option[0];//button set
			else
				$type='save';

			$title = $option[1] ?? '';
			$redirectlink = $option[2] ?? null;
			$optional_class = $option[3] ?? '';

			$b = $ct_html->button($type, $title, $redirectlink, $optional_class);

			$pagelayout = str_replace($buttons[$i], $b, $pagelayout);
		}//for

		return $ct_html->button_objects;
    }

    protected static function renderFields(&$row,&$pagelayout,$parentid,&$esinputbox,&$calendars,string $style,$replaceitecode,&$items_to_replace)
	{
		$field_objects = [];
		$calendars=array();

		//custom layout
		if(!isset($esinputbox->ct->Table->fields) or !is_array($esinputbox->ct->Table->fields))
			return [];
			
    	for($f=0;$f<count($esinputbox->ct->Table->fields);$f++ )
		{
			$esfield=$esinputbox->ct->Table->fields[$f];
			$options=array();
			$entries=JoomlaBasicMisc::getListToReplace($esfield['fieldname'],$options,$pagelayout,'[]');

			if(count($entries)>0)
			{
				$i=0;
				for($i;$i<count($entries);$i++)
				{
					$option_list=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);

					$result = '';

					if($esfield['type']=='date')
						$calendars[] = $esinputbox->ct->Env->field_prefix.$esfield['fieldname'];

					if($esfield['type']!='dummy')
						$result =  $esinputbox->renderFieldBox($esfield,$row,$option_list);

					if($esinputbox->ct->Env->frmt == 'json')
					{
						$field_objects[] = $result;
						$result = '';
					}

					$new_replaceitecode=$replaceitecode.str_pad(count($items_to_replace), 9, '0', STR_PAD_LEFT).str_pad($i, 4, '0', STR_PAD_LEFT);

					$items_to_replace[]=array($new_replaceitecode,$result);
					$pagelayout=str_replace($entries[$i],$new_replaceitecode,$pagelayout);
				}
			}
		}
		
		return $field_objects;
	}
}
