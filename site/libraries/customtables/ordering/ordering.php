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
use \JoomlaBasicMisc;
use CustomTables\Fields;
use \ESTables;

//Old CTOrdering
class Ordering
{
	var $Table=null;
	var $inner=null;
	var $selects=null;
	var $orderby=null;
	
	var $ordering_processed_string=null;
	
	function __construct(&$Table)
	{
		$this->Table = $Table;
	}
	
    function parseOrderByString()
	{
		//Order by string examples:
		//name desc
		//_id
		//client.user desc
		//birthdate:%m%d DESC converted to DATE_FORMAT(realfieldname,"%m%d") DESC;
		
		if(strpos($this->ordering_processed_string,"DATE_FORMAT")!==false)
		{
			//date_format()
			$this->orderby = $this->ordering_processed_string;
			return true;
		}
		
		
		
		$inners = [];
	
		$oPair=explode(' ',$this->ordering_processed_string);
		$oPair2=explode('.',$oPair[0]);
		$orderby_field = $oPair2[0];
		$subtype='';
		if(isset($oPair2[1]) and $oPair2[1]!='')
			$subtype=$oPair2[1];
			
		$direction='';
		if(isset($oPair[1]))
		{
			$direction = strtolower($oPair[1]);
			$direction = ($direction=='desc' ? ' DESC' : '');
		}
			
		if($orderby_field=='_id')
		{
			$this->orderby = 'id'.$direction;
			return true;
		}
		elseif($orderby_field=='_published')
		{
			$this->orderby = 'published'.$direction;
			return true;
		}
		
		$realfieldname=Fields::getRealFieldName($orderby_field, $this->Table->fields, true);
		
		if($realfieldname=='')
			return false;
		
		switch($subtype)
		{
			case 'user':
				$inners[]='LEFT JOIN #__users ON #__users.id='.$this->Table->realtablename.'.'.$realfieldname.'';
				$this->selects = 'name AS t1';
				$this->orderby = '#__users.name'.$direction;
			break;
		
			case 'customtables':
				$inners[]='LEFT JOIN #__customtables_options ON familytreestr='.$realfieldname.'';
				$this->selects = '#__customtables_options.title'.$this->Table->Languages->Postfix.' AS t1';
				$this->orderby = 'title'.$this->Table->Languages->Postfix.$direction;
			break;
			
			case 'sqljoin':
		
				if(isset($oPair2[2]))
				{
					$typeparams=explode(',',$oPair2[2]);
					$join_table=$typeparams[0];
					$join_field='';
					if(isset($typeparams[1]))
						$join_field=$typeparams[1];

					if($join_table!='' and $join_field!='')
					{
						$real_joined_fieldname=$join_field;
						if($real_joined_fieldname!='')
						{
							$join_table_row = ESTables::getTableRowByName($join_table);
							
							$w=$join_table_row->realtablename.'.id='.$this->Table->realtablename.'.'.$realfieldname;
							$this->orderby = '(SELECT '.$join_table_row->realtablename.'.es_'.$real_joined_fieldname.' FROM '.$join_table_row->realtablename.' WHERE '.$w.') '.$direction;
						}
					}
				}
			break;
				
			default:
				$this->orderby = $realfieldname.$direction;
			break;
		}
		
		if(count($inners)>0)
			$this->inner=implode(' ',$inners);
		
	}
	
	function parseOrderByParam($blockExternalVars,&$menu_params,$Itemid)
	{
		//get sort field (and direction) example "price desc"
		$jinput = Factory::getApplication()->input;
		$mainframe = Factory::getApplication();
		
		//$orderby_forced = false;
		$ordering_param_string='';
		
		if($blockExternalVars)
		{
			//module or plugin
			if($menu_params->get( 'sortby' )!='')
				$ordering_param_string=$menu_params->get( 'sortby' );
		}
		else
		{
			if($menu_params->get( 'forcesortby' )!='')
			{
				$ordering_param_string=$menu_params->get( 'forcesortby' );
				$orderby_forced = true;
			}
			elseif($jinput->get('esordering','','CMD'))
			{
				$ordering_param_string=$jinput->getString('esordering','');
				$ordering_param_string=trim(preg_replace("/[^a-zA-Z-+%.: ,_]/", "",$ordering_param_string));
			}
			else
			{
				$Itemid = $jinput->getInt('Itemid', 0);
				$ordering_param_string = $mainframe->getUserState( 'com_customtables.orderby_'.$Itemid,'' );

				if($ordering_param_string=='')
				{
					if($menu_params->get( 'sortby' )!='')
						$ordering_param_string=$menu_params->get( 'sortby' );
				}
			}
		}
/*
		if(!$orderby_forced)
		{
			$ordering_param_string_state = $mainframe->getUserState( 'com_customtables.orderby_'.$Itemid,'' );
		
			if($ordering_param_string_state != '')
				$this->ordering_processed_string = $this->processOrderingString($ordering_param_string_state);
			else
				$this->ordering_processed_string = $this->processOrderingString($ordering_param_string);
		}
		else
		*/	
		$this->ordering_processed_string = $this->processOrderingString($ordering_param_string);

		//set state
		if(!$blockExternalVars)
		{
			//component
			$mainframe->setUserState( 'com_customtables.esorderby',$this->ordering_processed_string);
		}
	}

