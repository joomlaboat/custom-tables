<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL *
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

$jinput = JFactory::getApplication()->input;
$task = JFactory::getApplication()->input->getCmd('task','');

switch ($task)
{
	case 'publish':
		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization())
		{
			$link = JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode(JoomlaBasicMisc::curPageURL()));
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		else
		{
			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model->load($params);
			$count = $model->setPublishStatus(1);
			$link = JoomlaBasicMisc::curPageURL();
			$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');
			
			$msg = ($count > 0 ? 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED' : 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_PUBLISHED');
			if($count == 1)
				$msg.='_1';
			
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended($msg,abs($count)));
		}

	break;

	case 'unpublish':

		$model = $this->getModel('edititem');
		if (!$model->CheckAuthorization())
		{
			if ($JoomlaVersionRelease != 1.5) $link = JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode(JoomlaBasicMisc::curPageURL()));
			else $link = JRoute::_('index.php?option=com_user&view=login&return=' . base64_encode(JoomlaBasicMisc::curPageURL()));
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		else
		{
			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model->load($params);
			$count = $model->setPublishStatus(0);
			$link = JoomlaBasicMisc::curPageURL();
			$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');
			
			$msg = ($count > 0 ? 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED' : 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_UNPUBLISHED');
			if($count == 1)
				$msg.='_1';
			
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended($msg,abs($count)));
		}
	break;

	default:

		if ($task == 'cart_addtocart' or $task == 'cart_form_addtocart' or $task == 'cart_setitemcount' or $task == 'cart_deleteitem' or $task == 'cart_emptycart')
		{
			$model = $this->getModel('catalog');
			$model->load($params, false);
			if ($model->params->get('cart_returnto'))
			{
				$link = $model->params->get('cart_returnto');
			}
			else
			{
				$theLink = JoomlaBasicMisc::curPageURL();
				$pair = explode('?', $theLink);
				if (isset($pair[1]))
				{
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'task');
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'cartprefix');
				}
				$link = implode('?', $pair); //'index.php?option=com_customtables&view=catalog&Itemid='.JFactory::getApplication()->input->getInt(
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
				if (JFactory::getApplication()->input->getString('msg'))
					$msg = JFactory::getApplication()->input->getString('msg');
				elseif ($param_msg != '')
					$msg = $param_msg;
				else
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOPPING_CART_UPDATED');

				$this->setRedirect($link, $msg);
			}
			else
			{
				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOPPING_CART_NOT_UPDATED');
				$this->setRedirect($link, $msg, 'error');
			}
		}
		else
		{
			parent::display();
		}
}
