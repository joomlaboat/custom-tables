<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.8.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		customtables.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * @package		Joomla
 * @subpackage	List
 */
class CustomTablesControllerListOfOptions extends JControllerAdmin
{


	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFOPTIONS';
	/**
	 * Proxy for getModel.
	 * @since	2.5
	 */
	public function getModel($name = 'Options', $prefix = 'CustomtablesModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}
}

	/*
	function display()
	{
		$task=JRequest::getVar( 'task');

		switch($task)
		{
						case 'delete':
								$this->delete();
								break;
						case 'list.delete':
								$this->delete();
								break;
						case 'remove_confirmed':
								$this->remove_confirmed();
								break;
						case 'list.remove_confirmed':
								$this->remove_confirmed();
								break;
						case 'copy':
								$this->copyItem();
								break;
						case 'list.copy':
								$this->copyItem();
								break;

						default:
								JRequest::setVar( 'view', 'list');
								parent::display();
								break;
		}


	}

	public function getModel($name = 'List', $prefix = 'CustomTablesModel')
	{
		//echo 'solomka';
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}


	function ExportItem()
	{
		JRequest::setVar( 'view', 'listexport');
		parent::display();
	}


	function cancelItem()
	{

		$model = $this->getModel('item');
		$model->checkin();

		$this->setRedirect( 'index.php?option=com_customtables&view=listofoptions&task=view');
	}

	function cancel()
	{
		$this->setRedirect( 'index.php?option=com_customtables&view=listofoptions');
	}


	function orderup()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );


		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		JArrayHelper::toInteger($cid);

		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			$this->setRedirect( 'index.php?option=com_customtables&view=list&task=view');
			return false;
		}

		$model =& $this->getModel( 'List' );
		if ($model->orderItem($id, -1)) {
			$msg = JText::_( 'Menu Item Moved Up' );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect( 'index.php?option=com_customtables&view=list&task=view', $msg );
	}


	function orderdown()
	{

		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );


		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		JArrayHelper::toInteger($cid);

		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			$this->setRedirect( 'index.php?option=com_customtables&view=list&task=view', JText::_('No Items Selected') );
			return false;
		}

		$model =& $this->getModel( 'List' );
		if ($model->orderItem($id, 1)) {
			$msg = JText::_( 'Menu Item Moved Down' );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect( 'index.php?option=com_customtables&view=list&task=view', $msg );
	}

	function saveorder()
	{

		//$this->setRedirect( 'index.php?option=com_customtables&view=list', "save order test" );

		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );


		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		JArrayHelper::toInteger($cid);



		$model =& $this->getModel( 'List' );

		if ($model->setOrder($cid, $menu)) {
			$msg = JText::_( 'New ordering saved' );
		} else {
			$msg = $model->getError();
		}

		$this->setRedirect( 'index.php?option=com_customtables&view=list', $msg );

	}



	function remove()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );

		// Get some variables from the request

		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		JArrayHelper::toInteger($cid);

		if (!count($cid)) {
			$this->setRedirect( 'index.php?option=com_customtables&view=list&task=view', JText::_('No Items Selected') );
			return false;
		}

		$model =& $this->getModel( 'List' );
		if ($n = $model->delete($cid)) {
			$msg = JText::sprintf( 'Item(s) deleted', $n );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect( 'index.php?option=com_customtables&view=list&task=view', $msg );
	}


	function copyItem()
	{
	    $cid = JRequest::getVar( 'cid', array(), 'post', 'array' );



	    $model = $this->getModel('list');


	    if($model->copyItem($cid))
	    {
		$msg = JText::_( 'Item(s) Copied Successfully' );
	    }
	    else
	    {
		$msg = JText::_( 'Item(s) was Unabled to Copy' );
	    }

	    $link 	= 'index.php?option=com_customtables&view=list';
	    $this->setRedirect($link, $msg);
	}


	function RefreshFamily()
	{
		$model =& $this->getModel( 'List' );
		$model->RefreshFamily();

		$this->setRedirect( 'index.php?option=com_customtables&view=list&task=view', "Family Tree has been Refreshed");

	}

*/
