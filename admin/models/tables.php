<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage models/tables.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Fields;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// import Joomla modelform library
jimport('joomla.application.component.modeladmin');

class CustomtablesModelTables extends JModelAdmin
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

        $this->ct = new CT;
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
        $form = $this->loadForm('com_customtables.tables', 'tables', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        // The front end calls this model and uses a_id to avoid id clashes, so we need to check for that first.
        if ($this->ct->Env->jinput->get('a_id')) {
            $id = $this->ct->Env->jinput->get('a_id', 0, 'INT');
        } // The back end uses id, so we use that the rest of the time and set it to 0 by default.
        else {
            $id = $this->ct->Env->jinput->get('id', 0, 'INT');
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
        // If this is a new item insure the greated by is set.
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
            $redirectedField = $this->ct->Env->jinput->get('ref', null, 'STRING');
            // Set redirected field value
            $redirectedValue = $this->ct->Env->jinput->get('refid', 0, 'INT');
            if (0 != $redirectedValue && $redirectedField) {
                // Now set the local-redirected field default value
                $form->setValue($redirectedField, null, $redirectedValue);
            }
        }

        return $form;
    }

    public function getScript()
    {
        return JURI::root(true) . '/administrator/components/com_customtables/models/forms/tables.js';
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
        $db = Factory::getDBO();

        foreach ($pks as $tableid) {
            $table_row = ESTables::getTableRowByID($tableid);

            if (isset($table_row->tablename) and (!isset($table_row->customtablename) or $table_row->customtablename === null)) // do not delete third-party tables
            {
                $realtablename = $db->getPrefix() . 'customtables_table_' . $table_row->tablename; //not available for custom tablenames

                if ($db->serverType == 'postgresql')
                    $query = 'DROP TABLE IF EXISTS ' . $realtablename;
                else
                    $query = 'DROP TABLE IF EXISTS ' . $db->quoteName($realtablename);

                $db->setQuery($query);
                $db->execute();

                if ($db->serverType == 'postgresql') {
                    $query = 'DROP SEQUENCE IF EXISTS ' . $realtablename . '_seq CASCADE';
                    $db->setQuery($query);
                    $db->execute();
                }
            }
        }

        if (!parent::delete($pks))
            return false;

        Fields::deleteTablelessFields();

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
            $this->setError(Text::_('JGLOBAL_NO_ITEM_SELECTED'));
            return false;
        }

        $done = false;

        // Set some needed variables.
        $this->user = Factory::getUser();
        $this->table = $this->getTable();
        $this->tableClassName = get_class($this->table);
        $this->contentType = new JUcmType;
        $this->type = $this->contentType->getTypeByTable($this->tableClassName);
        $this->canDo = CustomtablesHelper::getActions('tables');
        $this->batchSet = true;

        if (!$this->canDo->get('core.batch')) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));
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
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));

            return false;
        }

        // Clear the cache
        $this->cleanCache();

        return true;
    }

    public function getTable($type = 'tables', $prefix = 'CustomtablesTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
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
        die;
    }

    /**
     * Batch move items to a new category
     *
     * @param integer $value The new category ID.
     * @param array $pks An array of row IDs.
     * @param array $contexts An array of item contexts.
     *
     * @return  boolean  True if successful, false otherwise and internal error is set.
     *
     * @since 12.2
     */
    protected function batchMove($values, $pks, $contexts)
    {
        die;
    }

    public function save($data)
    {
        $conf = Factory::getConfig();

        $database = $conf->get('db');
        $dbPrefix = $conf->get('dbprefix');

        $data_extra = $this->ct->Env->jinput->get('jform', array(), 'ARRAY');

        $moreThanOneLanguage = false;

        $fields = Fields::getListOfExistingFields('#__customtables_tables', false);
        foreach ($this->ct->Languages->LanguageList as $lang) {
            $id_title = 'tabletitle';
            $id_desc = 'description';
            if ($moreThanOneLanguage) {
                $id_title .= '_' . $lang->sef;
                $id_desc .= '_' . $lang->sef;

                if (!in_array($id_title, $fields))
                    Fields::addLanguageField('#__customtables_tables', 'tabletitle', $id_title);

                if (!in_array($id_desc, $fields))
                    Fields::addLanguageField('#__customtables_tables', 'description', $id_desc);
            }

            $data[$id_title] = $data_extra[$id_title];
            $data[$id_desc] = $data_extra[$id_desc];
            $moreThanOneLanguage = true; //More than one language installed
        }

        $tabletitle = $data['tabletitle'];
        $tableid = (int)$data['id'];

        if (function_exists("transliterator_transliterate"))
            $tablename = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", $data['tablename']);
        else
            $tablename = $data['tablename'];

        $tablename = strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $tablename)));

        //If it's a new table, check if field name is unique or add number "_1" if it's not.
        if ($tableid == 0)
            $tablename = ESTables::checkTableName($tablename);

        $data['tablename'] = $tablename;

        if ($tableid != 0 and (string)$data['customtablename'] == '')//do not rename real table if it's a third-party table - not part of the Custom Tables
        {
            ESTables::renameTableIfNeeded($tableid, $database, $dbPrefix, $tablename);
        }

        $old_tablename = '';

        // Alter the unique field for save as copy
        if ($this->ct->Env->jinput->get('task') === 'save2copy') {
            $originalTableId = $this->ct->Env->jinput->getInt('originaltableid', 0);

            if ($originalTableId != 0) {
                $old_tablename = ESTables::getTableName($originalTableId);

                if ($old_tablename == $tablename)
                    $tablename = 'copy_of_' . $tablename;

                while (ESTables::getTableID($tablename) != 0)
                    $tablename = 'copy_of_' . $tablename;

                $data['tablename'] = $tablename;
            }
        }

        if ($data['customtablename'] == '-new-') {
            $data['customtablename'] = $tablename;
            $data['customidfield'] = 'id';

            if (parent::save($data)) {

                ESTables::createTableIfNotExists($database, $dbPrefix, $tablename, $tabletitle, $data['customtablename']);
                return true;
            }
        } else {
            if (parent::save($data)) {
                $originalTableId = $this->ct->Env->jinput->getInt('originaltableid', 0);

                if ($originalTableId != 0 and $old_tablename != '')
                    ESTables::copyTable($this->ct, $originalTableId, $tablename, $old_tablename, $data['customtablename']);

                ESTables::createTableIfNotExists($database, $dbPrefix, $tablename, $tabletitle, $data['customtablename']);

                //Add fields if it's a third-party table and no fields added yet.
                if ($data['customtablename'] !== null and $data['customtablename'] != '')
                    ESTables::addThirdPartyTableFieldsIfNeeded($database, $tablename, $data['customtablename']);

                return true;
            }
        }
        return false;
    }

    public function copyTable($originaltableid, $new_table, $old_table)
    {
        return ESTables::copyTable($this->ct, $originaltableid, $new_table, $old_table);
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

        return Factory::getUser()->authorise('core.edit', 'com_customtables.tables.' . ((int)isset($data[$key]) ? $data[$key] : 0)) or parent::allowEdit($data, $key);
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
        $date = Factory::getDate();

        if (isset($table->name)) {
            $table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);
        }

        if (empty($table->id)) {
            $table->created = $date->toSql();
            // set the user
            if ($table->created_by == 0 || empty($table->created_by)) {
                $table->created_by = $this->ct->Env->user->id;
            }
        } else {
            $table->modified = $date->toSql();
            $table->modified_by = $this->ct->Env->user->id;
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
     * @return  mixed  Object on success, false on failure.
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
				$item->tags->getTagIds($item->id, 'com_customtables.tables');
			}
            */
        }

        return $item;
    }
}
