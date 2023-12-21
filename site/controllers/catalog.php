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
use CustomTables\Field;
use CustomTables\Fields;
use CustomTables\CTUser;
use CustomTables\SaveFieldQuerySet;

$view = common::inputGetCmd('view');

if ($view == 'home') {
	common::inputSet('homeparent', 'home');
	common::inputSet('view', 'catalog');
	parent::display();
}

$task = common::inputGetCmd('task');

//Check Authorization
$PermissionIndexes = ['setorderby' => 0, 'clear' => 3, 'delete' => 3, 'copy' => 4, 'copycontent' => 4, 'refresh' => 1, 'publish' => 2, 'unpublish' => 2, 'createuser' => 1, 'resetpassword' => 1];
//$PermissionWords=['clear'=>'core.delete','delete'=>'core.delete','copy'=>'core.create','refresh'=>'core.edit','publish'=>'core.edit.state','unpublish'=>'core.edit.state','createuser'=>'core.edit'];
$PermissionIndex = 0;
//$PermissionWord='';
//if (array_key_exists($task,$PermissionWords))
//$PermissionWord=$PermissionWords[$task];

if (array_key_exists($task, $PermissionIndexes))
	$PermissionIndex = $PermissionIndexes[$task];

if ($task != '') {

	$ct = new CT(null, false);

	/*
	 * $user = new CTUser();
	if ($user->authorise('core.admin', 'com_helloworld'))
				<action name="core.create" title="JACTION_CREATE" description="COM_CUSTOMTABLES_ACCESS_CREATE_DESC" />
	<action name="core.edit" title="JACTION_EDIT" description="COM_CUSTOMTABLES_ACCESS_EDIT_DESC" />
	<action name="core.edit.own" title="JACTION_EDITOWN" description="COM_CUSTOMTABLES_ACCESS_EDITOWN_DESC" />
	<action name="core.edit.state" title="JACTION_EDITSTATE" description="COM_CUSTOMTABLES_ACCESS_EDITSTATE_DESC" />
	<action name="core.delete" title="JACTION_DELETE" description="COM_CUSTOMTABLES_ACCESS_DELETE_DESC" />
	<action name="core.update" title="COM_CUSTOMTABLES_REFRESH" description="COM_CUSTOMTABLES_ACCESS_REFRESH_DESC" />
*/

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
			$link = $ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=1' . base64_encode(JoomlaBasicMisc::curPageURL());
			$this->setRedirect($link, common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
		}
	}

} else
	parent::display();

