<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage views/fields/view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Categories View class
 *
 * @since 3.0.0
 */
class CustomtablesViewCategories extends HtmlView
{
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
		$this->setMyDocument();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Setting the toolbar
	 * @since 1.0.0
	 */
	protected function addToolBar()
	{
		common::inputSet('hidemainmenu', true);
		$isNew = $this->item->id == 0;

		ToolbarHelper::title(common::translate($isNew ? 'COM_CUSTOMTABLES_CATEGORIES_NEW' : 'COM_CUSTOMTABLES_CATEGORIES_EDIT'), 'pencil-2 article-add');
		// Built the actions for new and existing records.
		if ($this->refid || $this->ref) {
			if ($this->canCreate && $isNew) {
				// We can create the record.
				ToolbarHelper::save('categories.save', 'JTOOLBAR_SAVE');
			} elseif ($this->canEdit) {
				// We can save the record.
				ToolbarHelper::save('categories.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew) {
				// Do not creat but cancel.
				ToolbarHelper::cancel('categories.cancel', 'JTOOLBAR_CANCEL');
			} else {
				// We can close it.
				ToolbarHelper::cancel('categories.cancel', 'JTOOLBAR_CLOSE');
			}
		} else {
			if ($isNew) {
				// For new records, check the create permission.
				if ($this->canCreate) {
					ToolbarHelper::apply('categories.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('categories.save', 'JTOOLBAR_SAVE');
					ToolbarHelper::custom('categories.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				ToolbarHelper::cancel('categories.cancel', 'JTOOLBAR_CANCEL');
			} else {
				if ($this->canEdit) {
					// We can save the new record
					ToolbarHelper::apply('categories.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('categories.save', 'JTOOLBAR_SAVE');
					// We can save this record, but check the create permission to see
					// if we can return to make a new one.
					if ($this->canCreate) {
						ToolbarHelper::custom('categories.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				if ($this->canCreate) {
					ToolbarHelper::custom('categories.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				ToolbarHelper::cancel('categories.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		ToolbarHelper::divider();
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function setMyDocument(): void
	{
		if ($this->item !== null) {
			$isNew = ($this->item->id < 1);

			$document = Factory::getApplication()->getDocument();

			$document->setTitle(common::translate($isNew ? 'COM_CUSTOMTABLES_CATEGORIES_NEW' : 'COM_CUSTOMTABLES_CATEGORIES_EDIT'));

			if (!CUSTOMTABLES_JOOMLA_MIN_4)
				$document->addCustomTag('<script src=' . common::UriRoot(true) . '/administrator/components/com_customtables/views/categories/submitbutton.js"></script>');
		}
	}
}
