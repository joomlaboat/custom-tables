<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.pane');
jimport( 'joomla.application.component.view'); //Important to get menu parameters

class CustomTablesViewEditItem extends JViewLegacy
{
	function display($tpl = null)
	{
		$Model = $this->getModel();
        $Model->load(JFactory::getApplication()->getParams());
		
        if(!$Model->CheckAuthorization(1))
    	{
    		//not authorized
            JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
    		return false;
        }

        if(!isset($Model->ct->Table->fields) or !is_array($Model->ct->Table->fields))
            return false;

		$formLink=$Model->ct->Env->WebsiteRoot.'index.php?option=com_customtables&amp;view=edititem'.($Model->ct->Env->Itemid!=0 ? '&amp;Itemid='.$Model->ct->Env->Itemid : '');
		
		if($Model->ct->Env->frmt == 'json')
			require_once('tmpl'.DIRECTORY_SEPARATOR.'json.php');
		else
			CTViewEdit($Model->ct, $Model->row, $Model->pagelayout, $Model->BlockExternalVars,$formLink,'eseditForm');
	}
}
