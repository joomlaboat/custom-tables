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

use Joomla\CMS\Version;

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
		$version = new Version;
		$this->version = (int)$version->getShortVersion();
		
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			CustomtablesHelper::addSubmenu('databasecheck');
			$this->addToolBar();
			if($this->version < 4)
				$this->sidebar = JHtmlSidebar::render();
		}

		// Set the document
		$this->setDocument();
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
			$this->document = JFactory::getDocument();

		$this->document->setTitle(JText::_('COM_CUSTOMTABLES_DATABASECHECK'));
		$this->document->addStyleSheet(JURI::root(true)."/administrator/components/com_customtables/css/fieldtypes.css");
	}
}
