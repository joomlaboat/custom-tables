<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

$jinput = JFactory::getApplication()->input;

$clean = $jinput->getInt('clean');
$WebsiteRoot = JURI::root(true);

if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)- 1] != '/') //Root must have slash / in the end
	$WebsiteRoot.= '/';

	$returnto = $jinput->get('returnto', '', 'BASE64');
	$Itemid = $jinput->getInt('Itemid', 0);
	if ($theview == 'home')
	{
		parent::display();
		$jinput->set('homeparent', 'home');
		$jinput->set('view', 'catalog');
	}

	$task = $jinput->getCmd('task');
	
	
	
	$app = JFactory::getApplication();
	$params=$app->getParams();
	$edit_model = $this->getModel('edititem');
	$edit_model->params=$params;
	$edit_model->id = $jinput->getInt('listing_id');
	
	//Check Authorization
	//3 - to delete
	$PermissionIndexes=['clear'=>3,'delete'=>3,'copy'=>4,'refresh'=>1,'publish'=>2,'unpublish'=>2,'createuser'=>1];
	//$PermissionWords=['clear'=>'core.delete','delete'=>'core.delete','copy'=>'core.create','refresh'=>'core.edit','publish'=>'core.edit.state','unpublish'=>'core.edit.state','createuser'=>'core.edit'];
	$PermissionIndex=0;
	//$PermissionWord='';
	//if (array_key_exists($task,$PermissionWords))
		//$PermissionWord=$PermissionWords[$task];
	
	if (array_key_exists($task,$PermissionIndexes))
		$PermissionIndex=$PermissionIndexes[$task];
	
	if($task!='')
	{
		/*
		if (JFactory::getUser()->authorise('core.admin', 'com_helloworld')) 
					<action name="core.create" title="JACTION_CREATE" description="COM_CUSTOMTABLES_ACCESS_CREATE_DESC" />
		<action name="core.edit" title="JACTION_EDIT" description="COM_CUSTOMTABLES_ACCESS_EDIT_DESC" />
		<action name="core.edit.own" title="JACTION_EDITOWN" description="COM_CUSTOMTABLES_ACCESS_EDITOWN_DESC" />
		<action name="core.edit.state" title="JACTION_EDITSTATE" description="COM_CUSTOMTABLES_ACCESS_EDITSTATE_DESC" />
		<action name="core.delete" title="JACTION_DELETE" description="COM_CUSTOMTABLES_ACCESS_DELETE_DESC" />
		<action name="core.update" title="COM_CUSTOMTABLES_REFRESH" description="COM_CUSTOMTABLES_ACCESS_REFRESH_DESC" />
*/
		//if ($edit_model->CheckAuthorizationACL($PermissionWord))
		if ($edit_model->CheckAuthorization($PermissionIndex))
		{
			$redirect=doTheTask($task,$params,$edit_model,$WebsiteRoot,$clean,$this);
			JFactory::getApplication()->enqueueMessage($redirect->msg);
			$this->setRedirect($redirect->link, $redirect->msg, $redirect->status);
		}
		else
		{
			// not authorized
			if ($clean == 1)
				die('not authorized');
			else
			{
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
				$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=1' . base64_encode(JoomlaBasicMisc::curPageURL());
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
				parent::display();
			}
		}
	}
	else
		parent::display();
	
