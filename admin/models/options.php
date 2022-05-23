<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

// Import Joomla! libraries

jimport('joomla.application.component.modeladmin');

class CustomTablesModelOptions extends JModelAdmin
{
    var $ct;
    public $typeAlias = 'com_customtables.options';
    public $es;
public $imagefolder;
        protected $text_prefix = 'COM_CUSTOMTABLES';//images/esoptimages

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->ct = new CT;
    }

    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_customtables.options', 'options', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        // The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
        if (Factory::getApplication()->input->get('a_id')) {
            $id = Factory::getApplication()->input->get('a_id', 0, 'INT');
        } // The back end uses id so we use that the rest of the time and set it to 0 by default.
        else {
            $id = Factory::getApplication()->input->get('id', 0, 'INT');
        }

        $user = Factory::getUser();

        // Check for existing item.
        // Modify the form based on Edit State access controls.
        if ($id != 0 && (!$user->authorise('core.edit.state', 'com_customtables.options.' . (int)$id))
            || ($id == 0 && !$user->authorise('core.edit.state', 'com_customtables'))) {
            // Disable fields for display.
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            //$form->setFieldAttribute('published', 'disabled', 'true');
            // Disable fields while saving.
            $form->setFieldAttribute('ordering', 'filter', 'unset');
            //$form->setFieldAttribute('published', 'filter', 'unset');
        }
        // If this is a new item insure the greated by is set.
        if (0 == $id) {
            // Set the created_by to this user
            $form->setValue('created_by', null, $user->id);
        }
        // Modify the form based on Edit Creaded By access controls.
        if (!$user->authorise('core.edit.created_by', 'com_customtables')) {
            // Disable fields for display.
            //$form->setFieldAttribute('created_by', 'disabled', 'true');
            // Disable fields for display.
            //$form->setFieldAttribute('created_by', 'readonly', 'true');
            // Disable fields while saving.
            //$form->setFieldAttribute('created_by', 'filter', 'unset');
        }
        // Modify the form based on Edit Creaded Date access controls.
        if (!$user->authorise('core.edit.created', 'com_customtables')) {
            // Disable fields for display.
            //$form->setFieldAttribute('created', 'disabled', 'true');
            // Disable fields while saving.
            //$form->setFieldAttribute('created', 'filter', 'unset');
        }
        // Only load these values if no id is found
        if (0 == $id) {
            // Set redirected field name
            $redirectedField = Factory::getApplication()->input->get('ref', null, 'STRING');
            // Set redirected field value
            $redirectedValue = Factory::getApplication()->input->get('refid', 0, 'INT');
            if (0 != $redirectedValue && $redirectedField) {
                // Now set the local-redirected field default value
                $form->setValue($redirectedField, null, $redirectedValue);
            }
        }

        return $form;
    }

    public function getScript()
    {
        //return JURI::root(true).'/administrator/components/com_customtables/models/forms/options.js';
    }

    public function save($data)//;//store($data)
    {
        $data_extra = Factory::getApplication()->input->get('jform', array(), 'ARRAY');

        $morethanonelang = false;
        $fields = Fields::getListOfExistingFields('#__customtables_options', false);
        foreach ($this->ct->Languages->LanguageList as $lang) {
            $id_title = 'title';
            if ($morethanonelang) {
                $id_title .= '_' . $lang->sef;

                if (!in_array($id_title, $fields))
                    Fields::addLanguageField('#__customtables_options', 'title', $id_title);

            }
            $data[$id_title] = $data_extra[$id_title];
            $morethanonelang = true; //More than one language installed
        }

        $optiontitle = $data['title'];

        if ($data['id'] == 0) {
            $optionname = strtolower(trim(preg_replace("/[^a-zA-Z0-9]/", "", $data['optionname'])));
            $data['optionname'] = $optionname;
        }

        $title = (trim($data['title']));

        if (parent::save($data))
            return true;

        return false;
    }

    function deleteItem()
    {
        $input = Factory::getApplication()->input;
        $cids = $input->post('cid', array(), 'ARRAY');

        $row = $this->getTable();

        if (count($cids)) {
            foreach ($cids as $cid) {
                if (!$row->delete($cid)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getTable($type = 'options', $prefix = 'CustomtablesTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    public function delete(&$pks)
    {

        if (!parent::delete($pks))
            return false;

        return true;
    }

    protected function canDelete($record)
    {
        if (!empty($record->id)) {
            //if ($record->published != -2)
            //{
            //return;
            //}

            $user = Factory::getUser();
            // The record has been set. Check the record permissions.
            return true;//$user->authorise('core.delete', 'com_customtables.options.' . (int) $record->id);
        }
        return false;
    }

    protected function canEditState($record)
    {
        $user = Factory::getUser();
        $recordId = (!empty($record->id)) ? $record->id : 0;

        if ($recordId) {
            // The record has been set. Check the record permissions.
            $permission = $user->authorise('core.edit.state', 'com_customtables.options.' . (int)$recordId);
            if (!$permission && !is_null($permission)) {
                return false;
            }
        }
        // In the absense of better information, revert to the component permissions.
        return parent::canEditState($record);
    }

    protected function allowEdit($data = array(), $key = 'id')
    {
        // Check specific edit permission then general edit permission.

        return Factory::getUser()->authorise('core.edit', 'com_customtables.options.' . ((int)isset($data[$key]) ? $data[$key] : 0)) or parent::allowEdit($data, $key);
    }

    protected function loadFormData()
    {

        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_customtables.edit.options.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }

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
				$item->tags->getTagIds($item->id, 'com_customtables.options');
			}
            */
        }

        return $item;
    }

    protected function getUniqeFields()
    {
        return false;
    }
}
