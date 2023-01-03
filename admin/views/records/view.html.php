<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage views/records/view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Inputbox;
use CustomTables\Layouts;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;

/**
 * Records View class
 */
require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'edititem.php');

class CustomtablesViewRecords extends JViewLegacy
{
    var CT $ct;
    var int $tableid;
    var string $pageLayout;
    var ?array $row;
    var $state;
    var $canDo;
    var $canCreate;
    var $canEdit;
    var int $refid;
    var string $referral;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();

        $this->tableid = $app->input->getint('tableid', 0);
        $listing_id = $app->input->getCmd('id');

        $paramsArray = array();
        $paramsArray['tableid'] = $this->tableid;
        $paramsArray['publishstatus'] = 1;
        $paramsArray['listingid'] = $listing_id;

        $params = new JRegistry;
        $params->loadArray($paramsArray);

        $this->ct = new CT;
        $this->ct->setParams($params, true);

        $key = $this->ct->Env->jinput->getCmd('key');
        if ($key != '') {
            Inputbox::renderTableJoinSelectorJSON($this->ct, $key);
        } else
            $this->renderForm($tpl, $params);
    }

    protected function renderForm($tpl, $params): bool
    {
        $Model = JModelLegacy::getInstance('EditItem', 'CustomTablesModel', $params);
        $Model->load($this->ct);

        $Layouts = new Layouts($this->ct);
        $this->ct->LayoutVariables['layout_type'] = 2;
        $this->pageLayout = $Layouts->createDefaultLayout_Edit($this->ct->Table->fields, false);

        $this->row = $Model->row;

        $this->state = $this->get('State');
        // get action permissions

        $this->canDo = ContentHelper::getActions('com_customtables', 'tables');
        $this->canCreate = $this->canDo->get('tables.edit');
        $this->canEdit = $this->canDo->get('tables.edit');

        // get input
        $this->ref = Factory::getApplication()->input->get('ref', 0, 'word');
        $this->refid = Factory::getApplication()->input->get('refid', 0, 'int');
        $this->referral = '';
        if ($this->refid) {
            // return to the item that referred to this item
            $this->referral = '&ref=' . (string)$this->ref . '&refid=' . (int)$this->refid;
        } elseif ($this->ref) {
            // return to the list view that referred to this item
            $this->referral = '&ref=' . (string)$this->ref;
        }

        // Set the toolbar
        $this->addToolBar();

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        // Display the template
        $this->formLink = JURI::root(false) . 'administrator/index.php?option=com_customtables&amp;view=records&amp;layout=edit&amp;tableid=' . $this->tableid . '&id=' . $this->ct->Params->listing_id;

        parent::display($tpl);

        // Set the document
        $this->setDocument();

        return true;
    }

    protected function addToolBar()
    {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $isNew = $this->ct->Params->listing_id == 0;

        JToolbarHelper::title(Text::_($isNew ? 'COM_CUSTOMTABLES_RECORDS_NEW' : 'COM_CUSTOMTABLES_RECORDS_EDIT'), 'pencil-2 article-add');

        if ($isNew) {
            // For new records, check the create permission.
            if ($this->canCreate) {
                JToolBarHelper::apply('records.apply', 'JTOOLBAR_APPLY');
                JToolBarHelper::save('records.save', 'JTOOLBAR_SAVE');
                JToolBarHelper::custom('records.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
            }
            JToolBarHelper::cancel('records.cancel', 'JTOOLBAR_CANCEL');
        } else {
            if ($this->canEdit) {
                // We can save the new record
                JToolBarHelper::apply('records.apply', 'JTOOLBAR_APPLY');
                JToolBarHelper::save('records.save', 'JTOOLBAR_SAVE');
                // We can save this record, but check the create permission to see
                // if we can return to make a new one.

                if ($this->canCreate) {
                    JToolBarHelper::custom('records.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                }

            }
            if ($this->canCreate) {
                JToolBarHelper::custom('records.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
            }
            JToolBarHelper::cancel('records.cancel', 'JTOOLBAR_CLOSE');
        }
        JToolbarHelper::divider();
    }

    protected function setDocument(): void
    {
        $isNew = $this->ct->Params->listing_id == 0;
        if (!isset($this->document)) {
            $this->document = Factory::getDocument();
        }
        $this->document->setTitle(Text::_($isNew ? 'COM_CUSTOMTABLES_RECORDS_NEW' : 'COM_CUSTOMTABLES_RECORDS_EDIT'));
    }
}
