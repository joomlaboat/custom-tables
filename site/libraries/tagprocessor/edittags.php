<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

class tagProcessor_Edit
{
    public static function process(&$Model,&$pagelayout,&$row,$fieldNamePrefix)
    {
        if(isset($row['listing_id']))
            $listing_id=(int)$row['listing_id'];
        else
        	$listing_id=0;
        
        $captcha_found=tagProcessor_Edit::process_captcha($Model,$pagelayout);
		
        $buttons = tagProcessor_Edit::process_button($Model,$pagelayout,$captcha_found,$listing_id);
        $buttons_obsolete = tagProcessor_Edit::process_buttons($Model,$pagelayout,$captcha_found,$listing_id);
        $fields = tagProcessor_Edit::process_fields($Model,$pagelayout,$row,$fieldNamePrefix);
		return ['fields' => $fields,'buttons' => $buttons];
    }
    
    protected static function process_fields(&$Model,&$pagelayout,&$row,$fieldNamePrefix)
    {
        require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'esinputbox.php');
        
    	$esinputbox = new ESInputBox;
    	$esinputbox->es=$Model->es;
    	$esinputbox->LanguageList=$Model->LanguageList;
        $esinputbox->langpostfix=$Model->langpostfix;
        $esinputbox->establename=$Model->establename;
    	$esinputbox->estableid=$Model->estableid;
        $esinputbox->requiredlabel=$Model->params->get( 'requiredlabel' );
        
        //Calendars of the child should be built again, because when Dom was ready they didn't exist yet.
        $calendars=array();
        $replaceitecode=JoomlaBasicMisc::generateRandomString();
		$items_to_replace=array();
        
		$field_objects = tagProcessor_Edit::renderFields($row,$Model,$pagelayout,$Model->langpostfix,0,$esinputbox,$calendars,'',$replaceitecode,$items_to_replace,$fieldNamePrefix);
        
		foreach($items_to_replace as $item)
			$pagelayout=str_replace($item[0],$item[1],$pagelayout);
			
