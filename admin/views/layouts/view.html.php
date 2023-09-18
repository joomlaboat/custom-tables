<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
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
        $layoutId = $this->ct->Env->jinput->getInt('id', 0);

        // Assign the variables
        $this->form = $this->get('Form');

        if ($this->ct->db->serverType == 'postgresql')
            $query = 'SELECT id, tableid, layoutname, layoutcode, layoutmobile, layoutcss, layoutjs, '
                . 'CASE WHEN modified IS NULL THEN extract(epoch FROM created) '
                . 'ELSE extract(epoch FROM modified) AS ts, '
                . 'layouttype '
                . 'FROM #__customtables_layouts WHERE id=' . $layoutId . ' LIMIT 1';
        else
            $query = 'SELECT id, tableid, layoutname, layoutcode, layoutmobile, layoutcss, layoutjs, '
                . 'IF(modified IS NULL,UNIX_TIMESTAMP(created),UNIX_TIMESTAMP(modified)) AS ts, '
                . 'layouttype '
                . 'FROM #__customtables_layouts WHERE id=' . $layoutId . ' LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadObjectList();
        if (count($rows) == 1)
            $this->item = $rows[0];
        else {
            $emptyItem = ['id' => 0, 'tableid' => null, 'layoutname' => null, 'layoutcode' => null, 'layoutmobile' => null, 'layoutcss' => null, 'layoutjs' => null, 'ts' => 0];
            $this->item = (object)$emptyItem;
        }

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
        $this->document->addCustomTag('<script src="' . JURI::root(true) . '/administrator/components/com_customtables/views/layouts/submitbutton.js"></script>');

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

    public function renderTextArea($value, $id, $typeBoxId, &$onPageLoads)
    {
        $result = '<div style="width: 100%;position: relative;">';

        if ($value != "")
            $result .= '				<div class="ct_tip">TIP: Double-Click on a Layout Tag to edit parameters.</div>';
        $result .= '	</div>
';

        $textAreaId = 'jform_' . $id;
        $textAreaCode = '<textarea name="jform[' . $id . ']" id="' . $textAreaId . '" filter="raw" style="width:100%" rows="30">' . htmlspecialchars($value ?? '') . '</textarea>';
        $textAreaTabId = $id . '-tab';

        $result .= renderEditor($textAreaCode, $textAreaId, $typeBoxId, $textAreaTabId, $onPageLoads);
        $result .= '
';
        return $result;
    }

    protected function getMenuItems()
    {
        if (!isset($this->row) or !is_array($this->row) or count($this->row) == 0)
            return '';

        $result = '';

        $whereToSearch = array();
        $whatToLookFor = array();

        switch ($this->row->layouttype) {
            case 1:
                $whereToSearch[] = 'escataloglayout';
                $whatToLookFor[] = $this->row->layoutname;
                break;

            case 2:
                $whereToSearch[] = 'eseditlayout';
                $whatToLookFor[] = $this->row->layoutname;
                $whereToSearch[] = 'editlayout';
                $whatToLookFor[] = 'layout:' . $this->row->layoutname;
                break;

            case 4:
                $whereToSearch[] = 'esdetailslayout';
                $whatToLookFor[] = $this->row->layoutname;
                break;

            case 5:
                $whereToSearch[] = 'escataloglayout';
                $whatToLookFor[] = $this->row->layoutname;
                break;

            case 6:
                $whereToSearch[] = 'esitemlayout';
                $whatToLookFor[] = $this->row->layoutname;
                break;

            case 7:
                $whereToSearch[] = 'onrecordaddsendemaillayout';
                $whatToLookFor[] = $this->row->layoutname;
                break;
        }

        $where = array();
        $i = 0;
        foreach ($whereToSearch as $w) {
            $where[] = 'INSTR(params,\'"' . $w . '":"' . $whatToLookFor[$i] . '"\')';
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