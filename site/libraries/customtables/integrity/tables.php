<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage integrity/tables.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\Integrity;
 
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;

use \ESTables;

class IntegrityTables extends \CustomTables\IntegrityChecks
{
	public static function checkTables(&$ct)
	{
		$tables = IntegrityTables::getTables();

		IntegrityTables::checkIfTablesExists($tables);

		$result = [];

		foreach($tables as $table)
		{
			//$link=JURI::root().'administrator/index.php?option=com_customtables&view=databasecheck&tableid='.$table['id'];
			$link=Uri::root().'administrator/index.php?option=com_customtables&view=databasecheck&tableid='.$table['id'];
			
			$content = IntegrityFields::checkFields($ct,$table['id'],$table['tablename'],$table['tabletitle'],$table['customtablename'],$link);	
		
			IntegrityOptions::checkOptions($ct);
		
			$zeroId=IntegrityTables::getZeroRecordID($table['realtablename'],$table['realidfieldname']);
		
			if($content !='' or $zeroId>0)
			{
				$result[]='<p><span style="font-size:1.3em;">'.$table['tabletitle'].'</span><br/><span style="color:gray;">'.$table['realtablename'].'</span></p>'
					.$content
					.($zeroId > 0 ? '<p style="font-size:1.3em;color:red;">Records with ID = 0 found. Please fix it manually.</p>' : '');
			}
		}
		
		return $result;
	}
	
	protected static function getTables()
	{
		// Create a new query object.
		$db = Factory::getDBO();
		$query = IntegrityTables::getTablesQuery();
		
		$db->setQuery( $query );
		$rows = $db->loadAssocList();
		
		return $rows;
	}
	
	protected static function getTablesQuery()
	{
		// Create a new query object.
		$db = Factory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$categoryname='(SELECT categoryname FROM #__customtables_categories AS categories WHERE categories.id=a.tablecategory LIMIT 1)';
		$fieldcount='(SELECT COUNT(fields.id) FROM #__customtables_fields AS fields WHERE fields.tableid=a.id AND fields.published=1 LIMIT 1)';
		
		$selects=array();
		$selects[]=ESTables::getTableRowSelects();
		$selects[]=$categoryname.' AS categoryname';
		$selects[]=$fieldcount.' AS fieldcount';
		
		$query->select(implode(',',$selects));

		// From the customtables_item table
		$query->from($db->quoteName('#__customtables_tables', 'a'));
		$query->where('a.published = 1');
		
		// Add the list ordering clause.
		$orderCol = 'tablename';
		$orderDirn = 'asc';
		$query->order($db->escape($orderCol . ' ' . $orderDirn));
		
		return $query;
	}
	
	protected static function checkIfTablesExists($tables_rows)
	{
		$conf = Factory::getConfig();
		$dbprefix = $conf->get('dbprefix');

		foreach($tables_rows as $row)
		{
			if(!ESTables::checkIfTableExists($dbprefix.'customtables_table_'.$row['tablename']))
			{
				$conf = Factory::getConfig();
				$database = $conf->get('db');
				$dbprefix = $conf->get('dbprefix');
		
				if(ESTables::createTableIfNotExists($database,$dbprefix,$row['tablename'],$row['tabletitle'],$row['customtablename']))
				{
					Factory::getApplication()->enqueueMessage('Table "'.$row['tabletitle'].'" created.','notice');
				}
			}
		}
	}
	
	protected static function getZeroRecordID($realtablename,$realidfieldname)
	{
		// Create a new query object.
		$db = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select('COUNT('.$realidfieldname.') AS cd_zeroIdRecords');
		$query->from($db->quoteName($realtablename, 'a'));
		$query->where($realidfieldname.' = 0');
		$query->setLimit(1);
		
		$db->setQuery( $query );
		$rows = $db->loadAssocList();
		$row=$rows[0];
		
		return $row['cd_zeroIdRecords'];
	}
}