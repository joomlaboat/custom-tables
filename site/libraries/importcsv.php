<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://joomlaboat.com
 * @license GNU/GPL
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;

function importCSVfile($filename, $ct_tableid)
{
    if(file_exists($filename))
  		return importCSVdata($filename,$ct_tableid);
    else
		return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_FOUND');
}

function getLines($filename)
{
	$delimiter = detectDelimiter($filename);
	
	if (($handle = fopen($filename, "r")) !== FALSE)
	{
		$lines = [];
		$enclosure = "\"";
		
		while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE)
			$lines[] = $data;

		fclose($handle);
		return $lines;
	}
	return null;
}

//https://stackoverflow.com/questions/26717462/php-best-approach-to-detect-csv-delimiter/59581170  
function detectDelimiter($csvFile)
{
	//first line is a list of field name, so this approach is ok here
    $delimiters = [";" => 0, "," => 0, "\t" => 0, "|" => 0];

    $handle = fopen($csvFile, "r");
    $firstLine = fgets($handle);
    fclose($handle); 
    foreach ($delimiters as $delimiter => &$count) {
        $count = count(str_getcsv($firstLine, $delimiter));
    }

    return array_search(max($delimiters), $delimiters);
}

function processFieldParams(&$fieldList, &$fields)
{
	foreach($fieldList as $f_index)
	{
		if($f_index>=0)
		{
			$fieldtype=$fields[$f_index]->type;
			if($fieldtype=='sqljoin')
			{
				$type_params=JoomlaBasicMisc::csv_explode(',',$fields[$f_index]->typeparams,'"',false);
			
				$tablename=$type_params[0];
				$fieldname=$type_params[1];
				
				$tablerow=ESTables::getTableRowByName($tablename);
				if(!is_object($tablerow))
				{
					echo json_encode(['error' => 'sqljoin field('.$fields[$f_index]->fieldtitle.') table not found']);
					die;
				}
				
				$sqljoin_field = Fields::getFieldRowByName($fieldname, $tablerow->id);

				$fields[$f_index]->sqljoin=(object)[
					'table'=>$tablerow->realtablename,
					'field'=>$sqljoin_field->realfieldname,
					'realidfieldname'=>$tablerow->realidfieldname,
					'published_field_found'=>$tablerow->published_field_found];
			}
		}
	}
	
	return $fields;
}

function importCSVdata($filename,$ct_tableid)
{
    $arrayOfLines = getLines($filename);
	if($arrayOfLines == null)
		return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CSV_FILE_EMPTY');		

    $tablerow=ESTables::getTableRowByID($ct_tableid);
    $fields = Fields::getFields($ct_tableid,true);

    $first_line_fieldnames=false;
    
	$line = $arrayOfLines[0]; //JoomlaBasicMisc::csv_explode(',',$arrayOfLines[0],'"',false);
	
    $fieldList=prepareFieldList($line,$fields,$first_line_fieldnames);
	$fields = processFieldParams($fieldList,$fields);

	foreach($fieldList as $f)
	{
		if($f==-2)
			return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FIELD_NAMES_DO_NOT_MATCH');		
	}

    $offset=0;
    if($first_line_fieldnames)
		$offset=1;
      
    $db = JFactory::getDBO();

    for($i=0+$offset;$i<count($arrayOfLines);$i++)
    {
		if(count($arrayOfLines[$i]) > 0)
		{
			$sets=prepareSQLQuery($fieldList,$fields,$arrayOfLines[$i]);
			$listing_id=findRecord($tablerow->realtablename,$tablerow->realidfieldname,$tablerow->published_field_found,$sets);

			if($listing_id==false)
			{
				$query='INSERT '.$tablerow->realtablename.' SET '.implode(', ',$sets);
				$db->setQuery( $query );
				$db->execute();	
			}
		}
    }
    return '';
}

function findRecord($realtablename,$realidfieldname,bool $published_field_found,$sets)
{
	$db = JFactory::getDBO();
	$wheres=$sets;
	
	if($published_field_found)
		$wheres[]='published=1';
	
	$query='SELECT '.$realidfieldname.' FROM '.$realtablename.' WHERE '.implode(' AND ',$wheres).' LIMIT 1';
	
	$db->setQuery($query);
	$records=$db->loadAssocList();
	
	if(count($records)==0)
		return false;
		
	return (int)$records[0][$realidfieldname];
}

function findSQLRecordJoin($realtablename,$join_realfieldname,$realidfieldname,bool $published_field_found,$vlus_str)
{
	$db = JFactory::getDBO();
	$vlus=explode(',',$vlus_str);
	$wheres_or=array();
	foreach($vlus as $vlu)
		$wheres_or[]=$db->quoteName($join_realfieldname).'='.$db->quote($vlu);
		
	$wheres[]='('.implode(' OR ',$wheres_or).')';
		
	if($published_field_found)
		$wheres[]='published=1';
	
	$query='SELECT '.$realidfieldname.' FROM '.$realtablename.' WHERE '.implode(' AND ',$wheres);
	$db->setQuery($query);
	$records=$db->loadAssocList();
  
	if(count($records)==0)
		return false;
    
	$listing_ids=array();
	foreach($records as $record)
		$listing_ids[]=$record[$realidfieldname];
	
	return $listing_ids;
}

