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
	var $user_id;

	function __construct()
	{
		$user = Factory::getUser();
		$this->user_id = (int)$user->get('id');
	}
	
	function name($user_id = 0)
	{
		if($user_id == 0)
			$user_id = $this->user_id;
		
		if($user_id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($user_id);
		return $user_row->name;
	}
	
	function username($user_id = 0)
	{
		if($user_id == 0)
			$user_id = $this->id;
		
		if($user_id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($user_id);
		return $user_row->username;
	}
	
	function email($user_id = 0)
	{
		if($user_id == 0)
			$user_id = $this->user_id;
		
		if($user_id == 0)
			return '';
		
		$user_row = (object)CTUser::GetUserRow($user_id);
		return $user_row->email;
	}
	
	function id()
	{
		if($this->user_id == 0)
			return 0;
		
		return $this->user_id;
	}
	
	function lastvisitDate($user_id = 0)
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
	
	function registerDate($user_id = 0)
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
	
	function usergroups($user_id = 0)
	{
		if($user_id == 0)
			$user_id = $this->user_id;
		
		if($user_id == 0)
			return '';
		
		return CTUser::GetUserGroups($user_id);
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
