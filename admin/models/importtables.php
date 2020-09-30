<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage importtables.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Import Joomla! libraries
jimport('joomla.application.component.model');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'uploader.php');

class CustomTablesModelImporttables extends JModelList
{
    function __construct()
    {
		parent::__construct();
    }

	function importtables(&$msg)
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'importtables.php');


            $jinput=JFactory::getApplication()->input;

			$fileid = $jinput->getCmd( 'fileid', '' );

			$filename=ESFileUploader::getFileNameByID($fileid);
			$menutype='Custom Tables Import Menu';

			$importfields = $jinput->getInt( 'importfields', 0 );
			$importlayouts = $jinput->getInt( 'importlayouts', 0 );
			$importmenu = $jinput->getInt( 'importmenu', 0 );

			$category='';
			return ImportTables::processFile($filename,$menutype,$msg,$category,$importfields,$importlayouts,$importmenu);

	}




	function getColumns($line)
	{
		$columns=explode(",",$line);
			if(count($columns)<1)
			{
				echo 'incorrect field header<br/>';
				return array();
			}

		for($i=0;$i<count($columns);$i++)
		{
			$columns[$i]=trim($columns[$i]);
		}
		return $columns;

	}
	function parseLine(&$columns,&$allowedcolumns,$fieldtypes,$line,&$maxid)
	{
		//,&$parents,&$level,
		$result=array();

		//$values=explode(',',$line);
		$values=$this->line_explode($line);




		$maxid++;




		$result[]=$maxid;								// id

		$c=0;
		for($i=0;$i<count($values);$i++)
		{
			if($allowedcolumns[$c])
			{
				//$result[]='"'.trim(preg_replace('/\s\s+/', ' ', $values[$i])).'"';
				$fieldtypepair=explode(':',$fieldtypes[$c]);

				if($fieldtypepair[0]=='string' or $fieldtypepair[0]=='multistring' or $fieldtypepair[0]=='text' or $fieldtypepair[0]=='multitext'  )
					$result[]='"'.$values[$i].'"';

				elseif($fieldtypepair[0]=='email' )
					$result[]='"'.$values[$i].'"';

				elseif($fieldtypepair[0]=='url' )
					$result[]='"'.$values[$i].'"';

				elseif($fieldtypepair[0]=='float' or $fieldtypepair[0]=='int')
					$result[]=$values[$i];

				elseif($fieldtypepair[0]=='checkbox' )
					$result[]=$values[$i];

				elseif($fieldtypepair[0]=='date' )
					$result[]='"'.$values[$i].'"';

				elseif($fieldtypepair[0]=='radio' )
					$result[]='"'.$values[$i].'"';

				elseif($fieldtypepair[0]=='customtables')
				{
					//this function must add item if not found
					$esValue=$this->getOptionListItem($fieldtypepair[1], $values[$i]);

					$result[]='"'.$esValue.'"';
				}
				else
				{
					//type unsupported
					$result[]='""';
				}


			}
			$c++;
		}

		return $result;
	}

	function getOptionListItem($optionname, $optionTitle)
	{
        $db = JFactory::getDbo();
		$parentid=$this->esmisc->getOptionIdFull($optionname);

		$rows=$this->esmisc->getHeritage($parentid, $db->quoteName('title').'='.$db->quote($optionTitle), 1);

		if(count($rows)==0)
		{
			//add item
			$newoptionname_original=strtolower(trim(preg_replace("/[^a-zA-Z0-9]/", "", $optionTitle)));
			$newoptionname=$newoptionname_original;
			$n=0;
			do
			{
				$rows_check=$this->esmisc->getHeritage($parentid, $db->quoteName('optionname').'='.$db->quote($newoptionname), 1);
				if(count($rows_check))
				{
					$n++;
					$newoptionname=$newoptionname_original.$n;
				}
				else
					break;
			}while(1==1);

			$familytree=$this->esmisc->getFamilyTreeByParentID($parentid).'-';

			$db = JFactory::getDBO();
			$query = 'INSERT #__customtables_options SET '
				.$db->quoteName('parentid').'='.$db->quote($parentid).', '
				.$db->quoteName('optionname').'='.$db->quote($newoptionname).', '
				.$db->quoteName('familytree').'='.$db->quote($familytree).', '
				.$db->quoteName('title').'='.$db->quote($optionTitle);

			$db->setQuery($query);
			$db->execute();

			$fulloptionname=','.$optionname.'.'.$newoptionname.'.,';

			return $fulloptionname;
		}
		else
		{
			$row=$rows[0];

			$newoptionname=','.$this->esmisc->getFamilyTreeString($parentid,1).'.'.$row['optionname'].'.,';


			return $newoptionname;
		}



	}


	function line_explode($str)
	{
		// !!! for php 5.3+ use str_getcsv instead
		$ar=$this->csv_explode(',', $str,'"', false);
		return $ar;
	}

	function csv_explode($delim=',', $str, $enclose='"', $preserve=false)
	{
		$resArr = array();
		$n = 0;
		$expEncArr = explode($enclose, $str);
		foreach($expEncArr as $EncItem)
		{
			if($n++%2){
				array_push($resArr, array_pop($resArr) . ($preserve?$enclose:'') . $EncItem.($preserve?$enclose:''));
			}else{
				$expDelArr = explode($delim, $EncItem);
				array_push($resArr, array_pop($resArr) . array_shift($expDelArr));
			    $resArr = array_merge($resArr, $expDelArr);
			}
		}
	return $resArr;
	}

	function findMaxId($table)
	{

		$db =& JFactory::getDBO();
		$query = ' SELECT id FROM #__customtables_table_'.$table.' ORDER BY id DESC LIMIT 1';

		$db->setQuery( $query );

		if (!$db->query())    die( $db->stderr());

		$num_rows = $db->getNumRows();
		if($num_rows==0)
			return 0;

		$maxidr= $db->loadObjectList();

		if(count($maxidr)!=1)
			return -1;

		$maxid=$maxidr[0]->id;
		return $maxidr[0]->id;
	}




	function getLanguageByCODE($code)
	{
		//Example: $code='en-GB';

		$db =& JFactory::getDBO();
		$query = ' SELECT id FROM #__customtables_languages WHERE language="'.$code.'" LIMIT 1';
		$db->setQuery( $query );
		$rows= $db->loadObjectList();
		if(count($rows)!=1)
			return -1;



		return $rows[0]->id;
	}




}

?>
