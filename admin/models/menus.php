<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTUser;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * Customtables Menus Model
 *
 * @since 1.0.0
 */
class CustomtablesModelMenus extends AdminModel
{
	/**
	 * The type alias for this content type.
	 *
	 * @var      string
	 * @since    3.2
	 */
	public $typeAlias = 'com_customtables.menus';
	/**
	 * @var        string    The prefix to use with controller messages.
	 * @since   1.6
	 */
	protected $text_prefix = 'COM_CUSTOMTABLES';

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
		$form = $this->loadForm('com_customtables.menus', 'menus', array('control' => 'jform', 'load_data' => $loadData));

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
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_customtables.menus.' . (int)$id))
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
	 * Method to delete one or more records.
	 *
	 * @param array  &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   12.2
	 */
	public function delete(&$pks): bool
	{
		if (!parent::delete($pks)) {
			return false;
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
	 * @throws Exception
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
		$slug = 'my-fist-menu-item';


		if (empty($data['alias']))
			$data['alias'] = $slug;

		if (common::inputGetCmd('task') === 'save2copy')
			$data['alias'] = $data['alias'] . '-copy';

		if (empty($data['menutype']))
			$data['menutype'] = 'main';

		if ($data['title'] === null)
			$data['title'] = 'Table Title';

		/*
		$columns = '(`id`, `menutype`, `title`, `alias`, `note`, `path`,
                 `link`, `type`, `published`, `parent_id`, `level`, `component_id`,
                 `checked_out`, `checked_out_time`, `browserNav`, `access`, `img`, `template_style_id`, `params`,
                  `lft`, `rgt`,
                  `home`, `language`, `client_id`,
                  `publish_up`, `publish_down`)';
		$values = "(NULL, 'main', '" . $category_row->categoryname . "', '" . $slug . "', '', 'com-customtables-menu/" . $slug . "',
                 'index.php?option=com_customtables&view=adminmenu&category=" . $category_row->id . "', 'component', 1, 1, 1, " . $component_id . ", NULL, NULL, 0, 1, ' ', 0, '',
                 " . ($rgt + 1) . ", " . ($rgt + 2) . ",
                 0, '*', 1,
                 NULL, NULL)";
		$query = 'INSERT INTO `#__menu` ' . $columns . ' VALUES ' . $values;
		*/

		if (parent::save($data))
			return true;

		return false;
	}

	public function getTable($type = 'menus', $prefix = 'CustomtablesTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
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
	protected function canDelete($record): bool
	{
		if (!empty($record->id)) {
			$user = new CTUser();
			// The record has been set. Check the record permissions.
			return $user->authorise('menus.delete', 'com_customtables.menus.' . (int)$record->id);
		}
		return false;
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

	/*
	protected function prepareTable($table)
	{
		$user = new CTUser();

		if (isset($table->name)) {
			$table->name = htmlspecialchars_decode($table->name ?? '', ENT_QUOTES);
		}

		if (isset($table->alias) && empty($table->alias)) {
			$table->generateAlias();
		}

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
*/

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
	protected function canEditState($record): bool
	{
		$user = new CTUser();
		$recordId = (!empty($record->id)) ? $record->id : 0;

		if ($recordId) {
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('menus.edit.state', 'com_customtables.menus.' . (int)$recordId);
			if (!$permission && !is_null($permission)) {
				return false;
			}
		}
		// In the absence of better information, revert to the component permissions.
		return parent::canEditState($record);
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
		$data = Factory::getApplication()->getUserState('com_customtables.edit.menus.data', array());

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
