<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\CTMiscHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

class RegisterController
{
	/**
	 * @throws Exception
	 *
	 * @since 3.5.0
	 */
	function execute()
	{
		// Get POST data
		$input = file_get_contents('php://input');
		$data = json_decode($input);

		// Validate required fields
		$requiredFields = ['name', 'username', 'email', 'password', 'password_confirm'];

		foreach ($requiredFields as $field) {
			if (empty($data->$field))
				CTMiscHelper::fireError(400, ucfirst($field) . ' is required', 'Bad Request');
		}

		// Validate passwords match
		if ($data->password !== $data->password_confirm) {
			CTMiscHelper::fireError(400, 'Passwords do not match', 'Bad Request');
		}

		// Validate email format
		if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
			CTMiscHelper::fireError(400, 'Invalid email format', 'Bad Request');
		}

		try {
			$db = Factory::getDbo();

			// Check if username exists
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__users'))
				->where($db->quoteName('username') . ' = ' . $db->quote($data->username));
			$db->setQuery($query);
			if ($db->loadResult() > 0) {
				throw new Exception('Username already exists');
			}

			// Check if email exists
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__users'))
				->where($db->quoteName('email') . ' = ' . $db->quote($data->email));
			$db->setQuery($query);
			if ($db->loadResult() > 0) {
				throw new Exception('Email already exists');
			}

			// Create user object
			$user = new User;
			$user->name = $data->name;
			$user->username = $data->username;
			$user->email = $data->email;
			$user->password = UserHelper::hashPassword($data->password);
			$user->block = 0;
			$user->registerDate = Factory::getDate()->toSql();

			// Get default user group from configuration
			$userParams = ComponentHelper::getParams('com_users');
			$defaultUserGroup = $userParams->get('new_usertype', 2);

			$user->groups = array($defaultUserGroup);

			// Start database transaction
			$db->transactionStart();

			// Save user
			if (!$user->save()) {
				CTMiscHelper::fireError(400, 'Failed to create user', 'Bad Request');
			}

			$db->transactionCommit();

			// Generate API token for immediate login
			$token = UserHelper::genRandomPassword(32);
			$series = 'API_' . UserHelper::genRandomPassword(16);

			// Store token in #__user_keys
			$tokenData = (object)[
				'id' => null,
				'user_id' => $user->id,
				'token' => $token,
				'series' => $series,
				'time' => Factory::getDate()->toSql(),
				'uastring' => 'API Access'
			];
			$db->insertObject('#__user_keys', $tokenData);

			// Get lifetime from Joomla config
			$lifetime = Factory::getApplication()->get('lifetime', 15);
			$expires_in = $lifetime * 60;

			CTMiscHelper::fireSuccess($user->id, [
				'access_token' => $token,
				'token_type' => 'Bearer',
				'expires_in' => $expires_in,
				'user' => [
					'id' => $user->id,
					'name' => $user->name,
					'username' => $user->username,
					'email' => $user->email
				]
			], 'Registration successful');
		} catch (Exception $e) {
			CTMiscHelper::fireError(400, $e->getMessage(), 'Bad Request');
		}
		die;
	}
}