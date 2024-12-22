<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class CustomTablesAPIHelpers
{
	static public function checkToken(): int
	{
		$app = Factory::getApplication();


		//try {
		// Get token from Authorization header
		$headers = getallheaders();
		$authHeader = $headers['Authorization'] ?? '';

		if (!$authHeader) {
			//throw new Exception('No token provided');

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
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['*'])
			->from($db->quoteName('#__user_keys'))
			->where($db->quoteName('token') . ' = ' . $db->quote($token))
			->where($db->quoteName('series') . ' = ' . $db->quote('API'));

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
		$lifetime = Factory::getApplication()->get('lifetime', 15); // minutes
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

		$user = Factory::getUser($result->user_id);

		// Create a new session if needed
		$session = Factory::getSession();
		$session->set('user', $user);

		// Set the user identity properly
		$app->loadIdentity($user);

		return $result->user_id;
	}
}

