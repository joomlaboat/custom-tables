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
\defined('_JEXEC') or die;

use CustomTables\CT;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

use CustomTables\Fields;

class CustomtablesViewListoflayouts extends JViewLegacy
{
    var CT $ct;

    function display($tpl = null)
    {
        $this->ct = new CT;

        if ($this->getLayout() !== 'modal') {
            // Include helper submenu
            CustomtablesHelper::addSubmenu('listoflayouts');
        }

        // Assign data to the view
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->user = Factory::getUser();
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->listOrder = $this->escape($this->state->get('list.ordering'));
        $this->listDirn = $this->escape($this->state->get('list.direction')) ?? '';

        // get global action permissions
        $this->canDo = ContentHelper::getActions('com_customtables', 'listoflayouts');
        $this->canCreate = $this->canDo->get('layouts.create');
        $this->canEdit = $this->canDo->get('layouts.edit');
        $this->canState = $this->canDo->get('layouts.edit.state');
        $this->canDelete = $this->canDo->get('layouts.delete');
        $this->isEmptyState = count($this->items ?? 0) == 0;

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {
            if ($this->ct->Env->version < 4) {
                $this->addToolbar_3();
                $this->sidebar = JHtmlSidebar::render();
            } else
                $this->addToolbar_4();

            // load the batch html
            //if ($this->canCreate && $this->canEdit && $this->canState)
            //{
            //$this->batchDisplay = JHtmlBatch_::render();
            //}
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
        JToolBarHelper::title(Text::_('COM_CUSTOMTABLES_LISTOFLAYOUTS'), 'joomla');

        if ($this->canCreate) {
            JToolBarHelper::addNew('layouts.add');
        }

        // Only load if there are items
        if (CustomtablesHelper::checkArray($this->items)) {
            if ($this->canEdit) {
                JToolBarHelper::editList('layouts.edit');
            }

            if ($this->canState) {
                JToolBarHelper::publishList('listoflayouts.publish');
                JToolBarHelper::unpublishList('listoflayouts.unpublish');
            }

            if ($this->canDo->get('core.admin')) {
                JToolBarHelper::checkin('listoflayouts.checkin');
            }

            if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete)) {
                JToolbarHelper::deleteList('', 'listoflayouts.delete', 'JTOOLBAR_EMPTY_TRASH');
            } elseif ($this->canState && $this->canDelete) {
                JToolbarHelper::trash('listoflayouts.trash');
            }
        }

        if ($this->canState) {
            /*
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
        /*
                $CTLayoutType = JFormHelper::loadFieldType('CTLayoutType', false);
                $CTLayoutTypeOptions = $CTLayoutType->getOptions(); // works only if you set your field getOptions on public!!

                JHtmlSidebar::addFilter(
                    Text::_('COM_CUSTOMTABLES_LAYOUTS_LAYOUTTYPE_SELECT'),
                    'filter_layouttype',
                    JHtml::_('select.options', $CTLayoutTypeOptions, 'value', 'text', $this->state->get('filter.layouttype'))
                );
        */
        // Set Tableid Selection

        /*
        $CTTable = JFormHelper::loadFieldType('CTTable', false);
        $CTTableOptions = $CTTable->getOptions(false); // works only if you set your field getOptions on public!!

        JHtmlSidebar::addFilter(
            Text::_('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT'),
            'filter_tableid',
            JHtml::_('select.options', $CTTableOptions, 'value', 'text', $this->state->get('filter.tableid'))
        );

        */

        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoflayouts');
    }

    protected function addToolbar_4()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_CUSTOMTABLES_LISTOFLAYOUTS'), 'joomla');

