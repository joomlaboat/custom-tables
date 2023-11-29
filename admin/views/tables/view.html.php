<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage view.html.php
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
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

/**
 * Tables View class
 */
class CustomtablesViewTables extends HtmlView//JViewLegacy
{
	var CT $ct;
	var $state;
	var $canDo;
	var $canCreate;
	var $canEdit;
	var $item;

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

		$this->ref = common::inputGet('ref', 0, 'word');
		$this->refid = common::inputGet('refid', 0, 'int');
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

		ToolbarHelper::title(common::translate($isNew ? 'COM_CUSTOMTABLES_TABLES_NEW' : 'COM_CUSTOMTABLES_TABLES_EDIT'), 'pencil-2 article-add');

		if ($isNew) {
			// For new records, check the create permission.
			if ($this->canCreate) {
				ToolbarHelper::apply('tables.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('tables.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::custom('tables.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
			};
			ToolbarHelper::cancel('tables.cancel', 'JTOOLBAR_CANCEL');
		} else {
			if ($this->canEdit) {
				// We can save the new record
				ToolbarHelper::apply('tables.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('tables.save', 'JTOOLBAR_SAVE');
				// We can save this record, but check the create permission to see
				// if we can return to make a new one.
				if ($this->canCreate) {
					ToolbarHelper::custom('tables.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				}
			}
			if ($this->canCreate) {
				ToolbarHelper::custom('tables.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}
			ToolbarHelper::cancel('tables.cancel', 'JTOOLBAR_CLOSE');
		}
		ToolbarHelper::divider();
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
			$document->setTitle(common::translate($isNew ? 'COM_CUSTOMTABLES_TABLES_NEW' : 'COM_CUSTOMTABLES_TABLES_EDIT'));
			$document->addCustomTag('<script src="' . Uri::root(true) . '/administrator/components/com_customtables/views/tables/submitbutton.js"></script>');
		}
	}
}
