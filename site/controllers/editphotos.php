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
use CustomTables\CTUser;

$ct = new CT(null, false);
$ct->Params->constructJoomlaParams();

$model = $this->getModel('edititem');
$user = new CTUser();

/*
if (!$ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT)) {
	//not authorized
	$returnToEncoded = common::makeReturnToURL();
	$link = Route::_('index.php?option=com_users&view=login&return=' . $returnToEncoded);
	$this->setRedirect($link, common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
} else {
	*/
switch (common::inputGetCmd('task')) {
	case 'add' :
		$model = $this->getModel('editphotos');
		$model->load($ct);

		if ($model->add()) {
			$message = common::translate('COM_CUSTOMTABLES_IMAGE_ADDED');
		} else {
			$message = common::translate('COM_CUSTOMTABLES_IMAGE_NOT_ADDED');
		}

		$tableName = common::inputGetCmd('establename');
		$galleryName = common::inputGet('galleryname', '', 'CMD');
		$listing_id = common::inputGet("listing_id", 0, 'INT');
		$returnToEncoded = common::getReturnToURL(false, null, 'create-edit-record');
		$Itemid = common::inputGet('Itemid', 0, 'INT');

		$link = 'index.php?option=com_customtables&view=editphotos'
			. '&establename=' . $tableName
			. '&galleryname=' . $galleryName
			. '&listing_id=' . $listing_id
			. '&returnto=' . $returnToEncoded
			. '&Itemid=' . $Itemid;

		$this->setRedirect($link, $message);
		break;

	case 'delete' :
		$model = $this->getModel('editphotos');
		$model->load($ct);

		if ($model->delete()) {
			$message = common::translate('COM_CUSTOMTABLES_IMAGE_DELETED');
		} else {
			$message = common::translate('COM_CUSTOMTABLES_IMAGE_NOT_DELETED');
		}

		$tableName = common::inputGetCmd('establename');
		$galleryName = common::inputGet('galleryname', '', 'CMD');
		$listing_id = common::inputGet("listing_id", 0, 'INT');
		$returnToEncoded = common::getReturnToURL(false, null, 'create-edit-record');
		$Itemid = common::inputGet('Itemid', 0, 'INT');

		$link = 'index.php?option=com_customtables&view=editphotos'
			. '&establename=' . $tableName
			. '&galleryname=' . $galleryName
			. '&listing_id=' . $listing_id
			. '&returnto=' . $returnToEncoded
			. '&Itemid=' . $Itemid;

		$this->setRedirect($link, $message);
		break;

	case 'saveorder' :
		$model = $this->getModel('editphotos');
		$model->load($ct);

		if ($model->reorder()) {
			$message = common::translate('COM_CUSTOMTABLES_IMAGE_ORDER_SAVED');
		} else {
			$message = common::translate('COM_CUSTOMTABLES_IMAGE_ORDER_NOT_SAVED');
		}

		$returnto = common::getReturnToURL(true, null, 'create-edit-record');
		$this->setRedirect($returnto, $message);
		break;

	case 'cancel' :
		$message = common::translate('COM_CUSTOMTABLES_EDIT_CANCELED');
		$returnto = common::getReturnToURL(true, null, 'create-edit-record');
		$this->setRedirect($returnto, $message);
		break;
	default:
		parent::display();
}
//}
