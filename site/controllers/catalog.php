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
use CustomTables\Field;
use CustomTables\CTUser;
use CustomTables\SaveFieldQuerySet;

$view = common::inputGetCmd('view');

if ($view == 'home') {
	common::inputSet('homeparent', 'home');
	common::inputSet('view', 'catalog');
	parent::display();
}

$task = common::inputGetCmd('task');

$updatedTask = ['delete', 'refresh', 'publish', 'unpublish', 'copy'];
if (in_array($task, $updatedTask)) {

	require_once CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'controllerHelper.php';
	$result = controllerHelper::doTheTask($task);

	if (isset($result['html']))
		echo $result['html'];

	if ($result['link'] !== null)
		$this->setRedirect($result['link'], $result['message'], $result['success'] ? 'success' : 'error');
	else
		parent::display();

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
				$ct->errors[] = 'Unknown task';
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
/*
function CustomTablesDelete($this_)
{
	$link = common::getReturnToURL() ?? '';

	$ct = new CT(null, false);
	$ct->Params->constructJoomlaParams();
	$layout = new Layouts($ct);

	$result = $layout->renderMixedLayout($ct->Params->editLayout);
	if ($result['success']) {
		if ($ct->Env->clean) {
			if ($ct->Env->frmt == 'json')
				CTMiscHelper::fireSuccess($result['id'], $result['data'], $ct->Params->msgItemIsSaved);
			else
				die($result['short'] ?? 'deleted');
		}

		if (isset($result['redirect']))
			$link = $result['redirect'];

		if ($result['message'] !== null) {
			$this_->setRedirect($link, $result['message']);
		} else
			$this_->setRedirect($link);
	} else {
		if ($ct->Env->clean) {
			if ($ct->Env->frmt == 'json')
				CTMiscHelper::fireError(500, $result['message'] ?? 'Error deleting record');
			else
				die($result['short'] ?? 'error');
		}

		if (isset($result['redirect']))
			$link = $result['redirect'];

		$this_->setRedirect($link, $result['message'], 'error');
	}
}
*/
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
		$link .= (is_null($ct->Params->ModuleId) ? '' : '&ModuleId=' . $ct->Params->ModuleId);
	}

	$link = CTMiscHelper::deleteURLQueryOption($link, 'task');

	switch ($task) {

		case 'createuser':

			$ct->getTable($ct->Params->tableName);
			if ($ct->Table === null) {
				return (object)array('link' => $link, 'msg' => 'Table not selected.', 'status' => 'error');
			}

			if ($ct->Table->useridfieldname === null) {
				return (object)array('link' => $link, 'msg' => 'User field not found.', 'status' => 'error');
			}

			if (common::inputGetCmd("listing_id")) {
				$ct->Params->listing_id = common::inputGetCmd("listing_id");
				if (!empty($ct->Params->listing_id))
					$ct->getRecord();
			}

			if ($ct->Table->record === null) {
				$ct->errors[] = 'User record ID: "' . $ct->Params->listing_id . '" not found.';
				return (object)array('link' => $link, 'msg' => 'User record ID: "' . $ct->Params->listing_id . '" not found.', 'status' => 'error');
			}

			$fieldRow = $ct->Table->getFieldByName($ct->Table->useridfieldname);

			$saveField = new SaveFieldQuerySet($ct, $ct->Table->record, false);
			$field = new Field($ct, $fieldRow);

			if ($saveField->Try2CreateUserAccount($field))
				return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_USER_CREATE_PSW_SENT'), 'status' => 'notice');
			else
				return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_ERROR_USER_NOTCREATED'), 'status' => 'error');

		case 'resetpassword':

			$ct->getTable($ct->Params->tableName);
			if ($ct->Table === null)
				return (object)array('link' => $link, 'msg' => 'Table not selected.', 'status' => 'error');

			$listing_id = common::inputGetInt("listing_id");
			if (CTUser::ResetPassword($ct, $listing_id)) {
				if ($ct->Env->clean == 1)
					die('password has been reset');
				else
					return (object)array('link' => $link, 'msg' => 'Password has been reset.', 'status' => null);
			} else {
				if ($ct->Env->clean == 1)
					die('error');
				else
					return (object)array('link' => $link, 'msg' => common::translate('COM_USERS_RESET_COMPLETE_ERROR'), 'status' => 'error');
			}

		case 'setorderby':

			$order_by = common::inputGetString('orderby', '');
			$order_by = trim(preg_replace("/[^a-zA-Z-+%.: ,_]/", "", $order_by));

			$ct->app->setUserState('com_customtables.orderby_' . $ct->Params->ItemId, $order_by);

			$link = CTMiscHelper::deleteURLQueryOption($link, 'task');
			$link = CTMiscHelper::deleteURLQueryOption($link, 'orderby');

			return (object)array('link' => $link, 'msg' => null, 'status' => null);

		case 'setlimit':

			$limit = common::inputGetInt('limit', 0);

			$ct->app->setUserState('com_customtables.limit_' . $ct->Params->ItemId, $limit);

			$link = CTMiscHelper::deleteURLQueryOption($link, 'task');
			$link = CTMiscHelper::deleteURLQueryOption($link, 'limit');

			return (object)array('link' => $link, 'msg' => null, 'status' => null);

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
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_CONTENT_COPIED';
					return (object)array('link' => $link, 'msg' => common::translate($msg), 'status' => null);
				}
			} else {
				if ($ct->Env->clean == 1) {
					if ($frmt == 'json')
						die(common::ctJsonEncode(['status' => 'error', 'msg' => 'not copied']));
					else
						die('error');
				} else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_CONTENT_NOT_COPIED';
					return (object)array('link' => $link, 'msg' => common::translate($msg), 'status' => 'error');
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
					$msg = common::inputPostString('msg', null, 'create-edit-record');

					if ($msg === null)
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
