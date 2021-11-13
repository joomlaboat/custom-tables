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
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'filtering.php');

class CustomTablesModelDetails extends JModelLegacy
{
	var $ct;

	var $TotalRows=0;

	var $LayoutProc;

	var $imagefolder;
	var $imagefolderweb;
	var $imagegalleryprefix;

	var $storby;
	var $showpublished;
	var $filter;
	var $redirectto;
	var $ShowDatailsLink;

	var $Itemid;
	var $params;

	function __construct()
	{
		parent::__construct();
		
		$this->ct = new CT;
		
		$jinput=Factory::getApplication()->input;

		if(method_exists(JFactory::getApplication(),"getParams"))
		{
			$params=JFactory::getApplication()->getParams();
		
			if($params->get( 'clean' )==1)
				$this->ct->Env->clean=1;

			if($jinput->getInt('listing_id',0) and $jinput->getInt('listing_id',0)!=0)
				$id= $jinput->getInt('listing_id', 0);
			else
				$id=(int)$params->get( 'listingid' );

			$this->Itemid=$jinput->getInt('Itemid',0);
	
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

		$jinput=JFactory::getApplication()->input;

		$this->imagefolderweb='images/esimages';
		$this->imagefolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'esimages';
		$this->imagegalleryprefix='g';
		

		//sort by field
		if(!$params_only and $jinput->getCmd('sortby'))
			$this->sortby=strtolower($jinput->getCmd('sortby'));
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
			$this->alias=JoomlaBasicMisc::slugify($jinput->getString('alias'));

			if(!$params_only and $jinput->getString('filter',''))
				$this->filter=$jinput->getString('filter','');
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

		$this->LayoutProc=new LayoutProcessor;
		$this->LayoutProc->Model=$this;

		if($this->filter!='' and $this->alias=='')
		{
			//Parse using layout
			$this->LayoutProc->layout=$this->filter;

			$this->filter=$this->LayoutProc->fillLayout(array(),null,'[]',true);
		}

		$this->redirectto=$this->params->get( 'redirectto' );
		$this->LayoutProc->layout='';
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
			$where='';

			$wherearr=array();

			if($this->filter!='')
			{
				$this->filtering = new ESFiltering($this->ct);
				
				$PathValue=array();
				$paramwhere=$this->filtering->getWhereExpression($this->filter,$PathValue);
				

				if($paramwhere!='')
					$wherearr[]=' ('.$paramwhere.' )';
			}
			else
			{
				$a=array(); //field not found. compatibility trick
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_NOFILTER'), 'error');
				return $a;
			}

			if(count($wherearr)>0)
				$where = ' WHERE '.implode(" AND ",$wherearr);


			if($this->ct->Table->published_field_found)
				$query = 'SELECT *, id AS listing_id, '.$this->ct->Table->realtablename.'.published AS listing_published ';
			else
				$query = 'SELECT *, id AS listing_id, 1 AS listing_published ';
			
			$query.=' FROM '.$this->ct->Table->realtablename.' '.$where;

			$query.=' ORDER BY id DESC'; //show last
			$query.=' LIMIT 1';
		}
		else
		{
			//show exact record
			if($this->ct->Table->published_field_found)
				$query = 'SELECT *, id AS listing_id, '.$this->ct->Table->realtablename.'.published AS listing_published ';
			else
				$query = 'SELECT *, id AS listing_id, 1 AS listing_published ';
				
			$query.=' FROM '.$this->ct->Table->realtablename.' WHERE id='.$this->_id.' LIMIT 1';
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
						$row[$ESField['realfieldname']]=$new_row[$ESField['realfieldname']];
				}

				return $this->getVersionData($row,$log_field,$version);
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
		if($version<=count($versions))
		{
					$data_editor=explode(',',$versions[$version-2]);
					$data_content=explode(',',$versions[$version-1]);

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
