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

use \Joomla\CMS\Factory;

class Languages
{
	var $LanguageList;
	var $Postfix;
	
	function __construct()
	{
		$this->LanguageList = $this->getLanguageList();
		$this->Postfix = $this->getLangPostfix();
	}
	
	function getLangPostfix()
	{
		$langObj=Factory::getLanguage();
		$nowLang=$langObj->getTag();
		$index=0;
		foreach($this->LanguageList as $lang)
		{
			if($lang->language==$nowLang)
			{
				if($index==0)
					return '';
				else
					return '_'.$lang->sef;
			}
			
			$index++;
		}
		return '';
	}
	
	function getLanguageList()
	{
		$db = Factory::getDBO();
		
		$query ='SELECT lang_id AS id, lang_code AS language, title AS caption, title, sef AS original_sef FROM #__languages WHERE published=1 ORDER BY lang_id';
		$db->setQuery( $query );
		
		$rows = $db->loadObjectList();
		
		$this->LanguageList = array();
		foreach($rows as $row)
		{
			$parts=explode('-',$row->original_sef);
			$row->sef = $parts[0];
			$this->LanguageList[] = $row;
		}
		
		return $this->LanguageList;
	}

	function getLanguageTagByID($id)
	{
		
		foreach($this->LanguageList as $lang)
		{
			if($lang->id==$id)
				return $lang->language;
		}
		return '';
	}

	function getLanguageByCODE($code)
	{
		$db = JFactory::getDBO();
		
		$query = ' SELECT lang_id AS id FROM #__languages WHERE lang_code="'.$code.'" LIMIT 1';

		$db->setQuery( $query );
		$rows= $db->loadObjectList();
		if(count($rows)!=1)
			return -1;
		
		return $rows[0]->id;
	}	
}
