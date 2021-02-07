<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

JHTML::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers');

class ESLogs
{

	public static function save($tableid,$listing_id,$action)
	{
		// 1 - New
		// 2 - Edit
		// 3 - Publish
		// 4 - Unpublish
		// 5 - Delete
		// 6 - Image Uploaded
		// 7 - Image Deleted
		// 8 - File Uploaded
		// 9 - File Deleted

		$user = JFactory::getUser();
		$userid = (int)$user->get('id');

		$db = JFactory::getDBO();

		$sets=array();
		$sets[]=(int)$userid;
		$sets[]='NOW()';
		$sets[]=(int)$tableid;
		$sets[]=(int)$listing_id;
		$sets[]=(int)$action;
		$sets[]=(int)JFactory::getApplication()->input->get('Itemid',0,'INT');

		$query = 'INSERT INTO #__customtables_log (userid,datetime,tableid,listingid,action,Itemid) VALUES ('.implode(',',$sets).')';

		$db->setQuery($query);
		$db->execute();	
	}
}
