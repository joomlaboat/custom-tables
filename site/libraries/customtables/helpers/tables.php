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

		return $db->loadObjectList();
	}
	
	public static function checkComponentTable($realtablename, $projected_fields)
	{
		$db = JFactory::getDBO();
		
		$ExistingFields=ESFields::getExistingFields($realtablename, false);
		
		if($db->serverType == 'postgresql')
			$type_field_name='postgresql_type';
		else
			$type_field_name='mysql_type';
		
		foreach($projected_fields as $projected_field)
		{
			$proj_field=$projected_field['name'];
			$fieldtype=$projected_field[$type_field_name];
        
			ESFields::checkField($ExistingFields,$realtablename,$projected_field['name'],$projected_field[$type_field_name]);
		}
	}
	
	public static function checkComponentTables()
	{
		$tables_projected_fields=array();
		$tables_projected_fields[]=['name'=>'id','mysql_type'=>'INT UNSIGNED NOT NULL AUTO_INCREMENT','postgresql_type'=>'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_tables_seq\')'];
//		$tables_projected_fields[]=['name'=>'asset_id','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'customphp','mysql_type'=>'VARCHAR(1024) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(1024) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'description','mysql_type'=>'TEXT NULL DEFAULT NULL','postgresql_type'=>'TEXT NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'tablecategory','mysql_type'=>'INT NULL DEFAULT NULL','postgresql_type'=>'INT NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'tablename','mysql_type'=>'VARCHAR(255) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(255) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[]=['name'=>'customtablename','mysql_type'=>'VARCHAR(100) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'customidfield','mysql_type'=>'VARCHAR(100) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'tabletitle','mysql_type'=>'VARCHAR(255) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(255) NULL DEFAULT NULL'];
		//$tables_projected_fields[]=['name'=>'params','mysql_type'=>'text NULL DEFAULT NULL','postgresql_type'=>'text NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'published','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[]=['name'=>'created_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'modified_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'created','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'modified','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'checked_out','mysql_type'=>'int UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'checked_out_time','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		//$tables_projected_fields[]=['name'=>'version','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 1','postgresql_type'=>'INT NOT NULL DEFAULT 1'];
		//$tables_projected_fields[]=['name'=>'hits','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		//$tables_projected_fields[]=['name'=>'ordering','mysql_type'=>'INT NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'allowimportcontent','mysql_type'=>'TINYINT NOT NULL DEFAULT 0','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 0'];
		
		ESTables::checkComponentTable('#__customtables_tables', $tables_projected_fields);
	}

	public static function checkIfTableExists($realtablename)
	{
		$conf = JFactory::getConfig();
		$database = $conf->get('db');

		$db = JFactory::getDBO();

		$realtablename=str_replace('#__',$db->getPrefix(),$realtablename);

		if($db->serverType == 'postgresql')
			$query = 'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_name = '.$db->quote($realtablename).' LIMIT 1';
		else
			$query = 'SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = '.$db->quote($database).' AND table_name = '.$db->quote($realtablename).' LIMIT 1';
		
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
		if($tableid==0)
			return 0;
			
		$row = ESTables::getTableRowByIDAssoc($tableid);
		if(!is_array($row))
			return 0;
		
		return (object)$row;
	}

	public static function getTableRowByIDAssoc($tableid)
	{
		$db = JFactory::getDBO();

		if($tableid==0)
			return 0;
			
		return ESTables::getTableRowByWhere('id='.(int)$tableid);
	}

	public static function getTableRowByName($tablename = '')
	{
		$db = JFactory::getDBO();

		if($tablename=='')
			return 0;
			
		$row = ESTables::getTableRowByNameAssoc($tablename);
		if(!is_array($row))
			return 0;
		
		return (object)$row;
	}
	
	public static function getTableRowByNameAssoc($tablename = '')
	{
		if($tablename=='')
			return 0;
			
		$db = JFactory::getDBO();
		
		return ESTables::getTableRowByWhere('tablename='.$db->quote($tablename));
	}
		
	public static function getTableRowByWhere($where)
	{
		$db = JFactory::getDBO();
	
			
		$query = 'SELECT '.ESTables::getTableRowSelects().' FROM #__customtables_tables AS s WHERE '.$where.' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return 0;
			
		$row=$rows[0];
			
		$published_field_found=true;
		if($row['customtablename']!='')
		{
			$realfields=ESFields::getListOfExistingFields($row['realtablename'],false);
			if(!in_array('published',$realfields))
				$published_field_found=false;
		}
		$row['published_field_found'] = $published_field_found;
				
		if($published_field_found)
			$query_selects='*, '.$row['realtablename'].'.'.$row['realidfieldname'].' AS listing_id, '.$row['realtablename'].'.published AS listing_published';
		else
			$query_selects='*, '.$row['realtablename'].'.'.$row['realidfieldname'].' AS listing_id, 1 AS listing_published';
		
		$row['query_selects']=$query_selects;

		return $row;
	}

	public static function getTableRowSelects()
	{
		$db = JFactory::getDBO();
		
		if($db->serverType == 'postgresql')
		{
			$realtablename_query='CASE WHEN customtablename!=\'\' THEN customtablename ELSE CONCAT(\'#__customtables_table_\', tablename) END AS realtablename';
			$realidfieldname_query='CASE WHEN customidfield!=\'\' THEN customidfield ELSE \'id\' END AS realidfieldname';
		}
		else
		{
			$realtablename_query='IF(customtablename!=\'\', customtablename, CONCAT(\'#__customtables_table_\', tablename)) AS realtablename';
			$realidfieldname_query='IF(customidfield!=\'\', customidfield, \'id\') AS realidfieldname';
		}
			
		return '*, '.$realtablename_query.','.$realidfieldname_query.', 1 AS published_field_found';
	}


	public static function createTableIfNotExists($database,$dbprefix,$tablename,$tabletitle,$complete_table_name='')
	{
		$db = JFactory::getDBO();

		if($db->serverType == 'postgresql')
		{
			//PostgreSQL
			//Check if table exists
			if($complete_table_name=='')
				$table_name=$dbprefix.'customtables_table_'.$tablename;
			else
				$table_name=$complete_table_name;// used for custom table names - to connect to third-part tables for example
				
			$fields = ESFields::getListOfExistingFields($table_name,false);
			
			if(count($fields)==0)
			{
				//create new table
				$db->setQuery('CREATE SEQUENCE IF NOT EXISTS '.$table_name.'_seq');
				$db->execute();
				
				
				$query = '
				CREATE TABLE IF NOT EXISTS '.$table_name.'
				(
					id int NOT NULL DEFAULT nextval (\''.$table_name.'_seq\'),
					published smallint NOT NULL DEFAULT 1,
					PRIMARY KEY (id)
				)';

				$db->setQuery( $query );
				$db->execute();
				
				$db->setQuery('ALTER SEQUENCE '.$table_name.'_seq RESTART WITH 1');
				$db->execute();
				
				return true;
			}
		}
		else
		{
			//Mysql;
			$rows2=ESTables::getTableStatus($database,$dbprefix,$tablename);

			if(count($rows2)>0)
			{
				if($complete_table_name=='')
				{
					//do not medify third-party tables
					$row2=$rows2[0];

					$table_name=$dbprefix.'customtables_table_'.$tablename;

					if($row2->Engine!='InnoDB')
					{
						$query = 'ALTER TABLE '.$table_name.' ENGINE = InnoDB';
						$db->setQuery( $query );
						$db->execute();
					}

					$query = 'ALTER TABLE '.$table_name.' COMMENT = "'.$tabletitle.'";';
					$db->setQuery( $query );
					$db->execute();
					
					return false;
				}
			}
			else
			{
				$query = '
				CREATE TABLE IF NOT EXISTS #__customtables_table_'.$tablename.'
				(
					id int(10) UNSIGNED NOT NULL auto_increment,
					published tinyint(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (id)
				) ENGINE=InnoDB COMMENT="'.$tabletitle.'" DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;
				';

				$db->setQuery( $query );
				$db->execute();
				
				return true;
			}
		}
		
		return false;
	}
	
	public static function insertRecords($realtablename,$realidfieldname,$sets)
	{
		$db = JFactory::getDBO();
		
		if($db->serverType == 'postgresql')
		{
			$set_fieldnames=array();
			$set_values=array();
			foreach($sets as $set)
			{
				$break_sets = explode('=',$set);
				$set_fieldnames[]=$break_sets[0];
				$set_values[]=$break_sets[1];
			}
			
			$query='INSERT INTO '.$realtablename.' ('.implode(',',$set_fieldnames).') VALUES ('.implode(',',$set_values).')';
			$db->setQuery( $query );
			$db->execute();
			
			//get last id
			$query='SELECT '.$realidfieldname.' AS listing_id FROM '.$realtablename.' ORDER BY '.$realidfieldname.' DESC LIMIT 1';
			$db->setQuery( $query );
			$temp_rows = $db->loadObjectList();
			return $temp_rows[0]->listing_id;
		}
		else
		{
			$query='INSERT '.$realtablename.' SET '.implode(', ',$sets);
			$db->setQuery( $query );
			$db->execute();
			return $db->insertid();	
		}
		return 0;
	}
}
