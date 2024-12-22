<?php

use Joomla\CMS\Factory;

class UserEmailController
{
	function execute()
	{
		$app = Factory::getApplication();
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

		if (!$result) {
			$app->setHeader('status', 401);
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 401,
						'title' => 'Invalid user',
						'detail' => 'User not found'
					]
				],
				'message' => 'Invalid user'
			]);
			die;
		}
// Return email
		echo json_encode([
			'success' => true,
			'data' => [
				'email' => $result->email
			],
			'message' => 'Email retrieved successfully'
		]);

		/*} catch
		(Exception $e) {
			$app->setHeader('status', 500);
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 500,
						'title' => 'Server Error',
						'detail' => $e->getMessage()
					]
				],
				'message' => 'Server error'
			]);
		}*/
	}
}