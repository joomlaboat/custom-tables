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

jimport('joomla.html.pane');

jimport( 'joomla.application.component.view'); //Important to get menu parameters
class CustomTablesViewDetails extends JViewLegacy
{
	var $catid=0;
	var $Model;
	var $row;
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
			$layout_catalog=Layouts::getLayout($this->params->get('esdetailslayout'),$layouttype);
				
			if($layouttype==8)
				$this->Model->ct->Env->frmt='xml';
			elseif($layouttype==9)
				$this->Model->ct->Env->frmt='csv';
			elseif($layouttype==10)
				$this->Model->ct->Env->frmt='json';

			$this->Model->LayoutProc->layout=$layout_catalog;
		}

		$this->row = $this->get('Data');

		if(count($this->row)>0)
		{
			if((!isset($this->row['listing_id']) or (int)$this->row['listing_id']==0) and $this->Model->redirectto!='')
			{
				$mainframe->redirect($this->Model->redirectto);
			}

			if ($this->Model->ct->Env->print)
			{
				$document	= JFactory::getDocument();
				$document->setMetaData('robots', 'noindex, nofollow');
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
		
		foreach($this->Model->ct->Table->fields as $mFld)
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

		foreach($this->Model->ct->Table->fields as $mFld)
		{
				$t=$mFld['type'];
				if(in_array($t,$allwedTypes))
				{

					$allow_count=true;
					$author_user_field=$mFld['typeparams'];

					if(!isset($author_user_field) or $author_user_field=='' or $rec['es_'.$author_user_field]==$this->Model->ct->Env->userid)
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
				$query= 'UPDATE #__customtables_table_'.$this->Model->ct->Table->tablename.' SET '.implode(', ', $updatefields).' WHERE id='.$rec['listing_id'];

				$db->setQuery($query);
				$db->execute();	
		}

	}
}

