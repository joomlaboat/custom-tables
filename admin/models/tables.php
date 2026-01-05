<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage models/tables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\TableHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

class CustomtablesModelTables extends AdminModel
{
	var CT $ct;
	/**
	 * The type alias for this content type.
	 *
	 * @var      string
	 * @since    3.2
	 */
	public $typeAlias = 'com_customtables.tables';
	/**
	 * @var        string    The prefix to use with controller messages.
	 * @since   1.6
	 */
	protected $text_prefix = 'COM_CUSTOMTABLES';

	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->ct = new CT([], true);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param array $data Data for the form.
	 * @param boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  false|\Joomla\CMS\Form\Form|\Joomla\CMS\User\CurrentUserInterface  A JForm object on success, false on failure
	 *
	 * @throws Exception
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_customtables.tables', 'tables', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		// The front end calls this model and uses a_id to avoid id clashes, so we need to check for that first.
		if (common::inputGetInt('a_id')) {
			$id = common::inputGetInt('a_id', 0);
		} // The back end uses id, so we use that the rest of the time and set it to 0 by default.
		else {
			$id = common::inputGetInt('id', 0);
		}

		// Check for existing item.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$this->ct->Env->user->authorise('core.edit.state', 'com_customtables.tables.' . (int)$id))
			|| ($id == 0 && !$this->ct->Env->user->authorise('core.edit.state', 'com_customtables'))) {
			// Disable fields for display.
			$form->setFieldAttribute('published', 'disabled', 'true');
			// Disable fields while saving.
			$form->setFieldAttribute('published', 'filter', 'unset');
		}
		// If this is a new item insure the created by is set.
		if (0 == $id) {
			// Set the created_by to this user
			$form->setValue('created_by', null, $this->ct->Env->user->id);
		}
		// Modify the form based on Edit Creaded By access controls.
		if (!$this->ct->Env->user->authorise('core.edit.created_by', 'com_customtables')) {
			// Disable fields for display.
			$form->setFieldAttribute('created_by', 'disabled', 'true');
			// Disable fields for display.
			$form->setFieldAttribute('created_by', 'readonly', 'true');
			// Disable fields while saving.
			$form->setFieldAttribute('created_by', 'filter', 'unset');
		}
		// Modify the form based on Edit Creaded Date access controls.
		if (!$this->ct->Env->user->authorise('core.edit.created', 'com_customtables')) {
			// Disable fields for display.
			$form->setFieldAttribute('created', 'disabled', 'true');
			// Disable fields while saving.
			$form->setFieldAttribute('created', 'filter', 'unset');
		}
		// Only load these values if no id is found
		if (0 == $id) {
			// Set redirected field name
			$redirectedField = common::inputGet('ref', null, 'STRING');
			// Set redirected field value
			$redirectedValue = common::inputGet('refid', 0, 'INT');
			if (0 != $redirectedValue && $redirectedField) {
				// Now set the local-redirected field default value
				$form->setValue($redirectedField, null, $redirectedValue);
			}
		}
		return $form;
	}

	public function getScript()
	{
		return common::UriRoot(true) . '/administrator/components/com_customtables/models/forms/tables.js';
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param array  &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @throws Exception
	 * @since 3.2.5
	 */
	public function delete(&$pks)
	{
		// Ensure the pks is an array
		if (!is_array($pks) || empty($pks)) {
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_NO_ITEM_SELECTED'));
			return false;
		}

		$db = database::getDB();

		try {
			// Start transaction
			$db->transactionStart();

			foreach ($pks as $tableid) {
				$table_row = TableHelper::deleteTable((int)$tableid);

				// Add to activity log if you have one
				//Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_TABLE_DELETED') . ' ' . $table_row->tablename);
			}

			//if (count($pks) == 1)
			//	common::enqueueMessage(common::translate('COM_CUSTOMTABLES_LISTOFTABLES_N_ITEMS_DELETED_1'), 'success');
			//else
			//common::enqueueMessage(common::translate('COM_CUSTOMTABLES_LISTOFTABLES_N_ITEMS_DELETED', count($pks)), 'success');

			// Commit transaction
			$db->transactionCommit();

			// Clear cache
			$this->cleanCache();

		} catch (Exception $e) {
			// Roll back transaction on error
			$db->transactionRollback();
			throw new Exception($e->getMessage());
		}
		return true;
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param array    &$pks A list of the primary keys to change.
	 * @param integer $value The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   12.2
	 */
	public function publish(&$pks, $value = 1)
	{
		if (!parent::publish($pks, $value)) {
			return false;
		}
		return true;
	}

	/**
	 * Method to perform batch operations on an item or a set of items.
	 *
	 * @param array $commands An array of commands to perform.
	 * @param array $pks An array of item ids.
	 * @param array $contexts An array of item contexts.
	 *
	 * @return  boolean  Returns true on success, false on failure.
	 *
	 * @throws Exception
	 * @since   12.2
	 */
	public function batch($commands, $pks, $contexts)
	{
		// Sanitize ids.
		$pks = array_unique($pks);
		ArrayHelper::toInteger($pks);

		// Remove any values of zero.
		if (array_search(0, $pks, true)) {
			unset($pks[array_search(0, $pks, true)]);
		}

		if (empty($pks)) {
			$this->setError(common::translate('JGLOBAL_NO_ITEM_SELECTED'));
			return false;
		}

		$done = false;

		// Set some needed variables.
		$this->table = $this->getTable();
		$this->tableClassName = get_class($this->table);
		$this->contentType = new JUcmType;
		$this->type = $this->contentType->getTypeByTable($this->tableClassName);
		$this->canDo = CustomtablesHelper::getActions('tables');
		$this->batchSet = true;

		if (!$this->canDo->get('core.batch')) {
			$this->setError(common::translate('COM_CUSTOMTABLES_JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));
			return false;
		}

		if ($this->type == false) {
			$type = new JUcmType;
			$this->type = $type->getTypeByAlias($this->typeAlias);
		}

		$this->tagsObserver = $this->table->getObserverOfClass('JTableObserverTags');

		if (!empty($commands['move_copy'])) {
			$cmd = ArrayHelper::getValue($commands, 'move_copy', 'c');

			if ($cmd == 'c') {
				$result = $this->batchCopy($commands, $pks, $contexts);

				if (is_array($result)) {
					foreach ($result as $old => $new) {
						$contexts[$new] = $contexts[$old];
					}
					$pks = array_values($result);
				} else {
					return false;
				}
			} elseif ($cmd == 'm' && !$this->batchMove($commands, $pks, $contexts)) {
				return false;
			}

			$done = true;
		}

		if (!$done) {
			$this->setError(common::translate('COM_CUSTOMTABLES_JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));
			return false;
		}

		// Clear the cache
		$this->cleanCache();
		return true;
	}

	public function getTable($type = 'tables', $prefix = 'CustomtablesTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Batch copy items to a new category or current.
	 *
	 * @param integer $values The new values.
	 * @param array $pks An array of row IDs.
	 * @param array $contexts An array of item contexts.
	 *
	 * @return  mixed  An array of new IDs on success, boolean false on failure.
	 *
	 * @since 12.2
	 */
	protected function batchCopy($values, $pks, $contexts)
	{
		die;//Batch table copy not implemented
	}

	/**
	 * Batch move items to a new category
	 *
	 * @param $values
	 * @param array $pks An array of row IDs.
	 * @param array $contexts An array of item contexts.
	 *
	 * @return  boolean  True if successful, false otherwise and internal error is set.
	 *
	 * @since 12.2
	 */
	protected function batchMove($values, $pks, $contexts)
	{
		die;//Batch table move not implemented
	}


	public function copyTable($originaltableid, $new_table, $old_table)
	{
		return TableHelper::copyTable($this->ct, $originaltableid, $new_table, $old_table);
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param object $record A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @throws Exception
	 * @since   1.6
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id)) {
			if ($record->published != -2) {
				return false;
			}

			// The record has been set. Check the record permissions.
			return $this->ct->Env->user->authorise('core.delete', 'com_customtables.tables.' . (int)$record->id);
		}
		return false;
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param object $record A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @throws Exception
	 * @since   1.6
	 */
	protected function canEditState($record)
	{
		$recordId = (!empty($record->id)) ? $record->id : 0;

		if ($recordId) {
			// The record has been set. Check the record permissions.
			$permission = $this->ct->Env->user->authorise('core.edit.state', 'com_customtables.tables.' . (int)$recordId);
			if (!$permission && !is_null($permission)) {
				return false;
			}
		}
		// In the absence of better information, revert to the component permissions.
		return parent::canEditState($record);
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param array $data An array of input data.
	 * @param string $key The name of the key for the primary key.
	 *
	 * @return    boolean
	 * @throws Exception
	 * @since    2.5
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Check specific edit permission then general edit permission.
		$user = new CTUser();
		return $user->authorise('core.edit', 'com_customtables.tables.' . ((int)isset($data[$key]) ? $data[$key] : 0)) or parent::allowEdit($data, $key);
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param JTable $table A JTable object.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function prepareTable($table)
	{
		if (isset($table->name)) {
			$table->name = htmlspecialchars_decode($table->name ?? '', ENT_QUOTES);
		}

		if (empty($table->id)) {
			$table->created = common::currentDate();
			// set the user
			if ($table->created_by == 0 || empty($table->created_by)) {
				$table->created_by = $this->ct->Env->user->id;
			}
		} else {
			$table->modified = common::currentDate();
			$table->modified_by = $this->ct->Env->user->id;
		}
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @throws Exception
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_customtables.edit.tables.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}
		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param integer $pk The id of the primary key.
	 *
	 * @return  false|stdClass  Object on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			if (!empty($item->params) && !is_array($item->params)) {
				// Convert the params field to an array.
				$registry = new Registry;
				$registry->loadString($item->params);
				$item->params = $registry->toArray();
			}

			if (!empty($item->metadata)) {
				// Convert the metadata field to an array.
				$registry = new Registry;
				$registry->loadString($item->metadata);
				$item->metadata = $registry->toArray();
			}
		}
		return $item;
	}
}
