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

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');

use CustomTables\common;
use CustomTables\CTMiscHelper;
use Joomla\CMS\Session\Session;

if (!$this->ct->Params->blockExternalVars and $this->ct->Params->showPageHeading) {

	if (defined('_JEXEC'))
		$response_object['page_title'] = common::translate($this->ct->Params->pageTitle);
	else
		$response_object['page_title'] = $this->ct->Params->pageTitle;
}

if (ob_get_contents())
	ob_end_clean();

//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.

if (isset($this->ct->Table->record[$this->ct->Table->realidfieldname]))
	$listing_id = (int)$this->ct->Table->record[$this->ct->Table->realidfieldname];
else
	$listing_id = 0;

if ($this->ct->Env->legacySupport) {
	require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'layout.php');
	$LayoutProc = new LayoutProcessor($this->ct, $this->pageLayout);

	//Better to run tag processor before rendering form edit elements because of IF statments that can exclude the part of the layout that contains form fields.
	$this->pageLayout = $LayoutProc->fillLayout($this->ct->Table->record, null, '||', false, true);
}


$form_items = tagProcessor_Edit::process($this->ct, $this->pageLayout, $this->ct->Table->record, true);

$response_object = [];

$encoded_returnto = common::makeReturnToURL($this->ct->Params->returnTo);

if ($listing_id == 0) {
	$publishStatus = $this->params->get('publishstatus');
	$response_object['published'] = (int)$publishStatus;
}

$response_object['form'] = $form_items;
$response_object['returnto'] = $encoded_returnto;
$response_object['token'] = Session::getFormToken();

$filename = CTMiscHelper::makeNewFileName($this->ct->Params->pageTitle, 'json');

header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: application/json; charset=utf-8');
header("Pragma: no-cache");
header("Expires: 0");

echo common::ctJsonEncode($response_object);
die;//JSON output