function findSQLJoin($realtablename,$join_realfieldname,$realidfieldname,bool $published_field_found,$vlu)
{
	$db = JFactory::getDBO();
	$wheres=[];
	if($published_field_found)
		$wheres[]='published=1';
		
	$wheres[]=$db->quoteName($join_realfieldname).'='.$db->quote($vlu);
	
	$query='SELECT '.$realidfieldname.' FROM '.$realtablename.' WHERE '.implode(' AND ',$wheres).' LIMIT 1';
	
	$db->setQuery($query);
	$records=$db->loadAssocList();
	
	if(count($records)==0)
		return false;
    
	return $records[0][$realidfieldname];
}

function addSQLJoinSets($realtablename,$sets)
{
	$db = JFactory::getDBO();
	$query='INSERT '.$realtablename.' SET '.implode(',',$sets);

	$db->setQuery($query);
	$db->execute();	
}

function prepareSQLQuery($fieldList,$fields,$line)
{
	//$line=JoomlaBasicMisc::csv_explode(',',$line_,'"',false);
  
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
				if(isset($fields[$f_index]->sqljoin))
				{
					$realtablename = $fields[$f_index]->sqljoin->table;
					
					$vlu=findSQLJoin(
						$realtablename,
						$fields[$f_index]->sqljoin->field,
						$fields[$f_index]->sqljoin->realidfieldname,
						(bool)$fields[$f_index]->sqljoin->published_field_found,
						$line[$i]);
          
					if($vlu==false)//Join table record doesnt exists 
					{
						$sub_sets=[];
						$sub_sets[]=$db->quoteName($fields[$f_index]->sqljoin->field).'='.$db->quote($line[$i]); 
						addSQLJoinSets($realtablename,$sub_sets);
						
						$vlu=findSQLJoin(
						$realtablename,
						$fields[$f_index]->sqljoin->field,
						$fields[$f_index]->sqljoin->realidfieldname,
						(bool)$fields[$f_index]->sqljoin->published_field_found,
						$line[$i]);

					}
          
					if((int)$vlu>0)
						$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'='.(int)$vlu;
					else
						$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'=NULL';
				}
			}
			elseif($fieldtype=='records')
			{
				if(isset($fields[$f_index]->sqljoin))
				{
					$realtablename = $fields[$f_index]->sqljoin->table;
					
					$vlu=findSQLRecordJoin(
						$realtablename,
						$fields[$f_index]->sqljoin->field,
						$fields[$f_index]->sqljoin->realidfieldname,
						(bool)$fields[$f_index]->sqljoin->published_field_found,
						$line[$i]);
         
					if($vlu==false)
					{
						$sub_sets=[];
						$sub_sets[]=$db->quoteName($fields[$f_index]->sqljoin->field).'='.$db->quote($line[$i]); 
						addSQLJoinSets($realtablename,$sub_sets);
						
						$vlu=findSQLRecordJoin(
						$realtablename,
						$fields[$f_index]->sqljoin->field,
						$fields[$f_index]->sqljoin->realidfieldname,
						(bool)$fields[$f_index]->sqljoin->published_field_found,
						$line[$i]);
					}
           
					if($vlu!=false)
						$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'='.$db->quote(','.implode(',',$vlu).',');
					else
						$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'=NULL';
				}
			}
			elseif($fieldtype=='date' or $fieldtype=='creationtime' or $fieldtype=='changetime')
			{
				if(isset($line[$i]) and $line[$i]!='')
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'='.$db->quote($line[$i]);
				else
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'=NULL';
			}
			elseif($fieldtype=='int' or $fieldtype=='user' or $fieldtype=='userid')
			{
				if(isset($line[$i]) and $line[$i]!='')
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'='.(int)$line[$i];
				else
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'=NULL';
			}
			elseif($fieldtype=='float')
			{
				if(isset($line[$i]) and $line[$i]!='')
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'='.(float)$line[$i];
				else
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'=NULL';
			}
			elseif($fieldtype=='checkbox')
			{
				if(isset($line[$i]) and $line[$i]!='')
				{
					if($line[$i]=='Yes' or $line[$i]=='1')
						$vlu = 1;
					else
						$vlu = 0;
						
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'='.(int)$vlu;
				}
				else
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'=NULL';
			}
			else
			{
				if(isset($line[$i]))//count($line)>$i and 
				{
					$vlu=$line[$i];
					$sets[]=$db->quoteName($fields[$f_index]->realfieldname).'='.$db->quote($vlu);
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
        $fieldName=strtolower(preg_replace("/[^a-zA-Z1-9 #]/", "", $fieldName));
		
        $found=false;
        foreach($fields as $field)
        {
            $clean_field_name=strtolower(preg_replace("/[^a-zA-Z1-9 #]/", "", $field->fieldtitle));
      
            if((string)$fieldName=='#' or (string)$fieldName=='')
            {
                $fieldList[]=-1;
                $found=true;
                $count++;
                $first_line_fieldnames=true;
                break;
            }
            elseif((string)$clean_field_name==(string)$fieldName or (string)$field->fieldname==(string)$fieldName)
            {
                $fieldList[]=$index;
                $found=true;
                $count++;
				$first_line_fieldnames=true;
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

    $first_line_fieldnames=true;
    return $fieldList;
}
