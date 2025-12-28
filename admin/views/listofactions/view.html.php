<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die;

use CustomTables\common;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use CustomTables\CT;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Customtables View class for the ListOfRecords
 *
 * @since 1.0.0
 */
class CustomtablesViewListOfActions extends HtmlView
{
	/**
	 * Listoffields view display method
	 * @return void
	 *
	 * @since 3.0.0
	 */
	var CT $ct;
	var $ordering_realfieldname;

	function display($tpl = null)
	{
		$model = $this->getModel();
		$this->ct = $model->ct;

		//Other parameters
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->listOrder = common::escape($this->state->get('list.ordering'));
		$this->listDirn = common::escape($this->state->get('list.direction'));
		$this->saveOrder = $this->listOrder == 'custom';

		// get global action permissions
		$this->canDo = ContentHelper::getActions('com_customtables', 'tables');
		$this->canEdit = $this->canDo->get('tables.edit');
		$this->canState = $this->canDo->get('tables.edit');
		$this->canCreate = $this->canDo->get('tables.edit');
		$this->canDelete = $this->canDo->get('tables.edit');

		if (is_bool($this->items))
			$this->isEmptyState = true;
		else
			$this->isEmptyState = count($this->items ?? 0) == 0;

		if ($this->getLayout() !== 'modal') {

			// Include helper submenu
			CustomtablesHelper::addSubmenu('listofactions');

			if (CUSTOMTABLES_JOOMLA_MIN_4) {
				$this->addToolbar_4();
			} else {
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			}
		}


		// Display the template
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			parent::display('quatro');
		else
			parent::display($tpl);
	}

	/**
	 * Setting the toolbar
	 * @since 3.0.0
	 */

	protected function addToolbar_4()
	{
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFACTIONS'), 'joomla');

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');
	}

	protected function addToolBar_3()
	{
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFACTIONS'), 'joomla');
	}
}
