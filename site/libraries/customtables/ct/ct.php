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
use CustomTables\Environment;
use CustomTables\Filtering;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;

class CT
{
	var $Languages;
	var $Env;
	var $Table;
	var $Records;
	var $GroupBy;
	var $Ordering;
	var $Filter;
	var $LayoutProc; //Old - will be deprictaed by 2023
	var $alias_fieldname;
	var $Limit;
	var $LimitStart;
	var $isEditForm;
	
	function __construct()
	{
		$this->Languages = new Languages;
		$this->Env = new Environment;
		
		$this->GroupBy = '';
		
		$this->isEditForm = false;
	}
	
	function getTable($tablename_or_id, $useridfieldname = null)
	{
		$this->Table = new Table($this->Languages, $this->Env, $tablename_or_id, $useridfieldname);
		$this->Ordering = new Ordering($this->Table);
		
		$this->prepareSEFLinkBase();
	}
	
	function setTable(&$tablerow, $useridfieldname = null, $load_fields = true)
	{
		$this->Table = new Table($this->Languages, $this->Env, 0);
		$this->Table->setTable($tablerow, $useridfieldname, $load_fields);
		
		$this->Ordering = new Ordering($this->Table);
		
		$this->prepareSEFLinkBase();
	}
	
	protected function prepareSEFLinkBase()
	{
		if($this->Table == null or $this->Table->fields == null)
			return null;
		
		if(strpos($this->Env->current_url,'option=com_customtables')===false)
	    {
			foreach($this->Table->fields as $fld)
			{
				if($fld['type']=='alias')
				{
					$this->alias_fieldname=$fld['fieldname'];
					break;
				}
			}
		}
		$this->alias_fieldname = null;
	}
	
	function setFilter($filter_string = '', $showpublished = 0)
	{
		$this->Filter = new Filtering($this, $showpublished);
		if($filter_string!='')
			$this->Filter->addWhereExpression($filter_string);
	}
	
	function getNumberOfRecords($where)
	{
		$db = Factory::getDBO();
		
		$query_analytical='SELECT COUNT('.$this->Table->tablerow['realidfieldname'].') AS count FROM '.$this->Table->realtablename.' '.$where;
		
		$db->setQuery($query_analytical);
		$rows=$db->loadObjectList();	
		if(count($rows)==0)
			$this->Table->recordcount = 0;
		else
			$this->Table->recordcount = $rows[0]->count;
	}
	
	function buildQuery($where)
	{
		$ordering = $this->GroupBy!='' ? [$this->GroupBy] : [];
		
		$selects = [$this->Table->tablerow['query_selects']];
		
		if($this->Ordering->ordering_processed_string!=null)
		{
			$this->Ordering->parseOrderByString();

			if($this->Ordering->orderby!=null)
			{
				if($this->Ordering->selects!=null)
					$selects[]=$this->Ordering->selects;
				
				$ordering[]=$this->Ordering->orderby;
			}
		}

		$query='SELECT '.implode(',',$selects).' FROM '.$this->Table->realtablename.' ';
		
		if($this->Ordering->inner!=null)
			$query.=' '.implode(' ',$this->Ordering->inner).' ';
			
		$query.=$where;
		
		$query.=' GROUP BY '.$this->Table->tablerow['realidfieldname'];
	
		if(count($ordering)>0)
			$query.=' ORDER BY '.implode(',',$ordering);
		
		return $query;
	}
	
	function getRecords($all = false)
	{
		$db = Factory::getDBO();
		
		$where = count($this->Filter->where) >0 ? ' WHERE '.implode(' AND ',$this->Filter->where) : '';
		$where = str_replace('\\','',$where); //Just to make sure that there is nothing weird in the query
		
		$this->getNumberOfRecords($where);
		
		$query = $this->buildQuery($where);

		if($this->Table->recordcount > 0)
		{
			$the_limit=(int)$this->Limit;
			
			if($all)
			{
				if($the_limit>0)
					$db->setQuery($query, 0, 20000); //or we will run out of memory
			}
			else
			{
				if($the_limit>20000)
					$the_limit=20000;

				if($the_limit==0)
					$the_limit=20000; //or we will run out of memory
				
				
				if($this->Table->recordcount < $this->LimitStart or $this->Table->recordcount < $the_limit)
					$this->LimitStart=0;

				$db->setQuery($query, $this->LimitStart, $the_limit);
			}

			$rows = $db->loadAssocList();
		}
		else
			$rows=array();
		
	
		$this->Records = $rows;
		
		return true;
	}
	
