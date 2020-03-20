<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @version 1.7.5
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://joomlaboat.com
 * @license GNU/GPL
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');

function importCSVfile($filename, $ct_tableid)
{
    if(file_exists($filename))
  		return importCSVdata($filename,$ct_tableid);
    else
      return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_FOUND');
}

function getLines($filename)
{
  $str=file_get_contents($filename);
  
  if(ifBomUtf8($str))
  {
    $str=removeBomUtf8($str);
  }
  else
    $str=mb_convert_encoding($str, 'UTF-8','UTF-16LE');
  
  //$lines= preg_split('/([\n\r]+)/', $str, null, PREG_SPLIT_DELIM_CAPTURE);
  //$str= str_replace("\n\r","\r", $str);
  //$str= str_replace("\r\n","\r", $str);
  //$str= str_replace("\n","\r", $str);
  //$lines= explode("\r", $str);
  
  $lines = explode("\n",
                    str_replace(array("\r\n","\n\r","\r"),"\n",$str)
            );
  
  return $lines;
}
  

function importCSVdata($filename,$ct_tableid)
{
  
    $arrayOfLines = getLines($filename);
    
    $tablerow=ESTables::getTableRowByID($ct_tableid);
    $fields=ESFields::getFields($ct_tableid,true);

    $first_line_fieldnames=false;
    
    
    $line=JoomlaBasicMisc::csv_explode(',',$arrayOfLines[0],'"',false);
        
    $fieldList=prepareFieldList($line,$fields,$first_line_fieldnames);

    $offset=0;
    if($first_line_fieldnames)
      $offset=1;
      
    $db = JFactory::getDBO();
    for($i=0+$offset;$i<count($arrayOfLines);$i++)
    {
      $str=trim($arrayOfLines[$i]);
      
      if($str!='')
        $sets=prepareSQLQuery($fieldList,$fields,$arrayOfLines[$i]);
     
     
     $id=findRecord($tablerow->tablename,$sets);
     if($id==false)
     {
      $query='INSERT #__customtables_table_'.$tablerow->tablename.' SET '.implode(', ',$sets);
      $db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());
     }
    }
    
    return '';
}

function findRecord($tablename,$sets)
{
  $db = JFactory::getDBO();
  
  $wheres=implode(' AND ',$sets);
  
  $query='SELECT id FROM #__customtables_table_'.$tablename.' WHERE published=1 AND '.$wheres.' LIMIT 1';
  
  $db->setQuery($query);
	if (!$db->query())    die( $db->stderr());

	$records=$db->loadAssocList();
  
  if(count($records)==0)
    return false;
    
  return (int)$records[0]['id'];

}

function findSQLRecordJoin($tablename,$join_fieldname,$vlus_str)
{
  $db = JFactory::getDBO();
  
  $vlus=explode(',',$vlus_str);
  $wheres=array();
  foreach($vlus as $vlu)
    $wheres[]=$db->quoteName('es_'.$join_fieldname).'='.$db->quote($vlu);
  
  $query='SELECT id FROM #__customtables_table_'.$tablename.' WHERE published=1 AND ('.implode(' OR ',$wheres).')';
  
  $db->setQuery($query);
	if (!$db->query())    die( $db->stderr());

	$records=$db->loadAssocList();
  
  if(count($records)==0)
    return false;
    
  $ids=array();
  foreach($records as $record)
    $ids[]=$record['id'];
    
  return $ids;
}

function findSQLJoin($tablename,$join_fieldname,$vlu)
{
  $db = JFactory::getDBO();
  $query='SELECT id FROM #__customtables_table_'.$tablename.' WHERE published=1 AND '.$db->quoteName('es_'.$join_fieldname).'='.$db->quote($vlu).' LIMIT 1';
  $db->setQuery($query);
	if (!$db->query())    die( $db->stderr());

	$records=$db->loadAssocList();
  
  if(count($records)==0)
    return false;
    
  return $records[0]['id'];
}

function addSQLJoin($tablename,$join_fieldname,$vlu)
{
  $db = JFactory::getDBO();
  $query='INSERT #__customtables_table_'.$tablename.' SET '.$db->quoteName('es_'.$join_fieldname).'='.$db->quote($vlu);
 
  $db->setQuery($query);
	if (!$db->query())    die( $db->stderr());
  
}

function addSQLJoinSets($tablename,$sets)
{
  $db = JFactory::getDBO();
  $query='INSERT #__customtables_table_'.$tablename.' SET '.implode(',',$sets);
 
  $db->setQuery($query);
	if (!$db->query())    die( $db->stderr());
  
}

function findOneTableSQLJoinFields(&$fieldList,&$fields,$tablename_to_lookfor,$line,$type)
{
  $db = JFactory::getDBO();
  $sub_sets=array();
  $i=0;
  
  foreach($fieldList as $f_index)
  {

    
    if($f_index!=-1)
    {
      
      $fieldtype=$fields[$f_index]->type;
      if($fieldtype==$type)//'sqljoin'
      {
        $params=JoomlaBasicMisc::csv_explode(',',$fields[$f_index]->typeparams,'"',false);
        $tablename=$params[0];
        
        if(isset($params[1]))
        {
          $join_fieldname=$params[1];
        
          if($tablename==$tablename_to_lookfor)
            $sub_sets[]=$db->quoteName('es_'.$join_fieldname).'='.$db->quote($line[$i]); 
        }
        
      }
      
    }
    $i++;
  }
  return $sub_sets;
}

