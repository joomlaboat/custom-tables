<?php

use Joomla\CMS\Factory;

class LoginController
{

	function execute()
	{
		// Get list of layouts
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('layoutname')
			->from($db->quoteName('#__customtables_layouts'));
		$db->setQuery($query);
		$response = $db->loadObjectList();

		// Send the response
		CustomTablesAPIHelpers::fireSuccess(null, $response, 'List of Layouts loaded');
	}
}