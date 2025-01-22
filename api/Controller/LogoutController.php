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

use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;

class LogoutController
{
	/**
	 * @throws Exception
	 *
	 * @since 3.5.0
	 */
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
				CTMiscHelper::fireError(401, 'User not found', 'Invalid user');

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
			CTMiscHelper::fireError(500, 'Server Error');
		}
	}
}