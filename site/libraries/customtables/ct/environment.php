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

use CustomTables\Languages;

use \JoomlaBasicMisc;

use \Joomla\CMS\Version;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;

class Environment
{
	var $version;
	var $current_url;
	var $encoded_current_url;
	var $userid;
	var $isUserAdministrator;
	var $print;
	var $clean;
	var $frmt;
	var $WebsiteRoot;
	var $advancedtagprocessor;
	
	function __construct()
	{
		$version_object = new Version;
		$this->version = (int)$version_object->getShortVersion();
		
		$jinput=Factory::getApplication()->input;

		$this->current_url=JoomlaBasicMisc::curPageURL();
		$this->encoded_current_url=base64_encode($this->current_url);

		$user = Factory::getUser();
		$this->userid=$user->id;

		$this->isUserAdministrator=JoomlaBasicMisc::isUserAdmin($this->userid);
		$this->print=(bool)$jinput->getInt('print',0);
		$this->clean=(bool)$jinput->getInt('clean',0);
		$this->frmt=$jinput->getCmd('frmt','html');
		if($jinput->getCmd('layout','') == 'json')
			$this->frmt = 'json';
		
		$mainframe = Factory::getApplication();
		if($mainframe->getCfg( 'sef' ))
		{
			$this->WebsiteRoot=Uri::root(true);
			if($this->WebsiteRoot=='' or $this->WebsiteRoot[strlen($this->WebsiteRoot)-1]!='/') //Root must have slash / in the end
				$this->WebsiteRoot.='/';
		}
		else
			$this->WebsiteRoot='';
			
			$this->advancedtagprocessor=false;
		
		$phptagprocessor=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
		if(file_exists($phptagprocessor))
		{
			//require_once($phptagprocessor);
			$this->advancedtagprocessor=true;
			
			
			$file=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
			if(file_exists($file))
				require_once($file);
			
		
			$file=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'customphp.php';
			if(file_exists($file))
				require_once($file);
		}
	}
}
