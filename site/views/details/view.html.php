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
	var $ct;
	var $row;
	var $imagegalleries;
	var $fileboxes;

	function display($tpl = null)
	{
		$this->Model = $this->getModel();
		
		$this->ct = $this->Model->ct;
		
		if(!isset($this->ct->LayoutProc))
			return;

		$layout_catalog='';

		if($this->ct->Env->menu_params->get('esdetailslayout')!='')
		{
			$Layouts = new Layouts($this->ct);
			$layout_catalog = $Layouts->getLayout($this->ct->Env->menu_params->get('esdetailslayout'));
				
			if($Layouts->layouttype==8)
				$this->ct->Env->frmt='xml';
			elseif($Layouts->layouttype==9)
				$this->ct->Env->frmt='csv';
			elseif($Layouts->layouttype==10)
				$this->ct->Env->frmt='json';

			$this->ct->LayoutProc->layout=$layout_catalog;
		}

		$this->row = $this->get('Data');

		if(count($this->row)>0)
		{
			$redirectto = $this->ct->Env->menu_params->get( 'redirectto' );
			
			if((!isset($this->row[$this->ct->Table->realidfieldname]) or (int)$this->row[$this->ct->Table->realidfieldname]==0) and $redirectto != '')
			{
				$mainframe->redirect($redirectto);
			}

			if ($this->ct->Env->print)
			{
				$document	= JFactory::getDocument();
				$document->setMetaData('robots', 'noindex, nofollow');
			}

			parent::display($tpl);

			//Save view log
			$this->SaveViewLogForRecord($this->row);
			$this->UpdatePHPOnView($this->row);
		}
	}

	function UpdatePHPOnView($row)//,$allowedfields
	{
		if(!isset($row[$this->ct->Table->realidfieldname]))
			return false;
		
		foreach($this->ct->Table->fields as $mFld)
		{
			if($mFld['type']=='phponview')
			{
				$fieldname=$mFld['fieldname'];
				$type_params=JoomlaBasicMisc::csv_explode(',',$mFld['typeparams'],'"',false);
				tagProcessor_PHP::processTempValue($this->Model,$row,$fieldname,$type_params,false);
			}
		}
	}

	function SaveViewLogForRecord($rec)//,$allowedfields
	{
		$updatefields=array();

		$allwedTypes=['lastviewtime','viewcount'];

		foreach($this->ct->Table->fields as $mFld)
		{
				$t=$mFld['type'];
				if(in_array($t,$allwedTypes))
				{

					$allow_count=true;
					$author_user_field=$mFld['typeparams'];

					if(!isset($author_user_field) or $author_user_field=='' or $rec[$this->ct->Env->field_prefix.$author_user_field]==$this->ct->Env->userid)
						$allow_count=false;

					if($allow_count)
					{
						$n=$this->ct->Env->field_prefix.$mFld['fieldname'];
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
				$query= 'UPDATE #__customtables_table_'.$this->ct->Table->tablename.' SET '.implode(', ', $updatefields).' WHERE id='.$rec[$this->ct->Table->realidfieldname];

				$db->setQuery($query);
				$db->execute();	
		}
	}
}
