<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class CustomTablesAPIHelpers
{
	static public function checkToken(): int
	{
		$app = Factory::getApplication();
		$db = Factory::getDbo();

		// Get token from Authorization header
		$headers = getallheaders();
		$authHeader = $headers['Authorization'] ?? '';
		if (!$authHeader) {
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 401,
						'title' => 'No token provided',
						'detail' => 'Token not found'
					]
				],
				'message' => 'Invalid token'
			]);
			die;
		}

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

		if (!$result) {
			$app->setHeader('status', 401);
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 401,
						'title' => 'Invalid Token',
						'detail' => 'Token not found'
					]
				],
				'message' => 'Invalid token'
			]);
			die;
		}

		// Check if token is expired
		$lifetime = $app->get('lifetime', 150); // minutes
		$expires = new DateTime($result->time);
		$expires->modify('+' . $lifetime . ' minutes');
		if ($expires < new DateTime()) {
			$app->setHeader('status', 401);
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 401,
						'title' => 'Token Expired',
						'detail' => 'Token has expired'
					]
				],
				'message' => 'Token expired'
			]);
			die;
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
}

