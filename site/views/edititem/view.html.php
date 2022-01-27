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
	var $ct;
    var $catid=0;
	var $pagelayout;
	var $BlockExternalVars;
	
	function display($tpl = null)
	{
	    $app = JFactory::getApplication();
		$this->params =$app->getParams();

	    //========= User info
		$user = JFactory::getUser();
		$this->userid = (int)$user->get('id');
		
		//------ end user info
		
		$this->Model = $this->getModel();
        $this->Model->load($this->params);
		
		$this->ct = $this->Model->ct;
		
		$this->pagelayout = $this->Model->pagelayout;
		$this->BlockExternalVars = $this->Model->BlockExternalVars;
		
        if(!$this->Model->CheckAuthorization(1))
    	{
    		//not authorized
            JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
    		return false;
        }

        if(!isset($this->Model->ct->Table->fields) or !is_array($this->Model->ct->Table->fields))
            return false;

		$this->row = $this->Model->row;

		$this->formLink=$this->Model->ct->Env->WebsiteRoot.'index.php?option=com_customtables&amp;view=edititem'.($this->Model->ct->Env->Itemid!=0 ? '&amp;Itemid='.$this->Model->ct->Env->Itemid : '');
		$this->formName='eseditForm';
		$this->formClass='form-validate form-horizontal well';
		
		//Non need because submit button (settask function) does it.
		$this->formAttribute='';// onsubmit="return checkRequiredFields(event);"';
		
		if($this->Model->ct->Env->frmt == 'json')
			require_once('tmpl'.DIRECTORY_SEPARATOR.'json.php');
		else
			parent::display($tpl);
	}
}
