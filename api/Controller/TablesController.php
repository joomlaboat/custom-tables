<?php

use Joomla\CMS\Factory;

class LoginController
{
	function execute()
	{
		// Get list of tables
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('tablename')
			->from($db->quoteName('#__customtables_tables'));
		$db->setQuery($query);
		$response = $db->loadObjectList();

		CustomTablesAPIHelpers::fireSuccess(null, $response, 'List of Tables loaded');
	}
}