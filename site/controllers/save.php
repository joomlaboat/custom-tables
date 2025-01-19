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

$task = common::inputGetCmd('task');
if ($task !== null) {
	require_once CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'controllerHelper.php';
	$result = controllerHelper::doTheTask($task);

	if (isset($result['html']))
		echo $result['html'];

	if ($result['link'] !== null)
		$this->setRedirect($result['link'], $result['message'], !$result['success'] ? 'error' : 'success');
	else
		parent::display();
} else {
	parent::display();
}


/**
 * @throws Exception
 * @since 3.5.0
 */
//function CustomTablesDelete($this_)
//{
/*
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
*/

/*
$listing_ids_str = common::inputPostString('ids');

if (!empty($listing_ids_str)) {

	$listing_ids_ = explode(',', $listing_ids_str);
	$record = new record($ct);
	if ($record->deleteMany($listing_ids_)) {
		if ($ct->Env->clean) {
			if ($ct->Env->frmt == 'json')
				CTMiscHelper::fireSuccess(null, $listing_ids_, $record->message);
			else
				die('deleted');
		}

		//This is to redirect to new record, if returnto contains $get_listing_id value
		$link = str_replace('$get_listing_id', common::inputGet("listing_id", 0, 'INT'), $link);

		if ($record->message !== null) {
			$this_->setRedirect($link, $record->message);
		} else
			$this_->setRedirect($link);

	} else {
		if ($record->unauthorized) {
			if ($ct->Env->clean == 1) {
				if ($ct->Env->frmt == "json")
					CTMiscHelper::fireError(401, 'unauthorized', common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
				else
					die('unauthorized');
			} else {
				$returnToEncoded = common::makeReturnToURL();
				$link = $ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $returnToEncoded;
				$this_->setRedirect($link, common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
			}
		} else {
			if ($ct->Env->clean == 1) {
				if ($ct->Env->frmt == "json")
					CTMiscHelper::fireError(500, 'error', $record->message);
				else
					die('error');
			} else {
				$this_->setRedirect($link, $record->message);
			}
		}
	}
}
*/
//}


