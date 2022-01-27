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

use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\SearchInputBox;
use CustomTables\CTUser;

use \JoomlaBasicMisc;
use \JESPagination;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;

use \JHTML;

class Twig_Html_Tags
{
	var $ct;
	var $isTwig;
	
	var $captcha_found;
	var $button_objects = []; //Not clear where and how this variable used.
	
	function __construct(&$ct,$isTwig = true)
	{
		$this->ct = $ct;
		$this->isTwig = $isTwig;
		
		$this->captcha_found = false;
		$this->button_objects = [];//Not clear where and how this variable used.
	}
	
	function add($Alias_or_ItemId = '')
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		$usergroups = $this->ct->Env->user->get('groups');
		
		if(isset($this->ct->Env->menu_params))
            $add_userGroup=(int)$this->ct->Env->menu_params->get( 'addusergroups' );
        else
            $add_userGroup=0;
		
		if(!$this->ct->Env->isUserAdministrator and !in_array($add_userGroup,$usergroups))
			return ''; //Not permitted

        //$isEditable=CTUser::checkIfRecordBelongsToUser($this->ct,$edit_userGroup);

		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return ''; //Not permitted
		
		if((int)$Alias_or_ItemId > 0)
			$link='/index.php?option=com_customtables&view=edititem&returnto='.$this->ct->Env->encoded_current_url.'&Itemid='.$Alias_or_ItemId;
		if($Alias_or_ItemId != '')
			$link='/index.php/'.$Alias_or_ItemId.'?returnto='.$this->ct->Env->encoded_current_url;
		else
			$link='/index.php?option=com_customtables&view=edititem&returnto='.$this->ct->Env->encoded_current_url.'&Itemid='.$this->ct->Env->Itemid;

		if($this->ct->Env->jinput->getCmd('tmpl','')!='')
			$link.='&tmpl='.$this->ct->Env->jinput->get('tmpl','','CMD');
                    
		$vlu='<a href="'.URI::root(true).$link.'" id="ctToolBarAddNew'.$this->ct->Table->tableid.'" class="toolbarIcons">'
			.'<img src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/new.png" alt="Add New" title="Add New" /></a>';
			
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	function importcsv()
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		if(isset($this->ct->Env->menu_params))
            $add_userGroup=(int)$this->ct->Env->menu_params->get( 'addusergroups' );
        else
            $add_userGroup=0;

		$usergroups = $this->ct->Env->user->get('groups');
		if(!$this->ct->Env->isUserAdministrator and !in_array($add_userGroup,$usergroups))
			return ''; //Not permitted

		$max_file_size=JoomlaBasicMisc::file_upload_max_size();
                    
		$fileid = JoomlaBasicMisc::generateRandomString();
        $fieldid = '9999';//some unique number
        $objectname='importcsv';

		JHtml::_('behavior.formvalidator');
    
        $vlu = '<div>
                    <div id="ct_fileuploader_'.$objectname.'"></div>
                    <div id="ct_eventsmessage_'.$objectname.'"></div>
                    <form action="" name="ctUploadCSVForm" id="ctUploadCSVForm">
                	<script>
                        UploadFileCount=1;

                    	var urlstr="/index.php?option=com_customtables&amp;view=fileuploader&amp;tmpl=component&'
                        .'tableid='.$this->ct->Table->tableid.'&'
                        .'task=importcsv&'
                        .$objectname.'_fileid='.$fileid.'&Itemid='.$this->ct->Env->Itemid.'&fieldname='.$objectname.'";
                        
                    	ct_getUploader('.$fieldid.',urlstr,'.$max_file_size.',"csv","ctUploadCSVForm",true,"ct_fileuploader_'.$objectname.'","ct_eventsmessage_'.$objectname.'","'.$fileid.'","'
                        .$this->ct->Env->field_input_prefix.$objectname.'","ct_uploadedfile_box_'.$objectname.'");
                    </script>
                    <input type="hidden" name="'.$this->ct->Env->field_input_prefix.$objectname.'" id="'.$this->ct->Env->field_input_prefix.$objectname.'" value="" />
			'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE').': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'
                    </form>
                </div>
';
		
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	function pagination()
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		if($this->ct->Table->recordcount <= $this->ct->Limit)
			return '';
		
