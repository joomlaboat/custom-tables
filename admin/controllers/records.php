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
	protected function allowEdit($data = array(), $key = 'id')
	{
		// get user object.
		$user = JFactory::getUser();
		// get record id.
		$recordId = (int) isset($data[$key]) ? $data[$key] : 0;


		if ($recordId)
		{
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit', 'com_customtables.records.' . (int) $recordId);
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
		return parent::allowEdit($data, $key);
	}

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
		$refid 	= $this->input->get('refid', 0, 'int');

		$tableid= $this->input->getint('tableid',0);
		// Setup redirect info.

		$append = '';

		if ($refid)
                {
			$append .= '&ref='.(string)$ref.'&refid='.(int)$refid;
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
		$this->ref 		= $this->input->get('ref', 0, 'word');
		$this->refid 	= $this->input->get('refid', 0, 'int');
		$tableid 	= $this->input->get('tableid', 0, 'int');

		// Redirect to the items screen.
		$this->setRedirect(
			JRoute::_(
				'index.php?option=' . $this->option . '&view=listofrecords&layout=edit&tableid='.(int)$tableid, false
			)
		);

		return;
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
		
		$recordid 	= $this->input->get('id', 0, 'int');
		
		//Get Edit model
		$paramsArray=$this->getRecordParams($tableid,$tablename,$recordid);
		
		$_params= new JRegistry;
		$_params->loadArray($paramsArray);
		
		//$config=array();
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'edititem.php');
		$editModel = JModelLegacy::getInstance('EditItem', 'CustomTablesModel', $_params);
		$editModel->load($_params,true);
		$editModel->pagelayout=ESLayouts::createDefaultLayout_Edit($editModel->esfields,false);

		// get the referal details
		/*
		$this->ref 		= $this->input->get('ref', 0, 'word');
		$this->refid 	= $this->input->get('refid', 0, 'int');
		*/
		

		$msg_='';

		if($this->task=='save2copy')
			$saved=$editModel->copy($msg_, $link);
		elseif($this->task=='save' or $this->task=='apply' or $this->task=='save2new')
			$saved=$editModel->store($msg_,$link);
		
		$redirect = 'index.php?option=' . $this->option;
				
		if($this->task=='apply')
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED'),'success');
			$redirect.='&view=records&layout=edit&id='.(int)$recordid.'&tableid='.(int)$tableid;
		}
		elseif($this->task=='save2copy')
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_COPIED'),'success');
			$redirect.='&view=records&task=records.edit&tableid='.(int)$tableid.'&id='.(int)$editModel->id;
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
