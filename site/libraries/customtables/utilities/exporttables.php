<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;

class ExportTables
{
	//this function creates json(.txt) file that will include instruction to create selected tables and depended menu items and layouts.
	//Records can be exported too, if it set in table parameters
	//file is creaated in /tmp folder or as set in $path parameter
	
	public function export(&$table_ids, $path = 'tmp')
	{
		$db = Factory::getDBO();

		$link='';

		$tables=array();
		$output=array();

		foreach($table_ids as $id )
	    {
			//get table
			$s1='(SELECT categoryname FROM #__customtables_categories WHERE #__customtables_categories.id=#__customtables_tables.tablecategory) AS categoryname';
			$query = 'SELECT *,'.$s1.' FROM #__customtables_tables WHERE published=1 AND id='.(int)$id.' LIMIT 1';
			
			$db->setQuery( $query );
			$table_rows=$db->loadAssocList();

			//Add the table with dependances to export array
			if(count($table_rows)==1)
			{
				$tables[]=$table_rows[0]['tablename'];
				$output[] = ExportTables::processTable($table_rows[0]);
			}
		}

		//Save the array to file
		if(count($output)>0)
		{
			//Prepare output string with data
			$output_str='<customtablestableexport>'.json_encode($output);

			$tmp_path=JPATH_SITE.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR;
			$filename=implode('_',$tables);
			$filename_available=$filename;
			$a='';
			$i=0;
			do{
				if(!file_exists($tmp_path.$filename.$a.'.txt'))
				{
					$filename_available=$filename.$a.'.txt';
					break;
				}

				$i++;
				$a=$i.'';

			}while(1==1);

			//Save file
			$link=Uri::root(false).$path.DIRECTORY_SEPARATOR.$filename_available;
			file_put_contents($tmp_path.$filename_available, $output_str);
			$output_str=null;
		}
		return $link;
	}
	
	protected static function processTable($table)
	{
		$db = Factory::getDBO();
				
		//get fields
		$query = 'SELECT * FROM #__customtables_fields WHERE published=1 AND tableid='.(int)$table['id'].'';
		$db->setQuery( $query );
		$fields=$db->loadAssocList();

		//get layouts
		$query = 'SELECT * FROM #__customtables_layouts WHERE published=1 AND tableid='.(int)$table['id'].'';
		$db->setQuery( $query );
		$layouts=$db->loadAssocList();

		//Get depended menu items
		$wheres=array();
		$wheres[]='published=1';
				
		if($db->serverType == 'postgresql')
		{
			$wheres[]='POSITION('.$db->quote("index.php?option=com_customtables&view=").' IN link)>0';
			$wheres[]='POSITION('.$db->quote('"establename":"'.$table['tablename'].'"').' IN params)>0';
		}
		else
		{
			$wheres[]='INSTR(link,'.$db->quote("index.php?option=com_customtables&view=").')';
			$wheres[]='INSTR(params,'.$db->quote('"establename":"'.$table['tablename'].'"').')';
		}

		$query = 'SELECT * FROM #__menu WHERE '.implode(' AND ',$wheres);
		$db->setQuery( $query );
		$menu = $db->loadAssocList();
				
		//Get depended records
		if(intval($table['allowimportcontent'])==1)
		{
			$query = 'SELECT * FROM #__customtables_table_'.$table['tablename'].' WHERE published=1';
			$db->setQuery( $query );
			$records=$db->loadAssocList();
		}
		else
			$records = null;
	
		return ['table'=>$table,'fields'=>$fields,'layouts'=>$layouts,'records'=>$records,'menu'=>$menu];
	}
}