<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;

use \Joomla\CMS\Factory;

jimport('joomla.application.component.model');
//jimport('joomla.application.component.controller');

JTable::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'tables');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');

class CustomTablesModelDetails extends JModelLegacy
{
	var $ct;

	var $storby;
	var $showpublished;
	var $filter;

	var $params;

	function __construct()
	{
		parent::__construct();
		
		$this->ct = new CT;
		
		$jinput=Factory::getApplication()->input;

		if(method_exists(JFactory::getApplication(),"getParams"))
		{
			$params=JFactory::getApplication()->getParams();
			$this->ct->Env->menu_params = $params;
		
			if($params->get( 'clean' )==1)
				$this->ct->Env->clean=1;

			if($this->ct->Env->jinput->getInt('listing_id',0) and $jinput->getInt('listing_id',0)!=0)
				$id= $this->ct->Env->jinput->getInt('listing_id', 0);
			else
				$id=(int)$params->get( 'listingid' );

			$this->load($params,$id);
		}
	}
	
	function setFrmt($frmt)
	{
		$this->ct->Env->frmt=$frmt;
	}

	function load($params,$id,$params_only=false,$custom_where='')
	{
		$this->params=$params;
		$this->ct->Env->menu_params = $this->params;

		//sort by field
		if(!$params_only and $this->ct->Env->jinput->getCmd('sortby'))
			$this->sortby=strtolower($this->ct->Env->jinput->getCmd('sortby'));
		else
			$this->sortby=strtolower($this->params->get( 'sortby' ));


		//optional filter
		if($custom_where!='' and $id==0)
		{
			$this->alias='';
			$this->filter=$custom_where;
		}
		else
		{
			$this->alias=JoomlaBasicMisc::slugify($this->ct->Env->jinput->getString('alias'));

			if(!$params_only and $this->ct->Env->jinput->getString('filter',''))
				$this->filter=$this->ct->Env->jinput->getString('filter','');
			else
				$this->filter=$this->params->get( 'filter' );
		}

		if($this->params->get( 'recordstable' )!='' and $this->params->get( 'recordsuseridfield' )!='' and $this->params->get( 'recordsfield' )!='')
		{
			if(!$this->checkRecordUserJoin($this->params->get( 'recordstable' ),$this->params->get( 'recordsuseridfield' ),$this->params->get( 'recordsfield' ),$id))
			{
				//YOU ARE NOT AUTHORIZED TO ACCESS THIS SOURCE';
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
				return false;
			}
		}

		$this->setId($id);
		
		$this->ct->getTable($this->params->get( 'establename' ), $this->params->get('useridfield'));
		
		if($this->ct->Table->tablename=='')
		{
			//JFactory::getApplication()->enqueueMessage('Table not selected (127).', 'error');
			return;
		}

		if($this->alias!='' and $this->ct->Table->alias_fieldname!='')
			$this->filter=$this->ct->Table->alias_fieldname.'='.$this->alias;

		$this->ct->LayoutProc=new LayoutProcessor($this->ct);
		
		if($this->filter!='' and $this->alias=='')
		{
			//Parse using layout
			$this->ct->LayoutProc->layout=$this->filter;

			$this->filter=$this->ct->LayoutProc->fillLayout(array(),null,'[]',true);
		}

		$this->ct->LayoutProc->layout='';
	}

	function checkRecordUserJoin($recordstable, $recordsuseridfield, $recordsfield, $id)
	{
		$user = JFactory::getUser();
		$userid = (int)$user->get('id');

		$db = JFactory::getDBO();

		$query='SELECT id FROM #__customtables_table_'.$recordstable.' WHERE es_'.$recordsuseridfield.'='.$userid.' AND INSTR(es_'.$recordsfield.',",'.$id.',")';
		$db->setQuery($query);

        $db->execute();
		$num_rows = $db->getNumRows();

		if($num_rows==0)
			return false;

		return true;
	}

	function setId($id)
	{
		$this->_id	= $id;
		$this->_data	= null;
	}

	function & getData()
	{
		$db = JFactory::getDBO();
		
		if($this->_id==0)
		{
			$this->_id	= 0;
			
			if($this->filter!='')
			{
				$this->ct->setFilter($this->filter, $this->ct->Env->menu_params->get('showpublished'));
			}
			else
			{
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_NOFILTER'), 'error');
				return [];//field not found. compatibility trick
			}

			$where = count($this->ct->Filter->where) > 0 ? ' WHERE '.implode(" AND ",$this->ct->Filter->where) : '';

			$this->ct->Ordering->orderby = 'id DESC';
			$query = $this->ct->buildQuery($where);
			$query.=' LIMIT 1';
		}
		else
		{
			//show exact record
			
			$query = $this->ct->buildQuery('WHERE id='.$this->_id);
			$query.=' LIMIT 1';
		}

		$db->setQuery($query);
		
		$rows=$db->loadAssocList();

		if(count($rows)<1)
		{
			$a=array();
			return $a;
		}

		$row=$rows[0];

		//get specific Version
		$version= JFactory::getApplication()->input->get('version',0,'INT');
		if($version!=0)
		{
			//get log field
			$log_field=$this->getTypeFieldName('log');;
			if($log_field!='')
			{
				$new_row= $this->getVersionData($row,$log_field,$version);
				if(count($new_row)>0)
				{
				    $row=$this->makeEmptyRecord($row['listing_id'],$new_row['listing_published']);

				    //Copy values
				    foreach($this->ct->Table->fields as $ESField)
					{
						if(isset($new_row[$ESField['realfieldname']]))
							$row[$ESField['realfieldname']]=$new_row[$ESField['realfieldname']];
					}
				}

				$versioned_data = $this->getVersionData($row,$log_field,$version);
				return $versioned_data;
			}
		}

		return $row;
	}


	function makeEmptyRecord($id,$published)
	{
	    $row=array();
	    $row['listing_id']=$id;
	    $row['listing_published']=$published;

	    foreach($this->ct->Table->fields as $ESField)
		$row[$ESField['realfieldname']]='';

	    return $row;
	}


	function getTypeFieldName($type)
	{
		foreach($this->ct->Table->fields as $ESField)
		{
			if($ESField['type']==$type)
				return $ESField['realfieldname'];
		}

		return '';
	}

	function getVersionData(&$row,$log_field,$version)
	{
		$creation_time_field=$this->getTypeFieldName('changetime');

		$versions=explode(';',$row[$log_field]);
		//print_r($versions);
		if($version<=count($versions))
		{
			if(count($versions)>1 and $version>1)
				$data_editor=explode(',',$versions[$version-2]);
			else
				$data_editor=[''];
			
			$data_content=explode(',',$versions[$version-1]); // version 1, 1 - 1 = 0; where 0 is the index

					if($data_content[3]!='')
					{
						$obj=json_decode(base64_decode($data_content[3]),true);
						$new_row=$obj[0];
						$new_row['listing_published']=$row['listing_published'];
						$new_row['listing_id']=$row['listing_id'];
						$new_row[$log_field]=$row[$log_field];


						if($creation_time_field)
						{
							$timestamp = date('Y-m-d H:i:s', (int)$data_editor[0]);
							$new_row[$creation_time_field]=$timestamp ; //time (int)
						}

						return $new_row;
					}

		}
		return array();
	}
}
