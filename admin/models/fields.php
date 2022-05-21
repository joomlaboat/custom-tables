<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage models/fields.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

// import Joomla modelform library
jimport('joomla.application.component.modeladmin');

/**
 * Customtables Fields Model
 */
class CustomtablesModelFields extends JModelAdmin
{
	var $ct;
	
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->ct = new CT;
	}
	
	/**
	 * @var        string    The prefix to use with controller messages.
	 * @since   1.6
	 */
	protected $text_prefix = 'COM_CUSTOMTABLES';

	/**
	 * The type alias for this content type.
	 *
	 * @var      string
	 * @since    3.2
	 */
	public $typeAlias = 'com_customtables.fields';

	public function getTable($type = 'fields', $prefix = 'CustomtablesTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{

			if (!empty($item->params) && !is_array($item->params))
			{
				// Convert the params field to an array.
				$registry = new Registry;
				$registry->loadString($item->params);
				$item->params = $registry->toArray();
			}

			if (!empty($item->metadata))
			{
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

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_customtables.fields', 'fields', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		// The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
		if (Factory::getApplication()->input->get('a_id'))
		{
			$id = Factory::getApplication()->input->get('a_id', 0, 'INT');
		}
		// The back end uses id so we use that the rest of the time and set it to 0 by default.
		else
		{
			$id = Factory::getApplication()->input->get('id', 0, 'INT');
		}

		$user = Factory::getUser();

		// Check for existing item.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_customtables.fields.' . (int) $id))
			|| ($id == 0 && !$user->authorise('core.edit.state', 'com_customtables')))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');
			// Disable fields while saving.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}
		// If this is a new item insure the greated by is set.
		if (0 == $id)
		{
			// Set the created_by to this user
			$form->setValue('created_by', null, $user->id);
		}
		// Modify the form based on Edit Creaded By access controls.
		if (!$user->authorise('core.edit.created_by', 'com_customtables'))
		{
			// Disable fields for display.
			$form->setFieldAttribute('created_by', 'disabled', 'true');
			// Disable fields for display.
			$form->setFieldAttribute('created_by', 'readonly', 'true');
			// Disable fields while saving.
			$form->setFieldAttribute('created_by', 'filter', 'unset');
		}
		// Modify the form based on Edit Creaded Date access controls.
		if (!$user->authorise('core.edit.created', 'com_customtables'))
		{
			// Disable fields for display.
			$form->setFieldAttribute('created', 'disabled', 'true');
			// Disable fields while saving.
			$form->setFieldAttribute('created', 'filter', 'unset');
		}
		// Only load these values if no id is found
		if (0 == $id)
		{
			// Set redirected field name
			$redirectedField = Factory::getApplication()->input->get('ref', null, 'STRING');
			// Set redirected field value
			$redirectedValue = Factory::getApplication()->input->get('refid', 0, 'INT');
			if (0 != $redirectedValue && $redirectedField)
			{
				// Now set the local-redirected field default value
				$form->setValue($redirectedField, null, $redirectedValue);
			}
		}

		return $form;
	}

	/**
	 * Method to get the script that have to be included on the form
	 *
	 * @return string	script files
	 */
	public function getScript()
	{
		return '/administrator/components/com_customtables/models/forms/fields.js';
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since   1.6
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id))
		{
			if ($record->published != -2)
			{
				return;
			}

			$user = Factory::getUser();
			// The record has been set. Check the record permissions.
			return $user->authorise('core.delete', 'com_customtables.fields.' . (int) $record->id);
		}
		return false;
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since   1.6
	 */
	protected function canEditState($record)
	{
		$user = Factory::getUser();
		$recordId = (!empty($record->id)) ? $record->id : 0;

		if ($recordId)
		{
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit.state', 'com_customtables.fields.' . (int) $recordId);
			if (!$permission && !is_null($permission))
			{
				return false;
			}
		}
		// In the absense of better information, revert to the component permissions.
		return parent::canEditState($record);
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param	array	$data	An array of input data.
	 * @param	string	$key	The name of the key for the primary key.
	 *
	 * @return	boolean
	 * @since	2.5
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Check specific edit permission then general edit permission.

		return Factory::getUser()->authorise('core.edit', 'com_customtables.fields.'. ((int) isset($data[$key]) ? $data[$key] : 0)) or parent::allowEdit($data, $key);
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   JTable  $table  A JTable object.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function prepareTable($table)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		if (isset($table->name))
		{
			$table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);
		}

