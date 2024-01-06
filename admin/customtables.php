<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage customtables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CTUser;
use Joomla\CMS\Factory;

$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
	. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'loader.php';

if (!file_exists($path))
	die('CT Loader not found.');

require_once($path);
CTLoader(true);

// Access check.
$user = new CTUser();
if (!$user->authorise('core.manage', 'com_customtables')) {
	Factory::getApplication()->enqueueMessage(common::translate('JERROR_ALERTNOAUTHOR'), 'error');
};

// require helper files
JLoader::register('CustomtablesHelper', dirname(__FILE__) . '/helpers/customtables.php');

use Joomla\CMS\MVC\Controller\BaseController;

// Get an instance of the controller prefixed by Customtables
$controller = BaseController::getInstance('Customtables');

// Perform the Request task
try {
	$controller->execute(common::inputGetCmd('task'));
} catch (Exception $e) {
	die($e->getMessage());
}

// Redirect if set by the controller
$controller->redirect();
