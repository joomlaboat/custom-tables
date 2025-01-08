<?php

use Joomla\CMS\Factory;

class LogoutController
{
	function execute()
	{
		$app = Factory::getApplication();
		$userId = CustomTablesAPIHelpers::checkToken();
		if (!$userId)
			die;

		try {
			// Get the user object
			$user = Factory::getUser($userId);

			if (!$user->id)
				CustomTablesAPIHelpers::fireError(401, 'User not found', 'Invalid user');

			// Get database connection
			$db = Factory::getDbo();

			// Delete the token from user_keys table
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__user_keys'))
				->where($db->quoteName('user_id') . ' = ' . $db->quote($userId));

			$db->setQuery($query);
			$db->execute();

			// Perform logout
			$app->logout($user->id);

			// Return success response
			echo json_encode([
				'success' => true,
				'data' => null,
				'message' => 'Logout successful'
			]);

		} catch (Exception $e) {
			CustomTablesAPIHelpers::fireError(500, 'Server Error');
		}
	}
}