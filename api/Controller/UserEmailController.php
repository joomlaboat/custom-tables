<?php

use Joomla\CMS\Factory;

class UserEmailController
{
	function execute()
	{
		$app = Factory::getApplication();
		$app->getSession()->close();
		$userId = CustomTablesAPIHelpers::checkToken();

		if (!$userId)
			die;

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['email'])
			->from($db->quoteName('#__users'))
			->where($db->quoteName('id') . ' = ' . $db->quote($userId));

		$db->setQuery($query);
		$result = $db->loadObject();

		if (!$result)
			CustomTablesAPIHelpers::fireError(401, 'User not found', 'Invalid user');

		CustomTablesAPIHelpers::fireSuccess($userId, ['email' => $result->email], 'Email retrieved successfully');
	}
}