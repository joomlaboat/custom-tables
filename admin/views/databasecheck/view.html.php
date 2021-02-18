<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Tables View class
 */
class CustomtablesViewDataBaseCheck extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	var $tables=false;
	
	public function display($tpl = null)
	{
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			CustomtablesHelper::addSubmenu('databasecheck');
			$this->addToolBar();
			$this->sidebar = JHtmlSidebar::render();
		}
		// Set the document
		$this->setDocument();
		
		$this->tables = $this->getTables();

		parent::display($tpl);

		
	}
	
	protected function addToolBar()
	{
	
		JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_DATABASECHECK'), 'joomla');
		JHtmlSidebar::setAction('index.php?option=com_customtables&view=databasecheck');
		
	}

	protected function setDocument()
	{
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_('COM_CUSTOMTABLES_DATABASECHECK'));
		$this->document->addStyleSheet(JURI::root(true)."/administrator/components/com_customtables/css/fieldtypes.css", (CustomtablesHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
		
		
	}
	
	protected function getTables()
	{
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $this->getTablesQuery();
		
		$db->setQuery( $query );
		$rows = $db->loadAssocList();
		
		return $rows;
	}
	
	protected function getTablesQuery()
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR
			.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
		
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$categoryname='(SELECT categoryname FROM #__customtables_categories AS categories WHERE categories.id=a.tablecategory LIMIT 1)';
		$fieldcount='(SELECT COUNT(fields.id) FROM #__customtables_fields AS fields WHERE fields.tableid=a.id AND fields.published=1 LIMIT 1)';
		$selects=array();
		$selects[]=ESTables::getTableRowSelects();
		$selects[]=$categoryname.' AS categoryname';
		$selects[]=$fieldcount.' AS fieldcount';
		
		$query->select(implode(',',$selects));

		// From the customtables_item table
		$query->from($db->quoteName('#__customtables_tables', 'a'));

		$query->where('a.published = 1');
		
		
		// Add the list ordering clause.
		$orderCol = 'tablename';
		$orderDirn = 'asc';
		$query->order($db->escape($orderCol . ' ' . $orderDirn));
		
		return $query;
	}

	
	
}
