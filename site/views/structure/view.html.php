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
	var $ct;
	
	function display($tpl = null)
	{
		$this->Model = $this->getModel();
		
		$this->ct = $this->Model->ct;
		
		$this->rows=$this->Model->getStructure();
		$this->pagination=$this->Model->getPagination();
		
		$this->record_count = $this->Model->record_count;
		
		$this->linkable = $this->Model->linkable;
		
		$this->esfieldname = $this->Model->esfieldname;
		
		$this->row_break = $this->Model->row_break;
		
		$this->image_prefix = $this->Model->image_prefix;
		$this->optionname = $this->Model->optionname;
		
        parent::display($tpl);
	}
}
