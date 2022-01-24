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
use CustomTables\Fields;

use \ESTables;
use \ESFields;

class Table
{
	use Logs;
	use SaveFieldQuerySet;
	
	var $Languages;
	var $Env;
		
	var $tableid;
	var $tablerow;
	var $tablename;
	var $published_field_found;
	
	var $customtablename;
	var $realtablename;
	var $realidfieldname;
		
	var $tabletitle;
	
	var $alias_fieldname;
	
	var $useridfieldname;
	var $useridrealfieldname;
	
	var $fields;
	var $record;

	var $recordcount;
	var $recordlist;
	
	var $db;
	
	function __construct(&$Languages, &$Env, $tablename_or_id_not_sanitized, $useridfieldname = null)
	{
		$this->db = Factory::getDBO();
				
		$this->Languages = $Languages;
		$this->Env = $Env;
		
		if($tablename_or_id_not_sanitized == null or $tablename_or_id_not_sanitized == '')
			return;
		elseif((int)$tablename_or_id_not_sanitized)
			$this->tablerow = ESTables::getTableRowByIDAssoc((int)$tablename_or_id_not_sanitized);// int sanitizes the input
		else
		{
			$tablename_or_id = strtolower(trim(preg_replace('/[^a-zA-Z]/', '', $tablename_or_id_not_sanitized)));
			$this->tablerow = ESTables::getTableRowByNameAssoc($tablename_or_id);
		}
			
		if(!isset($this->tablerow['id']))
			return;

		$this->setTable($this->tablerow, $useridfieldname, $load_fields = true);
	}
	
	function setTable(&$tablerow, $useridfieldname = null, $load_fields = true)
	{
		$this->tablerow = $tablerow;
		$this->tablename = $this->tablerow['tablename'];
		$this->tableid=$this->tablerow['id'];
		$this->published_field_found=$this->tablerow['published_field_found'];
		$this->customtablename=$this->tablerow['customtablename'];
		$this->realtablename=$this->tablerow['realtablename'];
		$this->realidfieldname=$this->tablerow['realidfieldname'];
		$this->tabletitle=$this->tablerow['tabletitle'.$this->Languages->Postfix];
		$this->alias_fieldname='';
		$this->imagegalleries=array();
		$this->fileboxes=array();
		$this->useridfieldname='';
		
		//Fields
		$this->fields = Fields::getFields($this->tableid);
		foreach($this->fields as $fld)
		{
			switch($fld['type'])
			{
				case 'alias':
					$this->alias_fieldname=$fld['fieldname'];
					break;
				case 'imagegallery':
					$this->imagegalleries[]=array($fld['fieldname'],$fld['fieldtitle'.$this->Languages->Postfix]);
					break;
				case 'filebox':
					$this->fileboxes[]=array($fld['fieldname'],$fld['fieldtitle'.$this->Languages->Postfix]);
					break;
				
				case 'user':
				case 'userid':
				
					if($useridfieldname == null or $useridfieldname == $fld['fieldname'])
					{
						$this->useridfieldname=$fld['fieldname'];
						$this->useridrealfieldname=$fld['realfieldname'];;
					}
						
					break;
			}
		}
	}
	
	function loadRecord($listing_id)
	{
		$query = 'SELECT '.$this->tablerow['query_selects'].' FROM '.$this->realtablename.' WHERE '.$this->realidfieldname.'='.$this->db->quote($listing_id).' LIMIT 1';

		$this->db->setQuery( $query );
	
		$recs = $this->db->loadAssocList( );
		if(!$recs) return $this->record = null;
		if (count($recs)<1) return $this->record = null;

		$this->record = $recs[0];
		
		return $this->record;
	}
}
