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

use \JoomlaBasicMisc;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;

class Twig_Record_Tags
{
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
	function id()
	{
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.id }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Table->record))
		{
			Factory::getApplication()->enqueueMessage('{{ record.id }} - Record not loaded.', 'error');
			return '';
		}
		
		return (int)$this->ct->Table->record['listing_id'];
	}
	
	function published()
	{
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.published }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Table->record))
		{
			Factory::getApplication()->enqueueMessage('{{ record.published }} - Record not loaded.', 'error');
			return '';
		}
		
		return (int)$this->ct->Table->record['listing_published'];
	}
	
	function number()
	{
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.number }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Table->record))
		{
			Factory::getApplication()->enqueueMessage('{{ record.number }} - Record not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Table->record['_number']))
		{
			Factory::getApplication()->enqueueMessage('{{ record.number }} - Record number not set.', 'error');
			return '';
		}
		
		return (int)$this->ct->Table->record['_number'];
	}
	
	function count($full_sentence = false)
	{
		if($this->ct->Env->frmt == 'csv')
			return '';	
			
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.count }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Records))
		{
			Factory::getApplication()->enqueueMessage('{{ record.count }} - Records not loaded.', 'error');
			return '';
		}
		
		if($full_sentence)
		{
			$vlu = '<span class="ctCatalogRecordCount">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FOUND' ).': '.$this->ct->Table->recordcount
				.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RESULT_S' ).'</span>';
				
			return new \Twig\Markup($vlu, 'UTF-8' );
		}
		else
			return $this->ct->Table->recordcount;
	}
	
	function list()
	{
		if($this->ct->Env->frmt == 'csv')
			return '';	
			
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.count }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Records))
		{
			Factory::getApplication()->enqueueMessage('{{ record.list }} - Records not loaded.', 'error');
			return '';
		}
		
		if($this->ct->Table->recordlist == null)
			$this->ct->getRecordList();
		
		return implode(',',$this->ct->Table->recordlist);
	}
}

class Twig_Field_Tags
{
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
	function json()
	{
		return json_encode(Fields::shortFieldObjects($this->ct->Table->fields));
	}
	
	function length()
	{
		return count($this->ct->Table->fields);
	}
}

class Twig_User_Tags
{
	var $id;

	function __construct()
	{
		$user = Factory::getUser();
		$this->id=(int)$user->get('id');
	}
	
