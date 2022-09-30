<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
\defined('_JEXEC') or die;

use CustomTables\CT;

use Joomla\CMS\Version;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

/**
 * Customtables View class for the Listofcategories
 */
class CustomtablesViewListofcategories extends JViewLegacy
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
        $this->user = Factory::getUser();
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->listOrder = $this->escape($this->state->get('list.ordering'));
        $this->listDirn = $this->escape($this->state->get('list.direction'));
        $this->saveOrder = $this->listOrder == 'ordering';

        // get global action permissions
        $this->canDo = ContentHelper::getActions('com_customtables', 'categories');
        $this->canCreate = $this->canDo->get('categories.create');
        $this->canEdit = $this->canDo->get('categories.edit');
        $this->canState = $this->canDo->get('categories.edit.state');
        $this->canDelete = $this->canDo->get('categories.delete');

        $this->isEmptyState = $this->get('IsEmptyState');
        //$this->canBatch = $this->canDo->get('core.batch');

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {
            if ($this->ct->Env->version < 4) {
                $this->addToolbar_3();
                $this->sidebar = JHtmlSidebar::render();
            } else
                $this->addToolbar_4();

            // load the batch html
            if ($this->canCreate && $this->canEdit && $this->canState) {
                $this->batchDisplay = JHtmlBatch_::render();
            }
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
        JToolBarHelper::title(Text::_('COM_CUSTOMTABLES_LISTOFCATEGORIES'), 'joomla');
        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listofcategories');
        //JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

        if ($this->canCreate)
            JToolBarHelper::addNew('categories.add');

        // Only load if there are items
        if (CustomtablesHelper::checkArray($this->items)) {
            if ($this->canEdit) {
                JToolBarHelper::editList('categories.edit');
            }

            if ($this->canState) {
                JToolBarHelper::publishList('listofcategories.publish');
                JToolBarHelper::unpublishList('listofcategories.unpublish');
            }

            if ($this->canDo->get('core.admin')) {
                JToolBarHelper::checkin('listofcategories.checkin');
            }

            if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete)) {
                JToolbarHelper::deleteList('', 'listofcategories.delete', 'JTOOLBAR_EMPTY_TRASH');
            } elseif ($this->canState && $this->canDelete) {
                JToolbarHelper::trash('listofcategories.trash');
            }
        }

        if ($this->canState) {

            $options = JHtml::_('jgrid.publishedOptions');
            $newOptions = [];
            foreach ($options as $option) {

                if ($option->value != 2)
                    $newOptions[] = $option;
            }

            /*
            JHtmlSidebar::addFilter(
                Text::_('JOPTION_SELECT_PUBLISHED'),
                'filter_published',
                JHtml::_('select.options', $newOptions, 'value', 'text', $this->state->get('filter.published'), true)
            );
            */
        }
    }

    protected function addToolbar_4()
    {
        $user = Factory::getUser();

        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_CUSTOMTABLES_LISTOFCATEGORIES'), 'joomla');

        if ($this->canCreate and $this->ct->Env->advancedtagprocessor)
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