	function getRecordsByKeyword()
	{
		$moduleid = $this->Env->jinput->get('moduleid',0,'INT');
		if($moduleid!=0)
		{
			$eskeysearch_=$this->Env->jinput->get('eskeysearch_'.$moduleid,'','STRING');
			if($eskeysearch_!='')
			{
				require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components'. DIRECTORY_SEPARATOR .'com_customtables'.DIRECTORY_SEPARATOR
					.'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR .'filter' . DIRECTORY_SEPARATOR .'keywordsearch.php');

				$KeywordSearcher=new CustomTablesKeywordSearch($this);

				$KeywordSearcher->groupby=$this->GroupBy;
				$KeywordSearcher->esordering=$this->Ordering->ordering_processed_string;

				$this->Records=$KeywordSearcher->getRowsByKeywords(
						$eskeysearch_,
						$this->Table->recordcount,
						(int)$this->getState('limit'),
						$this->LimitStart
				);

				if($this->Table->recordcount < $this->LimitStart )
					$this->LimitStart=0;
			}
		}
	}
	
	function getRecordList()
	{
		if($this->Table->recordlist != null)
			return $this->Table->recordlist;
		
		$recordlist = [];
		
		foreach($this->Records as $row)
			$recordlist[]=$row['listing_id'];
			
		$this->Table->recordlist = $recordlist;
		return $recordlist;
	}
	
	function applyLimits($blockExternalVars = true)
	{
		$limit_var = 'com_customtables.limit_'.$this->Env->Itemid;
		//Grouping
		if($this->Env->menu_params->get('groupby')!='')
			$this->GroupBy = Fields::getRealFieldName($this->Env->menu_params->get('groupby'),$this->Table->fields);
		else
			$this->GroupBy = '';
		
		$mainframe = Factory::getApplication('site');
		
		if($this->Env->frmt!='html')
		{
			//export all records if firmat is csv, xml etc.
			$this->Limit=0;
			$this->LimitStart=0;
			return;
		}
			
		if($blockExternalVars)
		{			
			if((int)$this->Env->menu_params->get( 'limit' ) > 0)
			{
				$this->Limit = (int)$this->Env->menu_params->get( 'limit' );
				$mainframe->setUserState($limit_var, $this->Limit);
			
				$this->LimitStart = $this->Env->jinput->getInt('start',0);
				$this->LimitStart = ($this->Limit != 0 ? (floor($this->LimitStart / $this->Limit) * $this->Limit) : 0);
			}
			else
			{
				$mainframe->setUserState($limit_var, 0);
				$this->Limit=0;
				$this->Limitstart=0;
			}
		}
		else
		{
			$this->LimitStart = $this->Env->jinput->getInt('start',0);

			if((int)$this->Env->menu_params->get( 'limit' )>0)
			{
				$this->Limit = (int)$this->Env->menu_params->get( 'limit' );
				$mainframe->setUserState($limit_var,$this->Limit);
			}
			else
			{
				$this->Limit = $mainframe->getUserState($limit_var, 0);
				$mainframe->setUserState($limit_var,$this->Limit);
			}
			
			// In case limit has been changed, adjust it
			$this->LimitStart = ($this->Limit != 0 ? (floor($this->LimitStart / $this->Limit) * $this->Limit) : 0);
		}
	}
	
	function loadJSAndCSS()
	{
		$document = Factory::getDocument();
		
		//JQuery and Bootstrap
		if($this->Env->version < 4)
		{
			$document->addCustomTag('<script src="'.URI::root(true).'/media/jui/js/jquery.min.js"></script>');
			$document->addCustomTag('<script src="'.URI::root(true).'/media/jui/js/bootstrap.min.js"></script>');
		}
		else
			$document->addCustomTag('<link rel="stylesheet" href="'.URI::root(true).'/media/system/css/fields/switcher.css">');
		
		$document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/js/jquery.uploadfile.min.js"></script>');
		$document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/js/jquery.form.js"></script>');

		$document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/js/ajax.js"></script>');
		$document->addScript(URI::root(true).'/components/com_customtables/libraries/customtables/media/js/base64.js');
		$document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/js/catalog.js" type="text/javascript"></script>');
		$document->addScript(URI::root(true).'/components/com_customtables/libraries/customtables/media/js/edit.js');
		$document->addScript(URI::root(true).'/components/com_customtables/libraries/customtables/media/js/esmulti.js');
		$document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/js/modal.js" type="text/javascript"></script>');
		$document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/js/uploader.js"></script>');
		$document->addScript(URI::root(true).'/components/com_customtables/libraries/customtables/media/js/combotree.js');

		//Styles
		$document->addCustomTag('<link href="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/css/style.css" type="text/css" rel="stylesheet" >');
		$document->addCustomTag('<link href="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/css/modal.css" type="text/css" rel="stylesheet" >');
		$document->addCustomTag('<link href="'.URI::root(true).'/components/com_customtables/libraries/customtables/media/css/uploadfile.css" rel="stylesheet">');
	}
}
