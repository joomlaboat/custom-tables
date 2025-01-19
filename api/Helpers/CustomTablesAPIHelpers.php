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

class CustomTablesAPIHelpers
{
	/**
	 * @throws Exception
	 * @since 3.4.8
	 */
	static public function checkToken(): int
	{
		self::loadCT();

		$app = Factory::getApplication();
		$db = Factory::getDbo();

		// Get token from Authorization header
		$headers = getallheaders();
		$authHeader = $headers['Authorization'] ?? '';
		if (!$authHeader)
			CTMiscHelper::fireError(401, 'No token provided or Token not found', 'Invalid token');

		// Extract token from "Bearer <token>"
		$token = str_replace('Bearer ', '', $authHeader);

		// Check token in database
		$query = $db->getQuery(true)
			->select(['*'])
			->from($db->quoteName('#__user_keys'))
			->where([
				$db->quoteName('token') . ' = ' . $db->quote($token),
				$db->quoteName('series') . ' LIKE ' . $db->quote('API_%')
			]);
		$db->setQuery($query);
		$result = $db->loadObject();

		if (!$result)
			CTMiscHelper::fireError(401, 'Token not found', 'Invalid token');

		// Check if token is expired
		$lifetime = $app->get('lifetime', 150); // minutes
		$expires = new DateTime($result->time);
		$expires->modify('+' . $lifetime . ' minutes');
		if ($expires < new DateTime())
			CTMiscHelper::fireError(401, 'Token Expired', 'Invalid token');

		$series = $result->series;
		$seriesParts = explode('_', $series);

		if (count($seriesParts) == 2) {
			$sessionId = $seriesParts[1];

			if (!class_exists(CTMiscHelper::class))
				CTMiscHelper::fireError(500, 'CTMiscHelper not installed');

			$session = $app->getSession();
			$session->setId($sessionId);
		}

		// Update the token's last used time
		$query = $db->getQuery(true)
			->update($db->quoteName('#__user_keys'))
			->set($db->quoteName('time') . ' = ' . $db->quote((new DateTime())->format('Y-m-d H:i:s')))
			->where($db->quoteName('token') . ' = ' . $db->quote($token));
		$db->setQuery($query);
		$db->execute();

		$user = Factory::getUser($result->user_id);
		$app->loadIdentity($user);

		return $result->user_id;
	}

	static protected function loadCT()
	{
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
			. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'loader.php';

		if (!file_exists($path))
			CTMiscHelper::fireError(500, 'CT Loader not found');

		require_once($path);
		CustomTablesLoader(false, false, null, 'com_customtables', true);
	}


}

