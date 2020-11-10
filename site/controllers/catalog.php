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
$encodedreturnto = base64_encode(JoomlaBasicMisc::curPageURL());
$returnto = $jinput->get('returnto', '', 'BASE64');
$decodedreturnto = base64_decode($returnto);
$user = JFactory::getUser();
$userid = (int)$user->get('id');

$clean = $jinput->getInt('clean');
$WebsiteRoot = JURI::root(true);

if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)- 1] != '/') //Root must have slash / in the end
	$WebsiteRoot.= '/';

$layout = $jinput->getCmd('layout', '');

if (($layout == 'currentuser' or $layout == 'customcurrentuser') and $userid == 0)
{
	$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
	$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
}
else
{
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
	
	//Check Authorization
	//3 - to delete
	$PermissionIndexes=['clear'=>3,'delete'=>3,'copy'=>1,'refresh'=>1,'publish'=>2,'unpublish'=>2,'createuser'=>1];
	$PermissionIndex=0;
	if(in_array($task, $PermissionIndexes))
		$PermissionIndex=$PermissionIndexes[$task];
	
	if (!$edit_model->CheckAuthorization($PermissionIndex))
	{
		// not authorized
		if ($clean == 1)
			die('not authorized');
		else
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
			$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		die;// not authorized
	}
	
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

			$model = $this->getModel('catalog');
			$model->load($params, false);

			if ($model->getSearchResult())
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED'));
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED'), 'error');

		break;

	case 'delete':
		
		if ($edit_model->delete())
		{
			if ($clean == 1)
				die('deleted');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED'));
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED'), 'error');
		}

		break;

	case 'copy':
		
		if ($edit_model->copy($msg, $link))
		{
			if ($clean == 1)
				die('copied');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_COPIED'));
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_COPIED'), 'error');
		}

		break;

	case 'refresh':

		if ($edit_model->Refresh())
		{
			if ($clean == 1)
				die('refreshed');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_REFRESHED'));
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_REFRESHED'), 'error');
		}
		break;

	case 'publish':

		if ($edit_model->setPublishStatus(1))
		{
			if ($clean == 1)
				die('published');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_PUBLISHED'));
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_PUBLISHED'), 'error');
		}
		
		break;

	case 'unpublish':
		
		if ($edit_model->setPublishStatus(0))
		{
			if ($clean == 1)
				die('unpublished');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_UNPUBLISHED'));
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_PUBLISHED'), 'error');
		}
		
		break;

	case 'createuser':
		
		//publishing the record will refresh its field values and will create user account if conditions are met.
		if ($edit_model->setPublishStatus(1))
		{
			if ($clean == 1)
				die('user created');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_USER_CREATED'));
		}
		else
		{
			if ($clean == 1)
				die('error');
			else
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_USER_NOT_CREATED'), 'error');
		}

		break;

	default:
		
		if ($task == 'cart_addtocart' or $task == 'cart_form_addtocart' or $task == 'cart_setitemcount' or $task == 'cart_deleteitem' or $task == 'cart_emptycart')
		{
			$model = $this->getModel('catalog');
			$model->load($params, false);
			if ($params->get('cart_returnto')) $link = $params->get('cart_returnto');
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
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOPPING_CART_UPDATED');
				elseif ($param_msg != '')
					$msg = $param_msg;

				$this->setRedirect($link, $msg);
			}
			else
			{
				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOPPING_CART_NOT_UPDATED');
				$this->setRedirect($link, $msg, 'error');
			}
		}
		else
			parent::display();
		
		break;
	}
}