	function name($id = 0)
	{
		if($id == 0)
			$id = $this->id;
		
		if($id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($id);
		return $user_row->name;
	}
	
	function username($id = 0)
	{
		if($id == 0)
			$id = $this->id;
		
		if($id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($id);
		return $user_row->username;
	}
	
	function email($id = 0)
	{
		if($id == 0)
			$id = $this->id;
		
		if($id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($id);
		return $user_row->email;
	}
	
	function id()
	{
		if($this->id == 0)
			return 0;
		
		return $this->id;
	}
	
	function lastvisitDate($id = 0)
	{
		if($id == 0)
			$id = $this->id;
		
		if($id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($id);
		
		if($user_row->lastvisitDate == '0000-00-00 00:00:00')
			return 'Never';
		else
			return $user_row->lastvisitDate;
	}
	
	function registerDate($id = 0)
	{
		if($id == 0)
			$id = $this->id;
		
		if($id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($id);
		
		if($user_row->registerDate == '0000-00-00 00:00:00')
			return 'Never';
		else
			return $user_row->registerDate;
	}
	
	function usergroups($id = 0)
	{
		if($id == 0)
			$id = $this->id;
		
		if($id == 0)
			return '';
		
		return CTUser::GetUserGroups($id);
	}
	
}

class Twig_Url_Tags
{
	var $ct;
	var $jinput;
	
	function __construct(&$ct)
	{
		$this->ct = $ct;
		$this->jinput=Factory::getApplication()->input;
	}
	
	function link()
	{
		return $this->ct->Env->current_url;
	}
	
	function base64()
	{
		return $this->ct->Env->encoded_current_url;
	}
	
	function root($includehost = false,$addtrailingslash = true)
	{
        if((bool)$includehost)
            $WebsiteRoot=Uri::root(false);
        else
			$WebsiteRoot=Uri::root(true);
			
		if((bool)$addtrailingslash)
        {
            if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
             	$WebsiteRoot.='/';    
        }
		else
        {
            $l=strlen($WebsiteRoot);
            if($WebsiteRoot!='' and $WebsiteRoot[$l-1]=='/')
                $WebsiteRoot=substr($WebsiteRoot,0,$l-1);//delete trailing slash
        }
			
        return $WebsiteRoot;
	}
	
	function getInt($param,$default = 0)
	{
		return $this->jinput->getInt($param,$default);
	}
	
	function getString($param,$default = '')
	{
		return $this->jinput->getString($param,$default);
	}
	
	function getUInt($param,$default = 0)
	{
		return $this->jinput->get($param,$default,'UINT');
	}
	
	function getFloat($param,$default = 0)
	{
		return $this->jinput->getFloat($param,$default);
	}
	
	function getWord($param,$default = '')
	{
		return $this->jinput->get($param,$default,'WORD');
	}
	
	function getAlnum($param,$default = '')
	{
		return $this->jinput->getCmd($param,$default);
	}
	
	function getCmd($param,$default = '')
	{
		return $this->jinput->getCmd($param,$default);
	}
	
	function getStringAndEncode($param,$default = '')
	{
		return base64_encode(strip_tags($this->jinput->getString($param,$default)));
	}
	
	function getStringAndDecode($param,$default = '')
	{
		return strip_tags(base64_decode($this->jinput->getString($param,$default)));
	}
	
	function Itemid()
	{
		return $this->jinput->getInt('Itemid',0);
	}
	
	function set($option, $param='')
	{
		$this->jinput->set($option,$param);
	}
		
	function server($param)
	{
		return $_SERVER[$param];
	}
}

class Twig_Html_Tags
{
	var $ct;
	var $jinput;

	function __construct(&$ct)
	{
		$this->ct = $ct;
		$this->jinput=Factory::getApplication()->input;
	}
	
	function goback($label='Go Back', $image_icon='components/com_customtables/images/arrow_rtl.png', $attribute='',  $returnto = '')
	{
		if($this->ct->Env->print==1)
            $gobackbutton='';
				
		if($returnto == '')
			$returnto = base64_decode($this->jinput->get('returnto','','BASE64'));
		
		if($returnto == '')
			return '';
		
		if($attribute == '')
			$attribute = 'class="ct_goback"';
		
		$vlu = '<a href="'.$returnto.'" '.$attribute.'><div>'.$label.'</div></a>';
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	function print($class='ctEditFormButton btn button')
	{
		$link=$this->ct->Env->current_url.(strpos($this->ct->Env->current_url,'?')===false ? '?' : '&').'tmpl=component&print=1';

		if($this->jinput->getInt('moduleid',0)!=0)
		{
			//search module

			$moduleid = $this->jinput->getInt('moduleid',0);
			$link.='&moduleid='.$moduleid;

			//keyword search
			$inputbox_name='eskeysearch_'.$moduleid ;
			$link.='&'.$inputbox_name.'='.$this->jinput->getString($inputbox_name,'');
		}

		if($this->ct->Env->print==1)
		{
				$vlu='<p><a href="#" onclick="window.print();return false;"><img src="'.URI::root(true).'/components/com_customtables/images/printButton.png" alt="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT').'"  /></a></p>';
		}
		else
		{
			$vlu='<input type="button" class="'.$class.'" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT' ).'" onClick=\'window.open("'.$link.'","win2","status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no"); return false; \'> ';
        }
			
		return new \Twig\Markup($vlu, 'UTF-8' );
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
			Factory::getApplication()->enqueueMessage('Search box: Field name not found.', 'error');
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
		
		return new \Twig\Markup($vlu, 'UTF-8' );
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
       
        return new \Twig\Markup($vlu, 'UTF-8' );
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
					
				$link.='&Itemid='.$menu_item_id;//.'&amp;returnto='.$returnto;
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
   					$image='/components/com_customtables/images/fileformats/'.$imagesize.'px/'.$format_image.'.png';

   				$alt='Download '.strtoupper($format).' file';
   				//add image anchor link
   				$vlu = '<a href="'.$link.'" class="toolbarIcons" id="ctToolBarExport2CSV" target="_blank"><img src="'.$image.'" alt="'.$alt.'" title="'.$alt.'" width="'.$imagesize.'" height="'.$imagesize.'"></a>';
				return new \Twig\Markup($vlu, 'UTF-8' );
   			}
   			elseif($link_type == '_value' or $link_type == 'linkonly')
   			{
   				//link only
				return $link;
   			}
		}
        
		return '';
	}
	
}

class Twig_Document_Tags
{
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
	function setMetaKeywords($metakeywords)
	{
		$doc = Factory::getDocument();
		$doc->setMetaData( 'keywords', $metakeywords );
	}

	function setMetaDescription($metadescription)
	{
		$doc = Factory::getDocument();
		$doc->setMetaData( 'description', $metadescription );
	}

	function setPageTitle($pagetitle)
	{
		$doc = Factory::getDocument();
        $doc->setTitle(JoomlaBasicMisc::JTextExtended($pagetitle));
	}

    function setHeadTag($headtag)
	{
		$doc = Factory::getDocument();
		$doc->addCustomTag($headtag);
	}
	
	function layout($layoutname, $ProcessContentPlugins = false)
	{
		$l =  new Layouts($this->ct);
		$layout = $l->getLayout($layoutname);
			
		if($ProcessContentPlugins)
			LayoutProcessor::applyContentPlugins($layout);
            
		return $layout;
	}
}
