<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use CustomTables\Layouts;

// import Joomla controllerform library
jimport('joomla.application.component.controllerform');

/**
 * Records Controller
 */
 
class CustomtablesControllerRecords extends JControllerForm
{
	/**
	 * Current or most recently performed task.
	 *
	 * @var    string
	 * @since  12.2
	 * @note   Replaces _task.
	 */
	protected $task;

	public function __construct($config = array())
	{
		//$jinput = JFactory::getApplication()->input;
		//$tableid=JFactory::getApplication()->input->getint('tableid',0);

		parent::__construct($config);
	}

        /**
	 * Method override to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowAdd($data = array())
	{		// In the absense of better information, revert to the component permissions.
		return parent::allowAdd($data);
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	/*
	protected function checkEditId($data = array(), $key = 'id')
	{
		echo 'ddd';
		die;
	}
	*/
	
	
	
	protected function allowEdit($data = array(), $key = 'id')
	{
		// get user object.
		
		// get record id.
		//$recordId = isset($data[$key]) ? $data[$key] : 0;
		
		//To support char type record id
		$recordId 	= $this->input->getCmd('id', 0);
		
		//echo '$recordId_:'.$recordId;
		//die;
		
		
		//$id    	= $this->input->getCmd('id');

		if ($recordId)
		{
			$user = JFactory::getUser();
			
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit', 'com_customtables.records.' . $recordId);

			if (!$permission)
			{
				if ($user->authorise('core.edit.own', 'com_customtables.records.' . $recordId))
				{
					// Now test the owner is the user.
					$ownerId = (int) isset($data['created_by']) ? $data['created_by'] : 0;
					if (empty($ownerId))
					{
						// Need to do a lookup from the model.
						$record = $this->getModel()->getItem($recordId);

						if (empty($record))
						{
							return false;
						}
						$ownerId = $record->created_by;
					}

					// If the owner matches 'me' then allow.
					if ($ownerId == $user->id)
					{
						if ($user->authorise('core.edit.own', 'com_customtables'))
						{
							return true;
						}
					}
				}
				return false;
			}
		}
		// Since there is no permission, revert to the component permissions.

		$data['id'] = $recordId;
		
		return true;;//parent::allowEdit($data, $key);
	}
	
	/*
	function edit($data = array(), $key = 'id')
	{

		$tableid 	= $this->input->get('tableid', 0, 'int');
		$recordId 	= $this->input->getCmd('id', 0);
		
		
		// Redirect to the items screen.
		$this->setRedirect(
			JRoute::_(
				'index.php?option=' . $this->option . '&view=records&layout=edit&tableid='.(int)$tableid.'&id='.$recordId, false
				//'index.php?option=' . $this->option . '&view=listofrecords&layout=edit&tableid='.(int)$tableid, false
			)
		);
	}
	*/

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   12.2
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$tmpl   = $this->input->get('tmpl');
		$layout = $this->input->get('layout', 'edit', 'string');

		$ref 	= $this->input->get('ref', 0, 'string');
		//$refid 	= $this->input->getCmd('refid', 0);

		//To support char type record id
		$recordId 	= $this->input->getCmd('id', 0);
		//$recordId 	= $this->input->getInt('id', 0);
		
		//throw new Exception('stop here');

		$tableid= $this->input->getInt('tableid',0);
		// Setup redirect info.

		$append = '';

		if ($refid)
		{
			$append .= '&ref='.(string)$ref.'&refid='.$refid;
		}
		elseif ($ref)
		{
			$append .= '&ref='.(string)$ref;
		}

		if ($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		if ($layout)
		{
			$append .= '&layout=' . $layout;
		}

		if ($recordId)
		{
			$append .= '&' . $urlVar . '=' . $recordId;
		}

		$append .= '&tableid=' . $tableid;
		
		//This is to overwrite Joomla current record ID state value. Joomla converts ID to integer, but we want to support both int and cmd (A-Za-z0-9_-)
		$values = (array) $this->app->getUserState('com_customtables.edit.records.id');
		$values[] = $recordId;
		$this->app->setUserState('com_customtables.edit.records.id', $values);

		return $append;
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param   string  $key  The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @since   12.2
	 */
	public function cancel($key = null)
	{
		// get the referal details
		$tableid 	= $this->input->get('tableid', 0, 'int');

		$cancel = parent::cancel($key);
	
		// Redirect to the items screen.
		$this->setRedirect(
			JRoute::_(
				'index.php?option=' . $this->option . '&view=listofrecords&tableid='.(int)$tableid, false
				//'index.php?option=' . $this->option . '&view=listofrecords&layout=edit&tableid='.(int)$tableid, false
			)
		);

		return $cancel;
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   12.2
	 */
	public function save($key = null, $urlVar = null)
	{
		$tableid 	= $this->input->get('tableid', 0, 'int');
		if($tableid!=0)
		{
			$table=ESTables::getTableRowByID($tableid);
			if(!is_object($table) and $table==0)
			{
				JFactory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			}
			else
			{
				$tablename=$table->tablename;
			}
		}
		
		$recordid 	= $this->input->getCmd('id', 0);
		
		//Get Edit model
		$paramsArray=$this->getRecordParams($tableid,$tablename,$recordid);
		
		$_params= new JRegistry;
		$_params->loadArray($paramsArray);
		
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'edititem.php');
		$editModel = JModelLegacy::getInstance('EditItem', 'CustomTablesModel', $_params);
		$editModel->load($_params,true);
		
		$Layouts = new Layouts($editModel->ct);
			
		$editModel->pagelayout = $Layouts->createDefaultLayout_Edit($editModel->ct->Table->fields,false);

		$msg_='';
		
		if($this->task=='save2copy')
			$saved=$editModel->copy($msg_, $link);
		elseif($this->task=='save' or $this->task=='apply' or $this->task=='save2new')
			$saved=$editModel->store($msg_,$link);
		
		$redirect = 'index.php?option=' . $this->option;
				
		if($this->task=='apply')
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED'),'success');
			$redirect.='&view=records&layout=edit&id='.$recordid.'&tableid='.(int)$tableid;
		}
		elseif($this->task=='save2copy')
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_COPIED'),'success');
			$redirect.='&view=records&task=records.edit&tableid='.(int)$tableid.'&id='.$editModel->id;
		}
		elseif($this->task=='save2new')
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED'),'success');
			$redirect.='&view=records&task=records.edit&tableid='.(int)$tableid;
		}
		else
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED'),'success');
			$redirect.='&view=listofrecords&tableid='.(int)$tableid;
		}
		
		if ($saved)
		{
			// Redirect to the item screen.
			$this->setRedirect(
				JRoute::_(
					$redirect, false
				)
			);
		}
		
		return $saved;
	}
	
	
	protected function getRecordParams($tableid,$tablename,$recordid)
	{
		$paramsArray=array();

		$paramsArray['listingid']=$recordid;
		$paramsArray['estableid']=$tableid;
		$paramsArray['establename']=$tablename;

		return $paramsArray;
	}

	/**
	 * Function that allows child controller access to model data
	 * after the data has been saved.
	 *
	 * @param   JModel  &$model     The data model object.
	 * @param   array   $validData  The validated data.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	protected function postSaveHook(JModelLegacy $model, $validData = array())
	{
		return;
	}

}
