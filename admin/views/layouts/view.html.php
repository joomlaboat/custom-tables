<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die();


use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\LayoutEditor;
use CustomTables\MySQLWhereClause;
use CustomTables\Tables;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Layouts View class
 * @since 3.2.2
 */
class CustomtablesViewLayouts extends HtmlView
{
	/**
	 * display method of View
	 * @return void
	 * @since 3.2.2
	 */

	var CT $ct;
	var array $allTables;
	var $document;
	var $item;
	var LayoutEditor $layoutEditor;

	public function display($tpl = null)
	{
		require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
			. 'libraries' . DIRECTORY_SEPARATOR . 'layouteditor.php');
		$this->layoutEditor = new LayoutEditor();

		$model = $this->getModel();
		$this->ct = $model->ct;
		$layoutId = common::inputGetInt('id', 0);

		// Assign the variables
		$this->form = $this->get('Form');
		$serverType = database::getServerType();

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', $layoutId);

		$selects = [
			'id',
			'tableid',
			'layoutname',
			'layoutcode',
			'layoutmobile',
			'layoutcss',
			'layoutjs',
			'layouttype',
			'MODIFIED_TIMESTAMP'
		];

		$rows = database::loadObjectList('#__customtables_layouts', $selects, $whereClause, null, null, 1);

		if (count($rows) == 1) {
			$this->item = $rows[0];
		} else {
			$emptyItem = ['id' => 0, 'tableid' => null, 'layoutname' => null, 'layoutcode' => null, 'layoutmobile' => null, 'layoutcss' => null, 'layoutjs' => null, 'modified_timestamp' => 0];
			$this->item = (object)$emptyItem;
		}

		$this->script = $this->get('Script');
		$this->state = $this->get('State');
		// get action permissions

		$this->canDo = ContentHelper::getActions('com_customtables', 'tables', $this->item->id);
		$this->canCreate = $this->canDo->get('layouts.create');
		$this->canEdit = $this->canDo->get('layouts.edit');

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
		$this->document = Factory::getDocument();

		// Set the document
		$this->setDocument($this->document);

