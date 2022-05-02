<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Layouts;
use \Joomla\CMS\Factory;

class CustomTablesViewCatalog extends JViewLegacy
{
	var $Model;
	var $imagegalleries;
	var $fileboxes;
	var $ct;
	var $listing_id;
	var $layoutType = 0;
	
	function display($tpl = null)
	{
		$this->Model = $this->getModel();
		
		$key = $this->Model->ct->Env->jinput->getCmd('key');
		if($key != '')
			$this->renderTableJoinSelectorJSON($key);
		else
			$this->renderCatalog($tpl);
	}
	
	function renderTableJoinSelectorJSON($key)
	{
		$db = Factory::getDBO();
		
		$this->ct = $this->Model->ct;
		$index = $this->Model->ct->Env->jinput->getInt('index');
		$selectors = (array)Factory::getApplication()->getUserState($key);
		
		if($index < 0 or $index >= count($selectors))
			die(json_encode(['error' => 'Index out of range.']));
		
		$selector = $selectors[$index];

		$tablename = $selector[0];
		if($tablename=='')
			die(json_encode(['error' => 'Table not selected']));
		
		$this->ct->getTable($tablename);
		if($this->ct->Table->tablename=='')
			die(json_encode(['error' => 'Table "' . $tablename . '"not found']));
		
		$fieldname_or_layout = $selector[1];
		if($fieldname_or_layout == null or $fieldname_or_layout=='')
			$fieldname_or_layout = $this->ct->Table->fields[0]['fieldname'];
		
		//$showpublished = 0 - show published
		//$showpublished = 1 - show unpublished
		//$showpublished = 2 - show any
		$showpublished = (($selector[2] ?? '') == '' ? 2 : ((int) ($selector[2] ?? 0) == 1 ? 0 : 1)); //$selector[2] can be "" or "true" or "false"
			
		$filter = $selector[3] ?? '';
		
		$additional_filter = $this->ct->Env->jinput->getCmd('filter');
		
		$additional_where = '';
		//Find the field name that has a join to the parend (index-1) table
		foreach($this->ct->Table->fields as $fld)
		{
			if($fld['type'] == 'sqljoin')
			{
				$type_params = JoomlaBasicMisc::csv_explode(',',$fld['typeparams'],'"',false);
				$join_tablename = $type_params[0];
				$join_to_tablename = $selector[5];
				
				if($additional_filter!='')
				{
					if($join_tablename == $join_to_tablename)
						$filter = $filter . ' and ' . $fld['fieldname'] . '=' . $additional_filter;
				}
				else
				{
					//Check if this table has self-parent field - the TableJoin field linked with the same table.
					if($join_tablename == $tablename)
					{
						$subfilter = $this->ct->Env->jinput->getCmd('subfilter');
						if($subfilter == '')
							$additional_where = '('.$fld['realfieldname'].' IS NULL OR '.$fld['realfieldname'].'="")';
						else
							$additional_where = $fld['realfieldname'].'='.$db->quote($subfilter);
						
						//ssecho '$additional_where = '.$additional_where.'<br>';
					}
				}
			}
		}
		$this->ct->setFilter($filter, $showpublished);
		if($additional_where != '')
			$this->ct->Filter->where[] = $additional_where;
		
		$orderby = $selector[4] ?? '';
			
		//sorting
		$this->ct->Ordering->ordering_processed_string = $orderby;
		$this->ct->Ordering->parseOrderByString();
		
		$this->ct->getRecords();
		
		$this->catalogtablecode=JoomlaBasicMisc::generateRandomString();//this is temporary replace place holder. to not parse content result again
			
		$this->pagelayout = '[{catalog:,notable,","}]';
		if(strpos($fieldname_or_layout,'{{') === false and strpos($fieldname_or_layout,'layout') === false)
		{
			$fieldname_or_layout_tag = '{{ '.$fieldname_or_layout.' }}';
		}
		else
		{
			$pair=explode(':',$fieldname_or_layout);

			if(count($pair)==2)
			{
				$layout_mode=true;
				if($pair[0]!='layout' and $pair[0]!='tablelesslayout')
					die(json_encode(['error' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_UNKNOW_FIELD_LAYOUT').' "'.$field.'"']));

				$Layouts = new Layouts($this->ct);
				$fieldname_or_layout_tag = $Layouts->getLayout($pair[1]);

				if(!isset($fieldname_or_layout_tag) or $fieldname_or_layout_tag=='')
					die(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LAYOUT_NOT_FOUND').' "'.$pair[1].'"');
			}
			else
				$fieldname_or_layout_tag = $fieldname_or_layout;
		}
		
		$this->itemlayout = '{"id":"{{ record.id }}","label":"'.$fieldname_or_layout_tag.'"}';

		$paramsArray['establename'] = $tablename;
			
		$_params= new JRegistry;
		$_params->loadArray($paramsArray);
		$this->ct->Env->menu_params = $_params;
			
		$this->ct->LayoutProc = new LayoutProcessor($this->ct);
		$this->ct->LayoutProc->layout = $this->pagelayout;
			
		require_once('tmpl'.DIRECTORY_SEPARATOR.'json.php');
	}
	
	function renderCatalog($tpl)
	{
		$menu_params=null;
		$this->Model->load($menu_params,false,Factory::getApplication()->input->getCMD('layout',''));
		
		$jinput=Factory::getApplication()->input;
		$this->ct = $this->Model->ct;
		
		$addition_filter='';
		$this->listing_id = $jinput->getCmd('listing_id','');
		if($this->listing_id != '')
			$addition_filter = $this->ct->Table->realidfieldname . '=' . $this->listing_id;
		
		$this->Model->getSearchResult($addition_filter);

		if(!isset($this->ct->Table->fields))
			return false;

		//Save view log
		$allowedfields=$this->SaveViewLog_CheckIfNeeded();
		if(count($allowedfields)>0 and $this->ct->Records !== null)
		{
			foreach($this->ct->Records as $rec)
				$this->SaveViewLogForRecord($rec,$allowedfields);
		}
		
		$this->catalogtablecode=JoomlaBasicMisc::generateRandomString();//this is temporary replace place holder. to not parse content result again
		
		$Layouts = new Layouts($this->ct);

		$this->pagelayout='';
		$layout_catalog_name = $this->ct->Env->menu_params->get( 'escataloglayout' );
		if($layout_catalog_name != '')
		{
			$this->pagelayout = $Layouts->getLayout($layout_catalog_name,false);//It is safier to process layout after rendering the table
		
			if($Layouts->layouttype==8)
				$this->ct->Env->frmt='xml';
			elseif($Layouts->layouttype==9)
				$this->ct->Env->frmt='csv';
			elseif($Layouts->layouttype==10)
				$this->ct->Env->frmt='json';
				
			$this->layoutType = $Layouts->layouttype;
		}
		else
			$this->pagelayout='{catalog:,notable}';
		
		$this->itemlayout='';
		$layout_item_name=$this->ct->Env->menu_params->get('esitemlayout');
		if($layout_item_name!='')
			$this->itemlayout = $Layouts->getLayout($layout_item_name);
		
		if($this->ct->Env->frmt == 'csv')
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
				
				Factory::getApplication()->enqueueMessage($msg, 'error');
			}
		}
		//elseif($this->ct->Env->frmt == 'json')
			//require_once('tmpl'.DIRECTORY_SEPARATOR.'json.php');
		else
		{
			parent::display($tpl);
		}
	}

	function SaveViewLogForRecord($rec,$allowedfields)
	{
		$updatefields=array();

		foreach($this->ct->Table->fields as $mFld)
		{
			if(in_array($mFld['fieldname'],$allowedfields))
			{
				if($mFld['type']=='lastviewtime')
					$updatefields[]=$mFld['realfieldname'].'="'.date('Y-m-d H:i:s').'"';

				if($mFld['type']=='viewcount')
					$updatefields[]=$mFld['realfieldname'].'="'.((int)($rec[$this->ct->Env->field_prefix.$mFld['fieldname']])+1).'"';
			}
		}

		if(count($updatefields)>0)
		{
			$db = Factory::getDBO();
			$query= 'UPDATE '.$this->ct->Table->realtablename.' SET '.implode(', ', $updatefields).' WHERE id='.$rec['listing_id'];
			$db->setQuery($query);
		    $db->execute();
		}
	}

	function SaveViewLog_CheckIfNeeded()
	{
		$user = Factory::getUser();
		$usergroups = $user->get('groups');
		$allowedfields=array();

		foreach($this->ct->Table->fields as $mFld)
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
