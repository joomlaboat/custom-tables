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

use Joomla\CMS\Factory;

// Get the application
$app = Factory::getApplication();

require_once JPATH_SITE . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR
	. 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'CustomTablesAPIHelpers.php';

CustomTablesAPIHelpers::loadCT();

// Check if site is offline
if ($app->get('offline') == '1')
	CTMiscHelper::fireError(401, 'offline', $app->get('offline_message', 'Site is offline for maintenance'));

$controller = $app->input->get('controller');

$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR
	. 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
	. 'Controller' . DIRECTORY_SEPARATOR . $controller . 'Controller.php';

if (file_exists($path)) {
	require_once $path;
	$className = $controller . 'Controller';
	$do = new $className;

	try {
		$do->execute();
	} catch (Throwable $e) {
		echo $e->getFile() . '<br/>' . $e->getLine() . '<br/>' . $e->getMessage() . '<br/>' . $e->getTraceAsString();
		CTMiscHelper::fireError(500, $e->getMessage());
	}
} else {
	CTMiscHelper::fireError(404, 'Controller [' . $controller . '] not found.', $app->get('offline_message', 'Controller not found.'));
}