    protected function processOrderingString($ordering_param_string)
	{
		if($ordering_param_string == '')
			return null;
			
		$ordering_processed_string = '';
		
		// Check if field exist
		$parts =explode(':',$ordering_param_string);

       	$esorderingtemp_arr=explode(' ' ,$parts[0]);
		
		$esorderingtemp_arr_pair=explode('.' ,$esorderingtemp_arr[0]);
		$fieldname=$esorderingtemp_arr_pair[0];
		$desc='';
		if(isset($esorderingtemp_arr[1]) and $esorderingtemp_arr[1]=='desc')
			$desc=' desc';
			
		if($fieldname=='_id' or $fieldname=='_published')
			return $fieldname.$desc;

		$order_params='';
        if(isset($parts[1]))
			$order_params=trim(preg_replace("/[^a-zA-Z-+%.: ,_]/", "", $parts[1]));

		foreach($this->Table->fields as $row)
		{
			if($row['fieldname']==$fieldname)
			{
				$fieldtype=$row['type'];
				$typeparams=$row['typeparams'];

				if($fieldtype=='sqljoin')
					return $fieldname.'.sqljoin.'.$typeparams.$desc;
				elseif($fieldtype=='customtables')
					return $fieldname.'.customtables.'.$desc;
				elseif($fieldtype=='userid' or $fieldtype=='user')
					return $fieldname.'.user.'.$desc;
				elseif($fieldtype!='dummy')
					return $fieldname.$desc;
				elseif($fieldtype=='date' or $fieldtype=='creationtime' or $fieldtype=='changetime' or $fieldtype=='lastviewtime')
				{
					if($order_params!='')
					{
						$db = Factory::getDBO();
						return 'DATE_FORMAT('.$row['realfieldname'].', '.$db->quote($order_params).')'.$desc;
					}
					else
						return $fieldname.$desc;
				}
			}
		}
		
		//Enable in developer mode
		//Factory::getApplication()->enqueueMessage('Order By parameter "'.$ordering_param_string.'" is incorrect or the field doesnt exist.', 'Error');
					
		return null;
	}
		
	function getSortByFields()
	{
		//default sort by fields
		$order_list=[];
		$order_values=[];
		
		$order_list[]='ID '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );
		$order_list[]='ID '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );
						
		$order_values[]='_id';
		$order_values[]='_id desc';
			
		$label=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED' ).' ';
		$order_list[]=$label.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );
		$order_list[]=$label.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );
						
		$order_values[]='_published';
		$order_values[]='_published desc';
			
		foreach($this->Table->fields as $row)
		{
			if($row['allowordering']==1)
			{
				if(!isset($row['fieldtitle'.$this->Table->Languages->Postfix]))
				{
					Factory::getApplication()->enqueueMessage(
						JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
					return null;	
				}
					
				$fieldtype=$row['type'];
				$fieldname=$row['fieldname'];
				$fieldtitle=$row['fieldtitle'.$this->Table->Languages->Postfix];
				$typeparams=$row['typeparams'];

				if($fieldtype=='string' or $fieldtype=='email' or $fieldtype=='url')
				{
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname;
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.' desc';
				}
				elseif($fieldtype=='sqljoin')
				{
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname.'.sqljoin.'.$typeparams;
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.'.sqljoin.'.$typeparams.' desc';
				}
				elseif($fieldtype=='phponadd' or $fieldtype=='phponchange')
				{
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname;
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.' desc';
				}
				elseif($fieldtype=='int' or $fieldtype=='float')
				{
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_MINMAX' );			$order_values[]=$fieldname;
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_MAXMIN' );			$order_values[]=$fieldname." desc";
				}
				elseif($fieldtype=='changetime' or $fieldtype=='creationtime' or $fieldtype=='date')
				{
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NEWOLD' );			$order_values[]=$fieldname." desc";
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OLDNEW' );			$order_values[]=$fieldname;
				}
				elseif(	$fieldtype=='multilangstring')
				{
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname.$this->Table->Languages->Postfix;
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.$this->Table->Languages->Postfix." desc";
				}
				elseif(	$fieldtype=='customtables')
				{
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname.'.customtables';
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.'.customtables desc';
				}
				elseif(	$fieldtype=='userid' or $fieldtype=='user')
				{
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname.'.user';
					$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.'.user desc';
				}
			}
		}
		return (object)['titles'=>$order_list,'values'=>$order_values];
	}
}
