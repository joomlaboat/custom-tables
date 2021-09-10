<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.html.pane');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layouts.php');

jimport( 'joomla.application.component.view'); //Important to get menu parameters
class CustomTablesViewDetails extends JViewLegacy {
	var $catid=0;
	var $Model;
	var $row;
	var $useridfieldname;
 var $imagegalleries;
	var $fileboxes;

	function display($tpl = null)
	{

		$this->Model = $this->getModel();
		if(!isset($this->Model->LayoutProc))
			return;

		$app = JFactory::getApplication();
		$this->params = $app->getParams();

		$layout_catalog='';

		if($this->params->get('esdetailslayout')!='')
		{
			$layouttype=0;
			$layout_catalog=ESLayouts::getLayout($this->params->get('esdetailslayout'),$layouttype);
				
			if($layouttype==8)
				$this->Model->frmt='xml';
			elseif($layouttype==9)
				$this->Model->frmt='csv';
			elseif($layouttype==10)
				$this->Model->frmt='json';

			$this->Model->LayoutProc->layout=$layout_catalog;
		}

		$this->row = $this->get('Data');

		if(count($this->row)>0)
		{
			if((!isset($this->row['listing_id']) or (int)$this->row['listing_id']==0) and $this->Model->redirectto!='')
			{
				$mainframe->redirect($this->Model->redirectto);
			}

			if ($this->Model->print)
			{
				$document	= JFactory::getDocument();
				$document->setMetaData('robots', 'noindex, nofollow');
			}

			$this->AllowPrint=(bool)$this->params->get('allowprint');

			$this->current_url=JoomlaBasicMisc::curPageURL();

			$this->imagegalleries=array();
			$this->useridfieldname='';

			foreach($this->Model->esfields as $mFld)
			{
				if($mFld['type']=='imagegallery')
					$this->imagegalleries[]=array($mFld['fieldname'],$mFld['fieldtitle'.$this->Model->langpostfix]);

				if($mFld['type']=='filebox')
					$this->fileboxes[]=array($mFld['fieldname'],$mFld['fieldtitle'.$this->Model->langpostfix]);

				if($mFld['type']=='userid')
						$this->useridfieldname=$mFld['fieldname'];
			}

			$document = JFactory::getDocument();
			$document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/style.css" type="text/css" rel="stylesheet" />');

			parent::display($tpl);

			//Save view log
			$this->SaveViewLogForRecord($this->row);
			$this->UpdatePHPOnView($this->row);
		}
	}

	function UpdatePHPOnView($row)//,$allowedfields
	{
		if(!isset($row['listing_id']))
			return false;
		
		$phptagprocessor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
  if(!file_exists($phptagprocessor_file))
			return false;
		
		require_once($phptagprocessor_file);
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
		
		foreach($this->Model->esfields as $mFld)
		{
			if($mFld['type']=='phponview')
			{
				$fieldname=$mFld['fieldname'];
				$params=JoomlaBasicMisc::csv_explode(',',$mFld['typeparams'],'"',false);
				tagProcessor_PHP::processTempValue($this->Model,$row,$fieldname,$params,false);
    
				}
				
			}
		
		
		
	}

	function SaveViewLogForRecord($rec)//,$allowedfields
	{
		$updatefields=array();

		$allwedTypes=['lastviewtime','viewcount'];

		foreach($this->Model->esfields as $mFld)
		{
				$t=$mFld['type'];
				if(in_array($t,$allwedTypes))
				{

					$allow_count=true;
					$author_user_field=$mFld['typeparams'];

					if(!isset($author_user_field) or $author_user_field=='' or $rec['es_'.$author_user_field]==$this->Model->userid)
						$allow_count=false;

					if($allow_count)
					{
						$n='es_'.$mFld['fieldname'];
						if($t=='lastviewtime')
							$updatefields[]=$n.'="'.date('Y-m-d H:i:s').'"';
						elseif($t=='viewcount')
							$updatefields[]=$n.'='.((int)($rec[$n])+1);
					}
				}
		}

		if(count($updatefields)>0)
		{
				$db = JFactory::getDBO();
				$query= 'UPDATE #__customtables_table_'.$this->Model->establename.' SET '.implode(', ', $updatefields).' WHERE id='.$rec['listing_id'];

				$db->setQuery($query);
				$db->execute();	
		}

	}
}

