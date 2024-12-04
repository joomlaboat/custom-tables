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
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\Component\ComponentHelper;

$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
	. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'loader.php';

if (!file_exists($path))
	die('CT Loader not found.');

require_once($path);

$params = ComponentHelper::getParams('com_customtables');
$loadTwig = $params->get('loadTwig') ?? true;

CustomTablesLoader(false, $include_html = true, null, 'com_customtables', $loadTwig);

// Require the base controller
require_once JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables'
	. DIRECTORY_SEPARATOR . 'controller.php';

// Initialize the controller
$controller = new CustomTablesController();
try {
	$controller->execute(null);
} catch (Exception $e) {
}

// Redirect if set by the controller

$view = common::inputGetCmd('view');
if ($view == 'xml') {
	$file = common::inputGetCmd('xmlfile');

	$xml = 'unknown file';
	if ($file == 'tags')

		$xml = common::getStringFromFile(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
			. 'media' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $file . '.xml');

	elseif ($file == 'fieldtypes')
		$xml = common::getStringFromFile(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
			. 'media' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $file . '.xml');

	echo $xml;
	die;//XML output
}

$controller->redirect();