function prepareSQLQuery($fieldList,$fields,$line_)
{
  $line=JoomlaBasicMisc::csv_explode(',',$line_,'"',false);
  
  $db = JFactory::getDBO();
  $sets=array();
  $i=0;
    

  foreach($fieldList as $f_index)
  {

    if($f_index>=0)
    {
      
      $fieldtype=$fields[$f_index]->type;
      
      if($fieldtype=='sqljoin')
      {
        $params=JoomlaBasicMisc::csv_explode(',',$fields[$f_index]->typeparams,'"',false);
        $tablename=$params[0];
        if(isset($params[1]))
        {
          $join_fieldname=$params[1];
        
          $vlu=findSQLJoin($tablename,$join_fieldname,$line[$i]);
          
          if($vlu==false)
          {
            $sub_sets=findOneTableSQLJoinFields($fieldList,$fields,$tablename,$line,'sqljoin');
            
            addSQLJoinSets($tablename,$sub_sets);
            $vlu=findSQLJoin($tablename,$join_fieldname,$line[$i]);
          }
          
          if((int)$vlu>0)
            $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'='.(int)$vlu;
          else
            $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'=NULL';
          
        }
      }
      elseif($fieldtype=='records')
      {
        $params=JoomlaBasicMisc::csv_explode(',',$fields[$f_index]->typeparams,'"',false);
        $tablename=$params[0];
        if(isset($params[1]))
        {
          $join_fieldname=$params[1];

         
          $vlu=findSQLRecordJoin($tablename,$join_fieldname,$line[$i]);
         
          if($vlu==false)
          {
            
            //die;
            //$sub_sets=findOneTableSQLRecordJoinFields($fieldList,$fields,$tablename,$line,'records');
            
            //addSQLJoinSets($tablename,$sub_sets);
            //$vlu=findSQLRecordJoin($tablename,$join_fieldname,$line[$i]);
          }
           
          if($vlu!=false)
            $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'='.$db->quote(','.implode(',',$vlu).',');
          else
            $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'=NULL';
        }
      }
      elseif($fieldtype=='date' or $fieldtype=='creationtime' or $fieldtype=='changetime')
      {
        if(isset($line[$i]) and $line[$i]!='')
          $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'='.$db->quote($line[$i]);
        else
          $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'=NULL';
      }
      elseif($fieldtype=='int' or $fieldtype=='user' or $fieldtype=='userid')
      {
        if(isset($line[$i]) and $line[$i]!='')
          $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'='.(int)$line[$i];
        else
          $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'=NULL';
      }
      elseif($fieldtype=='float')
      {
        if(isset($line[$i]) and $line[$i]!='')
          $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'='.(float)$line[$i];
        else
          $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'=NULL';
      }
      else
      {

        if(isset($line[$i]))//count($line)>$i and 
        {
          $vlu=$line[$i];
          $sets[]=$db->quoteName('es_'.$fields[$f_index]->fieldname).'='.$db->quote($vlu);
        }
      }
        
    }
    
    
    
    
    $i++;
  }

  return $sets;
}


function ifBomUtf8($s)
{
  if(substr($s,0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF')))
  {
       return true;
  }
  else
  {
      return true;
  }
  return false;
}

function removeBomUtf8($s)
{
  if(substr($s,0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF')))
  {
       return substr($s,3);
  }
  else
  {
      if(substr($s,0,2)==chr(hexdec('FF')).chr(hexdec('FE')))
          return substr($s,2);

       return $s;
  }
}

function prepareFieldList($fieldNames,$fields,&$first_line_fieldnames)
{
    $fieldList=array();
    
    //Lets check if first line is the field names
    $count=0;
    $found=false;
    
    foreach($fieldNames as $fieldName_)
    {

        $index=0;
        
        $fieldName=removeBomUtf8($fieldName_);
        $fieldName=preg_replace("/[^a-zA-Z1-9 #]/", "", $fieldName);
        $found=false;
        foreach($fields as $field)
        {
            $clean_field_name=preg_replace("/[^a-zA-Z1-9 #]/", "", $field->fieldtitle);
      
            if((string)$fieldName=='#')
            {
                $fieldList[]=-1;
                $found=true;
                $count++;
                $first_line_fieldnames=true;
                break;
            }
            else if((string)$clean_field_name==(string)$fieldName)
            {
                $first_line_fieldnames=true;
                $fieldList[]=$index;
                $found=true;
                $count++;
                break;
            }
            
              
            $index++;
        }
        
        if(!$found)
        {
              $count++;
              $fieldList[]=-2;
        }
    }
    
    /*
    if(!$found)
    {    
      //not found
        $i=0;
        foreach($fields as $field)
        {
          $fieldList[]=$i;
                
          if($i>=count($fieldNames))
            break;
          
          $i++;
        }
    }
    else
    */
      $first_line_fieldnames=true;
    
    return $fieldList;
}
