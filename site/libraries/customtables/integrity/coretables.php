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
use \ESLanguages;

class IntegrityCoreTables extends \CustomTables\IntegrityChecks
{
	public static function checkCoreTables()
	{
		$phptagprocessor=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';

		if(file_exists($phptagprocessor))
		{
			$phptagprocessor=true;
		}
		else
			$phptagprocessor=false;
		
		IntegrityCoreTables::createCoreTableIfNotExists(IntegrityCoreTables::getCoreTableFields_Tables());
		IntegrityCoreTables::createCoreTableIfNotExists(IntegrityCoreTables::getCoreTableFields_Fields());
		IntegrityCoreTables::createCoreTableIfNotExists(IntegrityCoreTables::getCoreTableFields_Layouts());
		IntegrityCoreTables::createCoreTableIfNotExists(IntegrityCoreTables::getCoreTableFields_Categories());
		
		if($phptagprocessor)
		{
			IntegrityCoreTables::createCoreTableIfNotExists(IntegrityCoreTables::getCoreTableFields_Log());
			IntegrityCoreTables::createCoreTableIfNotExists(IntegrityCoreTables::getCoreTableFields_Options());
		}
	}
		
	protected static function createCoreTableIfNotExists($table)
	{
		if(!ESTables::checkIfTableExists($table->realtablename))
			IntegrityCoreTables::createCoreTable($table);
	}
	
