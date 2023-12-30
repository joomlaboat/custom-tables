<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

$task = common::inputGetCmd('task', '');

$ct = null;
if ($task != '')
	$ct = new CT;

$user = new CTUser();

switch ($task) {
	case 'publish':

		$model = $this->getModel('edititem');
		if (!$ct->CheckAuthorization(2)) {
			$returnToEncoded = common::makeReturnToURL();
			$link = Route::_('index.php?option=com_users&view=login&return=' . $returnToEncoded);
			$this->setRedirect($link, common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		} else {
			$app = Factory::getApplication();

			$model->load($ct);
			$count = $model->setPublishStatus(1);
			$link = JoomlaBasicMisc::curPageURL();
			$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');

			$msg = ($count > 0 ? 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED' : 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_PUBLISHED');
			if ($count == 1)
				$msg .= '_1';

			$this->setRedirect($link, common::translate($msg, abs($count)));
		}

		break;

	case 'unpublish':

		$model = $this->getModel('edititem');
		if (!$ct->CheckAuthorization(2)) {
			$returnToEncoded = common::makeReturnToURL();
			if ($ct->Env->version != 1.5) $link = Route::_('index.php?option=com_users&view=login&return=' . $returnToEncoded);
			else $link = Route::_('index.php?option=com_user&view=login&return=' . $returnToEncoded);
			$this->setRedirect($link, common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		} else {
			$model->load($ct);
			$count = $model->setPublishStatus(0);
			$link = JoomlaBasicMisc::curPageURL();
			$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');

			$msg = ($count > 0 ? 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED' : 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_UNPUBLISHED');
			if ($count == 1)
				$msg .= '_1';

			$this->setRedirect($link, common::translate($msg, abs($count)));
		}
		break;

	default:

		if ($task == 'cart_addtocart' or $task == 'cart_form_addtocart' or $task == 'cart_setitemcount' or $task == 'cart_deleteitem' or $task == 'cart_emptycart') {
			$model = $this->getModel('catalog');
			$app = Factory::getApplication();
			$model->load($ct);
			if ($ct->Params->cartReturnTo) {
				$link = $ct->Params->cartReturnTo;
			} else {
				$theLink = JoomlaBasicMisc::curPageURL();
				$pair = explode('?', $theLink);
				if (isset($pair[1])) {
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'task');
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'cartprefix');
				}
				$link = implode('?', $pair); //'index.php?option=com_customtables&view=catalog&Itemid='.common::inputGetInt(
			}

			$param_msg = '';
			$result = '';

			switch ($task) {
				case 'cart_addtocart':
					$result = $model->cart_addtocart();
					if ($ct->Params->cartMsgItemAdded) $param_msg = $ct->Params->cartMsgItemAdded;
					break;

				case 'cart_form_addtocart':
					$result = $model->cart_form_addtocart();
					if ($ct->Params->cartMsgItemAdded) $param_msg = $ct->Params->cartMsgItemAdded;
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

			if ($result) {
				if (common::inputPostString('msg'))
					$msg = common::inputPostString('msg');
				elseif ($param_msg != '')
					$msg = $param_msg;
				else
					$msg = common::translate('COM_CUSTOMTABLES_SHOPPING_CART_UPDATED');

				$this->setRedirect($link, $msg);
			} else {
				$msg = common::translate('COM_CUSTOMTABLES_SHOPPING_CART_NOT_UPDATED');
				$this->setRedirect($link, $msg, 'error');
			}
		} else {
			parent::display();
		}
}
