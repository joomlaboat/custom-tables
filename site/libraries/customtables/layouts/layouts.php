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

class Layouts
{
	/*
	protected static function getLayoutID($layoutname)
    {
		$db = Factory::getDBO();

		if($tablename=='')
			return 0;

		$query = 'SELECT id FROM #__customtables_layouts AS s WHERE layoutname="'.$layoutname.'" LIMIT 1';
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		if(count($rows)!=1)
            return 0;

		return $rows[0]->id;
	}
	*/

    public static function getLayout($layoutname,&$type,$processLayoutTag = true)
	{

		if($layoutname=='')
			return '';

		$db = Factory::getDBO();
		
		
		if($db->serverType == 'postgresql')
			$query = 'SELECT id, layoutcode, extract(epoch FROM modified) AS ts, layouttype FROM #__customtables_layouts WHERE layoutname='.$db->quote($layoutname).' LIMIT 1';
		else
			$query = 'SELECT id, layoutcode, UNIX_TIMESTAMP(modified) AS ts, layouttype FROM #__customtables_layouts WHERE layoutname='.$db->quote($layoutname).' LIMIT 1';
			
		$db->setQuery( $query );
		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return '';

		$row=$rows[0];
        $type=(int)$row['layouttype'];

		$content=Layouts::getLayoutFileContent($row['id'],$row['ts'],$layoutname);
		if($content!='')
			return $content;

		//Get all layouts recursevly
		$layoutcode=$row['layoutcode'];
		
		if($processLayoutTag)
			Layouts::processLayoutTag($layoutcode);
			
		return $layoutcode;
	}
	
	public static function processLayoutTag(&$htmlresult)
	{
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('layout',$options,$htmlresult,'{}');
        
        if(count($fList)==0)
            return false;
        
        
		$i=0;
		foreach($fList as $fItem)
		{
			$optpair=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);
            $layoutname=$optpair[0];
			
			$ProcessContentPlugins = false;
			if(isset($optpair[1]) and $optpair[1] == 'process')
				$ProcessContentPlugins = true;
			
            $type='';
            $layout = Layouts::getLayout($layoutname,$type);
			
			if($ProcessContentPlugins)
				LayoutProcessor::applyContentPlugins($layout);
            
			$htmlresult=str_replace($fItem,$layout,$htmlresult);
			$i++;
		}
    }

	public static function getLayoutFileContent($id=0,$db_layout_ts=0,$layoutname)
	{
		$path=JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'layouts';
		$filename=$layoutname.'.html';

		if (file_exists($path.DIRECTORY_SEPARATOR.$filename))
		{
			$file_ts=filemtime ($path.DIRECTORY_SEPARATOR.$filename);

			if($db_layout_ts==0)
			{
				$db = Factory::getDBO();
				$query = 'SELECT UNIX_TIMESTAMP(modified) AS ts FROM #__customtables_layouts WHERE id='.$id.' LIMIT 1';
				$db->setQuery( $query );

				$recs = $db->loadAssocList( );
				
                if(count($recs)==0)
                    $db_layout_ts=0;
                else
                {
                    $rec=$recs[0];
                    $db_layout_ts=$rec['ts'];
                }
			}

			if($file_ts>$db_layout_ts)
			{

				$content=file_get_contents($path.DIRECTORY_SEPARATOR.$filename);

				$db = Factory::getDBO();

				$query = 'UPDATE #__customtables_layouts SET layoutcode="'.addslashes($content).'",modified=FROM_UNIXTIME('.$file_ts.') WHERE id='.$id;

				$db->setQuery( $query );
				$db->execute();

				return $content;
			}
		}

		return '';
	}

    public static function createDefaultLayout($fields,$type)
	{
		$result='';
		switch($type)
		{
			case 'edit':
				$result=Layouts::createDefaultLayout_Edit($fields);
			break;
			
			default:
				$result='';
				break;
		}
		return $result;
	}
	
	public static function createDefaultLayout_Edit(&$fields,$addToolbar=true)
	{
		$result='';

		$result.='<div class="form-horizontal">

';

		$fieldtypes_to_skip=['log','phponview','phponchange','phponadd','md5','id','server','userid','viewcount','lastviewtime','changetime','creationtime','imagegallery','filebox','dummy'];

		foreach ($fields as $field)
		{
			if(!in_array($field['type'],$fieldtypes_to_skip))
			{
				$result.='	<div class="control-group">
';
				$result.='		<div class="control-label">*'.$field['fieldname'].'*</div><div class="controls">['.$field['fieldname'].']</div>
';
				$result.='	</div>

';
			}
		}

		$result.='</div>

';

		foreach ($fields as $field)
		{
			if($field['type']==="dummy")
			{
				$result.='<p><span style="color: #FB1E3D; ">*</span> *'.$field['fieldname'].'*</p>
';
				break;
			}
		}

		if($addToolbar)
			$result.='<div style="text-align:center;">{button:save} {button:saveandclose} {button:saveascopy} {button:cancel}</div>
';
	
		return $result;
	}
	
	public static function storeAsFile(&$data)
	{
		$path=JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'layouts';
		$filename=$data['layoutname'].'.html';

		file_put_contents($path.DIRECTORY_SEPARATOR.$filename, $data['layoutcode']);

		$file_ts=filemtime ($path.DIRECTORY_SEPARATOR.$filename);
		if($file_ts=='')
		{
			//No permission -  file not saved
		}
		else
		{
			$id=(int)$data['id'];
			if($id==0)
				$id=Layouts::getLayoutID($data['layoutname']);

			$db = Factory::getDBO();
			$query = 'UPDATE #__customtables_layouts SET modified=FROM_UNIXTIME('.$file_ts.') WHERE id='.$id;
			$db->setQuery( $query );

			$db->execute();
		}

		return $file_ts;
	}
}
