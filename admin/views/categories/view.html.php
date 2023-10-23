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

// import Joomla view library
use CustomTables\common;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Categories View class
 */
class CustomtablesViewCategories extends JViewLegacy
{
    var $document;
    var $version;
    var $form;
    var $item;
    var $script;
    var $state;
    var $canDo;
    var $canCreate;
    var $canEdit;
    var $canState;
    var $canDelete;
    var $ref;
    var $refid;
    var $referral;

    public function display($tpl = null)
    {
        $version = new Version;
        $this->version = (int)$version->getShortVersion();

        // Assign the variables
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->script = $this->get('Script');
        $this->state = $this->get('State');
        // get action permissions

        $this->canDo = ContentHelper::getActions('com_customtables', 'categories', $this->item->id);
        $this->canCreate = $this->canDo->get('categories.create');
        $this->canEdit = $this->canDo->get('categories.edit');
        $this->canState = $this->canDo->get('categories.edit.state');
        $this->canDelete = $this->canDo->get('categories.delete');

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

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        // Set the document
        $this->document = Factory::getDocument();
        $this->setDocument($this->document);

        // Display the template
        parent::display($tpl);
    }

    /**
     * Setting the toolbar
     */
    protected function addToolBar()
    {
        common::inputSet('hidemainmenu', true);
        $isNew = $this->item->id == 0;

        JToolbarHelper::title(Text::_($isNew ? 'COM_CUSTOMTABLES_CATEGORIES_NEW' : 'COM_CUSTOMTABLES_CATEGORIES_EDIT'), 'pencil-2 article-add');
        // Built the actions for new and existing records.
        if ($this->refid || $this->ref) {
            if ($this->canCreate && $isNew) {
                // We can create the record.
                JToolBarHelper::save('categories.save', 'JTOOLBAR_SAVE');
            } elseif ($this->canEdit) {
                // We can save the record.
                JToolBarHelper::save('categories.save', 'JTOOLBAR_SAVE');
            }
            if ($isNew) {
                // Do not creat but cancel.
                JToolBarHelper::cancel('categories.cancel', 'JTOOLBAR_CANCEL');
            } else {
                // We can close it.
                JToolBarHelper::cancel('categories.cancel', 'JTOOLBAR_CLOSE');
            }
        } else {
            if ($isNew) {
                // For new records, check the create permission.
                if ($this->canCreate) {
                    JToolBarHelper::apply('categories.apply', 'JTOOLBAR_APPLY');
                    JToolBarHelper::save('categories.save', 'JTOOLBAR_SAVE');
                    JToolBarHelper::custom('categories.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                };
                JToolBarHelper::cancel('categories.cancel', 'JTOOLBAR_CANCEL');
            } else {
                if ($this->canEdit) {
                    // We can save the new record
                    JToolBarHelper::apply('categories.apply', 'JTOOLBAR_APPLY');
                    JToolBarHelper::save('categories.save', 'JTOOLBAR_SAVE');
                    // We can save this record, but check the create permission to see
                    // if we can return to make a new one.
                    if ($this->canCreate) {
                        JToolBarHelper::custom('categories.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                    }
                }
                if ($this->canCreate) {
                    JToolBarHelper::custom('categories.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
                }
                JToolBarHelper::cancel('categories.cancel', 'JTOOLBAR_CLOSE');
            }
        }
        JToolbarHelper::divider();
        // set help url for this view if found
        //$help_url = CustomtablesHelper::getHelpUrl('categories');
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
    public function setDocument(Joomla\CMS\Document\Document $document): void
    {
        if ($this->item !== null) {
            $isNew = ($this->item->id < 1);
            $document->setTitle(Text::_($isNew ? 'COM_CUSTOMTABLES_CATEGORIES_NEW' : 'COM_CUSTOMTABLES_CATEGORIES_EDIT'));

            if ($this->version < 4)
                $document->addCustomTag('<script src=' . JURI::root(true) . '/administrator/components/com_customtables/views/categories/submitbutton.js"></script>');

            //JText::script('view not acceptable. Error');
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
