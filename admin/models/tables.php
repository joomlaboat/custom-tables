<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage models/tables.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

// import Joomla modelform library
jimport('joomla.application.component.modeladmin');
require_once(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
/**
 * Customtables Tables Model
 */
class CustomtablesModelTables extends JModelAdmin
{
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
	public $typeAlias = 'com_customtables.tables';

	/**
	 * Returns a Table object, always creating it
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A database object
	 *
	 * @since   1.6
	 */
	public function getTable($type = 'tables', $prefix = 'CustomtablesTable', $config = array())
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

			if (!empty($item->id))
			{
				$item->tags = new JHelperTags;
				$item->tags->getTagIds($item->id, 'com_customtables.tables');
			}
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
		$form = $this->loadForm('com_customtables.tables', 'tables', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$jinput = JFactory::getApplication()->input;

		// The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
		if (JFactory::getApplication()->input->get('a_id'))
		{
			$id = JFactory::getApplication()->input->get('a_id', 0, 'INT');
		}
		// The back end uses id so we use that the rest of the time and set it to 0 by default.
		else
		{
			$id = JFactory::getApplication()->input->get('id', 0, 'INT');
		}

		$user = JFactory::getUser();

		// Check for existing item.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_customtables.tables.' . (int) $id))
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
			$redirectedField = JFactory::getApplication()->input->get('ref', null, 'STRING');
			// Set redirected field value
			$redirectedValue = JFactory::getApplication()->input->get('refid', 0, 'INT');
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
		return JURI::root(true).'/administrator/components/com_customtables/models/forms/tables.js';
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

			$user = JFactory::getUser();
			// The record has been set. Check the record permissions.
			return $user->authorise('core.delete', 'com_customtables.tables.' . (int) $record->id);
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
		$user = JFactory::getUser();
		$recordId = (!empty($record->id)) ? $record->id : 0;

		if ($recordId)
		{
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit.state', 'com_customtables.tables.' . (int) $recordId);
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

		return JFactory::getUser()->authorise('core.edit', 'com_customtables.tables.'. ((int) isset($data[$key]) ? $data[$key] : 0)) or parent::allowEdit($data, $key);
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
		$date = JFactory::getDate();
		$user = JFactory::getUser();

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
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select('MAX(ordering)')
					->from($db->quoteName('#__customtables_tables'));
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

		if (!empty($table->id))
		{
			// Increment the items version number.
			$table->version++;
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
		$data = JFactory::getApplication()->getUserState('com_customtables.edit.tables.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
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
		$tables=array();
		foreach($pks as $tableid)
		{
			$tablename=ESTables::getTableName($tableid);
			$tables[]=$tablename;
		}

		if (!parent::delete($pks))
			return false;


		$db = JFactory::getDBO();
		foreach($tables as $tablename)
		{
			$query = 'DROP TABLE IF EXISTS #__customtables_table_'.$tablename;
			$db->setQuery( $query );
			if (!$db->query())    die ( $db->stderr());
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
		$this->user			= JFactory::getUser();
		$this->table			= $this->getTable();
		$this->tableClassName		= get_class($this->table);
		$this->contentType		= new JUcmType;
		$this->type			= $this->contentType->getTypeByTable($this->tableClassName);
		$this->canDo			= CustomtablesHelper::getActions('tables');
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
					$pks = array_values($result);
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
		die;
		
	}

	/**
	 * Batch move items to a new category
	 *
	 * @param   integer  $value     The new category ID.
	 * @param   array    $pks       An array of row IDs.
	 * @param   array    $contexts  An array of item contexts.
	 *
	 * @return  boolean  True if successful, false otherwise and internal error is set.
	 *
	 * @since 12.2
	 */
	protected function batchMove($values, $pks, $contexts)
	{
		die;
	}

	public function checkTableName($tablename)
	{
		$new_tablename=$tablename;
		$i=1;
		do
		{

			$already_exists=ESTables::getTableID($tablename);
			if($already_exists!=0)
			{
				$pair=explode('_',$tablename);

				$cleantablename=$pair[0];
				$new_tablename=$cleantablename.'_'.$i;
				$i++;
			}
			else
				break;

		}while(1==1);

		return $new_tablename;
	}

	public function save($data)
	{
		$conf = JFactory::getConfig();

		$database = $conf->get('db');
		$dbprefix = $conf->get('dbprefix');

		$jinput	= JFactory::getApplication()->input;
		$filter	= JFilterInput::getInstance();

		$db = JFactory::getDBO();

		$data_extra = JFactory::getApplication()->input->get( 'jform',array(),'ARRAY');

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fields.php');
		$LangMisc	= new ESLanguages;
		$languages=$LangMisc->getLanguageList();

		$morethanonelang=false;
		$fields=ESFields::getListOfExistingFields('#__customtables_tables',false);
		foreach($languages as $lang)
		{
			$id_title='tabletitle';
			$id_desc='description';
			if($morethanonelang)
			{
				$id_title.='_'.$lang->sef;
				$id_desc.='_'.$lang->sef;

				if(!in_array($id_title,$fields))
					ESFields::addLanguageField('#__customtables_tables','tabletitle',$id_title);

				if(!in_array($id_desc,$fields))
					ESFields::addLanguageField('#__customtables_tables','description',$id_desc);
			}

			$data[$id_title] = $data_extra[$id_title];
			$data[$id_desc] = $data_extra[$id_desc];
			$morethanonelang=true; //More than one language installed
		}

		$tabletitle=$data['tabletitle'];

		$tableid=(int)$data['id'];

		$tablename=strtolower(trim(preg_replace("/[^a-zA-Z]/", "", $data['tablename'])));
		if($tableid==0)
			$tablename=$this->checkTableName($tablename);


		$data['tablename']=$tablename;

		if($tableid!=0)
		{

			$this->getRenameTableIfNeeded($tableid,$database,$dbprefix,$tablename);

		}

		// Alter the uniqe field for save as copy
		if (JFactory::getApplication()->input->get('task') === 'save2copy')
		{
			$originaltableid=JFactory::getApplication()->input->getInt( 'originaltableid',0);

			if($originaltableid!=0)
			{
				$old_tablename=ESTables::getTableName($originaltableid);	
				
				if($old_tablename==$tablename)
					$tablename='copy_of_'.$tablename;
				
				while($this->checkIfTableNameExists($tablename)!=0)
					$tablename='copy_of_'.$tablename;
				
				$data['tablename']=$tablename;
			}
		}

		if (parent::save($data))
		{
			if($originaltableid!=0)
				$this->copyTable($originaltableid,$tablename,$old_tablename);

			$this->processDBTable($database,$dbprefix,$tablename,$tabletitle);

			return true;
		}
		return false;
	}

	function getRenameTableIfNeeded($tableid,$database,$dbprefix,$tablename)
	{
		$db = JFactory::getDBO();
		$old_tablename=ESTables::getTableName($tableid);


		if($old_tablename!=$tablename)
		{
				//rename table
			$tablestatus=ESTables::getTableStatus($database,$dbprefix,$old_tablename);

			if(count($tablestatus)>0)
			{
				$query = 'RENAME TABLE '.$db->quoteName($database.'.'.$dbprefix.'customtables_table_'.$old_tablename).' TO '.$db->quoteName($database.'.'.$dbprefix.'customtables_table_'.$tablename).';';

				$db->setQuery( $query );

				if (!$db->query()) {
					$this->setError( $db->getErrorMsg() );
					return false;
				}
			}
		}
	}


	function processDBTable($database,$dbprefix,$tablename,$tabletitle)
	{
		$db = JFactory::getDBO();

		$rows2=ESTables::getTableStatus($database,$dbprefix,$tablename);


		if(count($rows2)>0)
		{

				$row2=$rows2[0];

				$table_name=$dbprefix.'customtables_table_'.$tablename;

				if($row2->Engine!='InnoDB')
				{
					$query = 'ALTER TABLE '.$table_name.' ENGINE = InnoDB';
					$db->setQuery( $query );
					if (!$db->query()) {
						$this->setError( $db->getErrorMsg() );
						return false;
					}


				}


				$query = 'ALTER TABLE '.$table_name.' COMMENT = "'.$tabletitle.'";';
				$db->setQuery( $query );
				if (!$db->query()) {
					$this->setError( $db->getErrorMsg() );
					return false;
				}

		}
		else
		{


			$query = '
			CREATE TABLE IF NOT EXISTS #__customtables_table_'.$tablename.'
			(
				id int(10) unsigned NOT NULL auto_increment,
				published tinyint(1) DEFAULT "1",
				PRIMARY KEY  (id)
			) ENGINE=InnoDB COMMENT="'.$tabletitle.'" DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
			';

			$db->setQuery( $query );
			if (!$db->query())
			{
				$this->setError( $db->getErrorMsg() );
				return false;
			}

		}


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


	function checkIfTableNameExists($tablename)
	{
		$db = $this->getDBO();

		$query = 'SELECT id FROM #__customtables_tables WHERE tablename='.$db->quote($tablename).' LIMIT 1';
		
		$db->setQuery( $query );
		if (!$db->query())    die ( $db->stderr());
		$rows=$db->loadAssocList();
		if(count($rows)!=1)
			return 0;
		
		return $rows[0]['id'];
	}

	public function copyTable($originaltableid,$new_table,$old_table)
	{
		//Copy Table
		$db = $this->getDBO();

		//get ID of new table
		$new_table_id=$this->checkIfTableNameExists($new_table);
		
		$query = 'CREATE TABLE #__customtables_table_'.$new_table.' SELECT * FROM #__customtables_table_'.$old_table.'';

		$db->setQuery( $query );
		if (!$db->query()) {
			$this->setError( $db->getErrorMsg() );
			//return false;
		}

		//Copy Fields
		$fields=array('fieldname','type','typeparams','ordering','defaultvalue','allowordering','parentid','isrequired','hidden','valuerule');

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
		$LangMisc	= new ESLanguages;
		$languages=$LangMisc->getLanguageList();

		$morethanonelang=false;
		foreach($languages as $lang)
		{
			$id='fieldtitle';
			if($morethanonelang)
				$id.='_'.$lang->sef;
			else
				$morethanonelang=true;
			
			$fields[]=$id;
		}

		$query = 'SELECT * FROM #__customtables_fields WHERE published=1 AND tableid='.$originaltableid;
		$db->setQuery( $query );
		if (!$db->query())    die ( $db->stderr());
		$rows=$db->loadAssocList();

		if(count($rows)==0)
			die('Original table has no fields.');

		foreach($rows as $row)
		{

			$inserts=array('tableid='.$new_table_id);
			foreach($fields as $fld)
			{
				$value=$row[$fld];
				$value=str_replace('"','\"',$value);

				$inserts[]=''.$fld.'="'.$value.'"';
			}

			$iq='INSERT INTO #__customtables_fields SET '.implode(', ',$inserts);
			$db->setQuery( $iq );
			$db->execute();
		}
		return true;
	}

	public function export(&$cids)
	{
		$link='';

		$tables=array();
		$output=array();

		foreach( $cids as $id )
	    {

			$item =$this->getTable();
			$item->load( $id );

			$db = $this->getDBO();


			$tables[]=$item->tablename;

			//get table
			$s1='(SELECT categoryname FROM #__customtables_categories WHERE #__customtables_categories.id=#__customtables_tables.tablecategory) AS categoryname';
			$query = 'SELECT *,'.$s1.' FROM #__customtables_tables WHERE published=1 AND id='.$id.' LIMIT 1';
			$db->setQuery( $query );
			if (!$db->query())    echo ( $db->stderr());
			$rows=$db->loadAssocList();
			if(count($rows)==1)
			{

				$table=$rows[0];

				//get fields
				$query = 'SELECT * FROM #__customtables_fields WHERE published=1 AND tableid='.$id.'';
				$db->setQuery( $query );
				if (!$db->query())    echo ( $db->stderr());
				$fields=$db->loadAssocList();

				//get layouts
				$query = 'SELECT * FROM #__customtables_layouts WHERE published=1 AND tableid='.$id.'';
				$db->setQuery( $query );
				if (!$db->query())    echo ( $db->stderr());
				$layouts=$db->loadAssocList();

				//get menu items
				$wheres=array();
				$wheres[]='published=1';
				$wheres[]='INSTR(link,"index.php?option=com_customtables&view=")';
				$wheres[]='INSTR(params,\'"establename":"'.$item->tablename.'"\')';

				$query = 'SELECT * FROM #__menu WHERE '.implode(' AND ',$wheres);

				$db->setQuery( $query );
				if (!$db->query())    echo ( $db->stderr());
				$menu=$db->loadAssocList();


				if(intval($table['allowimportcontent'])==1)
				{
					$tablename=$table['tablename'];

					$query = 'SELECT * FROM #__customtables_table_'.$tablename.' WHERE published=1';
					$db->setQuery( $query );
					if (!$db->query())    echo ( $db->stderr());
					$records=$db->loadAssocList();

					$output[]=['table'=>$table,'fields'=>$fields,'layouts'=>$layouts,'records'=>$records,'menu'=>$menu];
				}
				else
					$output[]=['table'=>$table,'fields'=>$fields,'layouts'=>$layouts,'menu'=>$menu];
			}
		}

		if(count($output)>0)
		{
			$output_str='<customtablestableexport>'.json_encode($output);

			$tmp_path=JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR;
			$filename=implode('_',$tables);
			$filename_available=$filename;
			$a='';
			$i=0;
			do{
				if(!file_exists($tmp_path.$filename.$a.'.txt'))
				{
					$filename_available=$filename.$a.'.txt';
					break;
				}

				$i++;
				$a=$i.'';

			}while(1==1);

			$link='/tmp/'.$filename_available;
			file_put_contents($tmp_path.$filename_available, $output_str);
			$output_str=null;
		}


		return $link;
	}

}