		return $field_objects;
    }

    protected static function process_captcha(&$Model,&$pagelayout)
    {
        $found=false;
        $options=array();
		$captcha=JoomlaBasicMisc::getListToReplace('captcha',$options,$pagelayout,'{}');

        if(count($captcha)>0)
        {
			if($Model->frmt!='csv')
            {
				$p=tagProcessor_Edit::getReCaptchaParams();
                if($p!=null)
                {
					JPluginHelper::importPlugin('captcha');
					$dispatcher = JEventDispatcher::getInstance();
                    $dispatcher->trigger('onInit','my_captcha_div');

                    $reCaptchaParams=json_decode($p->params);
                }
                else
					$reCaptchaParams=null;
            }
        }

		for($i=0;$i<count($captcha);$i++)
		{
			$captcha_code='';
            if($Model->frmt!='csv')
            {
				if($reCaptchaParams!=null and $reCaptchaParams->public_key!="" and isset($reCaptchaParams->size))
                {
					$captcha_code='
    <div id="my_captcha_div"
    class="g-recaptcha"
    data-sitekey="'.$reCaptchaParams->public_key.'"
    data-theme="'.$reCaptchaParams->theme.'"
    data-size="'.$reCaptchaParams->size.'"
    data-callback="recaptchaCallback"
    ></div>';
					$found =true;
                }
				$pagelayout=str_replace($captcha,$captcha_code,$pagelayout);
				$i++;
			}
		}
		
        return $found;
    }

    protected static function getReCaptchaParams()
    {
        $db = JFactory::getDBO();
		$query='SELECT params FROM #__extensions WHERE '.$db->quoteName("name").'='.$db->Quote("plg_captcha_recaptcha").' LIMIT 1';
		$db->setQuery( $query );

		$rows=$db->loadObjectList();
		if(count($rows)==0)
            return null;

        return $rows[0];
    }

    protected static function process_button(&$Model,&$pagelayout,$captcha_found,$listing_id)
    {
		$button_objects = [];
		
        $options=array();
		$buttons=JoomlaBasicMisc::getListToReplace('button',$options,$pagelayout,'{}');

		for($i=0;$i<count($buttons);$i++)
		{
			$b='';
			if($Model->frmt!='csv' and $Model->print==0)
            {
                $option=JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

                if($option[0]!='')
					$type=$option[0];//button set
                else
					$type='save';//$Model->submitbuttons;

                if(isset($option[1]))
					$title=$option[1];
                else
					$title='';
					
				if(isset($option[2]))
					$redirectlink=$option[2];
                else
					$redirectlink=$Model->params->get( 'returnto' );
                                    
                if($redirectlink!='')
				{
					$_row=array();
                    $_list=array();
                    tagProcessor_General::process($Model,$redirectlink,$_row,$_list,0);
				}

				
				
				$optional_class='';
                if(isset($option[3]))
					$optional_class=$option[3];
					

				switch($type)
                {
						case 'save':
                            $b=tagProcessor_Edit::renderSaveButton($Model,$captcha_found,$optional_class,$title);
                        break;
                                    
                        case 'saveandclose':
							$b=tagProcessor_Edit::renderSaveAndCloseButton($Model,$captcha_found,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'saveandprint':
                            $b=tagProcessor_Edit::renderSaveAndPrintButton($Model,$captcha_found,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'saveascopy':
                                        
                            if($listing_id==0)
                                $b='';
                            else
                                $b=tagProcessor_Edit::renderSaveAsCopyButton($Model,$captcha_found,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'cancel':
							$b=tagProcessor_Edit::renderCancelButton($Model,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'close':
                            $b=tagProcessor_Edit::renderCancelButton($Model,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'delete':

							$b=tagProcessor_Edit::renderDeleteButton($Model,$captcha_found,$optional_class,$title,$redirectlink);
							
						break;

						default:
							$b='';
                        break;
                    
					}//switch

				if($Model->frmt == 'json')
				{
					$button_objects[] = ['type' => $type, 'title' => $b, 'redirectlink' => $redirectlink];
					$b = '';
				}
				
				$pagelayout=str_replace($buttons[$i], $b, $pagelayout);
				
			}
			
		}//for
						
		return $button_objects;
    }

    protected static function process_buttons(&$Model,&$pagelayout,$captcha_found,$listing_id)
    {
                        $options=array();
						$buttons=JoomlaBasicMisc::getListToReplace('buttons',$options,$pagelayout,'{}');

						for($i=0;$i<count($buttons);$i++)
						{
                            $b='';
                            if($Model->frmt!='csv' and $Model->print==0)
                            {
                                $option=JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

                                if($option[0]!='')
                                	$submitbuttons=$option[0];//button set
                                else
                                	$submitbuttons=$Model->submitbuttons;

                                if(isset($option[1]))
                                	$button1title=$option[1];
                                else
                                	$button1title=$Model->applybuttontitle;

                                if(isset($option[2]))
                                	$button2title=$option[2];
                                else
                                    $button2title=$Model->applybuttontitle;

                                if(isset($option[3]))
                                	$button3title=$option[3];
                                else
                                	$button3title=$Model->applybuttontitle;

                                if(isset($option[4]))
                                	$redirectlink=$option[4];
                                else
                                	$redirectlink=$Model->params->get( 'returnto' );

								$optional_class='';
                                if(isset($option[5]))
                                	$optional_class=$option[5];

                                $b=tagProcessor_Edit::getToolbar($Model,$submitbuttons,$button1title,$button2title,$button3title,$redirectlink,$optional_class,$captcha_found,$listing_id);
			
                            }
                            
							$pagelayout=str_replace($buttons[$i], $b, $pagelayout);
						}
    }
    
    protected static function renderSaveButton(&$Model,$captcha_found,$optional_class,$title)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVE');
			
		if($Model->frmt == 'json')
			return $title;

        $attribute='';
        if($captcha_found)
            $attribute=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';
        
        $onclick='setTask(event, "saveandcontinue","'.$Model->encoded_current_url.'",true);';
		
		return '<input id="customtables_button_save" type="submit" class="'.$the_class.' validate"'.$attribute.' onClick=\''.$onclick.'\' value="'.$title.'">';
    }
    
    protected static function renderSaveAndCloseButton(&$Model,$captcha_found,$optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title= JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVEANDCLOSE');
			
		if($Model->frmt == 'json')
			return $title;
			
        $attribute='onClick=\'';
        
        $attribute.='setTask(event, "save","'.base64_encode ($redirectlink).'",true);';
            
        $attribute.='\'';
        
		if($captcha_found)
            $attribute.=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';

        
        return '<input id="customtables_button_saveandclose" type="submit" '.$attribute.' class="'.$the_class.' validate" value="'.$title.'" />';
    }
    
    protected static function renderSaveAndPrintButton(&$Model,$captcha_found,$optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NEXT');
			
		if($Model->frmt == 'json')
			return $title;
			
        $attribute='onClick=\'';
        $attribute='setTask(event, "saveandprint","'.base64_encode ($redirectlink).'",true);';
        $attribute.='\'';
        
        if($captcha_found)
            $attribute=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';
        
        return '<input id="customtables_button_saveandprint" type="submit" '.$attribute.' class="'.$the_class.' validate" value="'.$title.'" />';
    }
    
    protected static function renderSaveAsCopyButton(&$Model,$captcha_found,$optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVEASCOPYANDCLOSE');
			
		if($Model->frmt == 'json')
			return $title;
			
        $attribute='';//onClick="return checkRequiredFields();"';
        if($captcha_found)
            $attribute=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;//$the_class='ctEditFormButton '.$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';
            
        $onclick='setTask(event, "saveascopy","'.base64_encode ($redirectlink).'",true);';
        
        return '<input id="customtables_button_saveandcopy" type="submit" class="'.$the_class.' validate"'.$attribute.' onClick=\''.$onclick.'\' value="'.$title.'">';
    }
    
    protected static function renderCancelButton(&$Model,$optional_class,$title,$redirectlink)
    {
        if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CANCEL');
		
		if($Model->frmt == 'json')
			return $title;
            
        if($optional_class!='')
            $cancel_class=$optional_class;//$cancel_class='ctEditFormButton '.$optional_class;
        else
          	$cancel_class='ctEditFormButton btn button-cancel';

        $onclick='setTask(event, "cancel","'.base64_encode ($redirectlink).'",true);';
    	return '<input id="customtables_button_cancel" type="button" class="'.$cancel_class.'" value="'.$title.'" onClick=\''.$onclick.'\'>';
    }
    
    protected static function renderDeleteButton(&$Model,$captcha_found,$optional_class,$title,$redirectlink)
    {
        if($title=='')
			$title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE');
				
		if($Model->frmt == 'json')
			return $title;
            
        if($optional_class!='')
            $class=$optional_class;//$class='ctEditFormButton '.$optional_class;
        else
          	$class='ctEditFormButton btn button-cancel';

        $result='<input id="customtables_button_delete" type="button" class="'.$class.'" value="'.$title.'"
				onClick=\'
                if (confirm("'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DO_U_WANT_TO_DELETE').'"))
                {
                    this.form.task.value="delete";
                    '.($redirectlink!='' ? 'this.form.returnto.value="'.base64_encode ($redirectlink).'";' : '' ).'
                    this.form.submit();
                }
                \'>
			';

        return $result;
    }
    
    protected static function getToolbar(&$Model,$submitbuttons,$button1title,$button2title,$button3title,$redirectlink,$optional_class='',$captcha_found=false,$listing_id=0)
	{
        //will be depricated by July 2019
		$toolbar='';
        
		if($submitbuttons=='apply' or $submitbuttons=='saveandclose')
		{
            //Save and close
			$toolbar=tagProcessor_Edit::renderSaveAndCloseButton($captcha_found,$optional_class,$button1title,$redirectlink);
		}
		elseif($submitbuttons=='nextprint' or $submitbuttons=='saveandprint')
		{
            //Save and Open Print preview
			$toolbar=tagProcessor_Edit::renderSaveAndPrintButton($captcha_found,$optional_class,$button1title,$redirectlink);
		}
        
		elseif($submitbuttons=='savecancelsavenew' or $submitbuttons=='saveandclose.saveascopy.cancel')//savecancelsavenew - legacy support 
		{
            //Save & Close / Save as New & Close / Cancel
			$toolbar=tagProcessor_Edit::renderSaveAndCloseButton($captcha_found,$optional_class,$button1title,$redirectlink).' ';
            
            if($listing_id!=0)
                $toolbar.=tagProcessor_Edit::renderSaveAsCopyButton($captcha_found,$optional_class,$button2title,$redirectlink);
                
            $toolbar.=tagProcessor_Edit::renderCancelButton($optional_class,$button3title,$redirectlink);
        }
        elseif($submitbuttons=='applysavecancel' or $submitbuttons=='save.saveandclose.cancel')//applysavecancel -  legacy support
        {
            //Save / Save & Close / Cancel
            $toolbar=tagProcessor_Edit::renderSaveButton($Model,$captcha_found,$optional_class,$button1title).' ';
            $toolbar.=tagProcessor_Edit::renderSaveAndCloseButton($captcha_found,$optional_class,$button2title,$redirectlink).' ';
            $toolbar.=tagProcessor_Edit::renderCancelButton($optional_class,$button3title,$redirectlink);

		}
        else
        {
            //savecancel or saveandclose.cancel
            //Default
            //Save & Close / Save as New & Close / Cancel
			$toolbar=tagProcessor_Edit::renderSaveAndCloseButton($captcha_found,$optional_class,$button1title,$redirectlink);
            $toolbar.=tagProcessor_Edit::renderCancelButton($optional_class,$button3title,$redirectlink);
        }

		return $toolbar;

	}//function

    protected static function renderFields(&$row,&$Model,&$pagelayout,$langpostfix,$parentid,&$esinputbox,&$calendars,$style='',$replaceitecode,&$items_to_replace,$fieldNamePrefix)
	{
		$field_objects = [];
		$calendars=array();

		//custom layout
		if(!isset($Model->esfields) or !is_array($Model->esfields))
			return [];
			
    	for($f=0;$f<count($Model->esfields);$f++ )
		{
			$esfield=$Model->esfields[$f];
			$options=array();
			$entries=JoomlaBasicMisc::getListToReplace($esfield['fieldname'],$options,$pagelayout,'[]');

			if(count($entries)>0)
			{
				$i=0;
				for($i;$i<count($entries);$i++)
				{
					$option_list=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);
					// $option_list[0] - CSS Class
					// $option_list[1] - Optional Parameter
					$class=$option_list[0];
					$attribute='';
					
					if(isset($option_list[1]))
						$attribute=$option_list[1];
								
					if(strpos($class,':')!==false)//its a style, chanage it to attribute
    				{
						if($attribute!='')
    						$attribute.=' ';

						$attribute.='style="'.$class.'"';
						$class='';
					}

					$result=tagProcessor_Edit::renderField($row,$Model,$langpostfix,-1,$esinputbox,$calendars,$esfield,$class,$attribute,$option_list,$fieldNamePrefix);
					
					if($Model->frmt == 'json')
					{
						$field_objects[] = $result;
						$result = '';
					}

					$new_replaceitecode=$replaceitecode.str_pad(count($items_to_replace), 9, '0', STR_PAD_LEFT).str_pad($i, 4, '0', STR_PAD_LEFT);

					$items_to_replace[]=array($new_replaceitecode,$result);
					$pagelayout=str_replace($entries[$i],$new_replaceitecode,$pagelayout);
					
				}

				//$fieldstosave[]=$esfield['fieldname'];

			}
		}//for($f=0;$f<count($Model->esfields);$f++ )
		
		return $field_objects;
	}

	protected static function renderField(&$row,&$Model,$langpostfix,$parentid,&$esinputbox,&$calendars,&$esfield, $class='',$attributes='',$option_list,$fieldNamePrefix)
	{
		if($esfield['parentid']==$parentid or $parentid==-1)
		{
			if($esfield['type']=='date')
				$calendars[]='es_'.$esfield['fieldname'];

			if($esfield['type']!='dummy')
				return $esinputbox->renderFieldBox($Model,$fieldNamePrefix,$esfield,$row, $class,$attributes,$option_list);
		}
		return '';
	}
}
