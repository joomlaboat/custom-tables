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
jimport( 'joomla.application.component.view'); //Important to get menu parameters

class CustomTablesViewEditItem extends JViewLegacy
{
	function display($tpl = null)
	{
		$this->params =JFactory::getApplication()->getParams();
		
		$this->Model = $this->getModel();
        $this->Model->load($this->params);
		
        if(!$this->Model->CheckAuthorization(1))
    	{
    		//not authorized
            JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
    		return false;
        }

        if(!isset($this->Model->ct->Table->fields) or !is_array($this->Model->ct->Table->fields))
            return false;

		$this->formLink=$this->Model->ct->Env->WebsiteRoot.'index.php?option=com_customtables&amp;view=edititem'.($this->Model->ct->Env->Itemid!=0 ? '&amp;Itemid='.$this->Model->ct->Env->Itemid : '');
		$this->formName='eseditForm';
		$this->formClass='form-validate form-horizontal well';
		
		//Non need because submit button (settask function) does it.
		$this->formAttribute='';// onsubmit="return checkRequiredFields(event);"';
		
		if($this->Model->ct->Env->frmt == 'json')
			require_once('tmpl'.DIRECTORY_SEPARATOR.'json.php');
		else
			CTViewEdit($this->Model->ct, $this->Model->row, $this->Model->pagelayout, $this->Model->BlockExternalVars,$this->formLink,$this->formName,
				$this->formClass,$this->formAttribute);
	}
}
