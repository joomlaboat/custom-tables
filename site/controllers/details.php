<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\CTUser;
use Joomla\CMS\Factory;

$jinput = Factory::getApplication()->input;
$task = Factory::getApplication()->input->getCmd('task','');

$ct = null;
if($task != '')
    $ct = new CT;

switch ($task)
{
	case 'publish':

		$model = $this->getModel('edititem');
		if (!CTUser::CheckAuthorization($ct))
		{
			$link = JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode(JoomlaBasicMisc::curPageURL()));
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		else
		{
			$app = Factory::getApplication();

			$model->load($ct);
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
		if (!CTUser::CheckAuthorization($ct))
		{
			if ($ct->Env->version != 1.5) $link = JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode(JoomlaBasicMisc::curPageURL()));
			else $link = JRoute::_('index.php?option=com_user&view=login&return=' . base64_encode(JoomlaBasicMisc::curPageURL()));
			$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		else
		{
			$model->load($ct);
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
            $app = Factory::getApplication();
			$model->load($ct);
			if ($ct->Params->cartReturnTo)
			{
				$link = $ct->Params->cartReturnTo;
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
				$link = implode('?', $pair); //'index.php?option=com_customtables&view=catalog&Itemid='.Factory::getApplication()->input->getInt(
			}

			$param_msg = '';
            $result = '';

			switch ($task)
			{
			case 'cart_addtocart':
				$result = $model->cart_addtocart();
                if ($ct->Params->cartMsgItemAdded) $param_msg = $ct->Params->cartMsgItemAdded;
			break;

			case 'cart_form_addtocart':
				$result = $model->cart_form_addtocart();
				if ($$ct->Params->cartMsgItemAdded) $param_msg = $ct->Params->cartMsgItemAdded;
			break;

			case 'cart_setitemcount':
				$result = $model->cart_setitemcount();
				if ($ct->Params->cartMsgItemUpdated) $param_msg = $ct->Params->cartMsgItemUpdated;
			break;

			case 'cart_deleteitem':
				$result = $model->cart_deleteitem();
				if ($ct->Params->cartMsgItemDeleted) $param_msg = $ct->Params->cartMsgItemDeleted;
			break;

			case 'cart_emptycart':
				$result = $model->cart_emptycart();
				if ($ct->Params->cartMsgItemUpdated) $param_msg = $ct->Params->cartMsgItemUpdated;
			break;
			}

			if ($result)
			{
				if (Factory::getApplication()->input->getString('msg'))
					$msg = Factory::getApplication()->input->getString('msg');
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
