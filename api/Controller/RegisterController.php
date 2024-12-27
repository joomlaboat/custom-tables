<?php

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

class RegisterController
{
	function execute()
	{
		$app = Factory::getApplication();

		// Get POST data
		$input = file_get_contents('php://input');
		$data = json_decode($input);

		// Validate required fields
		$requiredFields = ['name', 'username', 'email', 'password', 'password_confirm'];
		$errors = [];

		foreach ($requiredFields as $field) {
			if (!isset($data->$field) || empty($data->$field)) {
				$errors[] = [
					'code' => 400,
					'title' => 'Bad Request',
					'detail' => ucfirst($field) . ' is required'
				];
			}
		}

		// Validate passwords match
		if ($data->password !== $data->password_confirm) {
			$errors[] = [
				'code' => 400,
				'title' => 'Bad Request',
				'detail' => 'Passwords do not match'
			];
		}

		// Validate email format
		if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = [
				'code' => 400,
				'title' => 'Bad Request',
				'detail' => 'Invalid email format'
			];
		}

		if (!empty($errors)) {
			$app->setHeader('status', 400);
			$app->sendHeaders();
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => $errors,
				'message' => 'Validation failed'
			]);
			return;
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
				throw new Exception('Failed to create user');
			}

			$db->transactionCommit();

			// Generate API token for immediate login
			$token = UserHelper::genRandomPassword(32);

			// Store token in #__user_keys
			$tokenData = (object)[
				'id' => null,
				'user_id' => $user->id,
				'token' => $token,
				'series' => 'API',
				'time' => Factory::getDate()->toSql(),
				'uastring' => 'API Access'
			];
			$db->insertObject('#__user_keys', $tokenData);

			// Get lifetime from Joomla config
			$lifetime = Factory::getApplication()->get('lifetime', 15);
			$expires_in = $lifetime * 60;

			$app->setHeader('status', 201);
			$app->sendHeaders();
			echo json_encode([
				'success' => true,
				'data' => [
					'access_token' => $token,
					'token_type' => 'Bearer',
					'expires_in' => $expires_in,
					'user' => [
						'id' => $user->id,
						'name' => $user->name,
						'username' => $user->username,
						'email' => $user->email
					]
				],
				'message' => 'Registration successful'
			]);
		} catch (Exception $e) {
			$app->setHeader('status', 400);
			$app->sendHeaders();
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 400,
						'title' => 'Bad Request',
						'detail' => $e->getMessage()
					]
				],
				'message' => 'Registration failed'
			]);
		}
		die;
	}
}