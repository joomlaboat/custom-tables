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

class CustomTablesViewCatalog extends JViewLegacy
{
	var $Model;
	var $imagegalleries;
	var $fileboxes;

	function display($tpl = null)
	{
		$this->Model = $this->getModel();

		$params=null;
		$this->Model->load($params,false,JFactory::getApplication()->input->getCMD('layout',''));

		//Is user super Admin?
		//$this->isUserAdministrator=JoomlaBasicMisc::isUserAdmin($this->Model->userid);

		$SearchResult=$this->Model->getSearchResult();

		$this->assignRef('SearchResult',$SearchResult);
//		$this->current_url=JoomlaBasicMisc::curPageURL();
		//$this->imagegalleries=array();
		//$this->fileboxes=array();

		if(!isset($this->Model->esfields))
				return false;

/*
		foreach($this->Model->esfields as $mFld)
		{
				if($mFld['type']=='imagegallery')
					$this->imagegalleries[]=array($mFld['fieldname'],$mFld['fieldtitle'.$this->Model->langpostfix]);

				if($mFld['type']=='filebox')
					$this->fileboxes[]=array($mFld['fieldname'],$mFld['fieldtitle'.$this->Model->langpostfix]);
		}
*/



        parent::display($tpl);

		//Save view log

		$allowedfields=$this->SaveViewLog_CheckIfNeeded();
		if(count($allowedfields)>0)
		{
				foreach($SearchResult as $rec)
						$this->SaveViewLogForRecord($rec,$allowedfields);
		}

		return;
	}

	function SaveViewLogForRecord($rec,$allowedfields)
	{
		$updatefields=array();

		foreach($this->Model->esfields as $mFld)
		{
				if(in_array($mFld['fieldname'],$allowedfields))
				{
						if($mFld['type']=='lastviewtime')
							$updatefields[]='es_'.$mFld['fieldname'].'="'.date('Y-m-d H:i:s').'"';

						if($mFld['type']=='viewcount')
							$updatefields[]='es_'.$mFld['fieldname'].'="'.((int)($rec['es_'.$mFld['fieldname']])+1).'"';
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


	function SaveViewLog_CheckIfNeeded()
	{
		$user = JFactory::getUser();
		$usergroups = $user->get('groups');

		$allowedfields=array();

		foreach($this->Model->esfields as $mFld)
		{
				if($mFld['type']=='lastviewtime' or $mFld['type']=='viewcount' or $mFld['type']=='phponview')
				{
						$pair=explode(',',$mFld['typeparams']);

						$usergroup='';


						if(isset($pair[1]))
						{
								if($pair[1]=='catalog')
										$usergroup=$pair[0];
						}
						else
								$usergroup=$pair[0];


						$groupid=JoomlaBasicMisc::getGroupIdByTitle($usergroup);

						if($usergroup!='')
						{
								if(in_array($groupid,$usergroups))
										$allowedfields[]=$mFld['fieldname'];

						}//if($usergroup!='')
				}
		}//foreach($this->Model->esfields as $mFld)

		return $allowedfields;
	}




}
?>
