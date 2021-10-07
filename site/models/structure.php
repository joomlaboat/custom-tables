<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Component\ComponentHelper;
use CustomTables\DataTypes\Tree;

jimport('joomla.application.component.model');

class CustomTablesModelStructure extends JModel
{
	var $ct;

		var $TotalRows=0;
	
		var $optionname;
		var $parentid;
		
		var $linkable;
		var $image_prefix;
		var $columns;
		var $row_break;
		
		var $esTable;
		var $establename;
		var $estableid;
		var $fieldname;
		var $fieldtype;

		var $ListingJoin;

		
		
		function __construct()
		{
			$this->ct = new CT;
			
				$this->esTable=new ESTables;
				
		        parent::__construct();
				$mainframe = JFactory::getApplication('site');
				
				 
				$params = ComponentHelper::getParams( 'com_customtables' );
				 
				if(JFactory::getApplication()->input->get('establename','','CMD'))
						$this->establename=JFactory::getApplication()->input->get('establename','','CMD');
				else
						$this->establename=$params->get( 'establename' );
				
				if(JFactory::getApplication()->input->get('esfieldname','','CMD'))
				{
						$esfn=JFactory::getApplication()->input->get('esfieldname','','CMD');
						$this->esfieldname=strtolower(trim(preg_replace("/[^a-zA-Z]/", "",$esfn )));
				}
				else
				{
						$esfn=$params->get( 'esfieldname' );
						
						$this->esfieldname=strtolower(trim(preg_replace("/[^a-zA-Z]/", "",$esfn )));
				}
				
				
				
				$tablerow = $this->esTable->getTableRowByName($this->establename);
				$this->estableid=$tablerow->id;
								
				 		       
				// Get pagination request variables
		        $limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		        $limitstart = JFactory::getApplication()->input->get('limitstart',0,'INT');//, '', 'int');
 
		        // In case limit has been changed, adjust it
		        $limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);
 
		        $this->setState('limit', $limit);
				$this->setState('limitstart', $limitstart);
				
				//get field
				$row=$this->esTable-> getFieldRowByName($this->esfieldname,$this->estableid);
				$this->fieldtype=$row->type;				
				
				if($params->get('optionname')!='')
						$this->optionname=$params->get('optionname');
				else
				{
						//get OptionName by FieldName

			
						$typeparams=explode(',',$row->typeparams);
						$this->optionname=$typeparams[0];
				}

				if(JFactory::getApplication()->input->getString('image_prefix'))
						$this->image_prefix=JFactory::getApplication()->input->getString('image_prefix');
				else
						$this->image_prefix=$params->get('image_prefix');
				
				
				if(JFactory::getApplication()->input->getInt('row_break',0))
						$this->row_break=JFactory::getApplication()->input->getInt('row_break',0);
				else
						$this->row_break=$params->get('row_break');
				
				
				
				if(JFactory::getApplication()->input->getInt('columns',0))
						$this->columns=JFactory::getApplication()->input->getInt('columns',0);
				else
						$this->columns=(int)$params->get('columns');
						
						
				if(JFactory::getApplication()->input->getInt('linkable',0))
						$this->linkable=JFactory::getApplication()->input->getInt('linkable',0);
				else
						$this->linkable=(int)$params->get('linkable');
						
				if(JFactory::getApplication()->input->getInt('listingjoin',0))
						$this->ListingJoin=JFactory::getApplication()->input->getInt('listingjoin',0);
				else
						$this->ListingJoin=(int)$params->get('listingjoin');

				

		}
		
		
		
		
		function getPagination()
		{
        
				// Load the content if it doesn't already exist
				if (empty($this->_pagination)) {
				    jimport('joomla.html.pagination');
					$a= new JPagination($this->TotalRows, $this->getState('limitstart'), $this->getState('limit') );
					return $a;

				}
				return $this->_pagination;
		}

		
		
	
		

	function getStructure()
	{
		if(!$this->fieldtype=='customtables')
				return array();
				
		$wherearr=array();
		
		if(JFactory::getApplication()->input->getString('alpha'))
		{
				$parentid=Tree::getOptionIdFull($this->optionname);
				$wherearr[]='INSTR(familytree,"-'.$parentid.'-") AND SUBSTRING(title'.$this->ct->Languages->Postfix.',1,1)="'.JFactory::getApplication()->input->getString('alpha').'"';
		}
		else
		{
				$this->parentid=$es->getOptionIdFull($this->optionname);
				$wherearr[]='parentid='.(int)$this->parentid;
		}
				
		
		$db = JFactory::getDBO();

		
		$where='';
		if(count($wherearr)>0)
				$where = ' WHERE '.implode(" AND ",$wherearr);

		
		if($this->ListingJoin)
		{
				$query = 'SELECT optionname, '
						.'CONCAT("",familytreestr,".",optionname) as theoptionname, '
						.'CONCAT( title'.$this->ct->Languages->Postfix.'," (",COUNT(#__customtables_table_'.$this->establename.'.id),")") AS optiontitle, '
						.'image, '
						.'imageparams '
						
						.'FROM #__customtables_options '
						.' INNER JOIN #__customtables_table_'.$this->establename
						.' ON INSTR(es_'.$this->esfieldname.', CONCAT(familytreestr,".",optionname))'
						.' '.$where
						.' GROUP BY #__customtables_options.id'
						.' ORDER BY title'.$this->ct->Languages->Postfix;
						

		}
		else
		{
				$query = 'SELECT optionname, '
						.'CONCAT("",familytreestr,".",optionname) as theoptionname, '
						.'title'.$this->ct->Languages->Postfix.' AS optiontitle, '
						.'image, '
						.'imageparams '
						
						.'FROM #__customtables_options '
						.' '.$where
						.' ORDER BY title'.$this->ct->Languages->Postfix;
				

		}
		
        
		$db->setQuery($query);
	        $db->execute();
		
		$this->TotalRows=$db->getNumRows();
		
		
		$db->setQuery($query, $this->getState('limitstart') , $this->getState('limit'));
        			
		$rows=$db->loadAssocList();
		$newrows=array();
		foreach($rows as $row)
		{
			$newrows[]=$row;
		}

		
		return $newrows;

		
	}
	

	
}
