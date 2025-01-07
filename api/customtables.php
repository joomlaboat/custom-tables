<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

// Get the application
$app = Factory::getApplication();

// Check if the user has permission
/*
$user = $app->getIdentity();
if (!$user->authorise('core.manage', 'com_customtables')) {
	throw new RuntimeException('JERROR_ALERTNOAUTHOR');
}
*/
// Get the application
$app = Factory::getApplication();

// Check if site is offline
if ($app->get('offline') == '1') {
	echo json_encode([
		'success' => false,
		'data' => null,
		'errors' => [
			[
				'code' => 401,
				'title' => 'offline'
			]
		],
		'message' => $app->get('offline_message', 'Site is offline for maintenance')
	]);
	die;
}

$controller = $app->input->get('controller');

require_once JPATH_SITE . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR
	. 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'CustomTablesAPIHelpers.php';

$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR
	. 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
	. 'Controller' . DIRECTORY_SEPARATOR . $controller . 'Controller.php';

if (file_exists($path)) {
	require_once $path;
	$className = $controller . 'Controller';
	$do = new $className;
	$do->execute();
} else {
	$app->setHeader('status', 404);
	$app->sendHeaders();
	echo json_encode(['errors' => [['title' => 'Controller not found.', 'controller' => $controller, 'code' => 404]]]);
}
die;
// /var/www/swaglivestreet/api/components/com_customtables/Controller/loginController.php
// /var/www/swaglivestreet/api/components/com_customtables