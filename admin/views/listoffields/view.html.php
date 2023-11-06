<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die;

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;
use CustomTables\database;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoffields.php');

/**
 * Customtables View class for the Listoffields
 */
class CustomtablesViewListoffields extends JViewLegacy
{
    /**
     * Listoffields view display method
     * @return void
     */
    var CT $ct;
    var $tableid;
    var $languages;

    function display($tpl = null)
    {
        $this->ct = new CT;

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

        $this->listOrder = $this->escape($this->state->get('list.ordering'));
        $this->listDirn = $this->escape($this->state->get('list.direction'));
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
        $this->tableid = common::inputGetInt('tableid', 0);

        if ($this->tableid != 0) {
            $tableRow = ESTables::getTableRowByIDAssoc($this->tableid);
            if (!is_object($tableRow) and $tableRow == 0) {
                Factory::getApplication()->enqueueMessage('Table not found', 'error');
                $this->tableid = 0;
            } else {
                $this->ct->setTable($tableRow, null, false);
            }
        }

        if ($this->getLayout() !== 'modal') {
            if ($this->ct->Env->version < 4) {
                $this->addToolbar_3();
                $this->sidebar = JHtmlSidebar::render();
            } else
                $this->addToolbar_4();

            // load the batch html
            if ($this->canCreate && $this->canEdit && $this->canState) {
                //$this->batchDisplay = JHtmlBatch_::render();
            }
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
        if ($this->tableid != 0) {
            JToolBarHelper::title('Table "' . $this->ct->Table->tabletitle . '" - ' . common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');
        } else
            JToolBarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');

        JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

        if ($this->canCreate) {
            JToolBarHelper::addNew('fields.add');
        }

        // Only load if there are items
        if (CustomtablesHelper::checkArray($this->items)) {
            if ($this->canEdit) {
                JToolBarHelper::editList('fields.edit');
            }

            if ($this->canState) {
                JToolBarHelper::publishList('listoffields.publish');
                JToolBarHelper::unpublishList('listoffields.unpublish');
            }

            if ($this->canDo->get('core.admin')) {
                JToolBarHelper::checkin('listoffields.checkin');
            }

            if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete)) {
                JToolbarHelper::deleteList('', 'listoffields.delete', 'JTOOLBAR_EMPTY_TRASH');
            } elseif ($this->canState && $this->canDelete) {
                JToolbarHelper::trash('listoffields.trash');
            }
        }

        // add the options comp button
        if ($this->canDo->get('core.admin') || $this->canDo->get('core.options')) {
            JToolBarHelper::preferences('com_customtables');
        }

        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoffields&tableid=' . $this->tableid);
    }

    protected function addToolbar_4()
    {
        $user = new CTUser();

        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        if ($this->tableid != 0) {
            JToolBarHelper::title('Custom Tables - Table "' . $this->ct->Table->tabletitle . '" - ' . common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');
        } else
            JToolBarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');

        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoffields&tableid=' . $this->tableid);
        JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

        //https://api.joomla.org/cms-3/classes/Joomla.CMS.Toolbar.ToolbarHelper.html

        JToolBarHelper::back('COM_CUSTOMTABLES_BUTTON_BACK2TABLES', 'index.php?option=com_customtables&view=listoftables');

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

    protected function getTheTypeSelections()
    {
        $query = 'SELECT ' . database::quoteName('type') . ' FROM ' . database::quoteName('#__customtables_fields') . ' ORDER BY ' . database::quoteName('type');
        $results = database::loadColumn($query);

        if ($results) {
            $results = array_unique($results);
            $_filter = array();
            foreach ($results as $type) {
                // Translate the type selection
                $text = '987';//$model->selectionTranslation($type, 'type');
                // Now add the type and its text to the options array
                $_filter[] = JHtml::_('select.option', $type, common::translate($text));
            }
            return $_filter;
        }
        return false;
    }
}
