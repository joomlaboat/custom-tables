<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die;

use CustomTables\common;
use CustomTables\CT;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

/**
 * Customtables View class for the Listofcategories
 */
class CustomtablesViewListofcategories extends HtmlView
{
	/**
	 * Listofcategories view display method
	 * @return void
	 */
	var CT $ct;
	var $isEmptyState = false;

	function display($tpl = null)
	{
		$this->ct = new CT;

		if ($this->getLayout() !== 'modal') {
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listofcategories');
		}

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
		$this->canDo = ContentHelper::getActions('com_customtables', 'categories');
		$this->canCreate = $this->canDo->get('categories.create');
		$this->canEdit = $this->canDo->get('categories.edit');
		$this->canState = $this->canDo->get('categories.edit.state');
		$this->canDelete = $this->canDo->get('categories.delete');

		$this->isEmptyState = count($this->items ?? 0) == 0;
		//$this->canBatch = $this->canDo->get('core.batch');

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal') {
			if ($this->ct->Env->version < 4) {
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			} else
				$this->addToolbar_4();
		}

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		if ($this->ct->Env->version < 4)
			parent::display($tpl);
		else
			parent::display('quatro');
	}

	protected function addToolBar_3()
	{
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFCATEGORIES'), 'joomla');
		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listofcategories');
		//JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->canCreate)
			ToolbarHelper::addNew('categories.add');

		// Only load if there are items
		if (CustomtablesHelper::checkArray($this->items)) {
			if ($this->canEdit) {
				ToolbarHelper::editList('categories.edit');
			}

			if ($this->canState) {
				ToolbarHelper::publishList('listofcategories.publish');
				ToolbarHelper::unpublishList('listofcategories.unpublish');
			}

			if ($this->canDo->get('core.admin')) {
				ToolbarHelper::checkin('listofcategories.checkin');
			}

			if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete)) {
				ToolbarHelper::deleteList('', 'listofcategories.delete', 'JTOOLBAR_EMPTY_TRASH');
			} elseif ($this->canState && $this->canDelete) {
				ToolbarHelper::trash('listofcategories.trash');
			}
		}

		if ($this->canState) {

			$options = HtmlHelper::_('jgrid.publishedOptions');
			$newOptions = [];
			foreach ($options as $option) {

				if ($option->value != 2)
					$newOptions[] = $option;
			}

			/*
			JHtmlSidebar::addFilter(
				common::translate('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				HTMLHelper::_('select.options', $newOptions, 'value', 'text', $this->state->get('filter.published'), true)
			);
			*/
		}
	}

	protected function addToolbar_4()
	{
		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFCATEGORIES'), 'joomla');

		if ($this->canCreate and $this->ct->Env->advancedTagProcessor)
			$toolbar->addNew('categories.add');

		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();

		if ($this->canState) {
			$childBar->publish('listofcategories.publish')->listCheck(true);
			$childBar->unpublish('listofcategories.unpublish')->listCheck(true);
		}

		if ($this->canDo->get('core.admin')) {
			$childBar->checkin('listoflayouts.checkin');
		}

		if (($this->canState && $this->canDelete)) {
			if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED) {
				$childBar->trash('listofcategories.trash')->listCheck(true);
			}

			if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete) {
				$toolbar->delete('listofcategories.delete')
					->text('JTOOLBAR_EMPTY_TRASH')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}
	}
}
