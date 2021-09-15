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

class CustomTablesViewEditItem extends JViewLegacy {
    var $catid=0;
	function display($tpl = null)
	{
	    $document = JFactory::getDocument();
		$document->addCustomTag('<link src="'.JURI::root(true).'/components/com_customtables/css/style.css" type="text/css" rel="stylesheet" >');

		$app = JFactory::getApplication();
		$this->params =$app->getParams();

	    //========= User info
		$user = JFactory::getUser();
		$this->userid = (int)$user->get('id');
		
		//------ end user info
		
		$this->Model = $this->getModel();
        $this->Model->load($this->params);
		
        if(!$this->Model->CheckAuthorization(1))
    	{
    		//not authorized
            JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
    		return false;
        }

        if(!isset($this->Model->esfields) or !is_array($this->Model->esfields))
            return false;

		$this->langpostfix = $this->Model->langpostfix;
		$this->esfields = $this->Model->esfields;
		$this->row = $this->Model->row;

		$WebsiteRoot=JURI::root(true);
		if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
			$WebsiteRoot.='/';

		$this->formLink=$WebsiteRoot.'index.php?option=com_customtables&amp;view=edititem'.($this->Model->Itemid!=0 ? '&amp;Itemid='.$this->Model->Itemid : '');//.'&amp;lang='.$lang;
		$this->formName='eseditForm';
		$this->formClass='form-validate form-horizontal well';
		
		//Non need because submit button (settask function) does it.
		$this->formAttribute='';// onsubmit="return checkRequiredFields(event);"';
		
		if($this->Model->frmt == 'json')
			require_once('tmpl'.DIRECTORY_SEPARATOR.'json.php');
		else
			parent::display($tpl);
	}
}
