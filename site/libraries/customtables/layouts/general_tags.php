<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
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

class Twig_Fields_Tags
{
	var $ct;
	var $isTwig;

	function __construct(&$ct,$isTwig = true)
	{
		$this->ct = $ct;
		$this->isTwig = $isTwig;
	}

	function json()//wizard ok
	{
		return json_encode(Fields::shortFieldObjects($this->ct->Table->fields));
	}
	
	function list($param = 'fieldname')//wizard ok
	{
		$available_params = ['fieldname','title','defaultvalue','description','isrequired','isdisabled','type','typeparams','valuerule','valuerulecaption'];
		
		if(!in_array($param, $available_params))
		{
			Factory::getApplication()->enqueueMessage('{{ fields.array("'.$param.'") }} - Unknow parameter.', 'error');
			return '';
		}
			
		$fields = Fields::shortFieldObjects($this->ct->Table->fields);
		$list = [];
		foreach($fields as $field)
			$list[] = $field[$param];
		
		return $list;
	}

	function count()//wizard ok
	{
		return count($this->ct->Table->fields);
	}
}

class Twig_User_Tags
{
	var $user_id;

	function __construct()
	{
		$user = Factory::getUser();
		$this->user_id = (int)$user->get('id');
	}
	
	function name($user_id = 0)//wizard ok
	{
		if($user_id == 0)
			$user_id = $this->user_id;
		
		if($user_id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($user_id);
		return $user_row->name;
	}
	
	function username($user_id = 0)//wizard ok
	{
		if($user_id == 0)
			$user_id = $this->id;
		
		if($user_id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($user_id);
		return $user_row->username;
	}
	
	function email($user_id = 0)//wizard ok
	{
		if($user_id == 0)
			$user_id = $this->user_id;
		
		if($user_id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($user_id);
		return $user_row->email;
	}
	
	function id()//wizard ok
	{
		if($this->user_id == 0)
			return 0;
		
		return $this->user_id;
	}
	
	function lastvisitdate($user_id = 0)//wizard ok
	{
		if($user_id == 0)
			$user_id = $this->user_id;
		
		if($user_id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($user_id);
		
		if($user_row->lastvisitDate == '0000-00-00 00:00:00')
			return 'Never';
		else
			return $user_row->lastvisitDate;
	}
	
	function registerdate($user_id = 0)//wizard ok
	{
		if($user_id == 0)
			$user_id = $this->user_id;
		
		if($user_id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($user_id);
		
		if($user_row->registerDate == '0000-00-00 00:00:00')
			return 'Never';
		else
			return $user_row->registerDate;
	}
	
	function usergroups($user_id = 0)//wizard ok
	{
		if($user_id == 0)
			$user_id = $this->user_id;
		
		if($user_id == 0)
			return '';
		
		return explode(',',CTUser::GetUserGroups($user_id));
	}
	
}

class Twig_Url_Tags
{
	var $ct;
	var $isTwig;
	var $jinput;
	
	function __construct(&$ct,$isTwig = true)
	{
		$this->ct = $ct;
		$this->isTwig = $isTwig;
		$this->jinput=Factory::getApplication()->input;
	}
	
	function link()//wizard ok
	{
		return $this->ct->Env->current_url;
	}
	
	function base64()//wizard ok
	{
		return $this->ct->Env->encoded_current_url;
	}
	
	function root($includehost = false,$addtrailingslash = true)//wizard ok
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
	
	function getInt($param,$default = 0)//wizard ok
	{
		return $this->jinput->getInt($param,$default);
	}
	
	function getString($param,$default = '')//wizard ok
	{
		return $this->jinput->getString($param,$default);
	}
	
	function getUInt($param,$default = 0)//wizard ok
	{
		return $this->jinput->get($param,$default,'UINT');
	}
	
	function getFloat($param,$default = 0)//wizard ok
	{
		return $this->jinput->getFloat($param,$default);
	}
	
	function getWord($param,$default = '')//wizard ok
	{
		return $this->jinput->get($param,$default,'WORD');
	}
	
	function getAlnum($param,$default = '')//wizard ok
	{
		return $this->jinput->getCmd($param,$default);
	}
	
	function getCmd($param,$default = '')//wizard ok
	{
		return $this->jinput->getCmd($param,$default);
	}
	
	function getStringAndEncode($param,$default = '')//wizard ok
	{
		return base64_encode(strip_tags($this->jinput->getString($param,$default)));
	}
	
	function getStringAndDecode($param,$default = '')//wizard ok
	{
		return strip_tags(base64_decode($this->jinput->getString($param,$default)));
	}
	
	function Itemid()//wizard ok
	{
		return $this->jinput->getInt('Itemid',0);
	}
	
	function set($option, $param='')//wizard ok
	{
		$this->jinput->set($option,$param);
	}
		
	function server($param)//wizard ok
	{
		return $_SERVER[$param];
	}
	
	function format($format, $link_type = 'anchor', $image = '', $imagesize = '', $menu_item_alias = '', $csv_column_separator = ',')//wizard ok
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
}

class Twig_Document_Tags
{
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
	function setmetakeywords($metakeywords)//wizard ok
	{
		$doc = Factory::getDocument();
		$doc->setMetaData( 'keywords', $metakeywords );
	}

	function setmetadescription($metadescription)//wizard ok
	{
		$doc = Factory::getDocument();
		$doc->setMetaData( 'description', $metadescription );
	}

	function setpagetitle($pagetitle)//wizard ok
	{
		$doc = Factory::getDocument();
        $doc->setTitle(JoomlaBasicMisc::JTextExtended($pagetitle));
	}

    function setheadtag($headtag)//wizard ok
	{
		$doc = Factory::getDocument();
		$doc->addCustomTag($headtag);
	}
	
	function layout($layoutname, $ProcessContentPlugins = false)//wizard ok
	{
		$l =  new Layouts($this->ct);
		$layout = $l->getLayout($layoutname);
		
		$twig = new TwigProcessor($this->ct, '{% autoescape false %}'.$layout.'{% endautoescape %}');
		$layout = $twig->process();
			
		if($ProcessContentPlugins)
			LayoutProcessor::applyContentPlugins($layout);
            
		return $layout;
	}
	
	function sitename()//wizard ok
	{
		return Factory::getApplication()->get('sitename');
	}
	
	function languagepostfix()//wizard ok
	{
		return $this->ct->Languages->Postfix;
	}
}

class Twig_Text_Tags
{
	function base64encode($str)
	{
		return base64_encode($str);
	}
}