        if ($this->canCreate)
            $toolbar->addNew('layouts.add');

        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);

        $childBar = $dropdown->getChildToolbar();

        if ($this->canState) {
            $childBar->publish('listoflayouts.publish')->listCheck(true);
            $childBar->unpublish('listoflayouts.unpublish')->listCheck(true);
        }

        if ($this->canDo->get('core.admin')) {
            $childBar->checkin('listoflayouts.checkin');
        }

        if (($this->canState && $this->canDelete)) {
            if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED) {
                $childBar->trash('listoflayouts.trash')->listCheck(true);
            }

            if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete) {
                $toolbar->delete('listoflayouts.delete')
                    ->text('JTOOLBAR_EMPTY_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE')
                    ->listCheck(true);
            }
        }
    }

    function isTwig(&$row)
    {
        $original_ct_tags_q = ['currenturl', 'currentuserid', 'currentusertype', 'date', 'gobackbutton', 'description', 'format', 'Itemid', 'returnto',
            'server', 'tabledescription', 'tabletitle', 'table', 'today', 'user', 'websiteroot', 'layout', 'if', 'headtag', 'metakeywords', 'metadescription',
            'pagetitle', 'php', 'php_a', 'php_b', 'php_c', 'catalogtable', 'catalog', 'recordlist', 'page', 'add', 'count', 'navigation', 'batchtoolbar',
            'checkbox', 'pagination', 'print', 'recordcount', 'search', 'searchbutton', 'button', 'buttons', 'captcha', 'id', 'published', 'link', 'linknoreturn',
            'number', 'toolbar', 'cart', 'createuser', 'resolve', '_value', 'sqljoin', 'attachment'];

        $original_ct_tags_s = ['_if', '_endif', '_value', '_edit'];

        $twig_tags = ['fields.list', 'fields.count', 'fields.json', 'user.name', 'user.username', 'user.email', 'user.id', 'user.lastvisitdate', 'user.registerdate',
            'user.usergroups', 'url.link', 'url.format', 'url.base64', 'url.root', 'url.getint', 'url.getstring', 'url.getuint', 'url.getfloat', 'url.getword',
            'url.getalnum', 'url.getcmd', 'url.getstringandencode', 'url.getstringanddecode', 'url.itemid', 'url.set', 'url.server', 'html.add', 'html.batch',
            'html.button', 'html.captcha', 'html.goback', 'html.importcsv', 'html.tablehead', 'html.limit', 'html.message', 'html.navigation', 'html.orderby',
            'html.pagination', 'html.print', 'html.recordcount', 'html.recordlist', 'html.search', 'html.searchbutton', 'html.toolbar', 'html.base64encode',
            'document.setmetakeywords', 'document.setmetadescription', 'document.setpagetitle', 'document.setheadtag', 'document.layout', 'document.sitename',
            'document.languagepostfi', 'record.advancedjoin', 'record.joincount', 'record.joinavg', 'record.joinmin', 'record.joinmax', 'record.joinvalue',
            'record.jointable', 'record.id', 'record.number', 'record.published', 'table.id', 'table.name', 'table.title', 'table.description', 'table.records',
            'table.fields', 'tables.getvalue', 'tables.getrecor', 'tables.getrecords'];

        $fields = Fields::getFields($row->tableid);

        // ------------------------ CT Original
        $original_ct_matches = 0;

        foreach ($original_ct_tags_s as $tag) {
            if (str_contains($row->layoutcode, '[' . $tag . ':'))
                $original_ct_matches += 1;

            if (str_contains($row->layoutcode, '[' . $tag . ']'))
                $original_ct_matches += 1;
        }

        foreach ($original_ct_tags_q as $tag) {
            if (str_contains($row->layoutcode, '{' . $tag . ':'))
                $original_ct_matches += 1;

            if (strpos($row->layoutcode, '{' . $tag . '}') !== false)
                $original_ct_matches += 1;
        }

        foreach ($fields as $field) {
            $fieldName = $field['fieldname'];

            if (str_contains($row->layoutcode, '*' . $fieldName . '*'))
                $original_ct_matches += 1;

            if (str_contains($row->layoutcode, '|' . $fieldName . '|'))
                $original_ct_matches += 1;

            if (str_contains($row->layoutcode, '[' . $fieldName . ':'))
                $original_ct_matches += 1;

            if (str_contains($row->layoutcode, '[' . $fieldName . ']'))
                $original_ct_matches += 1;
        }

        // ------------------------ Twig
        $twig_matches = 0;

        foreach ($twig_tags as $tag) {
            if (str_contains($row->layoutcode, '{{ ' . $tag . '('))
                $twig_matches += 1;

            if (str_contains($row->layoutcode, '{{ ' . $tag . ' }}'))
                $twig_matches += 1;
        }

        foreach ($fields as $field) {
            $fieldName = $field['fieldname'];

            if (str_contains($row->layoutcode, '{{ ' . $fieldName . '('))
                $twig_matches += 1;

            if (str_contains($row->layoutcode, '{{ ' . $fieldName . ' }}'))
                $twig_matches += 1;

            if (str_contains($row->layoutcode, '{{ ' . $fieldName . '.'))
                $twig_matches += 1;
        }

        return ['original' => $original_ct_matches, 'twig' => $twig_matches];
    }

}
