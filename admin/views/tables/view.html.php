<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import Joomla view library
//jimport('joomla.application.component.view');

use CustomTables\CT;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Tables View class
 */
class CustomtablesViewTables extends JViewLegacy
{
    var CT $ct;

    /**
     * display method of View
     * @return void
     */
    public function display($tpl = null)
    {
        $version = new Version;
        $this->version = (int)$version->getShortVersion();

        $model = $this->getModel();
        $this->ct = $model->ct;

        // Assign the variables
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->script = $this->get('Script');
        $this->state = $this->get('State');
        // get action permissions
        $this->canDo = ContentHelper::getActions('com_customtables', 'tables', $this->item->id);
        $this->canCreate = $this->canDo->get('tables.create');
        $this->canEdit = $this->canDo->get('tables.edit');
        //$this->canState = $this->canDo->get('tables.edit.state');
        //$this->canDelete = $this->canDo->get('tables.delete');

        // get input

        $this->ref = Factory::getApplication()->input->get('ref', 0, 'word');
        $this->refid = Factory::getApplication()->input->get('refid', 0, 'int');
        $this->referral = '';
        if ($this->refid) {
            // return to the item that refered to this item
            $this->referral = '&ref=' . (string)$this->ref . '&refid=' . (int)$this->refid;
        } elseif ($this->ref) {
            // return to the list view that refered to this item
            $this->referral = '&ref=' . (string)$this->ref;
        }

        // Set the toolbar
        $this->addToolBar();

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        // Display the template
        if ($this->version < 4)
            parent::display($tpl);
        else
            parent::display('quatro');

        // Set the document
        $this->setDocument();
    }


    /**
     * Setting the toolbar
     */
    protected function addToolBar()
    {
        Factory::getApplication()->input->set('hidemainmenu', true);
        $isNew = $this->item->id == 0;

        JToolbarHelper::title(Text::_($isNew ? 'COM_CUSTOMTABLES_TABLES_NEW' : 'COM_CUSTOMTABLES_TABLES_EDIT'), 'pencil-2 article-add');
        // Built the actions for new and existing records.
        /*
        if ($this->refid || $this->ref)
        {
            if ($this->canDo->get('core.create') && $isNew)
            {
                // We can create the record.
                JToolBarHelper::save('tables.save', 'JTOOLBAR_SAVE');
            }
            elseif ($this->canDo->get('core.edit'))
            {
                // We can save the record.
                JToolBarHelper::save('tables.save', 'JTOOLBAR_SAVE');
            }
            if ($isNew)
            {
                // Do not creat but cancel.
                JToolBarHelper::cancel('tables.cancel', 'JTOOLBAR_CANCEL');
            }
            else
            {
                // We can close it.
                JToolBarHelper::cancel('tables.cancel', 'JTOOLBAR_CLOSE');
            }
        }
        else
        {
            */
        if ($isNew) {
            // For new records, check the create permission.
            if ($this->canCreate) {
                JToolBarHelper::apply('tables.apply', 'JTOOLBAR_APPLY');
                JToolBarHelper::save('tables.save', 'JTOOLBAR_SAVE');
                JToolBarHelper::custom('tables.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
            };
            JToolBarHelper::cancel('tables.cancel', 'JTOOLBAR_CANCEL');
        } else {
            if ($this->canEdit) {
                // We can save the new record
                JToolBarHelper::apply('tables.apply', 'JTOOLBAR_APPLY');
                JToolBarHelper::save('tables.save', 'JTOOLBAR_SAVE');
                // We can save this record, but check the create permission to see
                // if we can return to make a new one.
                if ($this->canCreate) {
                    JToolBarHelper::custom('tables.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                }
            }
            if ($this->canCreate) {
                JToolBarHelper::custom('tables.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
            }
            JToolBarHelper::cancel('tables.cancel', 'JTOOLBAR_CLOSE');
        }
        //}
        JToolbarHelper::divider();
        // set help url for this view if found
        //$help_url = CustomtablesHelper::getHelpUrl('tables');
        //if (CustomtablesHelper::checkString($help_url))
        //{
        //JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
        //}
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument()
    {
        $isNew = ($this->item->id < 1);
        if (!isset($this->document)) {
            $this->document = Factory::getDocument();
        }
        $this->document->setTitle(Text::_($isNew ? 'COM_CUSTOMTABLES_TABLES_NEW' : 'COM_CUSTOMTABLES_TABLES_EDIT'));
        $this->document->addCustomTag('<script src="' . JURI::root(true) . '/administrator/components/com_customtables/views/tables/submitbutton.js"></script>');
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
        if (strlen($var) > 30) {
            // use the helper htmlEscape method instead and shorten the string
            return CustomtablesHelper::htmlEscape($var, $this->_charset, true, 30);
        }
        // use the helper htmlEscape method instead.
        return CustomtablesHelper::htmlEscape($var, $this->_charset);
    }
}