		$pagination = new JESPagination($this->ct->Table->recordcount, $this->ct->LimitStart, $this->ct->Limit);
		$vlu = '<div class="pagination">'.$pagination->getPagesLinks("").'</div>';
		
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	function limit($the_step = 1)
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		$pagination = new JESPagination($this->ct->Table->recordcount, $this->ct->LimitStart, $this->ct->Limit);
		$vlu = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW' ).': '.$pagination->getLimitBox($the_step);
		
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	function orderby()
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		$vlu = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY' ).': '.OrderingHTML::getOrderBox($this->ct->Ordering);
		
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
		
	function goback($label='Go Back', $image_icon='components/com_customtables/libraries/customtables/media/images/icons/arrow_rtl.png', $attribute='',  $returnto = '')
	{
		if($this->ct->Env->print==1)
            $gobackbutton='';
				
		if($returnto == '')
			$returnto = base64_decode($this->ct->Env->jinput->get('returnto','','BASE64'));
		
		if($returnto == '')
			return '';
		
		if($attribute == '')
			$attribute = 'class="ct_goback"';
		
		$vlu = '<a href="'.$returnto.'" '.$attribute.'><div>'.$label.'</div></a>';
		
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	protected function getAvailableModes()
	{
		$available_modes=array();
        
        $user = Factory::getUser();
		if($user->id!=0)
        {
            $publish_userGroup=(int)$this->ct->Env->menu_params->get( 'publishusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($publish_userGroup))
            {
                $available_modes[]='publish';
                $available_modes[]='unpublish';
            }
            
            $edit_userGroup=(int)$this->ct->Env->menu_params->get( 'editusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($edit_userGroup))
                $available_modes[]='refresh';
                
            $delete_userGroup=(int)$this->ct->Env->menu_params->get( 'deleteusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($delete_userGroup))
                $available_modes[]='delete';
        }
		return $available_modes;
	}
	
	function batch($buttons = [])
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		$available_modes = $this->getAvailableModes();
		if(count($available_modes) == 0)
			return '';
		
		$buttons_array = [];
		if(is_array($buttons))
			$buttons_array = $buttons;
		else
			$buttons_array = explode(',',$buttons);
		
		if(count($buttons_array) == 0)
			$buttons_array = $available_modes;
		
		$html_buttons = [];
		
		foreach($buttons_array as $mode)
		{
			if($mode == 'checkbox')
			{
				$html_buttons[] = '<input type="checkbox" id="esCheckboxAll'.$this->ct->Table->tableid.'" onChange="esCheckboxAllclicked('.$this->ct->Table->tableid.')" />';
			}
			else
			{
				if(in_array($mode,$available_modes))
				{
					$rid='esToolBar_'.$mode.'_box_'.$this->ct->Table->tableid;
					$alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_'.strtoupper($mode).'_SELECTED' );
					$img='<img src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/'.$mode.'.png" border="0" alt="'.$alt.'" title="'.$alt.'" />';
					$link='javascript:ctToolBarDO("'.$mode.'", '.$this->ct->Table->tableid.')';
					$html_buttons[] = '<div id="'.$rid.'" class="toolbarIcons"><a href=\''.$link.'\'>'.$img.'</a></div>';
				}
			}
		}
		
		if(count($html_buttons) == 0)
			return '';
		
		$vlu = implode('',$html_buttons);
		
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	function print($class='ctEditFormButton btn button')
	{
		$link=$this->ct->Env->current_url.(strpos($this->ct->Env->current_url,'?')===false ? '?' : '&').'tmpl=component&amp;print=1';

		if($this->ct->Env->jinput->getInt('moduleid',0)!=0)
		{
			//search module

			$moduleid = $this->ct->Env->jinput->getInt('moduleid',0);
			$link.='&amp;moduleid='.$moduleid;

			//keyword search
			$inputbox_name='eskeysearch_'.$moduleid ;
			$link.='&amp;'.$inputbox_name.'='.$this->ct->Env->jinput->getString($inputbox_name,'');
		}

		if($this->ct->Env->print==1)
		{
			$vlu='<p><a href="#" onclick="window.print();return false;"><img src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/print.png" alt="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT').'"  /></a></p>';
		}
		else
		{
			$vlu='<input type="button" class="'.$class.'" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT' ).'" onClick=\'window.open("'.$link.'","win2","status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no"); return false; \'> ';
        }
			
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	protected function getFieldTitles($list_of_fields)
    {
        $fieldtitles=array();
        foreach($list_of_fields as $fieldname)
        {
			if($fieldname=='_id')
				$fieldtitles[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ID');
			else						
			{
				foreach($this->ct->Table->fields as $fld)
				{
					if($fld['fieldname']==$fieldname)
					{
						$fieldtitles[]=$fld['fieldtitle'.$this->ct->Languages->Postfix];
						break;
					}
				}
			}
        }
        return $fieldtitles;
    }
	
	protected function prepareSearchElement($fld)
    {
		if(isset($fld['fields']) and count($fld['fields'])>0)
        {
			return 'es_search_box_'.$fld['fieldname'].':'.implode(';',$fld['fields']).':';
        }
        else
        {
			if($fld['type']=='customtables')
            {
				$exparams=explode(',',$fld['typeparams']);
    			if(count($exparams)>1)
    			{
					$esroot=$exparams[0];
    				return 'es_search_box_combotree_'.$this->ct->Table->tablename.'_'.$fld['fieldname'].'_1:'.$fld['fieldname'].':'.$esroot;
    			}
			}
    		else
    			return 'es_search_box_'.$fld['fieldname'].':'.$fld['fieldname'].':';
		}
		
        return '';       
    }
	
	function search($list_of_fields_string_or_array, $class = '', $reload = false, $improved = false)
	{
		if($this->ct->Env->print == 1 or $this->ct->Env->frmt == 'csv')
			return '';
				
		if(is_array($list_of_fields_string_or_array))
			$list_of_fields_string_array = $list_of_fields_string_or_array;
		else
			$list_of_fields_string_array = explode(',',$list_of_fields_string_or_array);
		
		if(count($list_of_fields_string_array) == 0)
		{
			Factory::getApplication()->enqueueMessage('Search box: Please specify a field name.', 'error');
			return '';
		}
		
		//Clean list of fields
		$list_of_fields=[];
		foreach($list_of_fields_string_array as $field_name_string)
		{
			if($field_name_string=='_id')
			{
				$list_of_fields[] = '_id';
			}
			else
			{
				//Check if field name is exist in selected table
				$fld = Fields::FieldRowByName($field_name_string,$this->ct->Table->fields);
				if(count($fld)>0)
					$list_of_fields[]=$field_name_string;
			}
		}

		if(count($list_of_fields) == 0)
		{
			Factory::getApplication()->enqueueMessage('Search box: Field name "'.implode(',',$list_of_fields_string_or_array).'" not found.', 'error');
			return '';
		}
		
		$vlu='Search field name is wrong';
		
		require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR.'libraries'
			. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'searchinputbox.php');
			
		$SearchBox = new SearchInputBox($this->ct, 'esSearchBox');
		
		$fld=[];
						
		$first_fld=$fld;
		$first_field_type='';
							
		foreach($list_of_fields as $field_name_string)
		{
			if($field_name_string=='_id')
			{
				$fld=array(
					'fieldname' => '_id',
					'type' => '_id',
					'typeparams' => '',
					'fieldtitle'.$this->ct->Languages->Postfix => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ID')
				);
			}
			else
			{
				//Date search no implemented yet. It will be range search
				$fld = Fields::FieldRowByName($field_name_string,$this->ct->Table->fields);
				if($fld['type']=='date')
				{
					$fld['typeparams']='date';
					$fld['type']='range';
				}
			}

			if($first_field_type == '')
			{
				$first_field_type = $fld['type'];
				$first_fld = $fld;
			}
			else
			{
				// If field types are mixed then use string search
				if($first_field_type != $fld['type'])
					$first_field_type = 'string';
			}
		}

		$first_fld['type']=$first_field_type;

		if(count($list_of_fields)>1)
		{
			$first_fld['fields']=$list_of_fields;
			$first_fld['typeparams']='';
		}

		//Add control elements
		$fieldtitles=$this->getFieldTitles($list_of_fields);
		$field_title=implode(' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OR' ).' ',$fieldtitles);

		$cssclass='ctSearchBox';
		if($class!='')
			$cssclass.=' '.$class;
		
		if($improved)
			$cssclass.=' ct_improved_selectbox';

		$default_Action = $reload ? ' onChange="ctSearchBoxDo();"' : ' ';//action should be a space not empty or this.value=this.value    

		$objectname = $first_fld['fieldname'];
							
		$vlu = $SearchBox->renderFieldBox('es_search_box_',$objectname,$first_fld,
			$cssclass,'0',
			'',false,'',$default_Action,$field_title);//action should be a space not empty or 
		//0 because its not an edit box and we pass onChange value even " " is the value;
			
		//$vlu=str_replace('"','&&&&quote&&&&',$vlu);
								
		$field2search = $this->prepareSearchElement($first_fld);
		$vlu.= '<input type=\'hidden\' ctSearchBoxField=\''.$field2search.'\' />';
		
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	function searchbutton($class_ = '')
	{
		if($this->ct->Env->print==1 or $this->ct->Env->frmt=='csv')
			return '';
		
		$class = 'ctSearchBox';
		
		if(isset($class_) and $class_!='')
			$class.=' '.$class_;
		else
			$class.=' btn button-apply btn-primary';
                    
        //JavascriptFunction
        $vlu= '<input type=\'button\' value=\'SEARCH\' class=\''.$class.'\' onClick=\'ctSearchBoxDo()\' />';
       
        if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	function message($html, $type = 'Message')
	{
		Factory::getApplication()->enqueueMessage($html, $type);
		
		return null;
	}
	
	function navigation($list_type = 'list', $ul_css_class = '')
	{
		$PathValue = $this->CleanNavigationPath($this->ct->Filter->PathValue);
		if(count($PathValue)==0)
			return '';
		elseif($list_type=='' or $list_type=='list')
		{
			$vlu = '<ul'.($ul_css_class != '' ? ' class="'.$ul_css_class.'"' : '').'><li>'.implode('</li><li>',$PathValue).'</li></ul>';
			return new \Twig\Markup($vlu, 'UTF-8' );
		}
		elseif($list_type=='comma')
			return implode(',',$PathValue);
		else
			return 'navigation: Unknown list type';
	}
	
	protected function CleanNavigationPath($thePath)
	{
		//Returns a list of unique search path criteria - eleminates duplicates
		$newPath=array();
		if(count($thePath)==0)
			return $newPath;

		for($i=count($thePath)-1;$i>=0;$i--)
		{
			$item=$thePath[$i];
			if(count($newPath)==0)
				$newPath[]=$item;
			else
			{
				$found=false;
				foreach($newPath as $newitem)
				{
					if(!(strpos($newitem,$item)===false))
					{
						$found=true;
						break;
					}
				}

				if(!$found)
					$newPath[]=$item;
			}
		}
		return array_reverse ($newPath);
	}
	
	function format($format, $link_type = 'anchor', $image = '', $imagesize = '', $menu_item_alias = '', $csv_column_separator = ',')
	{
		//$csv_column_separator parameter is only for csv output format
		
        if($this->ct->Env->frmt=='' or $this->ct->Env->frmt=='html')
        {
			if($menu_item_alias != '')
			{
				$menu_item=JoomlaBasicMisc::FindMenuItemRowByAlias($menu_item_alias);//Accepts menu Itemid and alias
				if($menu_item!=0)
				{
					$menu_item_id=(int)$menu_item['id'];
					$link=$menu_item['link'];
				}
					
				$link.='&Itemid='.$menu_item_id;//.'&returnto='.$returnto;
			}
			else
			{
				$link=JoomlaBasicMisc::deleteURLQueryOption($this->ct->Env->current_url, 'frmt');
			}
				
			$link = Route::_($link);
				
   			//check if format supported
   			$allowed_formats=['csv','json','xml','xlsx','pdf','image'];
   			if($format=='' or !in_array($format,$allowed_formats))
				$format='csv';
				
   			$link.=(strpos($link,'?')===false ? '?' : '&').'frmt='.$format.'&clean=1';
   			$vlu='';
			
			if($format == 'csv' and $csv_column_separator != ',')
				$link.='&sep='.$csv_column_separator;

   			if($link_type=='anchor' or $link_type=='')
   			{
   				$allowed_sizes=['16','32','48'];
   				if($imagesize=='' or !in_array($imagesize,$allowed_sizes))
   					$imagesize=32;

   				if($format=='image')
   					$format_image='jpg';
   				else
   					$format_image=$format;

   				if($image=='')
   					$image='/components/com_customtables/libraries/customtables/media/images/fileformats/'.$imagesize.'px/'.$format_image.'.png';

   				$alt='Download '.strtoupper($format).' file';
   				//add image anchor link
   				$vlu = '<a href="'.$link.'" class="toolbarIcons" id="ctToolBarExport2CSV" target="_blank"><img src="'.$image.'" alt="'.$alt.'" title="'.$alt.'" width="'.$imagesize.'" height="'.$imagesize.'"></a>';
				
				if($this->isTwig)
					return new \Twig\Markup($vlu, 'UTF-8' );
				else
					return $vlu;
   			}
   			elseif($link_type == '_value' or $link_type == 'linkonly')
   			{
   				//link only
				return $link;
   			}
		}
        
		return '';
	}
	
	function captcha()
	{
		if($this->ct->Env->frmt != '' and $this->ct->Env->frmt !='html')
			return '';
			
		JHtml::_('behavior.keepalive');
			
		$p = $this->getReCaptchaParams();
		if($p == null)
		{
			Factory::getApplication()->enqueueMessage('{{ html.captcha }} - Captcha plugin not enabled.', 'error');
			return '';
		}
		
		$reCaptchaParams=json_decode($p->params);

		if($reCaptchaParams == null or $reCaptchaParams->public_key == "" or !isset($reCaptchaParams->size))
		{
			Factory::getApplication()->enqueueMessage('{{ html.captcha }} - Captcha Public Key or size not set.', 'error');
			return '';
		}

		\JPluginHelper::importPlugin('captcha');

		if($this->ct->Env->version < 4)
		{
			$dispatcher = \JEventDispatcher::getInstance();
			$dispatcher->trigger('onInit','my_captcha_div');
		}
		else
		{
			Factory::getApplication()->triggerEvent('onInit', array(null, 'my_captcha_div', 'class=""'));
		}
		
		$this->captcha_found = true;
	
		$vlu = '
    <div id="my_captcha_div"
		class="g-recaptcha"
		data-sitekey="'.$reCaptchaParams->public_key.'"
		data-theme="'.$reCaptchaParams->theme.'"
		data-size="'.$reCaptchaParams->size.'"
		data-callback="recaptchaCallback">
	</div>';
	
		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;

	}
	
	protected function getReCaptchaParams()
    {
        $db = Factory::getDBO();
		$query='SELECT params FROM #__extensions WHERE '.$db->quoteName("name").'='.$db->Quote("plg_captcha_recaptcha").' LIMIT 1';
		$db->setQuery( $query );

		$rows=$db->loadObjectList();
		if(count($rows)==0)
            return null;

        return $rows[0];
    }
	
	public function button($type = 'save', $title = '', $redirectlink = null, $optional_class = '')
	{
		if($this->ct->Env->frmt != '' and $this->ct->Env->frmt !='html' and $this->ct->Env->frmt != 'json')
			return '';
		
		if($redirectlink == null and $this->ct->Env->menu_params != null)
			$redirectlink = $this->ct->Env->menu_params->get( 'returnto' );
		
		if($redirectlink != '' and $redirectlink != null)
		{
			//TODO: delete this part, no longer needed using TWIG
			
			$_row=array();
            $_list=array();
			\tagProcessor_General::process($this->ct,$redirectlink,$_row,$_list,0);
		}
		
		$vlu = '';

		switch($type)
		{
			case 'save':
				$vlu = $this->renderSaveButton($optional_class,$title);
				break;

			case 'saveandclose':
				$vlu = $this->renderSaveAndCloseButton($optional_class,$title,$redirectlink);
				break;

			case 'saveandprint':
				$vlu = $this->renderSaveAndPrintButton($optional_class,$title,$redirectlink);
                break;

			case 'saveascopy':
				
				if($this->ct->Table->record['listing_id'] == 0)
					$vlu = '';
				else
					$vlu = $this->renderSaveAsCopyButton($optional_class,$title,$redirectlink);
				break;

			case 'cancel':
				$vlu = $this->renderCancelButton($optional_class,$title,$redirectlink);
                break;

			case 'close':
				$vlu = $this->renderCancelButton($optional_class,$title,$redirectlink);
				break;
                                    
			case 'delete':
				$vlu = $this->renderDeleteButton($captcha_found,$optional_class,$title,$redirectlink);
				break;

			default:
				$vlu = '';

		}//switch

		//Not clear where and how this variable used.
		if($this->ct->Env->frmt == 'json')
		{
			$this->button_objects[] = ['type' => $type, 'title' => $b, 'redirectlink' => $redirectlink];
			return $title;
		}

		if($this->isTwig)
			return new \Twig\Markup($vlu, 'UTF-8' );
		else
			return $vlu;
	}
	
	protected function renderSaveButton($optional_class,$title)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVE');
			
		if($this->ct->Env->frmt == 'json')
			return $title;

        $attribute='';
        if($this->captcha_found)
            $attribute=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';
        
        $onclick='setTask(event, "saveandcontinue","'.$this->ct->Env->encoded_current_url.'",true);';
		
		return '<input id="customtables_button_save" type="submit" class="'.$the_class.' validate"'.$attribute.' onClick=\''.$onclick.'\' value="'.$title.'">';
    }
    
    protected function renderSaveAndCloseButton($optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title= JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVEANDCLOSE');
			
		if($this->ct->Env->frmt == 'json')
			return $title;
			
        $attribute='onClick=\'';
        
        $attribute.='setTask(event, "save","'.base64_encode ($redirectlink).'",true);';
            
        $attribute.='\'';
        
		if($this->captcha_found)
            $attribute.=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';

        
        return '<input id="customtables_button_saveandclose" type="submit" '.$attribute.' class="'.$the_class.' validate" value="'.$title.'" />';
    }
    
    protected function renderSaveAndPrintButton($optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NEXT');
			
		if($this->ct->Env->frmt == 'json')
			return $title;
			
        $attribute='onClick=\'';
        $attribute='setTask(event, "saveandprint","'.base64_encode ($redirectlink).'",true);';
        $attribute.='\'';
        
        if($this->captcha_found)
            $attribute=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';
        
        return '<input id="customtables_button_saveandprint" type="submit" '.$attribute.' class="'.$the_class.' validate" value="'.$title.'" />';
    }
    
    protected function renderSaveAsCopyButton($optional_class,$title,$redirectlink)
    {
		if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVEASCOPYANDCLOSE');
			
		if($this->ct->Env->frmt == 'json')
			return $title;
			
        $attribute='';//onClick="return checkRequiredFields();"';
        if($this->captcha_found)
            $attribute=' disabled="disabled"';
            
        if($optional_class!='')
			$the_class=$optional_class;//$the_class='ctEditFormButton '.$optional_class;
		else
			$the_class='ctEditFormButton btn button-apply btn-success';
            
        $onclick='setTask(event, "saveascopy","'.base64_encode ($redirectlink).'",true);';
        
        return '<input id="customtables_button_saveandcopy" type="submit" class="'.$the_class.' validate"'.$attribute.' onClick=\''.$onclick.'\' value="'.$title.'">';
    }
    
    protected function renderCancelButton($optional_class,$title,$redirectlink)
    {
		if($this->ct->Env->isModal)
			return '';
			
        if($title=='')
            $title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CANCEL');
		
		if($this->ct->Env->frmt == 'json')
			return $title;
            
        if($optional_class!='')
            $cancel_class=$optional_class;//$cancel_class='ctEditFormButton '.$optional_class;
        else
          	$cancel_class='ctEditFormButton btn button-cancel';

        $onclick='setTask(event, "cancel","'.base64_encode ($redirectlink).'",true);';
    	return '<input id="customtables_button_cancel" type="button" class="'.$cancel_class.'" value="'.$title.'" onClick=\''.$onclick.'\'>';
    }
    
    protected function renderDeleteButton($optional_class,$title,$redirectlink)
    {
        if($title=='')
			$title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE');
				
		if($this->ct->Env->frmt == 'json')
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
    
}
