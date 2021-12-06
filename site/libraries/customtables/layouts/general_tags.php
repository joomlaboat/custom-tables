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

use \JoomlaBasicMisc;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;

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
		
		return '<a href="'.$returnto.'" '.$attribute.'><div>'.$label.'</div></a>';
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
			
		return $vlu;
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
   				return '<a href="'.$link.'" class="toolbarIcons" id="ctToolBarExport2CSV" target="_blank"><img src="'.$image.'" alt="'.$alt.'" title="'.$alt.'" width="'.$imagesize.'" height="'.$imagesize.'"></a>';
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
