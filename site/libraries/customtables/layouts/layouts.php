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

use \LayoutProcessor;
use \JoomlaBasicMisc;
use \Joomla\CMS\Factory;


class Layouts
{
	var $ct;
	var $layouttype;
	
	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
    function getLayout(string $layoutname, bool $processLayoutTag = true)
	{
		if($layoutname=='')
			return '';
			
		$code_field = 'layoutcode';

		$db = Factory::getDBO();
		
		if($db->serverType == 'postgresql')
			$query = 'SELECT id, layoutcode, layoutmobile, layoutcss, layoutjs, extract(epoch FROM modified) AS ts, layouttype FROM #__customtables_layouts WHERE layoutname='.$db->quote($layoutname).' LIMIT 1';
		else
			$query = 'SELECT id, layoutcode, layoutmobile, layoutcss, layoutjs, UNIX_TIMESTAMP(modified) AS ts, layouttype FROM #__customtables_layouts WHERE layoutname='.$db->quote($layoutname).' LIMIT 1';
			
		$db->setQuery( $query );
		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return '';

		$row=$rows[0];
        $this->layouttype=(int)$row['layouttype'];

		$content=$this->getLayoutFileContent($row['id'],$row['ts'],$layoutname);
		if($content!='')
			return $content;

		//Get all layouts recursevly
		if($this->ct->Env->isMobile and trim($layoutcode=$row['layoutmobile']))
			$layoutcode=$row['layoutmobile'];
		else
			$layoutcode=$row['layoutcode'];
		
		if($processLayoutTag)
			$this->processLayoutTag($layoutcode);
			
		$this->addCSSandJSIfNeeded($row);
			
		return $layoutcode;
	}
	
	protected function addCSSandJSIfNeeded(&$row)
	{
		$document = Factory::getDocument();
		if(trim($row['layoutcss'])!='')
			$document->addCustomTag('<style>'.trim($row['layoutcss']).'</style>');

		if(trim($row['layoutjs'])!='')
			$document->addScriptDeclaration(trim($row['layoutjs']));
	}
	
	function processLayoutTag(&$htmlresult)
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
			
            $layout = $this->getLayout($layoutname);
			
			if($ProcessContentPlugins)
				LayoutProcessor::applyContentPlugins($layout);
            
			$htmlresult=str_replace($fItem,$layout,$htmlresult);
			$i++;
		}
    }

	protected function getLayoutFileContent(int $id, $db_layout_ts,$layoutname)
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

	function createDefaultLayout_Edit(&$fields,$addToolbar=true)
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
	
	public function storeAsFile(&$data)
	{
		$path=JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'layouts';
		$filename=$data['layoutname'].'.html';

		try
        {
			@file_put_contents($path.DIRECTORY_SEPARATOR.$filename, $data['layoutcode']);
		}
        catch (RuntimeException $e)
        {
			//$msg=$e->getMessage();
		}

		try
        {
			@$file_ts=filemtime ($path.DIRECTORY_SEPARATOR.$filename);
		}
        catch (RuntimeException $e)
        {
			//$msg=$e->getMessage();
			$file_ts='';
		}

		if($file_ts=='')
		{
			//No permission -  file not saved
		}
		else
		{
			$db = Factory::getDBO();
			
			$id=(int)$data['id'];
			
			if($id==0)
				$query = 'UPDATE #__customtables_layouts SET modified=FROM_UNIXTIME('.$file_ts.') WHERE layoutname='.$db->quote($data['layoutname']);
			else
				$query = 'UPDATE #__customtables_layouts SET modified=FROM_UNIXTIME('.$file_ts.') WHERE id='.$id;
			
			$db->setQuery( $query );
			$db->execute();
		}

		return $file_ts;
	}
	
	
	public function layoutTypeTranslation()
	{
		$layouttypeArray = array(
				1 => 'COM_CUSTOMTABLES_LAYOUTS_SIMPLE_CATALOG',
				5 => 'COM_CUSTOMTABLES_LAYOUTS_CATALOG_PAGE',
				6 => 'COM_CUSTOMTABLES_LAYOUTS_CATALOG_ITEM',
				2 => 'COM_CUSTOMTABLES_LAYOUTS_EDIT_FORM',
				4 => 'COM_CUSTOMTABLES_LAYOUTS_DETAILS',
				3 => 'COM_CUSTOMTABLES_LAYOUTS_RECORD_LINK',
				7 => 'COM_CUSTOMTABLES_LAYOUTS_EMAIL_MESSAGE',
				8 => 'COM_CUSTOMTABLES_LAYOUTS_XML',
				9 => 'COM_CUSTOMTABLES_LAYOUTS_CSV',
				10 => 'COM_CUSTOMTABLES_LAYOUTS_JSON'
		);
		
		return $layouttypeArray;
	}
}
