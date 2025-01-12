<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

// import Joomla view library
jimport('joomla.application.component.view');

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Diagram;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Tables View class
 *
 * @since 3.0.0
 */
class CustomtablesViewDataBaseCheck extends HtmlView
{
	/**
	 * display method of View
	 * @return void
	 *
	 * @since 3.0.0
	 */
	var CT $ct;

	var $tables = false;
	public $activeFilters;
	public $diagram;

	public function display($tpl = null)
	{
		$this->ct = new CT([], true);
		$this->state = $this->get('State');

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-diagram.php');
		if (CUSTOMTABLES_JOOMLA_MIN_4) {
			$this->diagram = new Diagram($this->state->get('list.tablecategory'));
			$this->filterForm = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
		} else
			$this->diagram = new Diagram($this->state->get('filter.tablecategory'));

		if ($this->getLayout() !== 'modal') {
			// Include helper submenu
			CustomtablesHelper::addSubmenu('databasecheck');

			if (CUSTOMTABLES_JOOMLA_MIN_4) {
				$this->addToolbar_4();
			} else {
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			}
		}

		if ($this->ct->Env->advancedTagProcessor) {
			// Display the template
			if (CUSTOMTABLES_JOOMLA_MIN_4) {
				parent::display('quatro');
			} else {
				parent::display($tpl);
			}
		} else {
			echo Text::_('COM_CUSTOMTABLES_AVAILABLE');
		}

		// Set the document
		$document = Factory::getDocument();
		$this->setDocument($document);
	}

	protected function addToolbar_4()
	{
		// Get the toolbar object instance
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_DATABASECHECK'), 'joomla');
	}

	protected function addToolBar_3()
	{
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_DATABASECHECK'), 'joomla');
		JHtmlSidebar::setAction('index.php?option=com_customtables&view=databasecheck');
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		$CTCategory = JFormHelper::loadFieldType('CTCategory', false);
		$CTCategoryOptions = $CTCategory->getOptions(false); // works only if you set your field getOptions on public!!

		JHtmlSidebar::addFilter(
			common::translate('COM_CUSTOMTABLES_TABLES_CATEGORY_SELECT'),
			'filter_tablecategory',
			HTMLHelper::_('select.options', $CTCategoryOptions, 'value', 'text', $this->state->get('filter.tablecategory'))
		);
	}

	public function setDocument(Joomla\CMS\Document\Document $document): void
	{
		$document->setTitle(common::translate('COM_CUSTOMTABLES_DATABASECHECK'));
		$document->addStyleSheet(common::UriRoot(true) . "/components/com_customtables/libraries/customtables/media/css/fieldtypes.css");
	}
}
