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
 * Customtables View class for the Listoffields
 */
class CustomtablesViewListoffields extends JViewLegacy
{
    /**
     * Listoffields view display method
     * @return void
     */
    var $ct;
    var $tableid;
    var $tablename;
    var $tabletitle;
    var $languages;

    function display($tpl = null)
    {
        $app = Factory::getApplication();

        if ($this->getLayout() !== 'modal') {
            // Include helper submenu
            CustomtablesHelper::addSubmenu('listoffields');
        }

        $model = $this->getModel();
        $this->ct = $model->ct;

        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->user = Factory::getUser();

        if ($this->ct->Env->version >= 4) {
            $this->filterForm = $this->get('FilterForm');
            $this->activeFilters = $this->get('ActiveFilters');
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

        $this->isEmptyState = $this->get('IsEmptyState');

        // We don't need toolbar in the modal window.
        $this->tableid = $app->input->getInt('tableid', 0);
        $this->customtablename = '';

        if ($this->tableid != 0) {
            $table = ESTables::getTableRowByIDAssoc($this->tableid);
            if (!is_object($table) and $table == 0) {
                Factory::getApplication()->enqueueMessage('Table not found', 'error');
                $this->tableid = 0;
            } else {
                $this->ct->setTable($table, $useridfieldname = null, $load_fields = false);

                $this->tablename = $table['tablename'];
                $this->tabletitle = $table['tabletitle'];
                $this->customtablename = $table['customtablename'];
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

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        $this->languages = $this->ct->Languages->LanguageList;

        // Display the template
        if ($this->ct->Env->version < 4)
            parent::display($tpl);
        else
            parent::display('quatro');

        // Set the document
        $this->setDocument();
    }

    /**
     * Escapes a value for output in a view script.
     *
     * @param mixed $var The output to escape.
     *
     * @return  mixed  The escaped value.
     */
    public function escape($var)
    {
        if (strlen($var) > 50) {
            // use the helper htmlEscape method instead and shorten the string
            return CustomtablesHelper::htmlEscape($var, $this->_charset, true);
        }
        // use the helper htmlEscape method instead.
        return CustomtablesHelper::htmlEscape($var, $this->_charset);
    }

    protected function addToolBar_3()
    {
        $app = Factory::getApplication();

        if ($this->tableid != 0) {
            JToolBarHelper::title('Table "' . $this->tabletitle . '" - ' . JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');
        } else
            JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');


        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoffields&tableid=' . $this->tableid);
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

        if ($this->canState) {
            /*
            JHtmlSidebar::addFilter(
                JText::_('JOPTION_SELECT_PUBLISHED'),
                'filter_published',
                JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
            );
            */

            $CTJStatus = JFormHelper::loadFieldType('CTJStatus', false);
            $CTJStatusOptions = $CTJStatus->getOptions(false); // works only if you set your field getOptions on public!!


            JHtmlSidebar::addFilter(
                JText::_('JOPTION_SELECT_PUBLISHED'),
                'filter_published',
                JHtml::_('select.options', $CTJStatusOptions, 'value', 'text', $this->state->get('filter.published'))
            );
        }

        // Set Type Selection
        /*
        $this->typeOptions = $this->getTheTypeSelections();
        if ($this->typeOptions)
        {
            // Type Filter
            JHtmlSidebar::addFilter(
                '- Select '.JText::_('COM_CUSTOMTABLES_FIELDS_TYPE_LABEL').' -',
                'filter_type',
                JHtml::_('select.options', $this->typeOptions, 'value', 'text', $this->state->get('filter.type'))
            );
        }
        */

        $CTField = JFormHelper::loadFieldType('CTField', false);
        $CTFieldOptions = $CTField->getOptions(false); // works only if you set your field getOptions on public!!

        JHtmlSidebar::addFilter(
            '- Select ' . JText::_('COM_CUSTOMTABLES_FIELDS_TYPE_LABEL') . ' -',
            'filter_type',
            JHtml::_('select.options', $CTFieldOptions, 'value', 'text', $this->state->get('filter.type'))
        );

        // Set Tableid Selection
        /*
        $CTTable = JFormHelper::loadFieldType('CTTable', false);
        $CTTableOptions=$CTTable->getOptions(false); // works only if you set your field getOptions on public!!

        JHtmlSidebar::addFilter(
        JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT'),
        'filter_tableid',
        JHtml::_('select.options', $CTTableOptions, 'value', 'text', $this->state->get('filter.tableid'))
        );

        $this->tableidOptions = $this->getTheTableidSelections();
        */
    }

    /**
     * Setting the toolbar
     */
    protected function addToolbar_4()
    {
        $user = Factory::getUser();

        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        if ($this->tableid != 0) {
            JToolBarHelper::title('Custom Tables - Table "' . $this->tabletitle . '" - ' . JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');
        } else
            JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');

        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoffields&tableid=' . $this->tableid);
        JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

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

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument()
    {
        if (!isset($this->document)) {
            $this->document = Factory::getDocument();
        }
        $this->document->setTitle(JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'));
    }

    /**
     * Returns an array of fields the table can be sorted by
     *
     * @return  array  Array containing the field name to sort by as the key and display text as value
     */

    protected function getSortFields()
    {
        //Joomla 3 only
        return array(
            'a.ordering' => JText::_('JGRID_HEADING_ORDERING'),
            'a.published' => JText::_('JSTATUS'),
            'a.fieldname' => JText::_('COM_CUSTOMTABLES_FIELDS_FIELDNAME_LABEL'),
            'a.type' => JText::_('COM_CUSTOMTABLES_FIELDS_TYPE_LABEL'),
            'a.id' => JText::_('JGRID_HEADING_ID')
        );
    }

    protected function getTheTypeSelections()
    {
        // Get a db connection.
        $db = Factory::getDbo();

        // Create a new query object.
        $query = $db->getQuery(true);

        // Select the text.
        $query->select($db->quoteName('type'));
        $query->from($db->quoteName('#__customtables_fields'));
        $query->order($db->quoteName('type') . ' ASC');

        // Reset the query using our newly populated query object.
        $db->setQuery($query);

        $results = $db->loadColumn();

        if ($results) {
            // get model
            $model = $this->getModel();
            $results = array_unique($results);
            $_filter = array();
            foreach ($results as $type) {
                // Translate the type selection
                $text = $model->selectionTranslation($type, 'type');
                // Now add the type and its text to the options array
                $_filter[] = JHtml::_('select.option', $type, JText::_($text));
            }
            return $_filter;
        }
        return false;
    }
}
