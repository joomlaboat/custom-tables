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
use CustomTables\CT;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

/**
 * Customtables View class for the ListOfMenus
 *
 * @since 3.3.7
 */
class CustomtablesViewListOfMenus extends HtmlView
{
	/**
	 * ListOfMenus view display method
	 * @return void
	 *
	 * @since 3.3.7
	 */
	var CT $ct;
	var $isEmptyState = false;
	var $categoryId;

	function display($tpl = null)
	{
		$this->ct = new CT([], true);

		if ($this->getLayout() !== 'modal') {
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listofmenus');
		}

		$this->categoryId = common::inputGetInt('categoryid');

		// Assign data to the view
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->listOrder = common::escape($this->state->get('list.ordering'));
		$this->listDirn = common::escape($this->state->get('list.direction'));
		$this->saveOrder = $this->listOrder == 'ordering';

		// get global action permissions
		$this->canDo = ContentHelper::getActions('com_customtables', 'menu');
		$this->canCreate = true;//$this->canDo->get('menus.create');
		$this->canEdit = true;// $this->canDo->get('menus.edit');
		$this->canState = true;// $this->canDo->get('menus.edit.state');
		$this->canDelete = true;//$this->canDo->get('menus.delete');

		if (is_array($this->items))
			$this->isEmptyState = count($this->items) == 0;
		else
			$this->isEmptyState = true;

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal') {
			$this->addToolbar_4();
		}

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		parent::display($tpl);
	}

	protected function addToolbar_4()
	{
		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFMENUS'), 'joomla');

		if ($this->canCreate)
			$toolbar->addNew('menus.add');

		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();

		if ($this->canState) {
			$childBar->publish('listofmenus.publish')->listCheck(true);
			$childBar->unpublish('listofmenus.unpublish')->listCheck(true);
		}

		if ($this->canDo->get('core.admin')) {
			$childBar->checkin('listofmenus.checkin');
		}

		if (($this->canState && $this->canDelete)) {
			if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED) {
				$childBar->trash('listofmenus.trash')->listCheck(true);
			}

			if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete) {
				$toolbar->delete('listofmenus.delete')
					->text('JTOOLBAR_EMPTY_TRASH')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}
	}
}
