<?php

/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access
defined('_JEXEC') or die('Restricted access');



jimport( 'joomla.application.component.view');
class CustomTablesViewStructure extends JView {
        var $catid=0;
	function display($tpl = null)
	{
		$mainframe = JFactory::getApplication('site');
		
		
		$Model = $this->getModel();
		$this->assignRef('Model',$Model);
		
		
		$rows=$Model->getStructure();
		
		$this->assignRef('rows',$rows);
		
		$pagination=$Model->getPagination();
		$this->assignRef('pagination', $pagination);


        parent::display($tpl);
	}
	


	
}
?>
