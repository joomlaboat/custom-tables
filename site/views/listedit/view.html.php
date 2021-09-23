<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');
class CustomTablesViewListEdit extends JView
{
    function display($tpl = null)
    {
		$mainframe = JFactory::getApplication();

		$this->optionRecord = $this->get('Data');
		
		$this->isNew= ($this->optionRecord->id < 1);

		$this->ListEditModel = $this->getModel();
	
		$filter_rootparent = $mainframe->getUserStateFromRequest( "com_customtables.filter_rootparent",'filter_rootparent','','int' );
			
        parent::display($tpl);
    }
}