function doTheTask(CT &$ct, $task, $edit_model, $this_)
{
	if ($ct->Params->returnTo != '') {
		$link = $ct->Params->returnTo;
		if (!str_contains($link, 'http:') and !str_contains($link, 'https:'))
			$link .= $ct->Env->WebsiteRoot . $link;
	} else {
		$link = $ct->Env->WebsiteRoot . 'index.php?Itemid=' . $ct->Params->ItemId;
		$link .= (is_null($ct->Params->ModuleId) ? '' : '&ModuleId=' . $ct->Params->ModuleId);
	}

	$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');

	if (!$edit_model->load($ct, false))
		die('Model not loaded');

	switch ($task) {

		case 'delete':

			$count = $edit_model->delete();
			if ($count > 0) {
				if ($ct->Env->clean == 1) {
					if (ob_get_contents())
						ob_end_clean();

					header('Content-Type: text/csv; charset=utf-8');
					header("Pragma: no-cache");
					header("Expires: 0");

					die('deleted');
				} else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_DELETED';
					if ($count == 1)
						$msg .= '_1';

					return (object)array('link' => $link, 'msg' => common::translate($msg, $count), 'status' => null);
					//COM_CUSTOMTABLES_RECORDS_DELETED
				}
			} elseif ($count < 0) {
				if ($ct->Env->clean == 1)
					die('error');
				else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_DELETED';
					if (abs($count) == 1)
						$msg .= '_1';

					return (object)array('link' => $link, 'msg' => common::translate($msg, abs($count)), 'status' => 'error');
				}
			}
			break;

		case 'copy':

			$msg = '';
			if ($edit_model->copy($msg, $link)) {
				if ($ct->Env->clean == 1)
					die('copied');
				else
					return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_RECORDS_COPIED'), 'status' => null);
			} else {
				if ($ct->Env->clean == 1)
					die('error');
				else
					return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_RECORDS_NOT_COPIED'), 'status' => 'error');
			}

		case 'refresh':

			$count = $edit_model->Refresh();
			if ($count > 0) {
				if ($ct->Env->clean == 1)
					die('refreshed');
				else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_REFRESHED';
					if ($count == 1)
						$msg .= '_1';

					return (object)array('link' => $link, 'msg' => common::translate($msg, $count), 'status' => null);
				}
			} else {
				if ($ct->Env->clean == 1)
					die('error');
				else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_REFRESHED';
					if (abs($count) == 1)
						$msg .= '_1';

					return (object)array('link' => $link, 'msg' => common::translate($msg, abs($count)), 'status' => 'error');
				}
			}

		case 'publish':

			$count = $edit_model->setPublishStatus(1);
			if ($count > 0) {
				if ($ct->Env->clean == 1)
					die('published');
				else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED';
					if ($count == 1)
						$msg .= '_1';

					return (object)array('link' => $link, 'msg' => common::translate($msg, $count), 'status' => null);
				}
			} elseif ($count < 0) {
				if ($ct->Env->clean == 1)
					die('error');
				else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_PUBLISHED';
					if (abs($count) == 1)
						$msg .= '_1';

					return (object)array('link' => $link, 'msg' => common::translate($msg, abs($count)), 'status' => 'error');
				}
			}

			break;

		case 'unpublish':

			$count = $edit_model->setPublishStatus(0);
			if ($count > 0) {
				if ($ct->Env->clean == 1)
					die('unpublished');
				else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED';
					if ($count == 1)
						$msg .= '_1';

					return (object)array('link' => $link, 'msg' => common::translate($msg, $count), 'status' => null);
				}
			} elseif ($count < 0) {
				if ($ct->Env->clean == 1)
					die('error');
				else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_UNPUBLISHED';
					if (abs($count) == 1)
						$msg .= '_1';

					return (object)array('link' => $link, 'msg' => common::translate($msg, abs($count)), 'status' => 'error');
				}
			}

			break;

		case 'createuser':

			$ct->getTable($ct->Params->tableName);
			if ($ct->Table->tablename === null) {
				return (object)array('link' => $link, 'msg' => 'Table not selected.', 'status' => 'error');
			}

			if ($ct->Table->useridfieldname === null) {
				return (object)array('link' => $link, 'msg' => 'User field not found.', 'status' => 'error');
			}

			$listing_id = common::inputGetInt("listing_id");
			$ct->Table->loadRecord($listing_id);
			if ($ct->Table->record === null) {
				$ct->errors[] = 'User record ID: "' . $listing_id . '" not found.';
				return (object)array('link' => $link, 'msg' => 'User record ID: "' . $listing_id . '" not found.', 'status' => 'error');
			}

			$fieldrow = Fields::getFieldAssocByName($ct->Table->useridfieldname, $ct->Table->tableid);

			$saveField = new SaveFieldQuerySet($ct, $ct->Table->record, false);
			$field = new Field($ct, $fieldrow);


			if ($saveField->Try2CreateUserAccount($field))
				return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_USER_CREATE_PSW_SENT'), 'status' => 'notice');
			else
				return (object)array('link' => $link, 'msg' => common::translate('COM_CUSTOMTABLES_ERROR_USER_NOTCREATED'), 'status' => 'error');

		case 'resetpassword':

			$ct->getTable($ct->Params->tableName);
			if ($ct->Table->tablename === null)
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

			$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');
			$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'orderby');

			return (object)array('link' => $link, 'msg' => null, 'status' => null);

		case 'setlimit':

			$limit = common::inputGetInt('limit', '');

			$ct->app->setUserState('com_customtables.limit_' . $ct->Params->ItemId, $limit);

			$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');
			$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'limit');

			return (object)array('link' => $link, 'msg' => null, 'status' => null);

		case 'copycontent':

			$frmt = common::inputGetCmd('frmt', '');

			$from = common::inputGetCmd('from', '');
			$to = common::inputGetCmd('to', '');

			if ($edit_model->copyContent($from, $to)) {
				if ($ct->Env->clean == 1) {
					if ($frmt == 'json')
						die(json_encode(['status' => 'copied']));
					else
						die('copied');
				} else {
					$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_CONTENT_COPIED';
					return (object)array('link' => $link, 'msg' => common::translate($msg), 'status' => null);
				}
			} else {
				if ($ct->Env->clean == 1) {
					if ($frmt == 'json')
						die(json_encode(['status' => 'error', 'msg' => 'not copied']));
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

			if ($ct->Table->tablename === null) {
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
					$theLink = JoomlaBasicMisc::curPageURL();
					$pair = explode('?', $theLink);
					if (isset($pair[1])) {
						$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'task');
						$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'cartprefix');
						$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], "listing_id");
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
					$msg = common::inputGetString('msg');

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
