<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class ESTables
{

	//This function works with MySQL not PostgreeSQL
	public static function getTableStatus($database,$dbprefix,$tablename)
	{
		$db = JFactory::getDBO();
		$query = 'SHOW TABLE STATUS FROM '.$db->quoteName($database).' LIKE '.$db->quote($dbprefix.'customtables_table_'.$tablename);
		$db->setQuery( $query );

		if (!$db->query()) {
			$this->setError( $db->getErrorMsg() );
			return false;
		}

		return $db->loadObjectList();
	}

	public static function checkIfTableExists($mysqltablename)
	{
		$conf = JFactory::getConfig();
		$database = $conf->get('db');

		$db = JFactory::getDBO();

		$mysqltablename=str_replace('#__',$db->getPrefix(),$mysqltablename);

		if($db->serverType == 'postgresql')
			$query = 'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_name = '.$db->quote($mysqltablename).' LIMIT 1';
		else
			$query = 'SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = '.$db->quote($database).' AND table_name = '.$db->quote($mysqltablename).' LIMIT 1';
		
		$db->setQuery( $query );
		$rows = $db->loadObjectList();

		$c=(int)$rows[0]->c;
		if($c>0)
			return true;

		return false;
	}

	public static function getTableName($tableid = 0)
	{
		$db = JFactory::getDBO();

		$jinput = JFactory::getApplication()->input;

		if($tableid==0)
			$tableid=JFactory::getApplication()->input->get('tableid',0,'INT');

		$query = 'SELECT tablename FROM #__customtables_tables AS s WHERE id='.(int)$tableid.' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return '';

		return $rows[0]->tablename;
	}
	
	public static function getRealTableName($tableid = 0)
	{
		$db = JFactory::getDBO();

		$jinput = JFactory::getApplication()->input;

		if($tableid==0)
			$tableid=JFactory::getApplication()->input->get('tableid',0,'INT');

		$query = 'SELECT tablename, customtablename FROM #__customtables_tables AS s WHERE id='.(int)$tableid.' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return '';

		$row = $rows[0];
		if($row->customtablename !='')
			return $row->customtablename;

		return '#__customtables_table_'.$row->tablename;
	}

	public static function getTableID($tablename)
	{
		if(strpos($tablename,'"')!==false)
			return 0;

		$db = JFactory::getDBO();

		if($tablename=='')
			return 0;

		$query = 'SELECT id FROM #__customtables_tables AS s WHERE tablename='.$db->quote($tablename).' LIMIT 1';

		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return 0;

		return $rows[0]->id;
	}

	public static function getTableRowByID($tableid)
	{
		$db = JFactory::getDBO();

		if($tableid==0)
			return 0;

		$query = 'SELECT * FROM #__customtables_tables AS s WHERE id='.(int)$tableid.' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return 0;

		return $rows[0];
	}

	public static function getTableRowByIDAssoc($tableid)
	{
		$db = JFactory::getDBO();

		if($tableid==0)
			return 0;

		$query = 'SELECT * FROM #__customtables_tables AS s WHERE id='.(int)$tableid.' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return 0;

		return $rows[0];
	}

	public static function getTableRowByName($tablename = '')
	{
		$db = JFactory::getDBO();

		if($tablename=='')
			return 0;

		$query = 'SELECT * FROM #__customtables_tables AS s WHERE tablename='.$db->quote($tablename).' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return '';

		return $rows[0];
	}
	public static function getTableRowByNameAssoc($tablename = '')
	{
		$db = JFactory::getDBO();

		if($tablename=='')
			return 0;

		$query = 'SELECT * FROM #__customtables_tables AS s WHERE tablename='.$db->quote($tablename).' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return '';

		return $rows[0];
	}
}
