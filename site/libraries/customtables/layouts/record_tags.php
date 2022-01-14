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

use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\SearchInputBox;

use \JoomlaBasicMisc;
use \ESTables;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;

class Twig_Record_Tags
{
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
	function id()
	{
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.id }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Table->record))
		{
			Factory::getApplication()->enqueueMessage('{{ record.id }} - Record not loaded.', 'error');
			return '';
		}

		return $this->ct->Table->record['listing_id'];
	}
	
	function published($type,$second_variable = null)
	{
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.published }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Table->record))
		{
			Factory::getApplication()->enqueueMessage('{{ record.published }} - Record not loaded.', 'error');
			return '';
		}
		
		if($type == 'yesno')
			$vlu = (int)$this->ct->Table->record['listing_published']==1 ? JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') : JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');
		elseif($options[$i]=='bool' or $options[$i]=='boolean')
			return (bool)(int)$this->ct->Table->record['listing_published'];
		else
		{
			if($second_variable != null)
				return $this->ct->Table->record['listing_published']==1 ? $type : $second_variable;
			else
				return (int)$this->ct->Table->record['listing_published'];
		}
	}
	
	function number()
	{
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.number }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Table->record))
		{
			Factory::getApplication()->enqueueMessage('{{ record.number }} - Record not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Table->record['_number']))
		{
			Factory::getApplication()->enqueueMessage('{{ record.number }} - Record number not set.', 'error');
			return '';
		}
		
		return (int)$this->ct->Table->record['_number'];
	}
	
	function count($full_sentence = false)
	{
		if($this->ct->Env->frmt == 'csv')
			return '';	
			
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.count }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Records))
		{
			Factory::getApplication()->enqueueMessage('{{ record.count }} - Records not loaded.', 'error');
			return '';
		}
		
		if($full_sentence)
		{
			$vlu = '<span class="ctCatalogRecordCount">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FOUND' ).': '.$this->ct->Table->recordcount
				.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RESULT_S' ).'</span>';
				
			return new \Twig\Markup($vlu, 'UTF-8' );
		}
		else
			return $this->ct->Table->recordcount;
	}
	
	function list()
	{
		if($this->ct->Env->frmt == 'csv')
			return '';	
			
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.count }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Records))
		{
			Factory::getApplication()->enqueueMessage('{{ record.list }} - Records not loaded.', 'error');
			return '';
		}
		
		if($this->ct->Table->recordlist == null)
			$this->ct->getRecordList();
		
		return implode(',',$this->ct->Table->recordlist);
	}
	
	function link($add_returnto = false, $menu_item_alias='', $custom_not_base64_returnto = '')
	{
		$menu_item_id=0;
        $viewlink='';
		
		if($menu_item_alias!="")
		{
			$menu_item=JoomlaBasicMisc::FindMenuItemRowByAlias($menu_item_alias);//Accepts menu Itemid and alias
			if($menu_item!=0)
			{
				$menu_item_id=(int)$menu_item['id'];
				$link=$menu_item['link'];

				if($link!='')
					$viewlink=JoomlaBasicMisc::deleteURLQueryOption($link, 'view');
			}
		}

		if($viewlink=='')
			$viewlink = 'index.php?option=com_customtables&view=details';
			
		if($this->ct->Table->alias_fieldname !='')
		{
			$alias = $this->ct->Table->record[$this->ct->Env->field_prefix.$this->ct->Table->alias_fieldname] ?? '';
			if($alias != '')
				$viewlink .= '&alias='.$alias;
			else
				$viewlink .= '&listing_id='.$this->ct->Table->record['listing_id'];
		}
		else
			$viewlink .= '&listing_id='.$this->ct->Table->record['listing_id'];

		$viewlink .= '&Itemid=' . ($menu_item_id == 0 ? $this->ct->Env->Itemid : $menu_item_id);

		$viewlink=JoomlaBasicMisc::deleteURLQueryOption($viewlink, 'returnto');

		if($add_returnto)
		{
			if($custom_not_base64_returnto)
				$returnto = base64_encode($custom_not_base64_returnto);			
			else
				$returnto = base64_encode($this->ct->Env->current_url.'#a'.$this->ct->Table->record['listing_id']);			
		
			$viewlink .= ($returnto!='' ? '&returnto='.$returnto : '');
		}

		$viewlink=Route::_($viewlink);
		return new \Twig\Markup($viewlink, 'UTF-8' ); //Twig replaces & with &amp;
    }
	
	protected function join_getRealFieldName($fieldname,$tableid)
	{
		if($fieldname=='_id')
		{
			return [$this->ct->Table->tablerow['realidfieldname'],'_id'];
		}
		elseif($fieldname=='_published')
		{
			if($this->ct->Table->tablerow['published_field_found'])
				return ['published','_published'];
			else
				Factory::getApplication()->enqueueMessage('{{ record.join }} - Table doesn\' have "published" field.', 'error');
		}
		else
		{
			$field1_row=Fields::getFieldRowByName($fieldname, $tableid);
			if(is_object($field1_row))
			{
				return [$field1_row->realfieldname,$field1_row->type];
			}
			else
				Factory::getApplication()->enqueueMessage('{{ record.join }} - Field "'.$fieldname.'" not found.', 'error');
		}
		return null;
	}
	
	protected function join_processWhere($additional_where,$sj_tablename)
	{
		if($additional_where == '')
			return '';
		
		$w=array();
		
		$af=explode(' ',$additional_where);
		foreach($af as $a)
		{
			$b=strtolower(trim($a));
			if($b!='')
			{
				if($b!='and' and $b!='or')
				{
					$b=str_replace('$now','now()',$b);

					//read $get_ values
					$b=$this->join_ApplyQueryGetValue($b,$sj_tablename);

					$w[]=$b;
				}
				else
					$w[]=$b;
			}
		}
		return implode(' ',$w);
	}
	
	protected function join_buildQuery($sj_function, &$tablerow, $field1_findwhat, $field2_lookwhere, $field2_type, $field3_readvalue, $additional_where, $order_by_option)
	{
		$db = Factory::getDBO();
		
		if($sj_function=='count')
			$query = 'SELECT count('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
		elseif($sj_function=='sum')
			$query = 'SELECT sum('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
		elseif($sj_function=='avg')
			$query = 'SELECT avg('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
		elseif($sj_function=='min')
			$query = 'SELECT min('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
		elseif($sj_function=='max')
			$query = 'SELECT max('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
		else
		{
			//need to resolve record value if it's "records" type
			$query = 'SELECT '.$tablerow['realtablename'].'.'.$field3_readvalue.' AS vlu '; //value or smart
		}

		$query.=' FROM '.$this->ct->Table->realtablename.' ';
		
		$sj_tablename = $tablerow['tablename'];

		if($this->ct->Table->tablename != $sj_tablename)
		{
			// Join not needed when we are in the same table
			$query.=' LEFT JOIN '.$tablerow['realtablename'].' ON ';

			if($field2_type=='records')
			{
				$query.='INSTR('.$tablerow['realtablename'].'.'.$field2_lookwhere.',  CONCAT(",",'.$this->ct->Table->realtablename.'.'.$field1_findwhat.',","))' ;
			}
			else
			{
				$query.=' '.$this->ct->Table->realtablename.'.'.$field1_findwhat.' = '
					.' '.$tablerow['realtablename'].'.'.$field2_lookwhere;
			}
		}

		$wheres=array();

		if($this->ct->Table->tablename != $sj_tablename)
		{
			//don't attach to specific record when it is the same table, example : to find averages
			$wheres[]=$this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'='.$db->quote($this->ct->Table->record['listing_id']);
		}
		else
		{
			//$wheres[]='#__customtables_table_'.$sj_tablename.'.published=1';//to join with published record only, preferably set in parameters
		}

		if($additional_where!='')
			$wheres[]='('.$additional_where.')';

		if(count($wheres)>0)
			$query.=' WHERE '.implode(' AND ', $wheres);

		if($order_by_option!='')
			$query.=' ORDER BY '.$tablerow['realtablename'].'.'.$order_by_option;

		$query.=' LIMIT 1';
		
		return $query;
	}
	
	
	function join_ApplyQueryGetValue($str,$sj_tablename)
	{
		$list=explode('$get_',$str);
		if(count($list)==2)
		{
			$q=$list[1];

			$v=$this->ct->Env->jinput->getString($q);
			$v=str_replace('"','',$v);
			$v=str_replace("'",'',$v);

			if(strpos($v,','))
			{
				$f='#__customtables_table_'.$sj_tablename.'.es_'.str_replace('$get_'.$q,'',$str);
				$values=explode(',',$v);


				$vls=array();
				foreach($values as $v1)
				{
					$vls[]=$f.'"'.$v1.'"';
				}

				$v='('.implode(' or ',$vls).')';
				return $v;
			}

			return '#__customtables_table_'.$sj_tablename.'.es_'.str_replace('$get_'.$q,'"'.$v.'"',$str);
		}
        else
        {
            if(strpos($str,'_id')!==false)
                return '#__customtables_table_'.$sj_tablename.'.'.str_replace('_id','listing_id',$str);
            elseif(strpos($str,'_published')!==false)
                return '#__customtables_table_'.$sj_tablename.'.'.str_replace('_published','published',$str);
        }

		$str=str_replace('!=null',' IS NOT NULL',$str);
		$str=str_replace('=null',' IS NULL',$str);

		return '#__customtables_table_'.$sj_tablename.'.es_'.$str;
	}
	
	function join($sj_function, $sj_tablename, $field1_findwhat, $field2_lookwhere, $field3_readvalue, $additional_where, $order_by_option, $value_option_list)
	{
		if($sj_tablename=='')	return '';

		$tablerow = ESTables::getTableRowByNameAssoc($sj_tablename);
		if(!is_array($tablerow)) return '';

		//field1_findwhat
		$field_details = $this->join_getRealFieldName($field1_findwhat, $this->ct->Table->tablerow['id']);
		if($field_details == null) return '';
		$field1_findwhat_realname = $field_details[0];
		
		$field_details = $this->join_getRealFieldName($field2_lookwhere, $tablerow['id']);
		if($field_details == null)	return '';
		$field2_lookwhere_realname = $field_details[0];
		$field2_type = $field_details[1];
		
		$field_details = $this->join_getRealFieldName($field3_readvalue, $tablerow['id']);
		if($field_details == null)	return '';
		$field3_readvalue_realname = $field_details[0];
		
		$sj_tablename = $tablerow['tablename'];
		$additional_where = $this->join_processWhere($additional_where, $sj_tablename);

		if($order_by_option!='')
		{
			$field_details = $this->join_getRealFieldName($order_by_option, $tablerow['id']);
			$order_by_option_realname = $field_details[0] ?? '';
		}
		else
			$order_by_option_realname = '';

		$query = $this->join_buildQuery($sj_function, $tablerow, $field1_findwhat_realname, $field2_lookwhere_realname, 
				$field2_type, $field3_readvalue_realname, $additional_where, $order_by_option_realname);
		
		$db = Factory::getDBO();
		
		$db->setQuery($query);

		$rows=$db->loadAssocList();

		if(count($rows)==0)
		{
			$vlu='';
		}
		else
		{
			$row=$rows[0];

			if($sj_function=='smart')
			{
				$getGalleryRows=array();
				$getFileBoxRows=array();
				$vlu=$row['vlu'];

				$temp_ctfields = Fields::getFields($tablerow['id']);

				foreach($temp_ctfields as $ESField)
				{
					if($ESField['fieldname']==$field3_readvalue)
					{
						$ESField['realfieldname'] = 'vlu';
						
						$valueProcessor = new Value($this->ct);
						$vlu = $valueProcessor->renderValue($ESField,$row,$value_option_list);
						
						break;
					}
				}
			}
			else
				$vlu = $row['vlu'];
		}
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
}