<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');

class CustomTablesViewStructure extends JView
{
	function display($tpl = null)
	{
		$mainframe = JFactory::getApplication('site');
		
		$this->Model = $this->getModel();
		
		$this->rows=$this->Model->getStructure();
		
		$this->pagination=$Model->getPagination();
		
        parent::display($tpl);
	}
}
