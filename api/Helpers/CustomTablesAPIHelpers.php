<?php
defined('_JEXEC') or die;

use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;

class CustomTablesAPIHelpers
{
	static public function fireSuccess(?string $id = null, $dataVariable = null, ?string $message = null, ?array $metadata = null): void
	{
		if (is_array($dataVariable)) {
			$data = $dataVariable;
		} else {

			try {
				$data = json_decode($dataVariable, true, 512, JSON_THROW_ON_ERROR);

				if ($id === null and $data !== null and is_array($data) and $data['id'] !== null)
					$id = $data['id'];
			} catch (Exception $e) {
				$data = ['error' => $e->getMessage(), 'result' => $dataVariable];
			}
		}

		$app = Factory::getApplication();
		$app->setHeader('status', 200);
		$app->setHeader('Content-Type', 'application/vnd.api+json');
		$app->sendHeaders();

		$result = [
			'success' => true,
			'data' => $data,
			'message' => $message
		];

		if ($id !== null)
			$result['id'] = $id;

		if ($metadata !== null)
			$result['metadata'] = $metadata;
		
		die(json_encode($result, JSON_PRETTY_PRINT));
	}

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
			CustomTablesAPIHelpers::fireError(401, 'No token provided or Token not found', 'Invalid token');

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
			CustomTablesAPIHelpers::fireError(401, 'Token not found', 'Invalid token');

		// Check if token is expired
		$lifetime = $app->get('lifetime', 150); // minutes
		$expires = new DateTime($result->time);
		$expires->modify('+' . $lifetime . ' minutes');
		if ($expires < new DateTime())
			CustomTablesAPIHelpers::fireError(401, 'Token Expired', 'Invalid token');

		$series = $result->series;
		$seriesParts = explode('_', $series);

		if (count($seriesParts) == 2) {
			$sessionId = $seriesParts[1];

			if (!class_exists(CTMiscHelper::class))
				CustomTablesAPIHelpers::fireError(500, 'CTMiscHelper not installed');

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
			CustomTablesAPIHelpers::fireError(500, 'CT Loader not found');

		require_once($path);
		CustomTablesLoader(false, false, null, 'com_customtables', true);
	}

	/**
	 * @throws Exception
	 * @since 3.4.8
	 */
	static public function fireError(int $code = 500, ?string $title = null, ?string $message = null): void
	{
		$app = Factory::getApplication();
		// Handle invalid request method
		$app->setHeader('status', $code);
		echo json_encode([
			'success' => false,
			'data' => null,
			'errors' => [
				[
					'code' => $code,
					'title' => $title ?? 'Error'
				]
			],
			'message' => $message ?? ($title ?? 'Error')
		], JSON_PRETTY_PRINT);
		die;
	}
}

