<?php
/**
 * CustomTables Joomla! 3.x Native Component
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

		$this->SearchResult = $this->Model->getSearchResult();

		if(!isset($this->Model->esfields))
				return false;

		//Save view log
		$allowedfields=$this->SaveViewLog_CheckIfNeeded();
		if(count($allowedfields)>0)
		{
			foreach($this->SearchResult as $rec)
				$this->SaveViewLogForRecord($rec,$allowedfields);
		}

		if($this->Model->frmt == 'json')
			require_once('tmpl'.DIRECTORY_SEPARATOR.'json.php');
		else
			parent::display($tpl);
	}

	function SaveViewLogForRecord($rec,$allowedfields)
	{
		$updatefields=array();

		foreach($this->Model->esfields as $mFld)
		{
				if(in_array($mFld['fieldname'],$allowedfields))
				{
						if($mFld['type']=='lastviewtime')
							$updatefields[]=$mFld['realfieldname'].'="'.date('Y-m-d H:i:s').'"';

						if($mFld['type']=='viewcount')
							$updatefields[]=$mFld['realfieldname'].'="'.((int)($rec['es_'.$mFld['fieldname']])+1).'"';
				}
		}

		if(count($updatefields)>0)
		{
			$db = JFactory::getDBO();
			$query= 'UPDATE '.$this->Model->realtablename.' SET '.implode(', ', $updatefields).' WHERE id='.$rec['listing_id'];
			$db->setQuery($query);
		    $db->execute();
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

