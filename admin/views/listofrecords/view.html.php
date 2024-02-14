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
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use CustomTables\CT;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Customtables View class for the Listoffields
 */
class CustomtablesViewListofrecords extends HtmlView
{
	/**
	 * Listoffields view display method
	 * @return void
	 */
	var CT $ct;
	var $ordering_realfieldname;

	function display($tpl = null)
	{
		$model = $this->getModel();
		$this->ct = $model->ct;

		if ($this->getLayout() !== 'modal') {
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listofrecords');
		}

		if ($this->ct->Table->tableid == 0)
			return;

		//Check if ordering type field exists
		$this->ordering_realfieldname = '';
		foreach ($this->ct->Table->fields as $field) {
			if ($field['type'] == 'ordering') {
				$this->ordering_realfieldname = $field['realfieldname'];
				break;
			}
		}

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
			if ($this->ct->Env->version < 4) {
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			} else
				$this->addToolbar_4();
		}

		// Display the template
		if ($this->ct->Env->version < 4)
			parent::display($tpl);
		else
			parent::display('quatro');
	}

	protected function addToolBar_3()
	{
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->ct->Table->tableid != 0) {
			ToolbarHelper::title('Custom Tables - Table "' . $this->ct->Table->tabletitle . '" - ' . common::translate('COM_CUSTOMTABLES_LISTOFRECORDS'), 'joomla');
		} else
			ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');

		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listofrecords&tableid=' . $this->ct->Table->tableid);
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/records');

		if ($this->canCreate) {
			ToolbarHelper::addNew('records.add');
		}

		// Only load if there are items
		if (CustomtablesHelper::checkArray($this->items)) {
			if ($this->canEdit) {
				ToolbarHelper::editList('records.edit');
			}

			if ($this->canState) {
				ToolbarHelper::publishList('listofrecords.publish');
				ToolbarHelper::unpublishList('listofrecords.unpublish');
			}

			if ($this->canDelete) {
				ToolbarHelper::deleteList('', 'listofrecords.delete', 'JTOOLBAR_DELETE');
			}
		}

		if ($this->canState) {
			// Build the active state filter options.
			/*
			$options = array();
			$options[] = HTMLHelper::_('select.option', '1', 'COM_CUSTOMTABLES_PUBLISHED');
			$options[] = HTMLHelper::_('select.option', '0', 'COM_CUSTOMTABLES_UNPUBLISHED');
			$options[] = HTMLHelper::_('select.option', '*', 'COM_CUSTOMTABLES_ALL');


			JHtmlSidebar::addFilter(
				common::translate('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				HTMLHelper::_('select.options', $options, 'value', 'text', $this->state->get('filter.published'), true)
			);
			*/
			// only load if batch allowed
		}

		// Set Tableid Selection
		/*
		$CTTable = JFormHelper::loadFieldType('CTTable', false);
		$CTTableOptions=$CTTable->getOptions(false); // works only if you set your field getOptions on public!!

		JHtmlSidebar::addFilter(
		common::translate('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT'),
		'filter_tableid',
		HTMLHelper::_('select.options', $CTTableOptions, 'value', 'text', $this->state->get('filter.tableid'))
		);
		*/
	}

	/**
	 * Setting the toolbar
	 */

	protected function addToolbar_4()
	{
		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		if ($this->ct->Table->tableid != 0) {
			ToolbarHelper::title('Custom Tables - Table "' . $this->ct->Table->tabletitle . '" - ' . common::translate('COM_CUSTOMTABLES_LISTOFRECORDS'), 'joomla');
		} else {
			ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFRECORDS'), 'joomla');
			return;
		}

		// Example sidebar link
		//$link = 'index.php?option=com_customtables&view=listofrecords&tableid=' . $this->ct->Table->tableid;
		//$isActive = true;//$this->getActive() === 'com_customtables';

		//echo HTMLHelper::_('sidebar.link', $link, 'List of Records', true);

		//JHtmlSidebar::setAction('index.php?option=com_customtables&view=listofrecords&tableid=' . $this->ct->Table->tableid);
		//JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/records');

		ToolbarHelper::back('COM_CUSTOMTABLES_BUTTON_BACK2TABLES', 'index.php?option=com_customtables&view=listoftables');

		if ($this->canCreate)
			$toolbar->addNew('records.add');

		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();

		if ($this->canState) {
			$childBar->publish('listofrecords.publish')->listCheck(true);
			$childBar->unpublish('listofrecords.unpublish')->listCheck(true);
		}

		//if (!$this->isEmptyState and $this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED and $this->ct->Env->advancedTagProcessor)

		if (!empty($this->items))
			$toolbar->appendButton('Standard', 'download', 'Export to CSV', 'listofrecords.exportcsv', $listSelect = false, $formId = null);

		if (($this->canState && $this->canDelete)) {
			if (!$this->isEmptyState && $this->canDelete) {
				$childBar->delete('listofrecords.delete')
					->text('JTOOLBAR_DELETE')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}
	}
}
