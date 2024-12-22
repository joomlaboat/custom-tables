<?php

use Joomla\CMS\Factory;
use JsonResponse;

class LoginController
{
	function execute()
	{
		$app = Factory::getApplication();

		// Get list of tables
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__customtables_tables'));
		$db->setQuery($query);
		$response = $db->loadObjectList();

		// Send the response
		$app->setHeader('status', 200);
		$app->setHeader('Content-Type', 'application/vnd.api+json');
		$app->sendHeaders();

		echo new JsonResponse([
			'data' => $response
		]);
	}
}