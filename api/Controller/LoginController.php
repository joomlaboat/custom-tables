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

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

class LoginController
{
	function execute()
	{
		$app = Factory::getApplication();

		// Get POST data
		$input = file_get_contents('php://input');
		$data = json_decode($input);

		// Get username and password
		$username = $data->username ?? '';
		$password = $data->password ?? '';

		// Attempt to login
		$credentials = [
			'username' => $username,
			'password' => $password
		];

		// Get authentication response
		$auth = new Authentication;

		// Perform authentication
		$response = $auth->authenticate($credentials);

		if ($response->status === Authentication::STATUS_SUCCESS) {

			// Get user details
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('id, name, username, email')
				->from($db->quoteName('#__users'))
				->where($db->quoteName('username') . ' = ' . $db->quote($username));

			$db->setQuery($query);
			$user = $db->loadObject();

			// Create session
			$session = Factory::getSession();
			$session->set('user', new User($user->id));

			// Update or insert session in database
			$sessionId = $session->getId();

			// First try to update existing session
			$query = $db->getQuery(true)
				->update($db->quoteName('#__session'))
				->set([
					$db->quoteName('guest') . ' = 0',
					$db->quoteName('time') . ' = ' . $db->quote(time()),
					$db->quoteName('userid') . ' = ' . $db->quote($user->id),
					$db->quoteName('username') . ' = ' . $db->quote($user->username)
				])
				->where($db->quoteName('session_id') . ' = ' . $db->quote($sessionId));


			$db->setQuery($query);
			$result = $db->execute();

			// If no session exists, create new one
			if ($db->getAffectedRows() == 0) {
				$data = (object)[
					'session_id' => $sessionId,
					'client_id' => 0,
					'guest' => 0,
					'time' => time(),
					'data' => '',
					'userid' => $user->id,
					'username' => $user->username
				];

				$db->insertObject('#__session', $data);
			}


			// Prevent session auto-start for API requests
			if ($app->getSession()->isActive()) {
				$series = 'API_' . $app->getSession()->getId();
			} else {
				// Generate unique series identifier (prefix API_ followed by random string)
				$series = 'API_NEW_' . UserHelper::genRandomPassword(16);
			}


// Generate token
			$token = UserHelper::genRandomPassword(32);

// Delete any existing tokens for this user (using user_id index)
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__user_keys'))
				->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id))
				->where($db->quoteName('series') . ' LIKE ' . $db->quote('API_%'));
			$db->setQuery($query)->execute();

// Store new token
			$data = (object)[
				'id' => null,
				'user_id' => $user->id,
				'token' => $token,
				'series' => $series,  // Using our unique series
				'time' => Factory::getDate()->toSql(),
				'uastring' => 'API Access'
			];

			try {
				$db->insertObject('#__user_keys', $data);
			} catch (Exception $e) {
				echo $e->getMessage();
			}

			// Get lifetime from Joomla config
			$lifetime = Factory::getApplication()->get('lifetime', 15); // Default 15 minutes if not set
			$expires_in = $lifetime * 60; // Convert minutes to seconds

			CustomTablesAPIHelpers::fireSuccess($user->id, [
				'access_token' => $token,
				'token_type' => 'Bearer',
				'expires_in' => $expires_in,
				'user' => [
					'id' => $user->id,
					'name' => $user->name,
					'username' => $user->username,
					'email' => $user->email
				]], 'Authentication successful');

		} else {
			$message = 'Authentication failed';

			// Check authentication status
			switch ($response->status) {
				case Authentication::STATUS_FAILURE:
					$message = 'Invalid credentials';
					break;

				case Authentication::STATUS_EXPIRED:
					$message = 'Account expired';
					break;

				case Authentication::STATUS_DENIED:
					$message = 'Account denied/blocked';
					break;

				case Authentication::STATUS_UNKNOWN:
					$message = 'Unknown error during authentication';
					break;
			}

			CustomTablesAPIHelpers::fireError(401, $message, 'Authentication failed');
		}
	}
}