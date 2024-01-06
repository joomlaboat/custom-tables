<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\TwigProcessor;
use Joomla\CMS\Factory;

$task = common::inputGetCmd('task');

switch ($task) {
	case 'saveandcontinue':
	case 'saveascopy':
	case 'save' :
		if (CustomTablesSave($task, $this))
			parent::display();

		break;

	case 'cancel':
		$msg = common::translate('COM_CUSTOMTABLES_EDIT_CANCELED');
		$link = common::getReturnToURL();
		$this->setRedirect($link, $msg);
		break;

	case 'delete':
		if (CustomTablesDelete($this))
			parent::display();

		break;

	default:
		parent::display();
}

function CustomTablesDelete($this_)
{
	$ct = new CT;
	$edit_model = $this_->getModel('edititem');
	$edit_model->load($ct);

	if (!$ct->CheckAuthorization(3)) {
		// not authorized
		if ($ct->Env->clean == 1) {
			Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
			return false;
		} else {
			$returnToEncoded = common::makeReturnToURL();
			$link = $edit_model->ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $returnToEncoded;
			$this_->setRedirect($link, common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
		}
		return true;
	} else {
		$decodedReturnTo = common::getReturnToURL();

		if ($decodedReturnTo != '') {
			$link = $decodedReturnTo;
			if (!str_contains($link, 'http:') and !str_contains($link, 'https:')) $link .= $edit_model->ct->Env->WebsiteRoot . $link;
		} else
			$link = $ct->Env->WebsiteRoot . 'index.php?Itemid=' . $ct->Params->ItemId;

		if ($edit_model->delete()) {
			if ($ct->Env->clean == 1)
				die('deleted');
			else
				$this_->setRedirect($link, common::translate('COM_CUSTOMTABLES_RECORDS_DELETED'));
		} else {
			if ($ct->Env->clean == 1)
				die('error');
			else
				$this_->setRedirect($link, common::translate('COM_CUSTOMTABLES_RECORDS_NOT_DELETED'));
		}
	}
	return true;
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function CustomTablesSave($task, $this_)
{
	$link = common::getReturnToURL();

	common::inputSet('task', '');
	$ct = new CT(null, false);
	$model = $this_->getModel('edititem');

	if (!$model->load($ct))
		return false;

	if (!$ct->CheckAuthorization()) {
		$returnToEncoded = common::makeReturnToURL();
		$link = $ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $returnToEncoded;
		$this_->setRedirect($link, common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
	} else {
		$msg_ = '';
		$isOk = true;

		if ($task == 'saveascopy')
			$isOk = $model->copy($msg_, $link);
		else
			$isOk = $model->store($msg_, $link);

		if ($task == 'saveandcontinue') {
			$link = JoomlaBasicMisc::deleteURLQueryOption($link, "listing_id");

			if (!str_contains($link, "?"))
				$link .= '?';
			else
				$link .= '&';

			$link .= 'listing_id=' . common::inputPostInt("listing_id");
			//stay on the same page if "saveandcontinue"
		}

		if ($isOk) {

			if ($ct->Params->msgItemIsSaved == '-')
				$msg = '';
			elseif ($msg_ != '')
				$msg = $msg_;
			elseif ($ct->Params->msgItemIsSaved == '')
				$msg = common::translate('COM_CUSTOMTABLES_RECORD_SAVED');
			else
				$msg = $ct->Params->msgItemIsSaved;

			if ($ct->Env->legacySupport) {
				$siteLibraryPath = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR;
				require_once($siteLibraryPath . 'layout.php');

				$LayoutProc = new LayoutProcessor($ct);
				$LayoutProc->layout = $msg;
				$msg = $LayoutProc->fillLayout(null, null, '[]', true);
			}

			$twig = new TwigProcessor($ct, $msg);
			$msg = $twig->process();
			if ($twig->errorMessage !== null) {
				$ct->errors[] = $twig->errorMessage;
				return false;
			}

			if (common::inputGetInt('clean', 0) == 1) {

				$res = ['status' => 'saved', 'id' => $model->listing_id, 'message' => implode(',', $model->ct->messages)];

				if (common::inputGetInt('load', 0) == 1) {
					$ct->Table->loadRecord($model->listing_id);
					$res['record'] = $ct->Table->record;
				}

				die(common::ctJsonEncode($res));

			} elseif ($link != '') {
				$link = str_replace('$get_listing_id', common::inputGet("listing_id", 0, 'INT'), $link);

				if (!str_contains($link, 'tmpl=component')) {
					if ($msg != '') {
						$this_->setRedirect($link, $msg);
					} else
						$this_->setRedirect($link);


				} else {
					$this_->setRedirect($link);
				}

			} else {
				if (common::inputGet('submitbutton', '', 'CMD') == 'nextprint') {
					$link = $ct->Env->WebsiteRoot . 'index.php?option=com_customtables&view=details'
						. '&Itemid=' . common::inputGet('Itemid', 0, 'INT')
						. '&listing_id=' . common::inputGet("listing_id", 0, 'INT')
						. '&tmpl=component'
						. '&print=1';

					$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

					echo '<p style="text-align:center;">
						<input type="button" class="button" value="' . common::translate('COM_CUSTOMTABLES_PRINT') . '"
					onClick=\'window.open("' . $link . '","win2","' . $status . '"); return false; \'>
					</p>';

					common::inputGetCmd('view', 'details');
					return true;

				} else {
					$link = $ct->Env->WebsiteRoot . 'index.php?option=com_customtables&view=catalog&Itemid=' . common::inputGet('Itemid', 0, 'INT');

					if ($msg != '')
						$this_->setRedirect($link, $msg);
					else
						$this_->setRedirect($link);
				}
			}
		} else {

			if (common::inputGetInt('clean', 0) == 1) {
				$res = ['status' => 'error', 'id' => $model->listing_id, 'message' => implode(',', $model->ct->errors)];
				die(common::ctJsonEncode($res));
			}

			if ($msg_ == 'COM_CUSTOMTABLES_INCORRECT_CAPTCHA') {
				$msg = common::translate('COM_CUSTOMTABLES_INCORRECT_CAPTCHA');
				Factory::getApplication()->enqueueMessage($msg, 'error');
				echo '
				<script>
setTimeout("history.go(-1)", 2000);
</script>';

			} else {
				if ($link != '') {
					$msg = common::translate('COM_CUSTOMTABLES_RECORD_NOT_SAVED');
					$this_->setRedirect($link, $msg, 'error');
				} else
					Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_RECORD_NOT_SAVED'), 'error');
			}
		}
	}
	return true;
}
