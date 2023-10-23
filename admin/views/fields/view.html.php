<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @subpackage views/fields/view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Tables;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Fields View class
 */
class CustomtablesViewFields extends JViewLegacy
{
    /**
     * display method of View
     * @return void
     */
    var CT $ct;
    var $tableid;
    var $table_row;
    var $allTables;
    var $docuemnt;
    var $item;

    public function display($tpl = null)
    {
        $version = new Version;
        $this->version = (int)$version->getShortVersion();

        $model = $this->getModel();
        $this->ct = $model->ct;

        // Assign the variables
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if ((int)$this->item->id == 0)
            $this->tableid = common::inputGetInt('tableid', 0);
        else
            $this->tableid = $this->item->tableid;

        $this->table_row = ESTables::getTableRowByID($this->tableid);

        $this->script = $this->get('Script');
        $this->state = $this->get('State');

        // get action permissions
        $this->canDo = ContentHelper::getActions('com_customtables', 'tables', $this->item->id);

        $this->canCreate = $this->canDo->get('tables.edit');
        $this->canEdit = $this->canDo->get('tables.edit');

        // get input
        $this->ref = common::inputGet('ref', 0, 'word');
        $this->refid = common::inputGet('refid', 0, 'int');
        $this->referral = '';
        if ($this->refid) {
            // return to the item that referred to this item
            $this->referral = '&ref=' . $this->ref . '&refid=' . (int)$this->refid;
        } elseif ($this->ref) {
            // return to the list view that referred to this item
            $this->referral = '&ref=' . $this->ref;
        }

        // Set the toolbar
        $this->addToolBar();

        $this->extrataskOptions = ['updateimages', 'updatefiles', 'updateimagegallery', 'updatefilebox'];

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        $this->allTables = Tables::getAllTables();

        // Set the document
        $this->document = Factory::getDocument();
        $this->setDocument($this->document);

        // Display the template
        if ($this->version < 4)
            parent::display($tpl);
        else
            parent::display('quatro');
    }

    /**
     * Setting the toolbar
     */
    protected function addToolBar()
    {
        common::inputSet('hidemainmenu', true);
        $isNew = $this->item->id == 0;

        JToolbarHelper::title(Text::_($isNew ? 'COM_CUSTOMTABLES_FIELDS_NEW' : 'COM_CUSTOMTABLES_FIELDS_EDIT'), 'pencil-2 article-add');
        // Built the actions for new and existing records.
        if ($this->refid || $this->ref) {
            if ($this->canCreate && $isNew) {
                // We can create the record.
                JToolBarHelper::save('fields.save', 'JTOOLBAR_SAVE');
            } elseif ($this->canEdit) {
                // We can save the record.
                JToolBarHelper::save('fields.save', 'JTOOLBAR_SAVE');
            }
            if ($isNew) {
                // Do not creat but cancel.
                JToolBarHelper::cancel('fields.cancel', 'JTOOLBAR_CANCEL');
            } else {
                // We can close it.
                JToolBarHelper::cancel('fields.cancel', 'JTOOLBAR_CLOSE');
            }
        } else {
            if ($isNew) {
                // For new records, check the create permission.
                if ($this->canCreate) {
                    JToolBarHelper::apply('fields.apply', 'JTOOLBAR_APPLY');
                    JToolBarHelper::save('fields.save', 'JTOOLBAR_SAVE');
                    JToolBarHelper::custom('fields.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                };
                JToolBarHelper::cancel('fields.cancel', 'JTOOLBAR_CANCEL');
            } else {
                if ($this->canEdit) {
                    // We can save the new record
                    JToolBarHelper::apply('fields.apply', 'JTOOLBAR_APPLY');
                    JToolBarHelper::save('fields.save', 'JTOOLBAR_SAVE');
                    // We can save this record, but check the create permission to see
                    // if we can return to make a new one.
                    if ($this->canCreate) {
                        JToolBarHelper::custom('fields.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                    }
                }
                if ($this->canCreate) {
                    JToolBarHelper::custom('fields.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
                }
                JToolBarHelper::cancel('fields.cancel', 'JTOOLBAR_CLOSE');
            }
        }
        JToolbarHelper::divider();
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    public function setDocument(Joomla\CMS\Document\Document $document): void
    {
        if ($this->item !== null) {
            $isNew = ($this->item->id < 1);
            $document->setTitle(Text::_($isNew ? 'COM_CUSTOMTABLES_FIELDS_NEW' : 'COM_CUSTOMTABLES_FIELDS_EDIT'));
            $document->addCustomTag('<script src="' . JURI::root(true) . '/administrator/components/com_customtables/views/fields/submitbutton.js"></script>');
            JText::script('view not acceptable. Error');
        }
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
