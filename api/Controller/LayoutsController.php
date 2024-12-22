<?php

use Joomla\CMS\Factory;

class LoginController
{

	function execute()
	{
		$app = Factory::getApplication();

		// Get list of layouts
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__customtables_layouts'));
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