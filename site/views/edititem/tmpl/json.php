<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access

defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');

jimport('joomla.html.html.bootstrap');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');
JHtml::_('behavior.calendar');
JHtml::_('bootstrap.popover');

use Joomla\CMS\Session\Session;

if (!$this->ct->Params->blockExternalVars and $this->ct->Params->showPageHeading)
    $response_object['page_title'] = JoomlaBasicMisc::JTextExtended($this->ct->Params->pageTitle);

if (ob_get_contents())
    ob_end_clean();

//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.

if (isset($this->row[$this->ct->Table->realidfieldname]))
    $listing_id = (int)$this->row[$this->ct->Table->realidfieldname];
else
    $listing_id = 0;

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');
$LayoutProc = new LayoutProcessor($this->ct, $this->pagelayout);

//Better to run tag processor before rendering form edit elements because of IF statments that can exclude the part of the layout that contains form fields.
$this->pagelayout = $LayoutProc->fillLayout($this->row, null, '||', false, true);

$form_items = tagProcessor_Edit::process($this->ct, $this->pagelayout, $this->row, 'comes_');

$response_object = [];

$encoded_returnto = base64_encode($this->ct->Params->returnTo);

if ($listing_id == 0) {
    $publishstatus = $this->params->get('publishstatus');
    $response_object['published'] = (int)$publishstatus;
}

$response_object['form'] = $form_items;
$response_object['returnto'] = $encoded_returnto;
$response_object['token'] = Session::getFormToken();

$filename = JoomlaBasicMisc::makeNewFileName($this->ct->Params->pageTitle, 'json');

header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: application/json; charset=utf-8');
header("Pragma: no-cache");
header("Expires: 0");

echo json_encode($response_object);
die;
