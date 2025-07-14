<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/// No direct access to this file

defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;
use CustomTables\Layouts;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Customtables Layouts Model
 */
class CustomtablesModelLayouts extends AdminModel
{
	var CT $ct;
	/**
	 * The type alias for this content type.
	 *
	 * @var      string
	 * @since    3.2
	 */
	public $typeAlias = 'com_customtables.layouts';
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
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_customtables.layouts', 'layouts', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		// The front end calls this model and uses a_id to avoid id clashes, so we need to check for that first.
		if (common::inputGetInt('a_id')) {
			$id = common::inputGetInt('a_id', 0);
		} // The back end uses id, so we use that the rest of the time and set it to 0 by default.
		else {
			$id = common::inputGetint('id', 0);
		}

		$user = new CTUser();

		// Check for existing item.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_customtables.layouts.' . (int)$id))
			|| ($id == 0 && !$user->authorise('core.edit.state', 'com_customtables'))) {
			// Disable fields for display.
			$form->setFieldAttribute('published', 'disabled', 'true');
			// Disable fields while saving.
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
			$redirectedField = common::inputGetString('ref');
			// Set redirected field value
			$redirectedValue = common::inputGetInt('refid', 0);
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
	 * @since 3.0.0
	 */
	public function getScript()
	{
		return common::UriRoot(true) . '/administrator/components/com_customtables/models/forms/layouts.js';
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
	public function delete(&$pks)
	{
		$Layouts = new Layouts($this->ct);

		foreach ($pks as $layoutId) {
			$row = $Layouts->getLayoutRowById($layoutId);
			$Layouts->deleteLayoutFiles($row['layoutname']);
		}

		if (!parent::delete($pks))
			return false;

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
			$this->setError(common::translate('COM_CUSTOMTABLES_LAYOUTS_NOT_SELECTED'));
			return false;
		}

		$done = false;

		// Set some needed variables.
		$this->table = $this->getTable();
		$this->tableClassName = get_class($this->table);
		$this->contentType = new JUcmType;
		$this->type = $this->contentType->getTypeByTable($this->tableClassName);
		//$this->canDo			= CustomtablesHelper::getActions('layouts');
		$this->canDo = ContentHelper::getActions('com_customtables', 'layouts');

		$this->batchSet = true;


		if (!$this->canDo['core.batch'] == 0) {
			$this->setError(common::translate('COM_CUSTOMTABLES_JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));
			return false;
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

	public function getTable($type = 'layouts', $prefix = 'CustomtablesTable', $config = array())
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
		if (empty($this->batchSet)) {
			// Set some needed variables.
			$this->table = $this->getTable();
			$this->tableClassName = get_class($this->table);
			$this->canDo = ContentHelper::getActions('com_customtables', 'layouts');
			//$this->canDo		= CustomtablesHelper::getActions('layouts');
		}

		if (!$this->canDo['core.create'] || !$this->canDo['core.batch']) {
			return false;
		}

		// get list of unique fields
		$uniqueFields = $this->getUniqueFields();
		// remove move_copy from array
		unset($values['move_copy']);

		// make sure published is set
		if (!isset($values['published'])) {
			$values['published'] = 0;
		} elseif (isset($values['published']) && !$this->canDo['core.edit.state']) {
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
			if (common::checkString($this->table->layoutname) && !is_numeric($this->table->layoutname)) {
				$this->table->layoutname = $this->generateUnique('layoutname', $this->table->layoutname);
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

			// Check the row.
			if (!$this->table->check()) {
				$this->setError($this->table->getError());

				return false;
			}

			/*
						if (!empty($this->type))
						{
							$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
						}*/

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
	 * @return  mixed  An array of field names, boolean false if none is set.
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
	 * @since   3.0
	 */
	protected function generateUnique($field, $value)
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
			//$this->canDo		= CustomtablesHelper::getActions('layouts');
			$this->canDo = ContentHelper::getActions('com_customtables', 'layouts');
		}

		if (!$this->canDo['core.edit'] && !$this->canDo['core.batch']) {
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

			/*
						if (!empty($this->type))
						{
							$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
						}
						*/

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
	 * Method to save the form data.
	 *
	 * @param array $data The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws Exception
	 * @since   1.6
	 */
	public function save($data): bool
	{
		if (function_exists("transliterator_transliterate"))
			$layoutName = transliterator_transliterate("Any-Latin; Latin-ASCII;", $data['layoutname']);
		else
			$layoutName = $data['layoutname'];

		$layoutName = str_replace(" ", "_", $layoutName);
		$layoutName = trim(preg_replace("/[^a-z A-Z_\d]/", "", $layoutName));
		$data['layoutname'] = $layoutName;

		// Set the Params Items to data
		/*
		if (isset($data['params']) && is_array($data['params'])) {
			$params = new Registry($data['params']);
			$data['params'] = (string)$params;
		}
		*/

		// Alter the unique field for save as copy
		if (common::inputGetCmd('task') === 'save2copy') {
			// Automatic handling of other unique fields
			$uniqueFields = $this->getUniqueFields();
			if (CustomtablesHelper::checkArray($uniqueFields)) {
				foreach ($uniqueFields as $uniqueField) {
					$data[$uniqueField] = $this->generateUnique($uniqueField, $data[$uniqueField]);
				}
			}
		}

		$Layouts = new Layouts($this->ct);
		$Layouts->storeAsFile($data);

		$params = [];
		$params['filter'] = $data['filter'] ?? null;
		$params['addusergroups'] = $data['addusergroups'] ?? null;
		$params['editusergroups'] = $data['editusergroups'] ?? null;
		$params['publishusergroups'] = $data['publishusergroups'] ?? null;
		$params['deleteusergroups'] = $data['deleteusergroups'] ?? null;
		$params['publishstatus'] = $data['publishstatus'] ?? null;
		$params['mimetype'] = $data['mimetype'] ?? null;

		$data['params'] = json_encode($params);

		if (parent::save($data)) {
			return true;
		}
		return false;
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
			return $user->authorise('core.delete', 'com_customtables.layouts.' . (int)$record->id);
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
	 * @since   1.6
	 */
	protected function canEditState($record)
	{
		$user = new CTUser();
		$recordId = (!empty($record->id)) ? $record->id : 0;

		if ($recordId) {
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit.state', 'com_customtables.layouts.' . (int)$recordId);
			if (!$permission && !is_null($permission)) {
				return false;
			}
		}
		// In the absense of better information, revert to the component permissions.
		return parent::canEditState($record);
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param array $data An array of input data.
	 * @param string $key The name of the key for the primary key.
	 *
	 * @return    boolean
	 * @since    2.5
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Check specific edit permission then general edit permission.
		$user = new CTUser();
		return $user->authorise('core.edit', 'com_customtables.layouts.' . ((int)isset($data[$key]) ? $data[$key] : 0)) or parent::allowEdit($data, $key);
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
		$user = new CTUser();

		if (isset($table->name))
			$table->name = htmlspecialchars_decode($table->name ?? '', ENT_QUOTES);

		if (empty($table->id)) {
			$table->created = common::currentDate();
			// set the user
			if ($table->created_by == 0 || empty($table->created_by)) {
				$table->created_by = $user->id;
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
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_customtables.edit.layouts.data', array());
		if (empty($data)) {
			$data = $this->getItem();
		}

		if (isset($data->params)) {
			if (isset($data->params['filter']))
				$data->filter = $data->params['filter'];

			//if (isset($data->params['guestcanaddnew']))
			//$data->guestcanaddnew = $data->params['guestcanaddnew'];

			if (isset($data->params['addusergroups']))
				$data->addusergroups = $data->params['addusergroups'];

			if (isset($data->params['editusergroups']))
				$data->editusergroups = $data->params['editusergroups'];

			if (isset($data->params['publishusergroups']))
				$data->publishusergroups = $data->params['publishusergroups'];

			if (isset($data->params['deleteusergroups']))
				$data->deleteusergroups = $data->params['deleteusergroups'];

			if (isset($data->params['publishstatus']))
				$data->publishstatus = $data->params['publishstatus'];

			if (isset($data->params['mimetype']))
				$data->mimetype = $data->params['mimetype'];
		}

		return $data;
	}
}
