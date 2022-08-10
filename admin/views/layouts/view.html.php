<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}


use CustomTables\CT;
use CustomTables\Tables;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Layouts View class
 */
class CustomtablesViewLayouts extends JViewLegacy
{
    /**
     * display method of View
     * @return void
     */

    var CT $ct;
    var $allTables;

    public function display($tpl = null)
    {
        $model = $this->getModel();
        $this->ct = $model->ct;

        // Assign the variables
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->script = $this->get('Script');
        $this->state = $this->get('State');
        // get action permissions

        $this->canDo = ContentHelper::getActions('com_customtables', 'tables', $this->item->id);
        $this->canCreate = $this->canDo->get('layouts.create');
        $this->canEdit = $this->canDo->get('layouts.edit');

        // get input

        $jinput = Factory::getApplication()->input;
        $this->ref = $jinput->get('ref', 0, 'word');
        $this->refid = $jinput->get('refid', 0, 'int');
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

        $this->active_tab = 'general';
        if ($this->item->layoutcode != '')
            $this->active_tab = 'layoutcode-tab';
        elseif ($this->item->layoutmobile != '')
            $this->active_tab = 'layoutmobile-tab';
        elseif ($this->item->layoutcss != '')
            $this->active_tab = 'layoutcss-tab';
        elseif ($this->item->layoutjs != '')
            $this->active_tab = 'layoutjs-tab';

        $this->allTables = Tables::getAllTables();

        // Display the template
        if ($this->ct->Env->version < 4)
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

        JToolbarHelper::title(Text::_($isNew ? 'COM_CUSTOMTABLES_LAYOUTS_NEW' : 'COM_CUSTOMTABLES_LAYOUTS_EDIT'), 'pencil-2 article-add');
        // Built the actions for new and existing records.
        /*
        if ($this->refid || $this->ref)
        {
            if ($this->canCreate && $isNew)
            {
                // We can create the record.
                JToolBarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
            }
            elseif ($this->canEdit)
            {
                // We can save the record.
                JToolBarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
            }
            if ($isNew)
            {
                // Do not creat but cancel.
                JToolBarHelper::cancel('layouts.cancel', 'JTOOLBAR_CANCEL');
            }
            else
            {
                // We can close it.
                JToolBarHelper::cancel('layouts.cancel', 'JTOOLBAR_CLOSE');
            }
        }
        else
        {
            */
        if ($isNew) {
            // For new records, check the create permission.
            if ($this->canCreate) {
                JToolBarHelper::apply('layouts.apply', 'JTOOLBAR_APPLY');
                JToolBarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
                JToolBarHelper::custom('layouts.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
            };

            JToolbarHelper::custom('layoutwizard', 'wizzardbutton', 'wizzardbutton', 'COM_CUSTOMTABLES_BUTTON_LAYOUTAUTOCREATE', false);//Layout Wizard
            JToolbarHelper::custom('addfieldtag', 'fieldtagbutton', 'fieldtagbutton', 'COM_CUSTOMTABLES_BUTTON_ADDFIELDTAG', false);
            JToolbarHelper::custom('addlayouttag', 'layouttagbutton', 'layouttagutton', 'COM_CUSTOMTABLES_BUTTON_ADDLAYOUTTAG', false);

            JToolBarHelper::cancel('layouts.cancel', 'JTOOLBAR_CANCEL');
        } else {
            if ($this->canEdit) {
                // We can save the new record
                JToolBarHelper::apply('layouts.apply', 'JTOOLBAR_APPLY');
                JToolBarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
                // We can save this record, but check the create permission to see
                // if we can return to make a new one.
                if ($this->canCreate) {
                    JToolBarHelper::custom('layouts.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                }
            }
            if ($this->canCreate) {
                JToolBarHelper::custom('layouts.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
            }

            JToolbarHelper::custom('layoutwizard', 'wizzardbutton', 'wizzardbutton', 'COM_CUSTOMTABLES_BUTTON_LAYOUTAUTOCREATE', false);//Layout Wizard
            JToolbarHelper::custom('addfieldtag', 'fieldtagbutton', 'fieldtagbutton', 'COM_CUSTOMTABLES_BUTTON_ADDFIELDTAG', false);
            JToolbarHelper::custom('addlayouttag', 'layouttagbutton', 'layouttagutton', 'COM_CUSTOMTABLES_BUTTON_ADDLAYOUTTAG', false);
            JToolbarHelper::custom('dependencies', 'dependencies', 'dependencies', 'COM_CUSTOMTABLES_BUTTON_DEPENDENCIES', false);

            JToolBarHelper::cancel('layouts.cancel', 'JTOOLBAR_CLOSE');
        }
        //}
        JToolbarHelper::divider();
        // set help url for this view if found
        //$help_url = CustomtablesHelper::getHelpUrl('layouts');
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
        if (!isset($this->document))
            $this->document = Factory::getDocument();

        $this->document->setTitle(Text::_($isNew ? 'COM_CUSTOMTABLES_LAYOUTS_NEW' : 'COM_CUSTOMTABLES_LAYOUTS_EDIT'));

        $this->document->addScript(JURI::root(true) . "/administrator/components/com_customtables/views/layouts/submitbutton.js");

        JText::script('view not acceptable. Error');
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

    public function renderTextArea($value, $id, $typeboxid, &$onPageLoads)
    {
        $result = '
			
						<div style="width: 100%;position: relative;">
						';

        if ($value != "")
            $result .= '				<div class="ct_tip">TIP: Double Click on a Layout Tag to edit parameters.</div>';
        $result .= '	</div>
						';

        $textareaid = 'jform_' . $id;
        $textareacode = '<textarea name="jform[' . $id . ']" id="' . $textareaid . '" filter="raw" style="width:100%" rows="30">' . $value . '</textarea>';

        $textareatabid = $id . '-tab';

        $result .= renderEditor($textareacode, $textareaid, $typeboxid, $textareatabid, $onPageLoads);
        $result .= '
		';

        return $result;
    }

    protected function getMenuItems()
    {
        if (!isset($this->row) or !is_array($this->row) or count($this->row) == 0)
            return '';

        $result = '';

        $wheretosearch = array();
        $whattolookfor = array();

        switch ($this->row->layouttype) {
            case 1:
                $wheretosearch[] = 'escataloglayout';
                $whattolookfor[] = $this->row->layoutname;
                break;

            case 2:
                $wheretosearch[] = 'eseditlayout';
                $whattolookfor[] = $this->row->layoutname;
                $wheretosearch[] = 'editlayout';
                $whattolookfor[] = 'layout:' . $this->row->layoutname;
                break;

            case 4:
                $wheretosearch[] = 'esdetailslayout';
                $whattolookfor[] = $this->row->layoutname;
                break;

            case 5:
                $wheretosearch[] = 'escataloglayout';
                $whattolookfor[] = $this->row->layoutname;
                break;

            case 6:
                $wheretosearch[] = 'esitemlayout';
                $whattolookfor[] = $this->row->layoutname;
                break;

            case 7:
                $wheretosearch[] = 'onrecordaddsendemaillayout';
                $whattolookfor[] = $this->row->layoutname;
                break;
        }

        $where = array();
        $i = 0;
        foreach ($wheretosearch as $w) {
            $where[] = 'INSTR(params,\'"' . $w . '":"' . $whattolookfor[$i] . '"\')';
            $i++;
        }

        if (count($where) > 0) {

            $db = Factory::getDBO();
            $query = 'SELECT id,title FROM #__menu WHERE ' . implode(' OR ', $where);

            $db->setQuery($query);

            $recs = $db->loadAssocList();

            if (count($recs) > 0) {
                $result = '<hr/><p>List of Menu Items that use this layout:</p><ul>';
                foreach ($recs as $r) {
                    $link = '/administrator/index.php?option=com_menus&view=item&layout=edit&id=' . $r['id'];
                    $result .= '<li><a href="' . $link . '" target="_blank">' . $r['title'] . '</a></li>';
                }
                $result .= '</ul>';
            }
        }

        return $result;
    }

    protected function getLayouts()
    {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id,layoutname,tableid,layouttype');
        $query->from('#__customtables_layouts');
        $query->order('layoutname');
        $query->where('published=1');
        $db->setQuery((string)$query);
        return $db->loadObjectList();
    }
}