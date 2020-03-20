<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

$encodedreturnto = base64_encode(JoomlaBasicMisc::curPageURL());
$returnto = JFactory::getApplication()->input->get('returnto', '', 'BASE64');
$decodedreturnto = base64_decode($returnto);
$user = JFactory::getUser();
$userid = (int)$user->get('id');
$jinput = JFactory::getApplication()->input;
$clean = JFactory::getApplication()->input->get('clean', 0, 'INT');
$WebsiteRoot = JURI::root(true);

if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)- 1] != '/') //Root must have slash / in the end
	$WebsiteRoot.= '/';

$layout = JFactory::getApplication()->input->getCMD('layout', '');

if (($layout == 'currentuser' or $layout == 'customcurrentuser') and $userid == 0)
{
	$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
	$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
}
else
{
	$returnto = JFactory::getApplication()->input->get('returnto', '', 'BASE64');
	$Itemid = JFactory::getApplication()->input->getInt('Itemid', 0);
	if ($theview == 'home')
	{
		parent::display();
		JFactory::getApplication()->input->set('homeparent', 'home');
		JFactory::getApplication()->input->set('view', 'catalog');
	}

	$task = JFactory::getApplication()->input->getCmd('task');
	switch ($task)
	{
	case 'clear':
		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization(3)) //3 is to delete
		{

			// not authorized

			$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
			die ;
		}
		else
		{
			if ($returnto != '')
			{
				$link = $decodedreturnto;
				if (strpos($link, 'http:') === false and strpos($link, 'https:') === false) $link.= $WebsiteRoot . $link;
			}
			else
			{
				$link = $WebsiteRoot . 'index.php?Itemid=' . $Itemid;
			}

			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model = $this->getModel('catalog');
			$model->load($params, false);

			if ($model->getSearchResult())
			{
				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED');
				$this->setRedirect($link, $msg);
			}
			else
			{
				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED');
				$this->setRedirect($link, $msg, 'error');
			}
		} //if(!$model->CheckAuthorization())
		break;

	case 'delete':
		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization(3)) //3 is to delete
		{

			// not authorized

			if ($clean == 1)
			{
				/** Alternatively you may use chaining */
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
				return;
			}
			else
			{
				$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
			}

			die ;// not authorized
		}
		else
		{
			if ($returnto != '')
			{
				$link = $decodedreturnto;
				if (strpos($link, 'http:') === false and strpos($link, 'https:') === false) $link.= $WebsiteRoot . $link;
			}
			else $link = $WebsiteRoot . 'index.php?Itemid=' . $Itemid;
			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model = $this->getModel('catalog');
			$model->load($params, false);
			if ($model->delete())
			{
				if ($clean == 1)
				{
					echo 'deleted';
					die ;
				}
				else
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED');
					$this->setRedirect($link, $msg);
				}
			}
			else
			{
				if ($clean == 1)
				{
					echo 'error';
					die ;
				}
				else
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED');
					$this->setRedirect($link, $msg);
				}
			}
		} //if(!$model->CheckAuthorization())
		break;

	/*case 'getfile': UNUSED
		require_once (JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'filelink.php');

		getSecureFile();
		*/
	case 'copy':
		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization(1)) //3 is to delete
		{

			// not authorized

			$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		else
		{
			if ($returnto != '')
			{
				$link = $decodedreturnto;
				if (strpos($link, 'http:') === false and strpos($link, 'https:') === false) $link.= $WebsiteRoot . $link;
			}
			else $link = $WebsiteRoot . 'index.php?Itemid=' . $Itemid;
			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model->load($params);
			if ($model->copy($msg, $link))
			{

				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_COPIED');
				$this->setRedirect($link, $msg);
			}
			else
			{
				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_COPIED');
				$this->setRedirect($link, $msg, 'error');
			}
		} //if(!$model->CheckAuthorization())
		break;

	case 'refresh':
		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization(1))
		{
			// not authorized

			if ($clean == 1)
			{
				echo 'not authorized';
				die ;
			}
			else
			{
				$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
			}

			die ;
		}
		else
		{
			$link = JoomlaBasicMisc::curPageURL();
			$link = str_replace('&task=refresh', '', $link);
			$link = str_replace('?task=refresh', '?', $link);
			$app = JFactory::getApplication();
			$params = $app->getParams();

			$model->load($params, false);
			if ($model->Refresh())
			{
				if ($clean == 1)
				{
					echo 'refreshed';
					die ;
				}
				else
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_REFRESHED');
					$this->setRedirect($link, $msg);
				}
			}
			else
			{
				if ($clean == 1)
				{
					echo 'error';
					die ;
				}
				else
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_REFRESHED');
					$this->setRedirect($link, $msg, 'error');
				}
			}
		} //if(!$model->CheckAuthorization())

		break;

	case 'publish':
		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization(2)) //2 - publish
		{

			// not authorized

			$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		else
		{
			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model->load($params);
			$model->setPublishStatus(1);
			$link = JoomlaBasicMisc::curPageURL();
			$link = str_replace('&task=publish', '', $link);
			$link = str_replace('?task=publish&', '?', $link);
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_PUBLISHED'));
		} //if(!$model->CheckAuthorization())
		break;

	case 'unpublish':
		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization(2))
		{

			// not authorized

			$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		else
		{
			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model->load($params);
			$model->setPublishStatus(0);
			$link = JoomlaBasicMisc::curPageURL();
			$link = str_replace('&task=unpublish', '', $link);
			$link = str_replace('?task=unpublish&', '?', $link);
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_UNPUBLISHED'));
		} //if(!$model->CheckAuthorization())
		break;

	case 'createuser':
		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization(1)) //1 - edit permissions
		{

			// not authorized

			$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		else
		{
			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model->load($params);
			$model->setPublishStatus(1);
			$link = JoomlaBasicMisc::curPageURL();
			$link = str_replace('&task=publish', '', $link);
			$link = str_replace('?task=publish&', '?', $link);
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_PUBLISHED'));
		} //if(!$model->CheckAuthorization())
		break;

	default:
		$app = JFactory::getApplication();
		$params = $app->getParams();
		if ($task == 'cart_addtocart' or $task == 'cart_form_addtocart' or $task == 'cart_setitemcount' or $task == 'cart_deleteitem' or $task == 'cart_emptycart')
		{
			$model = $this->getModel('catalog');
			$model->load($params, false);
			if ($model->params->get('cart_returnto')) $link = $model->params->get('cart_returnto');
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
				if ($model->params->get('cart_msgitemadded')) $param_msg = $model->params->get('cart_msgitemadded');
				break;

			case 'cart_form_addtocart':
				$result = $model->cart_form_addtocart();
				if ($model->params->get('cart_msgitemadded')) $param_msg = $model->params->get('cart_msgitemadded');
				break;

			case 'cart_setitemcount':
				$result = $model->cart_setitemcount();
				if ($model->params->get('cart_msgitemupdated')) $param_msg = $model->params->get('cart_msgitemupdated');
				break;

			case 'cart_deleteitem':
				$result = $model->cart_deleteitem();
				if ($model->params->get('cart_msgitemdeleted')) $param_msg = $model->params->get('cart_msgitemdeleted');
				break;

			case 'cart_emptycart':
				$result = $model->cart_emptycart();
				if ($model->params->get('cart_msgitemupdated')) $param_msg = $model->params->get('cart_msgitemupdated');
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
		else parent::display();
		break;
	}

}
