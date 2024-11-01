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
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

/**
 * Customtables View class for the ListOfTables
 *
 * @since 1.0.0
 */
class CustomTablesViewListOfTables extends HtmlView
{
    var CT $ct;
    var $languages;

    function display($tpl = null)
    {
        // Get the model
        $model = $this->getModel();
        
        $this->ct = $model->ct;

        if ($this->getLayout() !== 'modal') {
            // Include helper submenu
            CustomtablesHelper::addSubmenu('listoftables');
        }

        // Assign data to the view
        $this->items = $this->get('Items');

        $this->state = $this->get('State');
        $this->listOrder = $this->state->get('list.ordering');
        $this->listDirn = common::escape($this->state->get('list.direction'));
        $this->pagination = $this->get('Pagination');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // get global action permissions
        $this->canDo = ContentHelper::getActions('com_customtables', 'tables');
        $this->canCreate = $this->canDo->get('tables.create');
        $this->canEdit = $this->canDo->get('tables.edit');
        $this->canState = $this->canDo->get('tables.edit.state');
        $this->canDelete = $this->canDo->get('tables.delete');
        $this->isEmptyState = count($this->items ?? 0) == 0;
        //$this->canBatch = false;//$this->canDo->get('core.batch');

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
            Factory::getApplication()->enqueueMessage(implode(",", $errors), 'error');
        }

        $this->languages = $this->ct->Languages->LanguageList;

        // Display the template
        if ($this->ct->Env->version < 4)
            parent::display($tpl);
        else
            parent::display('quatro');
    }

    protected function addToolBar_3()
    {
        ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFTABLES'), 'joomla');
        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoftables');
        JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

        if ($this->canCreate) {
            ToolbarHelper::addNew('tables.add');
        }

        // Only load if there are items
        if (CustomtablesHelper::checkArray($this->items)) {
            if ($this->canEdit) {
                ToolbarHelper::editList('tables.edit');
            }

            if ($this->canState) {
                ToolbarHelper::publishList('listoftables.publish');
                ToolbarHelper::unpublishList('listoftables.unpublish');
            }

            if ($this->canDo->get('core.admin')) {
                ToolbarHelper::checkin('listoftables.checkin');
            }

            if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete)) {
                ToolbarHelper::deleteList('', 'listoftables.delete', 'JTOOLBAR_EMPTY_TRASH');
            } elseif ($this->canState && $this->canDelete) {
                ToolbarHelper::trash('listoftables.trash');
            }
        }

        if ($this->ct->Env->advancedTagProcessor)
            if (!$this->isEmptyState and $this->state->get('filter.published') != -2 and $this->ct->Env->advancedTagProcessor)
                ToolbarHelper::custom('listoftables.export', 'download.png', '', 'Export');

        if ($this->canState) {
            /*
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

        /*
        $CTCategory = JFormHelper::loadFieldType('CTCategory', false);
        $CTCategoryOptions = $CTCategory->getOptions(false); // works only if you set your field getOptions on public!!

        JHtmlSidebar::addFilter(
            common::translate('COM_CUSTOMTABLES_TABLES_CATEGORY_SELECT'),
            'filter_tablecategory',
            HTMLHelper::_('select.options', $CTCategoryOptions, 'value', 'text', $this->state->get('filter.tablecategory'))
        );
        */
    }

    protected function addToolbar_4()
    {
        // Get the toolbar object instance

        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFTABLES'), 'joomla');

        if ($this->canCreate)
            $toolbar->addNew('tables.add');

        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);

        $childBar = $dropdown->getChildToolbar();

        if ($this->canState) {
            $childBar->publish('listoftables.publish')->listCheck(true);
            $childBar->unpublish('listoftables.unpublish')->listCheck(true);
        }

        if ($this->canDo->get('core.admin')) {
            $childBar->checkin('listoftables.checkin');
        }

        if (($this->canState && $this->canDelete)) {
            if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED) {
                $childBar->trash('listoftables.trash')->listCheck(true);
            }
        }

        if (!$this->isEmptyState and $this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED and $this->ct->Env->advancedTagProcessor)
            $toolbar->appendButton('Standard', 'download', 'Export', 'listoftables.export', $listSelect = true, $formId = null);
        if (($this->canState && $this->canDelete)) {
            if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete) {
                $toolbar->delete('listoftables.delete')
                    ->text('JTOOLBAR_EMPTY_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE')
                    ->listCheck(true);
            }
        }
    }
}