<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use Joomla\CMS\Factory;

// import Joomla modelform library
jimport('joomla.application.component.modeladmin');

/**
 * Customtables Records Model
 */
class CustomtablesModelRecords extends JModelAdmin
{
    var CT $ct;
    /**
     * The type alias for this content type.
     *
     * @var      string
     * @since    3.2
     */
    public $typeAlias = 'com_customtables.records';
    /**
     * @var        string    The prefix to use with controller messages.
     * @since   1.6
     */
    protected $text_prefix = 'COM_CUSTOMTABLES';

    public function getTable($type = 'records', $prefix = 'CustomtablesTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    public function getItem($pk = null)
    {
        return null;
    }

    public function getForm($data = array(), $loadData = true)
    {
        return null;
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
    public function delete(&$pks)
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
     * Method to test whether a record can be deleted.
     *
     * @param object $record A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.6
     */
    protected function canDelete($record)
    {
        if (!empty($record->id)) {
            $user = Factory::getUser();
            // The record has been set. Check the record permissions.
            return $user->authorise('core.delete', 'com_customtables.records.' . $record->id);
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
        $user = Factory::getUser();
        $recordId = (!empty($record->id)) ? $record->id : 0;

        if ($recordId) {
            // The record has been set. Check the record permissions.
            $permission = $user->authorise('core.edit.state', 'com_customtables.records.' . $recordId);
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

        return Factory::getUser()->authorise('core.edit', 'com_customtables.records.' . (isset($data[$key]) ? $data[$key] : 0)) or parent::allowEdit($data, $key);
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
        return null;//$data;
    }
}
