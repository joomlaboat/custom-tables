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

class tagProcessor_Edit
{
    public static function process(&$ct,&$pagelayout,&$row)
    {
        if(isset($row['listing_id']))
            $listing_id=(int)$row['listing_id'];
        else
        	$listing_id=0;
        
        $captcha_found=tagProcessor_Edit::process_captcha($ct,$pagelayout);
	
        $buttons = tagProcessor_Edit::process_button($ct,$pagelayout,$captcha_found,$listing_id);
        
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

    protected static function process_captcha(&$ct,&$pagelayout)
    {
        $found=false;
        $options=array();
		$captcha=JoomlaBasicMisc::getListToReplace('captcha',$options,$pagelayout,'{}');
		
		if(count($captcha)>0)
        {
			JHtml::_('behavior.keepalive');
			
			if($ct->Env->frmt!='csv')
            {
				$p=tagProcessor_Edit::getReCaptchaParams();
                if($p!=null)
                {
					JPluginHelper::importPlugin('captcha');
					
					if($ct->Env->version < 4)
					{
						$dispatcher = JEventDispatcher::getInstance();
						$dispatcher->trigger('onInit','my_captcha_div');
					}
					else
					{
						JFactory::getApplication()->triggerEvent('onInit', array(null, 'my_captcha_div', 'class=""'));
						//JFactory::getApplication()->triggerEvent( 'onInit','my_captcha_div');
					}

                    $reCaptchaParams=json_decode($p->params);
                }
                else
					$reCaptchaParams=null;
            }
        }

		for($i=0;$i<count($captcha);$i++)
		{
			$captcha_code='';
            if($ct->Env->frmt!='csv')
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

    protected static function process_button(&$ct,&$pagelayout,$captcha_found,$listing_id)
    {
		$button_objects = [];
		
        $options=array();
		$buttons=JoomlaBasicMisc::getListToReplace('button',$options,$pagelayout,'{}');

		for($i=0;$i<count($buttons);$i++)
		{
			$b='';
			if($ct->Env->frmt!='csv' and $ct->Env->print==0)
            {
                $option=JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

                if($option[0]!='')
					$type=$option[0];//button set
                else
					$type='save';

                if(isset($option[1]))
					$title=$option[1];
                else
					$title='';
					
				if(isset($option[2]))
					$redirectlink=$option[2];
                else
					$redirectlink=$ct->Env->menu_params->get( 'returnto' );
                                    
                if($redirectlink!='')
				{
					$_row=array();
                    $_list=array();
                    tagProcessor_General::process($ct,$redirectlink,$_row,$_list,0);
				}

				
				
				$optional_class='';
                if(isset($option[3]))
					$optional_class=$option[3];
					

				switch($type)
                {
						case 'save':
                            $b=tagProcessor_Edit::renderSaveButton($ct,$captcha_found,$optional_class,$title);
                        break;
                                    
                        case 'saveandclose':
							$b=tagProcessor_Edit::renderSaveAndCloseButton($ct,$captcha_found,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'saveandprint':
                            $b=tagProcessor_Edit::renderSaveAndPrintButton($ct,$captcha_found,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'saveascopy':
                                        
                            if($listing_id==0)
                                $b='';
                            else
                                $b=tagProcessor_Edit::renderSaveAsCopyButton($ct,$captcha_found,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'cancel':
							$b=tagProcessor_Edit::renderCancelButton($ct,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'close':
                            $b=tagProcessor_Edit::renderCancelButton($ct,$optional_class,$title,$redirectlink);
                        break;
                                    
                        case 'delete':

							$b=tagProcessor_Edit::renderDeleteButton($ct,$captcha_found,$optional_class,$title,$redirectlink);
							
						break;

						default:
							$b='';
                        break;
                    
					}//switch

				if($ct->Env->frmt == 'json')
				{
					$button_objects[] = ['type' => $type, 'title' => $b, 'redirectlink' => $redirectlink];
					$b = '';
				}
				
				$pagelayout=str_replace($buttons[$i], $b, $pagelayout);
				
			}
			
		}//for
						
		return $button_objects;
    }

    protected static function renderSaveButton(&$ct,$captcha_found,$optional_class,$title)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVE');
			
		if($ct->Env->frmt == 'json')
			return $title;

        $attribute='';
        if($captcha_found)
            $attribute=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';
        
        $onclick='setTask(event, "saveandcontinue","'.$ct->Env->encoded_current_url.'",true);';
		
		return '<input id="customtables_button_save" type="submit" class="'.$the_class.' validate"'.$attribute.' onClick=\''.$onclick.'\' value="'.$title.'">';
    }
    
    protected static function renderSaveAndCloseButton(&$ct,$captcha_found,$optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title= JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVEANDCLOSE');
			
		if($ct->Env->frmt == 'json')
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
    
    protected static function renderSaveAndPrintButton(&$ct,$captcha_found,$optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NEXT');
			
		if($ct->Env->frmt == 'json')
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
    
    protected static function renderSaveAsCopyButton(&$ct,$captcha_found,$optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVEASCOPYANDCLOSE');
			
		if($ct->Env->frmt == 'json')
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
    
    protected static function renderCancelButton(&$ct,$optional_class,$title,$redirectlink)
    {
        if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CANCEL');
		
		if($ct->Env->frmt == 'json')
			return $title;
            
        if($optional_class!='')
            $cancel_class=$optional_class;//$cancel_class='ctEditFormButton '.$optional_class;
        else
          	$cancel_class='ctEditFormButton btn button-cancel';

        $onclick='setTask(event, "cancel","'.base64_encode ($redirectlink).'",true);';
    	return '<input id="customtables_button_cancel" type="button" class="'.$cancel_class.'" value="'.$title.'" onClick=\''.$onclick.'\'>';
    }
    
    protected static function renderDeleteButton(&$ct,$captcha_found,$optional_class,$title,$redirectlink)
    {
        if($title=='')
			$title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE');
				
		if($ct->Env->frmt == 'json')
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
					
					//if($esfield['parentid']==$parentid or $parentid==-1)
					//{
						if($esfield['type']=='date')
							$calendars[] = $esinputbox->ct->Env->field_prefix.$esfield['fieldname'];

						if($esfield['type']!='dummy')
							$result =  $esinputbox->renderFieldBox($esfield,$row,$option_list);
					//}
					
					
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
