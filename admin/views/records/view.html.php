<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage views/records/view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Inputbox;
use CustomTables\Layouts;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Records View class
 */
require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'edititem.php');

class CustomtablesViewRecords extends HtmlView
{
    var CT $ct;
    var int $tableId;
    var string $pageLayout;
    var ?array $row;
    var $canDo;
    var bool $canCreate;
    var bool $canEdit;
    var int $refId;
    var string $referral;
    var string $formLink;
    var $document;

    public function display($tpl = null)
    {
        $this->tableId = common::inputGetInt('tableid', 0);
        $listing_id = common::inputGetCmd('id');

        $paramsArray = array();
        $paramsArray['tableid'] = $this->tableId;
        $paramsArray['publishstatus'] = 1;
        $paramsArray['listingid'] = $listing_id;

        // Assuming $paramsArray is your array of parameters
        $this->ct = new CT;
        $this->ct->setParams($paramsArray);
        $this->ct->getTable($this->tableId);
        $this->row = $this->ct->Table->loadRecord($listing_id);

        $key = common::inputGetCmd('key');
        if ($key != '')
            Inputbox::renderTableJoinSelectorJSON($this->ct, $key);
        else
            $this->renderForm($tpl);
    }

    protected function renderForm($tpl): bool
    {
        $Layouts = new Layouts($this->ct);
        $this->ct->LayoutVariables['layout_type'] = 2;
        $this->pageLayout = $Layouts->createDefaultLayout_Edit($this->ct->Table->fields, false);

        // get action permissions
        $this->canDo = ContentHelper::getActions('com_customtables', 'tables');
        $this->canCreate = $this->canDo->get('tables.edit');
        $this->canEdit = $this->canDo->get('tables.edit');

        // get input
        $this->ref = common::inputGet('ref', 0, 'word');
        $this->refId = common::inputGet('refid', 0, 'int');
        $this->referral = '';
        if ($this->refId) {
            // return to the item that referred to this item
            $this->referral = '&ref=' . $this->ref . '&refid=' . (int)$this->refId;
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

        // Display the template
        $this->formLink = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&amp;view=records&amp;layout=edit&amp;tableid=' . $this->tableId . '&id=' . $this->ct->Params->listing_id;

        // Set the document
        $this->document = Factory::getDocument();
        $this->setDocument($this->document);

        parent::display($tpl);
        return true;
    }

    protected function addToolBar()
    {
        common::inputSet('hidemainmenu', true);
        $isNew = $this->ct->Params->listing_id == 0;

        ToolbarHelper::title(common::translate($isNew ? 'COM_CUSTOMTABLES_RECORDS_NEW' : 'COM_CUSTOMTABLES_RECORDS_EDIT'), 'pencil-2 article-add');

        if ($isNew) {
            // For new records, check the create permission.
            if ($this->canCreate) {
                ToolbarHelper::apply('records.apply', 'JTOOLBAR_APPLY');
                ToolbarHelper::save('records.save', 'JTOOLBAR_SAVE');
                ToolbarHelper::custom('records.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
            }
            ToolbarHelper::cancel('records.cancel', 'JTOOLBAR_CANCEL');
        } else {
            if ($this->canEdit) {
                // We can save the new record
                ToolbarHelper::apply('records.apply', 'JTOOLBAR_APPLY');
                ToolbarHelper::save('records.save', 'JTOOLBAR_SAVE');
                // We can save this record, but check the create permission to see
                // if we can return to make a new one.

                if ($this->canCreate) {
                    ToolbarHelper::custom('records.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                }

            }
            if ($this->canCreate) {
                ToolbarHelper::custom('records.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
            }
            ToolbarHelper::cancel('records.cancel', 'JTOOLBAR_CLOSE');
        }
        ToolbarHelper::divider();
    }

    public function setDocument(Joomla\CMS\Document\Document $document): void
    {
        if (isset($this->ct) and $this->ct !== null) {
            $isNew = $this->ct->Params->listing_id == 0;
            $document->setTitle(common::translate($isNew ? 'COM_CUSTOMTABLES_RECORDS_NEW' : 'COM_CUSTOMTABLES_RECORDS_EDIT'));
        }
    }
}
