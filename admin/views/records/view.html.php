<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage views/records/view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Layouts;
use CustomTables\ProInputBoxTableJoin;
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

	public function display($tpl = null)
	{
		$key = common::inputGetCmd('key');
		if ($key != '') {

			$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;

			if (file_exists($path . 'tablejoin.php') and file_exists($path . 'tablejoinlist.php')) {
				require_once($path . 'tablejoin.php');
				require_once($path . 'tablejoinlist.php');

				$this->ct = new CT([], true);
				ProInputBoxTableJoin::renderTableJoinSelectorJSON($this->ct, $key);//Inputbox
			}
		} else {
			$tableId = common::inputGetInt('tableid');
			if ($tableId === null) {
				$Itemid = common::inputGetInt('Itemid');

				if ($Itemid === null) {
					Factory::getApplication()->enqueueMessage('Table not selected..', 'error');
					return;
				}

			} else {
				$this->tableId = $tableId;
			}

			// Assuming $paramsArray is your array of parameters
			$this->ct = new CT([], true);
			$this->ct->Params->setParams([
				'tableid' => $this->tableId,
				'publishstatus' => 1,//for new records
				'listingid' => common::inputGetCmd('id')
			]);
			$this->ct->getTable($this->tableId);

			if (!empty($this->ct->Params->listing_id))
				$this->ct->getRecord();

			$this->row = $this->ct->Table->record;
			$this->renderForm($tpl);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function renderForm($tpl): bool
	{
		$Layouts = new Layouts($this->ct);
		$this->ct->LayoutVariables['layout_type'] = CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM;
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
		$this->setMyDocument();

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

	public function setMyDocument(): void
	{
		if (isset($this->ct) and $this->ct !== null) {
			$isNew = $this->ct->Params->listing_id == 0;
			$document = Factory::getApplication()->getDocument();
			$document->setTitle(common::translate($isNew ? 'COM_CUSTOMTABLES_RECORDS_NEW' : 'COM_CUSTOMTABLES_RECORDS_EDIT'));
		}
	}
}