/*
		if (isset($table->alias) && empty($table->alias))
		{
			$table->generateAlias();
		}
		*/

		if (empty($table->id))
		{
			$table->created = $date->toSql();
			// set the user
			if ($table->created_by == 0 || empty($table->created_by))
			{
				$table->created_by = $user->id;
			}
			// Set ordering to the last item if not set
			if (empty($table->ordering))
			{
				$db = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('MAX(ordering)')
					->from($db->quoteName('#__customtables_fields'));
				$db->setQuery($query);
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
		}
		else
		{
			$table->modified = $date->toSql();
			$table->modified_by = $user->id;
		}

		/*
		if (!empty($table->id))
		{
			// Increment the items version number.
			$table->version++;
		}
		*/
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
		$data = Factory::getApplication()->getUserState('com_customtables.edit.fields.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get the unique fields of this table.
	 *
	 * @return  mixed  An array of field names, boolean false if none is set.
	 *
	 * @since   3.0
	 */
	protected function getUniqeFields()
	{
		return false;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   12.2
	 */
	public function delete(&$pks)
	{
		if (!parent::delete($pks))
		{
			return false;
		}

		foreach($pks as $fieldid)
		{
			Fields::deleteField_byID($ct,$fieldid);
		}

		return true;
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   12.2
	 */
	public function publish(&$pks, $value = 1)
	{
		if (!parent::publish($pks, $value))
		{
			return false;
		}

		return true;
        }

	/**
	 * Method to perform batch operations on an item or a set of items.
	 *
	 * @param   array  $commands  An array of commands to perform.
	 * @param   array  $pks       An array of item ids.
	 * @param   array  $contexts  An array of item contexts.
	 *
	 * @return  boolean  Returns true on success, false on failure.
	 *
	 * @since   12.2
	 */
	public function batch($commands, $pks, $contexts)
	{
		// Sanitize ids.
		$pks = array_unique($pks);
		JArrayHelper::toInteger($pks);

		// Remove any values of zero.
		if (array_search(0, $pks, true))
		{
			unset($pks[array_search(0, $pks, true)]);
		}

		if (empty($pks))
		{
			$this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
			return false;
		}

		$done = false;

		// Set some needed variables.
		$this->user			= Factory::getUser();
		$this->table			= $this->getTable();
		$this->tableClassName		= get_class($this->table);
		$this->contentType		= new JUcmType;
		$this->type			= $this->contentType->getTypeByTable($this->tableClassName);
		$this->canDo			= CustomtablesHelper::getActions('fields');
		$this->batchSet			= true;

		if (!$this->canDo->get('core.batch'))
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));
			return false;
		}

		if ($this->type == false)
		{
			$type = new JUcmType;
			$this->type = $type->getTypeByAlias($this->typeAlias);
		}

		$this->tagsObserver = $this->table->getObserverOfClass('JTableObserverTags');

		if (!empty($commands['move_copy']))
		{
			$cmd = JArrayHelper::getValue($commands, 'move_copy', 'c');

			if ($cmd == 'c')
			{
				$result = $this->batchCopy($commands, $pks, $contexts);

				if (is_array($result))
				{
					foreach ($result as $old => $new)
					{
						$contexts[$new] = $contexts[$old];
					}
				}
				else
				{
					return false;
				}
			}
			elseif ($cmd == 'm' && !$this->batchMove($commands, $pks, $contexts))
			{
				return false;
			}

			$done = true;
		}

		if (!$done)
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));

			return false;
		}

		// Clear the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Batch copy items to a new category or current.
	 *
	 * @param   integer  $values    The new values.
	 * @param   array    $pks       An array of row IDs.
	 * @param   array    $contexts  An array of item contexts.
	 *
	 * @return  mixed  An array of new IDs on success, boolean false on failure.
	 *
	 * @since 12.2
	 */
	protected function batchCopy($values, $pks, $contexts)
	{
		if (empty($this->batchSet))
		{
			// Set some needed variables.
			$this->user 		= Factory::getUser();
			$this->table 		= $this->getTable();
			$this->tableClassName	= get_class($this->table);
			$this->canDo		= CustomtablesHelper::getActions('fields');
		}

		if (!$this->canDo->get('core.create') || !$this->canDo->get('core.batch'))
		{
			return false;
		}

		// get list of uniqe fields
		$uniqeFields = $this->getUniqeFields();
		// remove move_copy from array
		unset($values['move_copy']);

		// make sure published is set
		if (!isset($values['published']))
		{
			$values['published'] = 0;
		}
		elseif (isset($values['published']) && !$this->canDo->get('core.edit.state'))
		{
				$values['published'] = 0;
		}

		$newIds = array();
		// Parent exists so let's proceed
		while (!empty($pks))
		{
			// Pop the first ID off the stack
			$pk = array_shift($pks);

			$this->table->reset();

			// only allow copy if user may edit this item.
			if (!$this->user->authorise('core.edit', $contexts[$pk]))
			{
				// Not fatal error
				$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
				continue;
			}

			// Check that the row actually exists
			if (!$this->table->load($pk))
			{
				if ($error = $this->table->getError())
				{
					// Fatal error
					$this->setError($error);
					return false;
				}
				else
				{
					// Not fatal error
					$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
					continue;
				}
			}

			// Only for strings
			if (CustomtablesHelper::checkString($this->table->fieldtitle) && !is_numeric($this->table->fieldtitle))
			{
				$this->table->fieldtitle = $this->generateUnique('fieldtitle',$this->table->fieldtitle);
			}

			// insert all set values
			if (CustomtablesHelper::checkArray($values))
			{
				foreach ($values as $key => $value)
				{
					if (strlen($value) > 0 && isset($this->table->$key))
					{
						$this->table->$key = $value;
					}
				}
			}

			// update all uniqe fields
			if (CustomtablesHelper::checkArray($uniqeFields))
			{
				foreach ($uniqeFields as $uniqeField)
				{
					$this->table->$uniqeField = $this->generateUnique($uniqeField,$this->table->$uniqeField);
				}
			}

			// Reset the ID because we are making a copy
			$this->table->id = 0;

			// TODO: Deal with ordering?
			// $this->table->ordering = 1;

			// Check the row.
			if (!$this->table->check())
			{
				$this->setError($this->table->getError());

				return false;
			}

			if (!empty($this->type))
			{
				$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
			}

			// Store the row.
			if (!$this->table->store())
			{
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

	protected function batchMove($values, $pks, $contexts)
	{
		if (empty($this->batchSet))
		{
			// Set some needed variables.
			$this->user		= Factory::getUser();
			$this->table		= $this->getTable();
			$this->tableClassName	= get_class($this->table);
			$this->canDo		= CustomtablesHelper::getActions('fields');
		}

		if (!$this->canDo->get('core.edit') && !$this->canDo->get('core.batch'))
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
			return false;
		}

		// make sure published only updates if user has the permission.
		if (isset($values['published']) && !$this->canDo->get('core.edit.state'))
		{
			unset($values['published']);
		}
		// remove move_copy from array
		unset($values['move_copy']);

		// Parent exists so we proceed
		foreach ($pks as $pk)
		{
			if (!$this->user->authorise('core.edit', $contexts[$pk]))
			{
				$this->setError(JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
				return false;
			}

			// Check that the row actually exists
			if (!$this->table->load($pk))
			{
				if ($error = $this->table->getError())
				{
					// Fatal error
					$this->setError($error);
					return false;
				}
				else
				{
					// Not fatal error
					$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
					continue;
				}
			}

			// insert all set values.
			if (CustomtablesHelper::checkArray($values))
			{
				foreach ($values as $key => $value)
				{
					// Do special action for access.
					if ('access' === $key && strlen($value) > 0)
					{
						$this->table->$key = $value;
					}
					elseif (strlen($value) > 0 && isset($this->table->$key))
					{
						$this->table->$key = $value;
					}
				}
			}

			// Check the row.
			if (!$this->table->check())
			{
				$this->setError($this->table->getError());
				return false;
			}

			if (!empty($this->type))
			{
				$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
			}

			// Store the row.
			if (!$this->table->store())
			{
				$this->setError($this->table->getError());
				return false;
			}
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to the the field name.
	 *
	 * @param   int		$tableid  The Table ID.
	 * @param   string	$fieldname  The Field name.
	 *
	 * @return  string  New not occupied field name.
	 *
	 * @since   1.6
	 */

	public function checkFieldName($tableid,$fieldname)//,&$typeparams)
	{
		$new_fieldname=$fieldname;

		do
		{
			$already_exists=Fields::getFieldID($tableid, $new_fieldname);

			if($already_exists!=0)
			{
				$new_fieldname.='copy';
			}
			else
				break;

		}while(1==1);

		return $new_fieldname;
	}

	public function save($data)
	{
		$input	= Factory::getApplication()->input;

		$data_extra = $input->get( 'jform',array(),'ARRAY');

		//clean field name
		$esfieldname=strtolower(trim(preg_replace("/[^a-zA-Z0-9]/", "", $data_extra['fieldname'])));
		if(strlen ( $esfieldname )>40)
			$esfieldname=substr($esfieldname, 0, 40);


		$tableid=$data_extra['tableid'];
		$fieldid=$data['id'];
		
		if($fieldid==0)
			$esfieldname=$this->checkFieldName($tableid,$esfieldname);

		$data['fieldname']=$esfieldname;

		//Add language fields to the fields table if necessary
		
		$morethanonelang=false;
		$fields=Fields::getListOfExistingFields('#__customtables_fields',false);
		foreach($this->ct->Languages->LanguageList as $lang)
		{
				$id_title='fieldtitle';
				$id_description='description';

				if($morethanonelang)
				{
					$id_title.='_'.$lang->sef;
					$id_description.='_'.$lang->sef;

					if(!in_array($id_title,$fields))
						Fields::addLanguageField('#__customtables_fields','fieldtitle',$id_title);

					if(!in_array($id_description,$fields))
						Fields::addLanguageField('#__customtables_fields','description',$id_description);

				}

				$data[$id_title] = $data_extra[$id_title];
				$data[$id_description] = $data_extra[$id_description];
				$morethanonelang=true; //More than one language installed
		}

		// Alter the uniqe field for save as copy
		if ($input->get('task') === 'save2copy')
		{
			// Automatic handling of other unique fields
			$uniqeFields = $this->getUniqeFields();
			if (CustomtablesHelper::checkArray($uniqeFields))
			{
				foreach ($uniqeFields as $uniqeField)
				{
					$data[$uniqeField] = $this->generateUnique($uniqeField,$data[$uniqeField]);
				}
			}
		}
		else
		{
			//Process image type field
		}

		$table_row = ESTables::getTableRowByID($tableid);
		
		if(!is_object($table_row))
		{
			Factory::getApplication()->enqueueMessage('Table not found', 'error');
			return false;
		}
		
		if($table_row->customtablename=='') //do not create fields to third-purty tables
		{
			if(!$this->update_physical_field($table_row,$fieldid,$data))
			{
				//Cannot create
				return false;
			}
		}

		if (parent::save($data))
		{
			return true;
		}
		return false;
	}

	protected function update_physical_field($table_row,$fieldid,$data)
	{
		$db = Factory::getDBO();

		$realtablename=$table_row->realtablename;//$db->getPrefix().'customtables_table_'.$establename;
		$realtablename=str_replace('#__',$db->getPrefix(),$realtablename);

		if($fieldid!=0)
		{
			$fieldrow=Fields::getFieldRow($fieldid);
			
			$ex_type=$fieldrow->type;
			$ex_typeparams=$fieldrow->typeparams;
			$realfieldname=$fieldrow->realfieldname;
		}
		else
		{
			$ex_type='';
			$ex_typeparams='';
			$realfieldname='';
			
			if($table_row->customtablename=='')
				$realfieldname='es_'.$data['fieldname']; //Tablerow is not loaded and custom tables name not set so we assume that the field starts with es_
		}
		
		$new_typeparams=$data['typeparams'];
		$fieldtitle=$data['fieldtitle'];
		
		//---------------------------------- Convert Field

		$new_type=$data['type'];
		$PureFieldType=Fields::getPureFieldType($new_type, $new_typeparams);

		if($realfieldname!='')
			$fieldfound=Fields::checkIfFieldExists($realtablename,$realfieldname,false);
		else
			$fieldfound=false;

		if($fieldid!=0 and $fieldfound)
		{
			$ex_PureFieldType=Fields::getPureFieldType($ex_type, $ex_typeparams);

			if($PureFieldType=='')
			{
				//do nothing. field can be deleted
				$convert_ok=true;
			}
			else
				$convert_ok=Fields::ConvertFieldType($realtablename,$realfieldname,$ex_type, $ex_typeparams, $ex_PureFieldType, $new_type, $new_typeparams,$PureFieldType,$fieldtitle);

			if(!$convert_ok)
			{
				Factory::getApplication()->enqueueMessage('Cannot convert the type.', 'error');
				return false;
			}

			$input	= Factory::getApplication()->input;
			$extratask = '';

			if($ex_type==$new_type and $new_type=='image' and ($ex_typeparams !=$new_typeparams or strpos($new_typeparams,'|delete')!==false))
				$extratask = 'updateimages'; //Resize all images if neaded
			
			if($ex_type==$new_type and $new_type=='file' and $ex_typeparams !=$new_typeparams)
				$extratask = 'updatefiles';

			if($ex_type==$new_type and $new_type=='imagegallery' and $ex_typeparams !=$new_typeparams)
				$extratask = 'updateimagegallery'; //Resize or move all images in the gallery if neaded
			
			if($ex_type==$new_type and $new_type=='filebox' and $ex_typeparams !=$new_typeparams)
				$extratask = 'updatefilebox'; //Resize or move all images in the gallery if neaded
			
			if($extratask != '')
			{
				$input->set('extratask',$extratask);
				$input->set('old_typeparams',base64_encode($ex_typeparams));
				$input->set('new_typeparams',base64_encode($new_typeparams));
				$input->set('fieldid',$fieldid);
			}
		}
		//---------------------------------- end convert field

		if($fieldid==0 or !$fieldfound)
		{
			//Add Field
			
			Fields::addField($ct,$realtablename,$realfieldname,$new_type,$PureFieldType,$fieldtitle);
		}

		if($new_type=='sqljoin')
		{
				//Create Index if needed
				Fields::addIndexIfNotExist($realtablename,$realfieldname);

				//Add Foreign Key
				$msg='';
				Fields::addForeignKey($realtablename,$realfieldname,$new_typeparams,'','id',$msg);
		}

		if($new_type=='user' or $new_type=='userid')
		{
				//Create Index if needed
				Fields::addIndexIfNotExist($realtablename,$realfieldname);

				//Add Foreign Key
				$msg='';
				Fields::addForeignKey($realtablename,$realfieldname,'','#__users','id',$msg);
		}
		return true;
	}

	/**
	 * Method to generate a unique value.
	 *
	 * @param   string  $field name.
	 * @param   string  $value data.
	 *
	 * @return  string  New value.
	 *
	 * @since   3.0
	 */
	protected function generateUnique($field,$value)
	{
		// set field value unique
		$table = $this->getTable();

		while ($table->load(array($field => $value)))
		{
			$value = JString::increment($value);
		}

		return $value;
	}

	/**
	 * Method to change the title
	 *
	 * @param   string   $title   The title.
	 *
	 * @return	array  Contains the modified title and alias.
	 *
	 */
	protected function _generateNewTitle($title)
	{

		// Alter the title
		$table = $this->getTable();

		while ($table->load(array('title' => $title)))
		{
			$title = JString::increment($title);
		}

		return $title;
	}
}
