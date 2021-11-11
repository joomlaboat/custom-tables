<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Layouts;

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

		if(!isset($this->Model->ct->Table->fields))
				return false;

		//Save view log
		$allowedfields=$this->SaveViewLog_CheckIfNeeded();
		if(count($allowedfields)>0)
		{
			foreach($this->SearchResult as $rec)
				$this->SaveViewLogForRecord($rec,$allowedfields);
		}
		
		$this->catalogtablecode=JoomlaBasicMisc::generateRandomString();//this is temporary replace place holder. to not parse content result again
		
		$Layouts = new Layouts($this->Model->ct);

		$this->pagelayout='';
		$layout_catalog_name=$this->Model->params->get( 'escataloglayout' );
		if($layout_catalog_name!='')
		{
			$this->pagelayout = $Layouts->getLayout($layout_catalog_name,false);//It is safier to process layout after rendering the table
		
			if($Layouts->layouttype==8)
				$this->Model->ct->Env->frmt='xml';
			elseif($Layouts->layouttype==9)
				$this->Model->ct->Env->frmt='csv';
			elseif($Layouts->layouttype==10)
				$this->Model->ct->Env->frmt='json';
		}
		else
			$this->pagelayout='{catalog:,notable}';
		
		$this->itemlayout='';
		$layout_item_name=$this->Model->params->get('esitemlayout');
		if($layout_item_name!='')
			$this->itemlayout = $Layouts->getLayout($layout_item_name);
		
		if($this->Model->ct->Env->frmt == 'csv')
		{
			if(function_exists('mb_convert_encoding'))
			{
				require_once('tmpl'.DIRECTORY_SEPARATOR.'csv.php');
			}
			else
			{
				$msg = '"mbstring" PHP exntension not installed.<br/>
				You need to install this extension. It depends on of your operating system, here are some examples:<br/><br/>
				sudo apt-get install php-mbstring  # Debian, Ubuntu<br/>
				sudo yum install php-mbstring  # RedHat, Fedora, CentOS<br/><br/>
				Uncomment the following line in php.ini, and restart the Apache server:<br/>
				extension=mbstring<br/><br/>
				Then restart your webs server. Example:<br/>service apache2 restart';
				
				JFactory::getApplication()->enqueueMessage($msg, 'error');
			}
		}
		elseif($this->Model->ct->Env->frmt == 'json')
			require_once('tmpl'.DIRECTORY_SEPARATOR.'json.php');
		else
			parent::display($tpl);
	}

	function SaveViewLogForRecord($rec,$allowedfields)
	{
		$updatefields=array();

		foreach($this->Model->ct->Table->fields as $mFld)
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
			$query= 'UPDATE '.$this->Model->ct->Table->realtablename.' SET '.implode(', ', $updatefields).' WHERE id='.$rec['listing_id'];
			$db->setQuery($query);
		    $db->execute();
		}

	}


	function SaveViewLog_CheckIfNeeded()
	{
		$user = JFactory::getUser();
		$usergroups = $user->get('groups');

		$allowedfields=array();

		foreach($this->Model->ct->Table->fields as $mFld)
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
		}

		return $allowedfields;
	}
}
