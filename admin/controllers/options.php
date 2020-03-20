<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.8.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		view.html.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controllerform library
jimport('joomla.application.component.controllerform');

class CustomTablesControllerOptions extends JControllerForm
{
	protected $task;

	public function __construct($config = array())
	{
		$this->view_list = 'Listofoptions'; // safeguard for setting the return view listing to the main view.
		parent::__construct($config);
	}

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
			$permission = $user->authorise('core.edit', 'com_customtables.options.' . (int) $recordId);
			if (!$permission)
			{
				if ($user->authorise('core.edit.own', 'com_customtables.options.' . $recordId))
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

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$tmpl   = $this->input->get('tmpl');
		$layout = $this->input->get('layout', 'edit', 'string');

		$ref 	= $this->input->get('ref', 0, 'string');
		$refid 	= $this->input->get('refid', 0, 'int');

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

		return $append;
	}
	/*
    function display()
	{
		$jinput = JFactory::getApplication()->input;
		$task=$jinput->getCmd['task'];

		if($task=='options.add' or $task=='add' )
		{
			$this->setRedirect( 'index.php?option=com_customtables&view=options&layout=edit');
			return true;
		}

		if($task=='options.edit' or $task=='edit' )
		{
			$cid = JRequest::getVar( 'cid', array(), 'post', 'array' );

			if (!count($cid))
			{
				$this->setRedirect( 'index.php?option=com_customtables&view=listofoptions', JText::_('No Table Selected.'),'error' );
				return false;
			}

			$this->setRedirect( 'index.php?option=com_customtables&view=options&layout=edit&id='.$cid[0]);
			return true;
		}

		JRequest::setVar( 'view', 'options');
		JRequest::setVar( 'layout', 'edit');

		switch(JRequest::getVar( 'task'))
		{
		case 'apply':
			$this->save();
			break;
		case 'options.apply':
			$this->save();
			break;
		case 'save':
			$this->save();
			break;
		case 'options.save':
			$this->save();
			break;
		case 'cancel':
			$this->cancel();
			break;
		case 'options.cancel':
			$this->cancel();
			break;
		default:
			parent::display();
			break;
		}

	}
*/
/*
	function save()
	{
		$model = $this->getModel('options');

		$link 	= 'index.php?option=com_customtables&view=listofoptions';


		if ($model->store())
		{
			$msg = JText::_( 'List Item Saved Successfully' );
			$this->setRedirect($link, $msg);
		}
		else
		{
			$msg = JText::_( 'List Item was Unabled to Save');
			$this->setRedirect($link, $msg, 'error');
		}
	}
	*/
	public function save($key = null, $urlVar = null)
	{
		// get the referal details
		$this->ref 		= $this->input->get('ref', 0, 'word');
		$this->refid 	= $this->input->get('refid', 0, 'int');

		if ($this->ref || $this->refid)
		{
			// to make sure the item is checkedin on redirect
			$this->task = 'save';
		}

		$saved = parent::save($key, $urlVar);

		if ($this->refid && $saved)
		{
			$redirect = '&view='.(string)$this->ref.'&layout=default&id='.(int)$this->refid;

			// Redirect to the item screen.
			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . $redirect, false
				)
			);
		}
		elseif ($this->ref && $saved)
		{
			$redirect = '&view='.(string)$this->ref;

			// Redirect to the list screen.
			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . $redirect, false
				)
			);
		}
		return $saved;
	}

	public function cancel($key = null)
	{
		// get the referal details
		$this->ref 		= $this->input->get('ref', 0, 'word');
		$this->refid 	= $this->input->get('refid', 0, 'int');

		$cancel = parent::cancel($key);

		if ($cancel)
		{
			if ($this->refid)
			{
				$redirect = '&view='.(string)$this->ref.'&layout=edit&id='.(int)$this->refid;

				// Redirect to the item screen.
				$this->setRedirect(
					JRoute::_(
						'index.php?option=' . $this->option . $redirect, false
					)
				);
			}
			elseif ($this->ref)
			{
				$redirect = '&view='.(string)$this->ref;

				// Redirect to the list screen.
				$this->setRedirect(
					JRoute::_(
						'index.php?option=' . $this->option . $redirect, false
					)
				);
			}
		}
		else
		{
			// Redirect to the items screen.
			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list, false
				)
			);
		}
		return $cancel;
	}

}
