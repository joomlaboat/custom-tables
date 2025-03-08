<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage models/fields.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\Fields;
use CustomTables\MySQLWhereClause;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * Customtables Fields Model
 * @since 1.0.0
 */
class CustomtablesModelFields extends AdminModel
{
	var CT $ct;
	/**
	 * The type alias for this content type.
	 *
	 * @var      string
	 * @since    3.2
	 */
	public $typeAlias = 'com_customtables.fields';
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
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @throws Exception
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_customtables.fields', 'fields', array('control' => 'jform', 'load_data' => $loadData));
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

		$user = new CTUser();

		// Check for existing item.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_customtables.fields.' . (int)$id))
			|| ($id == 0 && !$user->authorise('core.edit.state', 'com_customtables'))) {
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');
			// Disable fields while saving.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}
		// If this is a new item insure the created by is set.
		if (0 == $id) {
			// Set the created_by to this user
			$form->setValue('created_by', null, $user->id);
		}
		// Modify the form based on Edit Created By access controls.
		if (!$user->authorise('core.edit.created_by', 'com_customtables')) {
			// Disable fields for display.
			$form->setFieldAttribute('created_by', 'disabled', 'true');
			// Disable fields for display.
			$form->setFieldAttribute('created_by', 'readonly', 'true');
			// Disable fields while saving.
			$form->setFieldAttribute('created_by', 'filter', 'unset');
		}
		// Modify the form based on Edit Created Date access controls.
		if (!$user->authorise('core.edit.created', 'com_customtables')) {
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

	/**
	 * Method to get the script that have to be included on the form
	 *
	 * @return string    script files
	 *
	 * @since 1.0.0
	 */
	public function getScript(): string
	{
		return '/administrator/components/com_customtables/models/forms/fields.js';
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param array  &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @throws Exception
	 * @since   12.2
	 */
	public function delete(&$pks): void
	{
		foreach ($pks as $fieldid) {
			Fields::deleteField_byID($this->ct, $fieldid);
		}
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
	public function publish(&$pks, $value = 1): bool
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
		$this->canDo = CustomtablesHelper::getActions('fields');
		$this->batchSet = true;

		if (!$this->canDo->get('core.batch')) {
			$this->setError(common::translate('COM_CUSTOMTABLES_JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));
			return false;
		}

		if (!$this->type) {
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

	public function getTable($type = 'fields', $prefix = 'CustomtablesTable', $config = array())
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
	 * @return  array|false  An array of new IDs on success, boolean false on failure.
	 *
	 * @throws Exception
	 * @since 12.2
	 */
	protected function batchCopy($values, $pks, $contexts)
	{
		if (empty($this->batchSet)) {
			// Set some needed variables.
			$this->table = $this->getTable();
			$this->tableClassName = get_class($this->table);
			$this->canDo = CustomtablesHelper::getActions('fields');
		}

		if (!$this->canDo->get('core.create') || !$this->canDo->get('core.batch')) {
			return false;
		}

		// get list of unique fields
		$uniqueFields = $this->getUniqueFields();
		// remove move_copy from array
		unset($values['move_copy']);

		// make sure published is set
		if (!isset($values['published'])) {
			$values['published'] = 0;
		} elseif (isset($values['published']) && !$this->canDo->get('core.edit.state')) {
			$values['published'] = 0;
		}

		$newIds = array();
		// Parent exists so let's proceed
		while (!empty($pks)) {
			// Pop the first ID off the stack
			$pk = array_shift($pks);

			$this->table->reset();

			// only allow copy if user may edit this item.
			if (!$this->ct->Env->user->authorise('core.edit', $contexts[$pk])) {
				// Not fatal error
				$this->setError(common::translate('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
				continue;
			}

			// Check that the row actually exists
			if (!$this->table->load($pk)) {
				if ($error = $this->table->getError()) {
					// Fatal error
					$this->setError($error);
					return false;
				} else {
					// Not fatal error
					$this->setError(common::translate('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
					continue;
				}
			}

			// Only for strings
			if (common::checkString($this->table->fieldtitle) && !is_numeric($this->table->fieldtitle)) {
				$this->table->fieldtitle = $this->generateUnique('fieldtitle', $this->table->fieldtitle);
			}

			// insert all set values
			if (CustomtablesHelper::checkArray($values)) {
				foreach ($values as $key => $value) {
					if (strlen($value) > 0 && isset($this->table->$key)) {
						$this->table->$key = $value;
					}
				}
			}

			// update all unique fields
			if (CustomtablesHelper::checkArray($uniqueFields)) {
				foreach ($uniqueFields as $uniqueField) {
					$this->table->$uniqueField = $this->generateUnique($uniqueField, $this->table->$uniqueField);
				}
			}

			// Reset the ID because we are making a copy
			$this->table->id = 0;

			// TODO: Deal with ordering?
			// $this->table->ordering = 1;

			// Check the row.
			if (!$this->table->check()) {
				$this->setError($this->table->getError());

				return false;
			}

			if (!empty($this->type)) {
				$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
			}

			// Store the row.
			if (!$this->table->store()) {
				$this->setError($this->table->getError());

				return false;
			}

			// Get the new item ID
			$newId = $this->table->get('id');

			// Add the new ID to the array
			$newIds[$pk] = $newId;
		}

		// Clean the cache
		$this->cleanCache();

		return $newIds;
	}

	/**
	 * Method to get the unique fields of this table.
	 *
	 * @return  false  An array of field names, boolean false if none is set.
	 *
	 * @since   3.0
	 */
	protected function getUniqueFields()
	{
		return false;
	}

	/**
	 * Method to generate a unique value.
	 *
	 * @param string $field name.
	 * @param string $value data.
	 *
	 * @return  string  New value.
	 *
	 * @throws Exception
	 * @since   3.0
	 */
	protected function generateUnique($field, $value): string
	{
		// set field value unique
		$table = $this->getTable();

		while ($table->load(array($field => $value))) {
			$value = StringHelper::increment($value);
		}

		return $value;
	}

	protected function batchMove($values, $pks, $contexts)
	{
		if (empty($this->batchSet)) {
			// Set some needed variables.
			$this->table = $this->getTable();
			$this->tableClassName = get_class($this->table);
			$this->canDo = CustomtablesHelper::getActions('fields');
		}

		if (!$this->canDo->get('core.edit') && !$this->canDo->get('core.batch')) {
			$this->setError(common::translate('COM_CUSTOMTABLES_JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
			return false;
		}

		// make sure published only updates if user has the permission.
		if (isset($values['published']) && !$this->canDo->get('core.edit.state')) {
			unset($values['published']);
		}
		// remove move_copy from array
		unset($values['move_copy']);

		// Parent exists so we proceed
		foreach ($pks as $pk) {
			if (!$this->ct->Env->user->authorise('core.edit', $contexts[$pk])) {
				$this->setError(common::translate('COM_CUSTOMTABLES_JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
				return false;
			}

			// Check that the row actually exists
			if (!$this->table->load($pk)) {
				if ($error = $this->table->getError()) {
					// Fatal error
					$this->setError($error);
					return false;
				} else {
					// Not fatal error
					$this->setError(common::translate('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
					continue;
				}
			}

			// insert all set values.
			if (CustomtablesHelper::checkArray($values)) {
				foreach ($values as $key => $value) {
					// Do special action for access.
					if ('access' === $key && strlen($value) > 0) {
						$this->table->$key = $value;
					} elseif (strlen($value) > 0 && isset($this->table->$key)) {
						$this->table->$key = $value;
					}
				}
			}

			// Check the row.
			if (!$this->table->check()) {
				$this->setError($this->table->getError());
				return false;
			}

			if (!empty($this->type)) {
				$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
			}

			// Store the row.
			if (!$this->table->store()) {
				$this->setError($this->table->getError());
				return false;
			}
		}

		// Clean the cache
		$this->cleanCache();

		return true;
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

			$user = new CTUser();
			// The record has been set. Check the record permissions.
			return $user->authorise('core.delete', 'com_customtables.fields.' . (int)$record->id);
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
		$user = new CTUser();
		$recordId = (!empty($record->id)) ? $record->id : 0;

		if ($recordId) {
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit.state', 'com_customtables.fields.' . (int)$recordId);
			if (!$permission && !is_null($permission)) {
				return false;
			}
		}
		// In the absense of better information, revert to the component permissions.
		return parent::canEditState($record);
	}

	/**
	 * Method overrides to check if you can edit an existing record.
	 *
	 * @param array $data An array of input data.
	 * @param string $key The name of the key for the primary key.
	 *
	 * @return    boolean
	 * @throws Exception
	 * @since    2.5
	 */
	protected function allowEdit($data = array(), $key = 'id'): bool
	{
		// Check specific edit permission then general edit permission.
		$user = new CTUser();
		return $user->authorise('core.edit', 'com_customtables.fields.' . ((int)isset($data[$key]) ? $data[$key] : 0)) or parent::allowEdit($data, $key);
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param JTable $table A JTable object.
	 *
	 * @return  void
	 *
	 * @throws DateInvalidTimeZoneException
	 * @since   1.6
	 */
	protected function prepareTable($table): void
	{
		$user = new CTUser();

		if (isset($table->name)) {
			$table->name = htmlspecialchars_decode($table->name ?? '', ENT_QUOTES);
		}

		if (empty($table->id)) {
			$table->created = common::currentDate();
			// set the user
			if ($table->created_by == 0 || empty($table->created_by)) {
				$table->created_by = $user->id;
			}
			// Set ordering to the last item if not set
			if (empty($table->ordering)) {
				$whereClause = new MySQLWhereClause();
				$col = database::loadColumn('#__customtables_fields', ['MAX(ordering)'], $whereClause);

				$max = $col[0];
				$table->ordering = $max + 1;
			}
		} else {
			$table->modified = common::currentDate();
			$table->modified_by = $user->id;
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
		$data = Factory::getApplication()->getUserState('com_customtables.edit.fields.data', array());

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
			/*
						if (!empty($item->id))
						{
							$item->tags = new JHelperTags;
							$item->tags->getTagIds($item->id, 'com_customtables.fields');
						}
						*/
		}

		return $item;
	}
}
