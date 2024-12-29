<?php

use CustomTables\common;
use CustomTables\CT;
use CustomTables\ProInputBoxTableJoin;
use Joomla\CMS\Factory;

class LookupTableController
{
	function execute()
	{
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
			. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'loader.php';

		if (!file_exists($path))
			die('CT Loader not found.');

		require_once($path);
		$loadTwig = true;
		CustomTablesLoader(false, false, null, 'com_customtables', $loadTwig);

		$userId = CustomTablesAPIHelpers::checkToken();
		$key = common::inputGetCmd('key');
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;
		$app = Factory::getApplication();

		if (file_exists($path . 'tablejoin.php') and file_exists($path . 'tablejoinlist.php')) {
			require_once($path . 'tablejoin.php');
			require_once($path . 'tablejoinlist.php');

			$ct = new CT(null, false);
			$result = ProInputBoxTableJoin::renderTableJoinSelectorJSON_getOptions($ct, $key, false);

			$app->setHeader('status', 200);
			$app->sendHeaders();

			echo json_encode([
				'success' => true,
				'data' => $result,
				'message' => 'Lookup Table records loaded'
			], JSON_PRETTY_PRINT);
			die;
		} else {
			$app->setHeader('status', 400);
			$app->sendHeaders();

			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 400,
						'title' => 'Bad Request',
					]
				],
				'message' => 'Lookup Table records NOT loaded'
			]);
			die;
		}
	}
}