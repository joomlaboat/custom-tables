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
	var $user;
	var $isUserAdministrator;
	var $print;
	var $clean;
	var $frmt;
	var $WebsiteRoot;
	var $advancedtagprocessor;
	var $jinput;
	var $isMobile;
	
	function __construct()
	{
		$version_object = new Version;
		$this->version = (int)$version_object->getShortVersion();
		
		$this->jinput=Factory::getApplication()->input;

		$this->current_url=JoomlaBasicMisc::curPageURL();
		$this->encoded_current_url=base64_encode($this->current_url);

		$this->user = Factory::getUser();
		$this->userid=$this->user->id;


		$this->isUserAdministrator=JoomlaBasicMisc::isUserAdmin($this->userid);
		$this->print=(bool)$this->jinput->getInt('print',0);
		$this->clean=(bool)$this->jinput->getInt('clean',0);
		$this->frmt=$this->jinput->getCmd('frmt','html');
		if($this->jinput->getCmd('layout','') == 'json')
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
		
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'protagprocessor' . DIRECTORY_SEPARATOR;
		$phptagprocessor=$path . 'phptags.php';
		if(file_exists($phptagprocessor))
		{
			$this->advancedtagprocessor=true;

			require_once($phptagprocessor);
			require_once($path . 'customphp.php');
			require_once($path . 'servertags.php');
		}
		
		$this->isMobile = $this->check_user_agent('mobile');
	}
	
	
	/* USER-AGENTS ================================================== */
	//http://stackoverflow.com/questions/6524301/detect-mobile-browser
	protected function check_user_agent ( $type = NULL )
	{
        $user_agent = strtolower ( $_SERVER['HTTP_USER_AGENT'] );
        if ( $type == 'bot' ) {
                // matches popular bots
                if ( preg_match ( "/googlebot|adsbot|yahooseeker|yahoobot|msnbot|watchmouse|pingdom\.com|feedfetcher-google/", $user_agent ) ) {
                        return true;
                        // watchmouse|pingdom\.com are "uptime services"
                }
        } else if ( $type == 'browser' ) {
                // matches core browser types
                if ( preg_match ( "/mozilla\/|opera\//", $user_agent ) ) {
                        return true;
                }
        } else if ( $type == 'mobile' ) {
                // matches popular mobile devices that have small screens and/or touch inputs
                // mobile devices have regional trends; some of these will have varying popularity in Europe, Asia, and America
                // detailed demographics are unknown, and South America, the Pacific Islands, and Africa trends might not be represented, here
                if ( preg_match ( "/phone|iphone|itouch|ipod|symbian|android|htc_|htc-|palmos|blackberry|opera mini|iemobile|windows ce|nokia|fennec|hiptop|kindle|mot |mot-|webos\/|samsung|sonyericsson|^sie-|nintendo/", $user_agent ) ) {
                        // these are the most common
                        return true;
                } else if ( preg_match ( "/mobile|pda;|avantgo|eudoraweb|minimo|netfront|brew|teleca|lg;|lge |wap;| wap /", $user_agent ) ) {
                        // these are less common, and might not be worth checking
                        return true;
                }
        }
        return false;
	}
	
	protected static function check_user_agent_for_apple ()
	{
		$user_agent = strtolower ( $_SERVER['HTTP_USER_AGENT'] );
                if ( preg_match ( "/iphone|itouch|ipod|ipad/", $user_agent ) ) {
                        // these are the most common
                        return true;
                }

		return false;
	}

	protected static function check_user_agent_for_ie ()
	{
		$u=$_SERVER['HTTP_USER_AGENT'];
		if(strpos($u, 'MSIE') !== FALSE)
			return true;
		elseif(strpos($u, 'Trident') !== FALSE)
			return true;

		return false;
	}
}
