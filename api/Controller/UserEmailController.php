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