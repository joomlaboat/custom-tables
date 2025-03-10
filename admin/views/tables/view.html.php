<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Integrity\IntegrityFields;
use CustomTables\TableHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Tables View class
 *
 * @since 3.0.0
 */
class CustomtablesViewTables extends HtmlView
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
	 *
	 * @throws Exception
	 * @since 3.0.0
	 */
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

		if ($this->item->tablename !== null)
			$this->ct->getTable($this->item->tablename);

		// Set the document
		$this->setMyDocument();

		// Display the template
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			parent::display('quatro');
		else
			parent::display($tpl);
	}


	/**
	 * Setting the toolbar
	 *
	 * @since 3.0.0
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
			}
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
	 *
	 * @throws Exception
	 * @since 3.0.0
	 */
	public function setMyDocument(): void
	{
		if ($this->item !== null) {
			$isNew = ($this->item->id < 1);
			$document = Factory::getApplication()->getDocument();
			$document->setTitle(common::translate($isNew ? 'COM_CUSTOMTABLES_TABLES_NEW' : 'COM_CUSTOMTABLES_TABLES_EDIT'));
			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/administrator/components/com_customtables/views/tables/submitbutton.js"></script>');
		}
	}

	public function getTableSchema($item): string
	{
		if ($this->ct->Table === null)
			return '';

		try {
			$tableExists = TableHelper::checkIfTableExists($this->ct->Table->realtablename);
		} catch (Exception $e) {
			common::enqueueMessage($e->getMessage());
			return '';
		}

		$content = '';

		if (!$tableExists) {
			if ($this->ct->Table->published_field_found) {
				$columns = [
					'published tinyint(1) NOT NULL DEFAULT 1'
				];
			} else {
				$columns = [];
			}

			$primaryKeyType = $item->customidfieldtype;
			if ($item->primarykeypattern == '' or str_contains('AUTO_INCREMENT', $item->primarykeypattern)) {
				$primaryKeyType .= ' AUTO_INCREMENT';
			}

			database::createTable($this->ct->Table->realtablename, $this->ct->Table->realidfieldname, $columns,
				$this->ct->Table->tabletitle, null, $primaryKeyType);

			common::enqueueMessage('DataBase table created.', 'notice');

			$link = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $this->ct->Table->tableid;
			try {
				$content = IntegrityFields::checkFields($this->ct, $link);
			} catch (Exception $e) {
				common::enqueueMessage('DataBase table created.');
			}
		}

		try {
			$tableCreateQuery = database::showCreateTable($this->ct->Table->realtablename);
		} catch (Exception $e) {
			common::enqueueMessage($e->getMessage());
			return $content;
		}

		if (count($tableCreateQuery) == 0) {
			common::enqueueMessage('Table not found');
			return $content;
		} else {
			$createTableSql = $tableCreateQuery[0];

			// Remove redundant COLLATE specifications
			$createStatement = $createTableSql['Create Table'];
			$createStatement = preg_replace(
				"/CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci|COLLATE utf8mb4_unicode_ci/",
				"",
				$createStatement
			);

			// Add IF NOT EXISTS
			$createStatement = str_replace(
				'CREATE TABLE',
				'CREATE TABLE IF NOT EXISTS',
				$createStatement
			);

			return $content . '<pre><code>' . $createStatement . '</code></pre>';
		}
	}
}
