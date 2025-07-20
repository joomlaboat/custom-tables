<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\CTUser;

try {
	$view = common::inputGetCmd('view');
	$task = common::inputGetCmd('task');

	if ($view == 'home') {
		common::inputSet('homeparent', 'home');
		common::inputSet('view', 'catalog');
		parent::display();
	}

} catch (Exception $e) {
	echo $e->getMessage();
}

$updatedTask = ['delete', 'refresh', 'publish', 'unpublish', 'copy', 'createuser', 'setorderby', 'setlimit'];
if (in_array($task, $updatedTask)) {

	require_once CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'controllerHelper.php';
	$result = controllerHelper::doTheTask($task);

	if (isset($result['content']))
		echo $result['content'];

	if ($result['link'] !== null) {
		$this->setRedirect($result['link'], $result['message'] ?? null, $result['success'] ? 'success' : 'error');
	} else {
		if (!empty($result['message']))
			common::enqueueMessage($result['message'], $result['success'] ? 'success' : 'error');

		parent::display();
	}

} else {
	//Check Authorization
	$PermissionIndexes = ['setorderby' => 0, 'clear' => 3, 'delete' => 3, 'copy' => 4, 'copycontent' => 4, 'refresh' => 1, 'publish' => 2, 'unpublish' => 2, 'createuser' => 1, 'resetpassword' => 1];
	$PermissionIndex = 0;

	if (array_key_exists($task, $PermissionIndexes))
		$PermissionIndex = $PermissionIndexes[$task];

	if ($task != '') {

		$ct = new CT(null, false);
		$ct->Params->constructJoomlaParams();

		if ($ct->CheckAuthorization($PermissionIndex)) {

			$edit_model = $this->getModel('edititem');
			$redirect = doTheTask($ct, $task, $edit_model, $this);
			if (is_null($redirect))
				common::enqueueMessage('Unknown task');
			else {
				$this->setRedirect($redirect->link, $redirect->msg, $redirect->status);
			}

		} else {
			// not authorized
			if ($ct->Env->clean == 1)
				die('not authorized');
			else {
				$returnToEncoded = common::makeReturnToURL();
				$link = $ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $returnToEncoded;
				$this->setRedirect($link, common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
			}
		}
	} else {
		parent::display();
	}
}

/**
 * @throws Exception
 *
 * @since 3.0.0
 */
function doTheTask(CT &$ct, $task, $edit_model, $this_)
{
	if ($ct->Params->returnTo != '') {
		$link = $ct->Params->returnTo;
		if (!str_contains($link, 'http:') and !str_contains($link, 'https:')) {
			if ($link !== '' and $link[0] == '/')
				$link = substr($link, 1);

			$link = $ct->Env->WebsiteRoot . $link;
		}

	} else {
		$link = $ct->Env->WebsiteRoot . 'index.php?Itemid=' . $ct->Params->ItemId;
		//$link .= (is_null($ct->Params->ModuleId) ? '' : '&ModuleId=' . $ct->Params->ModuleId);
	}

	$link = CTMiscHelper::deleteURLQueryOption($link, 'task');

	switch ($task) {

		case 'resetpassword':

			$ct->getTable($ct->Params->tableName);
			if ($ct->Table === null)
				return (object)array('link' => $link, 'msg' => 'Table not selected.', 'status' => 'error');

			$listing_id = common::inputGetInt("listing_id");

			try {
				CTUser::ResetPassword($ct, $listing_id);
			} catch (Exception $e) {
				if ($ct->Env->clean == 1)
					die('error');
				else
					return (object)array('link' => $link, 'msg' => common::translate('COM_USERS_RESET_COMPLETE_ERROR') . ':' . $e->getMessage(), 'status' => 'error');
			}

			return (object)array('link' => $link, 'msg' => common::translate('Password reset completed.'), 'status' => null);

		case 'copycontent':

			$frmt = common::inputGetCmd('frmt', '');

			$from = common::inputGetCmd('from', '');
			$to = common::inputGetCmd('to', '');

			if ($edit_model->copyContent($from, $to)) {
				if ($ct->Env->clean == 1) {
					if ($frmt == 'json')
						die(common::ctJsonEncode(['status' => 'copied']));
					else
						die('copied');
				} else {
					return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_CONTENT_COPIED'), 'status' => null);
				}
			} else {
				if ($ct->Env->clean == 1) {
					if ($frmt == 'json')
						die(common::ctJsonEncode(['status' => 'error', 'msg' => 'not copied']));
					else
						die('error');
				} else {
					return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_CONTENT_NOT_COPIED'), 'status' => 'error');
				}
			}

		case 'ordering':

			$tableid = common::inputGetInt('tableid');
			$ct->getTable($tableid);

			if ($ct->Table === null) {
				header("HTTP/1.1 500 Internal Server Error");
				die('Table not selected.');
			}

			$ordering = new CustomTables\Ordering($ct->Table, $ct->Params);

			if (!$ordering->saveorder()) {
				header("HTTP/1.1 500 Internal Server Error");
				die('Something went wrong.');
			}
			break;

		default:

			if ($task == 'cart_addtocart' or $task == 'cart_form_addtocart' or $task == 'cart_setitemcount' or $task == 'cart_deleteitem' or $task == 'cart_emptycart') {
				$model = $this_->getModel('catalog');
				$model->load($ct, false);
				if ($ct->Params->cartReturnTo) {
					$link = $ct->Params->cartReturnTo;
				} else {
					$theLink = common::curPageURL();
					$pair = explode('?', $theLink);
					if (isset($pair[1])) {
						$pair[1] = CTMiscHelper::deleteURLQueryOption($pair[1], 'task');
						$pair[1] = CTMiscHelper::deleteURLQueryOption($pair[1], 'cartprefix');
						$pair[1] = CTMiscHelper::deleteURLQueryOption($pair[1], "listing_id");
					}

					$link = implode('?', $pair);
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

				if ($result != "") {
					$message = common::inputPostString('msg', null, 'create-edit-record');

					if ($message === null)
						return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_SHOPPING_CART_UPDATED'), 'status' => null);
					elseif ($param_msg != '')
						return (object)array('link' => $link, 'msg' => $param_msg, 'status' => null);
				} else
					return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_SHOPPING_CART_NOT_UPDATED'), 'status' => 'error');
			} else
				return null;
	}

	return null;
}
