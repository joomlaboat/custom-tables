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

use CustomTables\common;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

jimport('joomla.application.component.view');

class CustomTablesViewOptions extends JViewLegacy
{
	function display($tpl = null)
	{
		$this->form = $this->get('Form');

		// Assign the variables
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->script = $this->get('Script');
		$this->state = $this->get('State');
		// get action permissions
		$this->canDo = CustomtablesHelper::getActions('options', $this->item);
		// get input

		$this->ref = common::inputGet('ref', 0, 'word');
		$this->refid = common::inputGet('refid', 0, 'int');
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
		parent::display($tpl);

		// Set the document
		$document = Factory::getDocument();
		$this->setDocument($document);
	}

	protected function addToolBar()
	{
		common::inputSet('hidemainmenu', true);
		$isNew = $this->item->id == 0;

		JToolbarHelper::title(common::translate($isNew ? 'COM_CUSTOMTABLES_OPTIONS_NEW' : 'COM_CUSTOMTABLES_OPTIONS_EDIT'), 'pencil-2 article-add');
		// Built the actions for new and existing records.
		if ($this->refid || $this->ref) {
			if ($this->canDo->get('core.create') && $isNew) {
				// We can create the record.
				JToolBarHelper::save('options.save', 'JTOOLBAR_SAVE');
			} elseif ($this->canDo->get('core.edit')) {
				// We can save the record.
				JToolBarHelper::save('options.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew) {
				// Do not creat but cancel.
				JToolBarHelper::cancel('options.cancel', 'JTOOLBAR_CANCEL');
			} else {
				// We can close it.
				JToolBarHelper::cancel('options.cancel', 'JTOOLBAR_CLOSE');
			}
		} else {
			if ($isNew) {
				// For new records, check the create permission.
				if ($this->canDo->get('core.create')) {
					JToolBarHelper::apply('options.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('options.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('options.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				JToolBarHelper::cancel('options.cancel', 'JTOOLBAR_CANCEL');
			} else {
				if ($this->canDo->get('core.edit')) {
					// We can save the new record
					JToolBarHelper::apply('options.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('options.save', 'JTOOLBAR_SAVE');
					// We can save this record, but check the create permission to see
					// if we can return to make a new one.
					if ($this->canDo->get('core.create')) {
						JToolBarHelper::custom('options.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				if ($this->canDo->get('core.create')) {
					JToolBarHelper::custom('options.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				JToolBarHelper::cancel('options.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		JToolbarHelper::divider();
		// set help url for this view if found
		$help_url = CustomtablesHelper::getHelpUrl('options');
		if (CustomtablesHelper::checkString($help_url)) {
			JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
		}
	}

	public function setDocument(Joomla\CMS\Document\Document $document): void
	{
		$isNew = ($this->item->id < 1);
		$document->setTitle(common::translate($isNew ? 'COM_CUSTOMTABLES_OPTIONS_NEW' : 'COM_CUSTOMTABLES_OPTIONS_EDIT'));
	}
}