		// Display the template
		if ($this->ct->Env->version < 4)
			parent::display($tpl);
		else
			parent::display('quatro');
	}

	/**
	 * Setting the toolbar
	 * @since 3.2.2
	 */
	protected function addToolBar()
	{
		common::inputSet('hidemainmenu', true);
		$isNew = $this->item->id == 0;

		ToolbarHelper::title(common::translate($isNew ? 'COM_CUSTOMTABLES_LAYOUTS_NEW' : 'COM_CUSTOMTABLES_LAYOUTS_EDIT'), 'pencil-2 article-add');
		// Built the actions for new and existing records.
		/*
		if ($this->refid || $this->ref)
		{
			if ($this->canCreate && $isNew)
			{
				// We can create the record.
				ToolbarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canEdit)
			{
				// We can save the record.
				ToolbarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// Do not creat but cancel.
				ToolbarHelper::cancel('layouts.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// We can close it.
				ToolbarHelper::cancel('layouts.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			*/
		if ($isNew) {
			// For new records, check the create permission.
			if ($this->canCreate) {
				ToolbarHelper::apply('layouts.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::custom('layouts.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
			}

			ToolbarHelper::custom('layoutwizard', 'wizzardbutton', 'wizzardbutton', 'COM_CUSTOMTABLES_BUTTON_LAYOUTAUTOCREATE', false);//Layout Wizard
			ToolbarHelper::custom('addfieldtag', 'fieldtagbutton', 'fieldtagbutton', 'COM_CUSTOMTABLES_BUTTON_ADDFIELDTAG', false);
			ToolbarHelper::custom('addlayouttag', 'layouttagbutton', 'layouttagutton', 'COM_CUSTOMTABLES_BUTTON_ADDLAYOUTTAG', false);

			ToolbarHelper::cancel('layouts.cancel', 'JTOOLBAR_CANCEL');
		} else {
			if ($this->canEdit) {
				// We can save the new record
				ToolbarHelper::apply('layouts.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
				// We can save this record, but check the create permission to see
				// if we can return to make a new one.
				if ($this->canCreate) {
					ToolbarHelper::custom('layouts.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				}
			}
			if ($this->canCreate) {
				ToolbarHelper::custom('layouts.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			ToolbarHelper::custom('layoutwizard', 'wizzardbutton', 'wizzardbutton', 'COM_CUSTOMTABLES_BUTTON_LAYOUTAUTOCREATE', false);//Layout Wizard
			ToolbarHelper::custom('addfieldtag', 'fieldtagbutton', 'fieldtagbutton', 'COM_CUSTOMTABLES_BUTTON_ADDFIELDTAG', false);
			ToolbarHelper::custom('addlayouttag', 'layouttagbutton', 'layouttagutton', 'COM_CUSTOMTABLES_BUTTON_ADDLAYOUTTAG', false);
			ToolbarHelper::custom('dependencies', 'dependencies', 'dependencies', 'COM_CUSTOMTABLES_BUTTON_DEPENDENCIES', false);

			ToolbarHelper::cancel('layouts.cancel', 'JTOOLBAR_CLOSE');
		}
		//}
		ToolbarHelper::divider();
	}

	/**
	 * Method to set up the document properties
	 *
	 * @param \Joomla\CMS\Document\Document $document
	 * @return void
	 * @since 3.2.2
	 */
	public function setDocument(Joomla\CMS\Document\Document $document): void
	{
		if ($this->item !== null) {
			$isNew = ($this->item->id < 1);
			$document->setTitle(common::translate($isNew ? 'COM_CUSTOMTABLES_LAYOUTS_NEW' : 'COM_CUSTOMTABLES_LAYOUTS_EDIT'));
			$document->addCustomTag('<script src="' . Uri::root(true) . '/administrator/components/com_customtables/views/layouts/submitbutton.js"></script>');
		}
	}

	public function renderTextArea($value, $id, $typeBoxId, &$onPageLoads): string
	{
		$result = '<div style="width: 100%;position: relative;">';

		if ($value != "")
			$result .= '<div class="ct_tip">TIP: Double-Click on a Layout Tag to edit parameters.</div>';

		$result .= '</div>';

		$textAreaId = 'jform_' . $id;
		$textAreaCode = '<textarea name="jform[' . $id . ']" id="' . $textAreaId . '" filter="raw" style="width:100%;" rows="30">' . htmlspecialchars($value ?? '') . '</textarea>';
		$textAreaTabId = $id . '-tab';

		$result .= $this->layoutEditor->renderEditor($textAreaCode, $textAreaId, $typeBoxId, $textAreaTabId, $onPageLoads);

		return $result;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function getMenuItems(): string
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

		//$where = array();
		$whereClause = new MySQLWhereClause();
		$i = 0;
		foreach ($whereToSearch as $w) {
			$whereClause->addOrCondition('params', '"' . $w . '":"' . $whatToLookFor[$i] . '"', 'INSTR');
			//$where[] = 'INSTR(params,\'"' . $w . '":"' . $whatToLookFor[$i] . '"\')';
			$i++;
		}

		if ($whereClause->hasConditions()) {

			//$query = 'SELECT id,title FROM #__menu WHERE ' . implode(' OR ', $where);

			$rows = database::loadAssocList('#__menu', ['id', 'title'], $whereClause, null, null);

			if (count($rows) > 0) {
				$result = '<hr/><p>List of Menu Items that use this layout:</p><ul>';
				foreach ($rows as $r) {
					$link = '/administrator/index.php?option=com_menus&view=item&layout=edit&id=' . $r['id'];
					$result .= '<li><a href="' . $link . '" target="_blank">' . $r['title'] . '</a></li>';
				}
				$result .= '</ul>';
			}
		}
		return $result;
	}
}