function doTheTask($task,$params,$edit_model,$WebsiteRoot,$clean,&$this_)	
{
	$user = JFactory::getUser();
	$userid = (int)$user->get('id');

	$jinput = JFactory::getApplication()->input;
	$encodedreturnto = base64_encode(JoomlaBasicMisc::curPageURL());
	$returnto = $jinput->get('returnto', '', 'BASE64');
	$decodedreturnto = base64_decode($returnto);
	
	//Return link
	if ($returnto != '')
	{
		$link = $decodedreturnto;
		if (strpos($link, 'http:') === false and strpos($link, 'https:') === false)
			$link.= $WebsiteRoot . $link;
	}
	else
		$link = $WebsiteRoot . 'index.php?Itemid=' . $Itemid;
	
	//$link = JoomlaBasicMisc::curPageURL();
	$link = str_replace('&task=refresh', '', $link);
	$link = str_replace('?task=refresh', '?', $link);
	$link = str_replace('&task=publish', '', $link);
	$link = str_replace('?task=publish&', '?', $link);
	
	
	$edit_model->load($params, false);
		
	switch ($task)
	{
		case 'clear':

			$model = $this_->getModel('catalog');
			$model->load($params, false);

			if ($model->getSearchResult())
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED'), 'status' => null);
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED'), 'status' => 'error');

		break;

	case 'delete':
		
		if ($edit_model->delete())
		{
			if ($clean == 1)
				die('deleted');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED'), 'status' => null);
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED'), 'status' => 'error');
		}

		break;

	case 'copy':
		
		if ($edit_model->copy($msg, $link))
		{
			if ($clean == 1)
				die('copied');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_REFRESHED'), 'status' => null);
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_COPIED'), 'status' => 'error');
		}

		break;

	case 'refresh':

		if ($edit_model->Refresh())
		{
			if ($clean == 1)
				die('refreshed');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_REFRESHED'), 'status' => null);
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_REFRESHED'), 'status' => 'error');
		}
		break;

	case 'publish':

		if ($edit_model->setPublishStatus(1))
		{
			if ($clean == 1)
				die('published');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_PUBLISHED'), 'status' => null);
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_PUBLISHED'), 'status' => 'error');
		}
		
		break;

	case 'unpublish':
		
		if ($edit_model->setPublishStatus(0))
		{
			if ($clean == 1)
				die('unpublished');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_UNPUBLISHED'), 'status' => null);
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_PUBLISHED'), 'status' => 'error');
		}
		
		break;

	case 'createuser':
		
		//publishing the record will refresh its field values and will create user account if conditions are met.
		if ($edit_model->setPublishStatus(1))
		{
			if ($clean == 1)
				die('user created');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_USER_CREATED'), 'status' => null);
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_USER_NOT_CREATED'), 'status' => 'error');
		}

		break;

	default:
		
		if ($task == 'cart_addtocart' or $task == 'cart_form_addtocart' or $task == 'cart_setitemcount' or $task == 'cart_deleteitem' or $task == 'cart_emptycart')
		{
			$model = $this_->getModel('catalog');
			$model->load($params, false);
			if ($params->get('cart_returnto'))
			{
				$link = $params->get('cart_returnto');
			}
			else
			{
				$theLink = JoomlaBasicMisc::curPageURL();
				$pair = explode('?', $theLink);
				if (isset($pair[1]))
				{
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'task');
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'cartprefix');
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'listing_id');
				}

				$link = implode('?', $pair);
			}

			$param_msg = '';
			switch ($task)
			{
			case 'cart_addtocart':
				$result = $model->cart_addtocart();
				if ($params->get('cart_msgitemadded')) $param_msg = $params->get('cart_msgitemadded');
				break;

			case 'cart_form_addtocart':
				$result = $model->cart_form_addtocart();
				if ($params->get('cart_msgitemadded')) $param_msg = $params->get('cart_msgitemadded');
				break;

			case 'cart_setitemcount':
				$result = $model->cart_setitemcount();
				if ($params->get('cart_msgitemupdated')) $param_msg = $params->get('cart_msgitemupdated');
				break;

			case 'cart_deleteitem':
				$result = $model->cart_deleteitem();
				if ($params->get('cart_msgitemdeleted')) $param_msg = $params->get('cart_msgitemdeleted');
				break;

			case 'cart_emptycart':
				$result = $model->cart_emptycart();
				if ($params->get('cart_msgitemupdated')) $param_msg = $params->get('cart_msgitemupdated');
				break;
			}

			if ($result)
			{
				$msg = JFactory::getApplication()->input->getString('msg', null);

				if ($msg == null)
					return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOPPING_CART_UPDATED'), 'status' => null);
				elseif ($param_msg != '')
					return (object) array('link' => $link, 'msg' => $param_msg, 'status' => null);
			}
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOPPING_CART_NOT_UPDATED'), 'status' => 'error');
		}
		break;
	}
}
