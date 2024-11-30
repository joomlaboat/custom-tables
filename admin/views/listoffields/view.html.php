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
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoffields.php');

/**
 * Customtables View class for the Listoffields
 *
 * @since 1.0.0
 */
class CustomtablesViewListOfFields extends HtmlView
{
    /**
     * ListOfFields view display method
     * @return void
     *
     * @since 1.0.0
     */
    var CT $ct;
    var int $tableid;
    var array $languages;

    function display($tpl = null)
    {
        $model = $this->getModel();
        $this->ct = $model->ct;

        if ($this->getLayout() !== 'modal') {
            // Include helper submenu
            CustomtablesHelper::addSubmenu('listoffields');
        }

        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        $this->listOrder = common::escape($this->state->get('list.ordering'));
        $this->listDirn = common::escape($this->state->get('list.direction'));
        $this->saveOrder = $this->listOrder == 'ordering' || $this->listOrder == 'a.ordering';

        // get global action permissions
        $this->canDo = ContentHelper::getActions('com_customtables', 'tables');
        $this->canEdit = $this->canDo->get('tables.edit');
        $this->canState = $this->canDo->get('tables.edit');
        $this->canCreate = $this->canDo->get('tables.edit');
        $this->canDelete = $this->canDo->get('tables.edit');
        //$this->canBatch = $this->canDo->get('tables.edit');
        $this->isEmptyState = count($this->items ?? 0) == 0;

        // We don't need toolbar in the modal window.

        if ($this->getLayout() !== 'modal') {
            if (CUSTOMTABLES_JOOMLA_MIN_4) {
                $this->addToolbar_4();
            } else {
                $this->addToolbar_3();
                $this->sidebar = JHtmlSidebar::render();
            }
        }

        $this->languages = $this->ct->Languages->LanguageList;

        // Display the template
        if (CUSTOMTABLES_JOOMLA_MIN_4)
            parent::display('quatro');
        else
            parent::display($tpl);
    }

    protected function addToolbar_4()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        if ($this->ct->Table->tableid != 0) {
            ToolbarHelper::title('Custom Tables - Table "' . $this->ct->Table->tabletitle . '" - ' . common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');
        } else
            ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');

        //JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoffields&tableid=' . $this->ct->Table->tableid);
        //JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

        //https://api.joomla.org/cms-3/classes/Joomla.CMS.Toolbar.ToolbarHelper.html

        ToolbarHelper::back('COM_CUSTOMTABLES_BUTTON_BACK2TABLES', 'index.php?option=com_customtables&view=listoftables');

        if ($this->canCreate)
            $toolbar->addNew('fields.add');

        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);

        $childBar = $dropdown->getChildToolbar();

        if ($this->canState) {
            $childBar->publish('listoffields.publish')->listCheck(true);
            $childBar->unpublish('listoffields.unpublish')->listCheck(true);
        }

        if ($this->canDo->get('core.admin')) {
            $childBar->checkin('listoffields.checkin');
        }

        if (($this->canState && $this->canDelete)) {
            if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED) {
                $childBar->trash('listoffields.trash')->listCheck(true);
            }

            if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete) {
                $toolbar->delete('listoffields.delete')
                    ->text('JTOOLBAR_EMPTY_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE')
                    ->listCheck(true);
            }
        }
    }

    protected function addToolBar_3()
    {
        if ($this->ct->Table->tableid != 0) {
            ToolbarHelper::title('Table "' . $this->ct->Table->tabletitle . '" - ' . common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');
        } else
            ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');

        JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

        if ($this->canCreate) {
            ToolbarHelper::addNew('fields.add');
        }

        // Only load if there are items
        if (CustomtablesHelper::checkArray($this->items)) {
            if ($this->canEdit) {
                ToolbarHelper::editList('fields.edit');
            }

            if ($this->canState) {
                ToolbarHelper::publishList('listoffields.publish');
                ToolbarHelper::unpublishList('listoffields.unpublish');
            }

            if ($this->canDo->get('core.admin')) {
                ToolbarHelper::checkin('listoffields.checkin');
            }

            if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete)) {
                ToolbarHelper::deleteList('', 'listoffields.delete', 'JTOOLBAR_EMPTY_TRASH');
            } elseif ($this->canState && $this->canDelete) {
                ToolbarHelper::trash('listoffields.trash');
            }
        }

        // add the options comp button
        if ($this->canDo->get('core.admin') || $this->canDo->get('core.options')) {
            ToolbarHelper::preferences('com_customtables');
        }

        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoffields&tableid=' . $this->ct->Table->tableid);
    }
}
