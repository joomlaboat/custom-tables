<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CustomTablesMisc
{
	static public function array_insert(&$array, $insert, $position = -1)
	{
	    $position = ($position == -1) ? (count($array)) : $position ;
	    if($position != (count($array))) {
	    $ta = $array;
	    for($i = $position; $i < (count($array)); $i++) {
               if(!isset($array[$i])) {
                    die("Invalid array: All keys must be numerical and in sequence.");
               }
               $tmp[$i+1] = $array[$i];
               unset($ta[$i]);
	    }
	    $ta[$position] = $insert;
	    $array = $ta + $tmp;

	    } else {
	         $array[$position] = $insert;
	    }
	    ksort($array);
	    return true;
	}
	
	function getAllParents($optionid,$filter_rootparent)
	{
		
		if($filter_rootparent)
		{
		    $available_categories=$this->getAllChild($optionid,$filter_rootparent,1);
		    
		    $db = JFactory::getDBO();
		    $query = ' SELECT optionname, id AS id FROM #__customtables_options WHERE ';
		    $query.= ' id='.$filter_rootparent.' LIMIT 1';
	    
		    $db->setQuery( $query );
		
		    $rpname= $db->loadObjectList();
		    if(count($rpname)==1)
			$this->array_insert(
			    $available_categories,
			    array("id" => $filter_rootparent, "name" => strtoupper($rpname[0]->optionname)),0);
		}
		else
		{
		    $available_categories=$this->getAllChild($optionid,0,1);
		}
				
		
	
		
		$this->array_insert($available_categories,array("id" => 0, "name" => JText::_( 'ROOT' )),count($available_categories));
		
		
				
		return $available_categories;

	}
	
	
	
	function getAllChildren($parentid,$langpostfix)
	{
		$db = JFactory::getDBO();
		$query = ' SELECT id, optionname, title_'.$langpostfix.' AS title FROM #__customtables_options WHERE parentid='.$parentid;
	    $query.= ' ORDER BY title';
		
		$db->setQuery($query);
		
		return $db->loadObjectList();
	}
	
	function getAllChild($optionid,$parentid,$level)
	{
	    $db = JFactory::getDBO();
	    
	    $result=array();
	    
	    
	    
	    $query = ' SELECT concat("'.str_repeat('- ',$level).'", optionname) AS name, id FROM #__customtables_options WHERE id!='.$optionid.' ';
	    $query.= ' AND parentid='.$parentid;
	    $query.= ' ORDER BY name';
	    

	    $db->setQuery( $query );
		
	    $rows= $db->loadObjectList();
	    
	    
	    foreach($rows as $item)
	    {
		
			$this->array_insert($result,array("id" => $item->id, "name" => $item->name),count($result));
		
		
			$childs=$this->getAllChild($optionid,$item->id,$level+1);
			if(count($childs)>0)
			{
			    $result=array_merge($result,$childs);
			}
	    }


	    
	
	    return $result;
	}
    
	function getAllRootParents()
	{
		$db = JFactory::getDBO();
		
		$query = "SELECT id, optionname FROM #__customtables_options WHERE parentid=0 ORDER BY optionname";
		$db->setQuery( $query );
		$available_rootparents = $db->loadObjectList();
		$this->array_insert($available_rootparents,array("id" => 0, "optionname" => JText::_( '-Select Parent' )),0);
		return $available_rootparents;

	}
	
	//getOptionTitle
	
	function getMultyValueTitles($PropertyTypes,$langpostfix,$StartFrom, $Separator,$TypeParams='')
	{
		if(strpos($PropertyTypes,'.')===false)
		{
			//Try to complete the value
			$paramslist=JoomlaBasicMisc::csv_explode(',', $TypeParams,'"', false);
			
			$PropertyTypes=','.$paramslist[0].'.'.$PropertyTypes.'.,';
		}
		
		$RowPropertyTypes=explode(",", $PropertyTypes);

		$titles=array();
		foreach($RowPropertyTypes as $row)
		{
			$a=trim($row);
			if(strlen($a)>0)
			{
				$b=$this->	getOptionTitleFullMulti($a,$langpostfix,$StartFrom);
				$titles[]=implode($Separator, $b);
			}
		}
		return $titles;
	}
	
	function getMultyValueFinalTitles($PropertyTypes,$langpostfix,$StartFrom)
	{
		$RowPropertyTypes=explode(",", $PropertyTypes);

		$titles=array();
		foreach($RowPropertyTypes as $row)
		{
			$a=trim($row);
			if(strlen($a)>0)
			{
				$b=$this->	getOptionTitleFullMulti($a,$langpostfix,$StartFrom);
				if(count($b)>0)
					$titles[]=$b[count($b)-1];
			}
		}
		return $titles;
	}
	
	
	
	function getOptionTitleFullMulti($optionname,$langpostfix,$StartFrom)
	{
		$names=explode(".",$optionname);
		$parentid=0;
	
		$title=array();
		$i=0;
		foreach($names as $optionname)
		{
			if($optionname=='')
				break;
			
			$a="";
		    $parentid=$this->getOptionTitle($optionname,$parentid,$a,$langpostfix);
			if($i>=$StartFrom)
				$title[]=$a;
			$i++;
		}
    
		return $title;
	}
	function getOptionTitleFull($optionname,$langpostfix)
	{
		$names=explode(".",$optionname);
		$parentid=0;
	
		$title="";
		foreach($names as $optionname)
		{
			$optionname=$optionname;
			if($optionname=='')
				break;
			

		    $parentid=$this->getOptionTitle($optionname,$parentid,$title,$langpostfix);
		}
    
		return $title;
	}
	
	function getOptionTitle($optionname,$parentid,&$title,$langpostfix)
	{
		// get database handle
		$db = JFactory::getDBO();
			
		$query = 'SELECT id, title'.$langpostfix.' AS title FROM #__customtables_options WHERE parentid='.$parentid.' AND optionname="'.$optionname.'" LIMIT 1';
		
		$db->setQuery($query);
                
		$rows=$db->loadObjectList();
	
		if(count($rows)!=1){
			$title="[no name]";
			return 0;
		}
	
		$title=$rows[0]->title;
		return $rows[0]->id;
	}

	
	
	
	function getParamValue($pars,$lookfor)
	{
		$r=explode('.',$lookfor);
		$c=count($r);
		foreach($pars as $par)
		{
			$p=strpos($par,$lookfor.".");
			if(!($p===false))
			{
				if($p==0)
				{
					$res=explode(".",$par);
					if(count($res)>1) return $res[$c];
				}
			}
		}
		
		return "";
	}
	
	function getParamFullValue($pars,$lookfor)
	{
		foreach($pars as $par)
		{
			$p=strpos($par,$lookfor.".");
			if(!($p===false))
			{
				if($p==0)
				{
					return $par;
				}
			}
		}
		
		return "";
	}

	function CleanLinkMulti($newparams, $deletewhat)
	{
		foreach($deletewhat as $what)
		{
			$newparams=$this->CleanLink($newparams, $what);
		}
		return $newparams;
	}
	
	function CleanLink($newparams, $deletewhat)
	{
	
		$i=0;
		do
		{
		    $npv=substr($newparams[$i],0,strlen($deletewhat));
		    if(!(strpos($npv,$deletewhat)===false))
		    {
			unset($newparams[$i]);
			$newparams=array_values($newparams);
			if(count($newparams)==0) return $newparams;
			
			$i=0;
			
		    }
		    else 
			$i++;
	    
		}while($i<count($newparams));
	    
		return $newparams;
    
	}

	function getOnlyOneParam($vlu)
	{
		
		$vluarray=explode(',',$vlu);
		foreach($vluarray as $item)
		{
			$a=trim($item);
			if($a!='')
			{
				return $a;
			}
			
		}
		return '';
	}
	
	function ShortenParam($vlu, $count)
	{
		$vluarr=explode('.',$vlu);
		$parms=array();
		$c=0;
		foreach($vluarr as $item)
		{
			$parms[]=$item;
			$c++;
			if($c>=$count)
				break;
		}
		return implode('.',$parms);
	}
	
	function ShortenParambyOne($vlu)
	{
		$vluarr=explode('.',$vlu);
		$count=count($vluarr)-2;

		if($count==0)
			return '';
		
		
		$parms=array();
		$c=0;
		foreach($vluarr as $item)
		{
			$parms[]=$item;
			$c++;
			if($c>=$count)
				break;
		}

		return implode('.',$parms);
	}
	
	function BuildULHtmlList(&$vlus,&$index,$langpostfix, $isFirstElement=true,$last='')
	{
		$parent='topics';
		$parentid=$this->getOptionIdFull($parent);
		$count=0;
		$field_value=implode(',',$vlus);
		$ItemList='';
		return $this->getMultiSelector($parentid,$parent,$langpostfix,$ItemList,$count,$field_value);
	}
	
	//--------------------------

	
	function getMultiSelector($parentid,$parentname,$langpostfix,&$ItemList,&$count,$field_value)
	{
		$ObjectName='temp_object';
		
		$result='';
		$rows=$this->getList($parentid, $langpostfix);

		if(count($rows)<1)
			return "";
		
		$result.='<ul>';
		$IDs=array();
		
		$count=count($rows);
		foreach($rows as $row)
		{
			
			$IDs[]=$row->id;
			
			$temp_Ids="";
			$count_child=0;
			
			if(strlen($parentname)==0)
				$optionnamefull=$row->optionname;
			else
				$optionnamefull=$parentname.'.'.$row->optionname;
				
			
			$ChildHTML=$this->getMultiSelector($row->id,$optionnamefull,$langpostfix,$temp_Ids,$count_child,$field_value);
			
			
			
			
			if($count_child>0)
			{
				if((strpos($field_value,$optionnamefull.'.')===false))
				{
					/*
					//<input type="checkbox" id="'.$ObjectName.'_'.$row->id.'" name="'.$ObjectName.'_'.$row->id.'" onClick=\'CustomTablesChildClick("'.$ObjectName.'_'.$row->id.'", "div'.$ObjectName.'_'.$row->id.'")\'  >
					$result.='<li>';
					//$result.='<b>'.$row->title.'</b> ('.$count_child.')';
					$result.='<div name="div'.$ObjectName.'_'.$row->id.'" id="div'.$ObjectName.'_'.$row->id.'" style="display: none;">';
					*/
				}
				else
				{
					//<input type="checkbox" id="'.$ObjectName.'_'.$row->id.'" name="'.$ObjectName.'_'.$row->id.'" onClick=\'CustomTablesChildClick("'.$ObjectName.'_'.$row->id.'", "div'.$ObjectName.'_'.$row->id.'")\' checked="checked" >
					
					
					if($ChildHTML=='')
						$result.='<li class="esSelectedElement">';
					else
						$result.='<li class="esElementParent">';
					 
					
					//$result.='<li class="esElementParent">';
					//'<span style="color:red;"><b>'.
					$result.=$row->title;//.'</b> ('.$count_child.')</span>';
					//name="div'.$ObjectName.'_'.$row->id.'" id="div'.$ObjectName.'_'.$row->id.'" style="display: block;"
					//$result.='<div>';
					
					//$result.=$ChildHTML.'</div></li>';
					$result.=$ChildHTML.'</li>';
				}
				/*
				if($count_child>1)
					{
						$result.='
				<div style="margin-left:100px">
				<a href=\'javascript:ESCheckAll("'.$ObjectName.'",Array('.$temp_Ids.'))\'>'.JText::_( 'CHECK ALL' ).'</a>&nbsp;&nbsp;
				<a href=\'javascript:ESUncheckAll("'.$ObjectName.'",Array('.$temp_Ids.'))\'>'.JText::_( 'UNCHECK ALL' ).'</a>
				</div>';
					}
					*/
				
							
				
			}
			else
			{
				
				
				if((strpos($field_value,$parentname.'.'.$row->optionname.'.')===false))
					$ItemSelected=false;
				else
					$ItemSelected=true;
				
				//<input type="checkbox" name="'.$ObjectName.'_'.$row->id.'" id="'.$ObjectName.'_'.$row->id.'" '.($ItemSelected ? ' checked="checked" ' :'').'>
//				<span style="color:red;"></span>
				if($ItemSelected)
					$result.='<li class="esSelectedElement">'.$row->title.'</li>';
				//else
					//$result.='<li><span style="">'.$row->title.'</span></li>';
				
			}
			

		}
		
		if($result=='<ul>')
			$result=''; //empty block
		else
			$result.='</ul>';
		
		
		
		$ItemList='"'.implode('","',$IDs).'"';
		
				
		return $result;
	}
	
	
	
	
	
	function getList($parentid, $langpostfix)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT id, optionname, title'.$langpostfix.' AS title FROM #__customtables_options WHERE parentid='.(int)$parentid;
		$query.=' ORDER BY ordering, title';
		$db->setQuery($query);
                
		return $db->loadObjectList();
	}

	
	function getESValue(&$a, &$innerrows)
	{
		if(!$a)return false;
		
		foreach($innerrows as $row)
		{
			if($row->id==$a)
			{
				$a=$row->title;
				return true;
			}
		}
		return false;
	}



	//Get Option ID
	function getOptionIdFull($optionname)
	{
	$names=explode(".",$optionname);
	$parentid=0;
	
	foreach($names as $name)
	{
	    $parentid=$this->getOptionId($name,$parentid);
	}
    
	return $parentid;
	}
	
	
	function getOptionId($optionname,$parentid)
	{
	// get database handle
		$db = JFactory::getDBO();
		
	$query = 'SELECT id FROM #__customtables_options WHERE parentid='.$parentid.' AND optionname="'.$optionname.'" LIMIT 1';
		
	$db->setQuery($query);

	$rows=$db->loadObjectList();
	
	if(count($rows)!=1)return 0;
	
	return $rows[0]->id;
    }

	function getOptionLinkFull($optionname)
	{
		$optid=$this->getOptionIdFull($optionname);
		
		if($optid==0)
			return "";
		
		
		$db = JFactory::getDBO();
		$query = 'SELECT link FROM #__customtables_options WHERE id='.$optid.' LIMIT 1';
		
		$db->setQuery($query);

		$rows=$db->loadObjectList();
	
		if(count($rows)!=1)
			return "";
		
		return $rows[0]->link;
	}
	
	function isRecordExist($checkvalue,$checkfield, $resultfield, $table)
	{
		$db = JFactory::getDBO();
		$query =' SELECT '.$resultfield.' AS resultfield FROM '.$table.' WHERE '.$checkfield.'="'.$checkvalue.'" LIMIT 1';
		$db->setQuery( $query );

		$espropertytype= $db->loadObjectList();

		if(count($espropertytype)>0)
			return $espropertytype[0]->resultfield;	
		
		return "";
	}

	function getOptionsByID($parentid=0)
	{
		if($parentid=='')
			return array();
			
		$db = JFactory::getDBO();
			
		$query = 'SELECT * FROM #__customtables_options WHERE parentid="'.$parentid.'" ORDER BY ordering';
		
		$db->setQuery($query);

		return $db->loadAssocList();
	}
	
	function getHeritageInfo($parentid, $fieldname)
	{
		if((int)$parentid==0)
			return '';
		
		$db = JFactory::getDBO();
		
		$query = 'SELECT id, '.$fieldname.' FROM #__customtables_options WHERE parentid="'.$parentid.'" LIMIT 1';
		$db->setQuery($query);
		
		$rows=$db->loadAssocList();
		if(count($rows)==1)
		{
			$row=$rows[0];
			$vlu=$row[$fieldname];
			
			if(strlen($vlu)>0)
				return $vlu;
			else
				return $this->getHeritageInfo($row[id], $fieldname);
		}
		else
			return '';
	}
	
	function getHeritage($parentid, $where='', $limit)
	{
		if((int)$parentid==0)
			return array();
		
		$db = JFactory::getDBO();
		
		$query = 'SELECT * FROM #__customtables_options WHERE parentid="'.$parentid.'" '
			.($where!='' ? ' AND '.$where : '')
			.($limit!='' ? ' LIMIT '.$limit : '');
		$db->setQuery($query);
		
		$rows=$db->loadAssocList();
		return $rows;
	}

	function getFamilyTreeByParentID($parentid)
	{
		
		if($parentid!=0)
		{
			return $this->getFamilyTree($parentid,0).'-'.$parentid;
			
		}
		return '';
		
	}
	
	function getFamilyTree($optionid,$level)
	{
				
		$db = JFactory::getDBO();
		$query = 'SELECT parentid FROM #__customtables_options WHERE id="'.$optionid.'" LIMIT 1';
		$db->setQuery($query);

		$rows=$db->loadObjectList();
		if(count($rows)!=1)
			return '';
		
		$id=$rows[0]->parentid;
		if($id!=0)
		{
			$parentid=$this->getFamilyTree($id,$level+1);
			if($level>0)
				$parentid.='-'.$optionid;
		}
		else
		{
			if($level>0)
				$parentid=$optionid;
		}
		
		return $parentid;
	}

	
	function getFamilyTreeString($optionid,$level)
	{
				
		$db = JFactory::getDBO();
		$query = 'SELECT parentid, optionname FROM #__customtables_options WHERE id="'.$optionid.'" LIMIT 1';
		$db->setQuery($query);

		$rows=$db->loadObjectList();
		if(count($rows)!=1)
			return '';
		
		$id=$rows[0]->parentid;
		if($id!=0)
		{
			$parentstring=$this->getFamilyTreeString($id,$level+1);
			if($level>0)
				$parentstring.='.'.$rows[0]->optionname;
		}
		else
		{
			if($level>0)
				$parentstring=$rows[0]->optionname;
		}
		
		return $parentstring;
	}
	
	
}