	protected static function getCoreTableFields_Tables()
	{
		$conf = Factory::getConfig();
		$dbprefix = $conf->get('dbprefix');
		
		$tables_projected_fields=[];
		$tables_projected_fields[]=['name'=>'id','mysql_type'=>'INT UNSIGNED NOT NULL AUTO_INCREMENT','postgresql_type'=>'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_tables_seq\')'];
		$tables_projected_fields[]=['name'=>'published','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		
		$tables_projected_fields[]=['name'=>'tablename','mysql_type'=>'VARCHAR(255) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(255) NOT NULL DEFAULT \'\''];
		
		$tables_projected_fields[]=['name'=>'tabletitle','mysql_type'=>'VARCHAR(255) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(255) NULL DEFAULT NULL','multilang'=>true];
		$tables_projected_fields[]=['name'=>'description','mysql_type'=>'TEXT NULL DEFAULT NULL','postgresql_type'=>'TEXT NULL DEFAULT NULL','multilang'=>true];

		//Not used inside Custom Tables
		//$tables_projected_fields[]=['name'=>'asset_id','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		
		$tables_projected_fields[]=['name'=>'tablecategory','mysql_type'=>'INT NULL DEFAULT NULL','postgresql_type'=>'INT NULL DEFAULT NULL'];
		
		$tables_projected_fields[]=['name'=>'customphp','mysql_type'=>'VARCHAR(1024) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(1024) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'customtablename','mysql_type'=>'VARCHAR(100) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'customidfield','mysql_type'=>'VARCHAR(100) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'allowimportcontent','mysql_type'=>'TINYINT NOT NULL DEFAULT 0','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 0'];
		
		$tables_projected_fields[]=['name'=>'created_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'modified_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'created','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'modified','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'checked_out','mysql_type'=>'int UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'checked_out_time','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		
		//Never used inside Custom Tables
		//$tables_projected_fields[]=['name'=>'version','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 1,','postgresql_type'=>'INT NOT NULL DEFAULT 1'];
		//$tables_projected_fields[]=['name'=>'hits','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		//$tables_projected_fields[]=['name'=>'ordering','mysql_type'=>'INT NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		
		//Not used inside Custom Tables
		//$tables_projected_fields[]=['name'=>'params','mysql_type'=>'text NULL DEFAULT NULL','postgresql_type'=>'text NULL DEFAULT NULL'];
		
		$tables_projected_indexes=[];
		$tables_projected_indexes[]=['name'=>'idx_published','field'=>'published'];
		$tables_projected_indexes[]=['name'=>'idx_tablename','field'=>'tablename'];
		$tables_projected_indexes[]=['name'=>'idx_tabletitle','field'=>'tabletitle'];
		
		return (object)['realtablename' => $dbprefix . 'customtables_tables',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'List of Custom Tables tables'];
	}
	
	protected static function getCoreTableFields_Fields()
	{
		$conf = Factory::getConfig();
		$dbprefix = $conf->get('dbprefix');
		
		$tables_projected_fields=array();
		
		$tables_projected_fields[]=['name'=>'id','mysql_type'=>'INT UNSIGNED NOT NULL AUTO_INCREMENT','postgresql_type'=>'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_options_seq\')'];
		$tables_projected_fields[]=['name'=>'published','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[]=['name'=>'tableid','mysql_type'=>'INT UNSIGNED NOT NULL','postgresql_type'=>'INT NOT NULL'];		
		
		$tables_projected_fields[]=['name'=>'fieldname','mysql_type'=>'VARCHAR(100) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(100) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[]=['name'=>'fieldtitle','mysql_type'=>'VARCHAR(255) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(255) NULL DEFAULT NULL','multilang'=>true];
		$tables_projected_fields[]=['name'=>'description','mysql_type'=>'TEXT NULL DEFAULT NULL','postgresql_type'=>'TEXT NULL DEFAULT NULL','multilang'=>true];
		
		$tables_projected_fields[]=['name'=>'allowordering','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[]=['name'=>'isrequired','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[]=['name'=>'isdisabled','mysql_type'=>'TINYINT NOT NULL DEFAULT 0','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'alwaysupdatevalue','mysql_type'=>'TINYINT NOT NULL DEFAULT 0','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 0','comment'=>'Update default value every time record is edited.'];

		$tables_projected_fields[]=['name'=>'defaultvalue','mysql_type'=>'VARCHAR(1024) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(1024) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[]=['name'=>'customfieldname','mysql_type'=>'VARCHAR(100) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(100) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[]=['name'=>'type','mysql_type'=>'VARCHAR(50) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(50) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[]=['name'=>'typeparams','mysql_type'=>'VARCHAR(1024) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(1024) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[]=['name'=>'valuerule','mysql_type'=>'VARCHAR(1024) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(1024) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[]=['name'=>'valuerulecaption','mysql_type'=>'VARCHAR(1024) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(1024) NOT NULL DEFAULT \'\''];

		$tables_projected_fields[]=['name'=>'created_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'modified_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'created','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'modified','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'checked_out','mysql_type'=>'int UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'checked_out_time','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		  
		$tables_projected_indexes=[];
		$tables_projected_indexes[]=['name'=>'idx_published','field'=>'published'];
		$tables_projected_indexes[]=['name'=>'idx_tableid','field'=>'tableid'];
		$tables_projected_indexes[]=['name'=>'idx_fieldname','field'=>'fieldname'];

		return (object)['realtablename' => $dbprefix . 'customtables_fields',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Custom Tables Fields'];
	}
	
	protected static function getCoreTableFields_Layouts()
	{
		$conf = Factory::getConfig();
		$dbprefix = $conf->get('dbprefix');
		
		$tables_projected_fields=array();
		
		$tables_projected_fields[]=['name'=>'id','mysql_type'=>'INT UNSIGNED NOT NULL AUTO_INCREMENT','postgresql_type'=>'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_options_seq\')'];
		$tables_projected_fields[]=['name'=>'published','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[]=['name'=>'tableid','mysql_type'=>'INT UNSIGNED NOT NULL','postgresql_type'=>'INT NOT NULL'];	
		
		$tables_projected_fields[]=['name'=>'layoutname','mysql_type'=>'VARCHAR(512) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(512) NOT NULL DEFAULT \'\''];
		$tables_projected_fields[]=['name'=>'layouttype','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];		
		
		$tables_projected_fields[]=['name'=>'layoutcode','mysql_type'=>'MEDIUMTEXT NULL DEFAULT NULL','postgresql_type'=>'TEXT NULL DEFAULT NULL'];
		
		$tables_projected_fields[]=['name'=>'changetimestamp','mysql_type'=>'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		
		$tables_projected_fields[]=['name'=>'created_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'modified_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'created','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'modified','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'checked_out','mysql_type'=>'int UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'checked_out_time','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
				
		$tables_projected_indexes=[];
		$tables_projected_indexes[]=['name'=>'idx_published','field'=>'published'];
		$tables_projected_indexes[]=['name'=>'idx_tableid','field'=>'tableid'];
		$tables_projected_indexes[]=['name'=>'idx_layoutname','field'=>'layoutname'];

		return (object)['realtablename' => $dbprefix . 'customtables_layouts',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Custom Tables Layouts'];
	}

	protected static function getCoreTableFields_Categories()
	{		
		$conf = Factory::getConfig();
		$dbprefix = $conf->get('dbprefix');
		
		$tables_projected_fields=array();
		
		$tables_projected_fields[]=['name'=>'id','mysql_type'=>'INT UNSIGNED NOT NULL AUTO_INCREMENT','postgresql_type'=>'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_options_seq\')'];
		$tables_projected_fields[]=['name'=>'published','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		
		$tables_projected_fields[]=['name'=>'categoryname','mysql_type'=>'VARCHAR(255) NOT NULL DEFAULT "tablename"','postgresql_type'=>'VARCHAR(255) NOT NULL DEFAULT \'\''];
		
		$tables_projected_fields[]=['name'=>'created_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'modified_by','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'created','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'modified','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'checked_out','mysql_type'=>'int UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'checked_out_time','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		
		$tables_projected_indexes=[];
		$tables_projected_indexes[]=['name'=>'idx_published','field'=>'published'];
		$tables_projected_indexes[]=['name'=>'idx_categoryname','field'=>'categoryname'];

		return (object)['realtablename' => $dbprefix . 'customtables_categories',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Custom Tables Categories'];
	}
	
	protected static function getCoreTableFields_Log()
	{
		$conf = Factory::getConfig();
		$dbprefix = $conf->get('dbprefix');
		
		$tables_projected_fields=array();
		
		$tables_projected_fields[]=['name'=>'id','mysql_type'=>'INT UNSIGNED NOT NULL AUTO_INCREMENT','postgresql_type'=>'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_options_seq\')'];
		$tables_projected_fields[]=['name'=>'userid','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];		
		$tables_projected_fields[]=['name'=>'datetime','mysql_type'=>'DATETIME NULL DEFAULT NULL','postgresql_type'=>'TIMESTAMP(0) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'tableid','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];		
		$tables_projected_fields[]=['name'=>'action','mysql_type'=>'SMALLINT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'MALLINT NOT NULL DEFAULT 0'];		
		$tables_projected_fields[]=['name'=>'listingid','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'Itemid','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		  
		$tables_projected_indexes=[];
		$tables_projected_indexes[]=['name'=>'idx_userid','field'=>'userid'];
		$tables_projected_indexes[]=['name'=>'idx_action','field'=>'action'];
  
		return (object)['realtablename' => $dbprefix . 'customtables_log',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Custom Tables Action Log'];
	}
	
	protected static function getCoreTableFields_Options()
	{
		$conf = Factory::getConfig();
		$dbprefix = $conf->get('dbprefix');
		
		$tables_projected_fields=array();
		$tables_projected_fields[]=['name'=>'id','mysql_type'=>'INT UNSIGNED NOT NULL AUTO_INCREMENT','postgresql_type'=>'id INT check (id > 0) NOT NULL DEFAULT NEXTVAL (\'#__customtables_options_seq\')'];
		$tables_projected_fields[]=['name'=>'published','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[]=['name'=>'optionname','mysql_type'=>'VARCHAR(50) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(50) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'title','mysql_type'=>'VARCHAR(100) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(100) NULL DEFAULT NULL','multilang'=>true];
		
		$tables_projected_fields[]=['name'=>'image','mysql_type'=>'BIGINT NULL','postgresql_type'=>'BIGINT NULL'];
		$tables_projected_fields[]=['name'=>'imageparams','mysql_type'=>'VARCHAR(100) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(100) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'ordering','mysql_type'=>'INT UNSIGNED NOT NULL DEFAULT 0','postgresql_type'=>'INT NOT NULL DEFAULT 0'];
		$tables_projected_fields[]=['name'=>'parentid','mysql_type'=>'INT UNSIGNED NULL','postgresql_type'=>'INT NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'sublevel','mysql_type'=>'INT NULL','postgresql_type'=>'INT NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'isselectable','mysql_type'=>'TINYINT NOT NULL DEFAULT 1','postgresql_type'=>'SMALLINT NOT NULL DEFAULT 1'];
		$tables_projected_fields[]=['name'=>'optionalcode','mysql_type'=>'TEXT NULL DEFAULT NULL','postgresql_type'=>'TEXT NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'link','mysql_type'=>'VARCHAR(1024) NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(1024) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'familytree','mysql_type'=>'VARCHAR(1024) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(1024) NULL DEFAULT NULL'];
		$tables_projected_fields[]=['name'=>'familytreestr','mysql_type'=>'VARCHAR(1024) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL','postgresql_type'=>'VARCHAR(1024) NULL DEFAULT NULL'];
  
		$tables_projected_indexes=[];
		$tables_projected_indexes[]=['name'=>'idx_published','field'=>'published'];
		$tables_projected_indexes[]=['name'=>'idx_optionname','field'=>'optionname'];
		$tables_projected_indexes[]=['name'=>'idx_familytree','field'=>'familytree'];
		$tables_projected_indexes[]=['name'=>'idx_familytreestr','field'=>'familytreestr'];
  
		return (object)['realtablename' => $dbprefix . 'customtables_options',
			'fields' => $tables_projected_fields,
			'indexes' => $tables_projected_indexes,
			'comment' => 'Hierarchical structure records (Custom Tables field type)'];
	}
	
	protected static function prepareAddFieldQuery($fields,$db_type)
	{
		$db = Factory::getDBO();
		
		$LangMisc	= new ESLanguages;
		$languages=$LangMisc->getLanguageList();
		
		$fields_sql=[];
		foreach($fields as $field)
		{
			if(isset($field['multilang']) and $field['multilang'] == true)
			{
				$morethanonelang=false;
				foreach($languages as $lang)
				{
					$fieldname = $field['name'];
					
					if($morethanonelang)
						$fieldname.='_'.$lang->sef;

					$fields_sql[] = $db->quoteName($fieldname) . ' ' . $field[$db_type];
					
					$morethanonelang=true;
				}
			}
			else
			{
				$fields_sql[] = $db->quoteName($field['name']) . ' ' . $field[$db_type];
			}
		}
		
		return $fields_sql;
	}
	
	protected static function prepareAddIndexQuery($indexes)
	{
		$db = Factory::getDBO();
		
		$indexes_sql=[];
		foreach($indexes as $index)
		{
			$idx = $db->quoteName($index['name']);
			$fld = $db->quoteName($index['field']);
			$indexes_sql[] = 'KEY ' . $idx . ' (' . $fld . ')';
		}
		
		return $indexes_sql;
	}

	protected static function createCoreTable($table)
	{
		//TODO:
		//Add InnoDB Row Formats to config file
		//https://dev.mysql.com/doc/refman/5.7/en/innodb-row-format.html
		
		$conf = Factory::getConfig();
		$database = $conf->get('db');

		$db = Factory::getDBO();
		
		$fields_sql = IntegrityCoreTables::prepareAddFieldQuery($table->fields,($db->serverType == 'postgresql' ? 'postgresql_type' : 'mysql_type'));
		$indexes_sql = IntegrityCoreTables::prepareAddIndexQuery($table->indexes);

		if($db->serverType == 'postgresql')
		{
			//PostgreSQL

			$fields = ESFields::getListOfExistingFields($table->realtablename, false);
			
			if(count($fields)==0)
			{
				//create new table
				$db->setQuery('CREATE SEQUENCE IF NOT EXISTS '.$table->realtablename.'_seq');
				$db->execute();
				
				$query = '
				CREATE TABLE IF NOT EXISTS '.$table->realtablename.'
				(
					'.implode(',',$fields_sql).',
					PRIMARY KEY (id)
				)';

				$db->setQuery( $query );
				$db->execute();
				
				$db->setQuery('ALTER SEQUENCE '.$table->realtablename.'_seq RESTART WITH 1');
				$db->execute();
				
				Factory::getApplication()->enqueueMessage('Table "'.$table->realtablename.'" added.','notice');			
				
				return true;
			}
		}
		else
		{
			//Mysql

			$query = '
			CREATE TABLE IF NOT EXISTS '.$table->realtablename.'
				(
					'.implode(',',$fields_sql).',
					PRIMARY KEY (id)
					
					'.(count($indexes_sql) > 0 ? ','.implode(',',$indexes_sql) : '').'
					
				) ENGINE=InnoDB'.(isset($table->comments) and $table->comments != null ? ' COMMENT='.$db->quoteName($table->comments) : '')
					.' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;
			';

			$db->setQuery( $query );
			$db->execute();

			Factory::getApplication()->enqueueMessage('Table "'.$table->realtablename.'" added.','notice');			
			
			return true;
		}
		
		return false;
	}
}