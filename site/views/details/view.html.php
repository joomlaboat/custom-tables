<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.6.1
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

		$app		= JFactory::getApplication();
		$params=$app->getParams();


		$this->assignRef('params',$params);

		$layout_catalog='';


		if($params->get('esdetailslayout')!='')
		{
			$layouttype=0;
			$layout_catalog=ESLayouts::getLayout($params->get('esdetailslayout'),$layouttype);
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



			$AllowPrint=(bool)$params->get('allowprint');
			$this->assignRef('AllowPrint', $AllowPrint);

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
		if(!isset($row['id']))
			return false;
		
		$phptagprocessor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
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
			    if (!$db->query())    die( $db->stderr());

		}

	}
/*

	function SaveViewLog_CheckIfNeeded()
	{
		$user = JFactory::getUser();
		$usergroups = $user->get('groups');

echo 'SaveViewLog_CheckIfNeeded<br/>';

		$allowedfields=array();

		foreach($this->Model->esfields as $mFld)
		{

				if($mFld['type']=='lastviewtime' or $mFld['type']=='viewcount' or $mFld['type']=='phponview')
				{



						//$pair=explode(',',$mFld['typeparams']);

						//$usergroup='';

						/*

						if(isset($pair[1]))
						{
								if($pair[1]=='details')
										$usergroup=$pair[0];
						}
						else
								$usergroup=$pair[0];

						*/


						//if($usergroup!='')
						//{
						/*
							if(count($usergroups))
							{
								$groupid=JoomlaBasicMisc::getGroupIdByTitle($usergroup);
								if(in_array($groupid,$usergroups))
										$allowedfields[]=$mFld['fieldname'];
							}
							else
							{
								if(strtolower($usergroup)=='public')
									$allowedfields[]=$mFld['fieldname'];
							}

							
						//	$allowedfields[]=$mFld['fieldname'];


						//}//if($usergroup!='')

				}
		}//foreach($this->Model->esfields as $mFld)


		return $allowedfields;
	}

*/

}

