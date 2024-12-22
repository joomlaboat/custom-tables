<?php

use CustomTables\Catalog;
use CustomTables\CT;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

class RecordsController
{
	function execute()
	{
		$app = Factory::getApplication();
		$userId = CustomTablesAPIHelpers::checkToken();

		if (!$userId)
			die;

		$layoutName = Factory::getApplication()->input->get('layout');

		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
			. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'loader.php';

		if (!file_exists($path))
			die('CT Loader not found.');

		require_once($path);

		$loadTwig = true;

		CustomTablesLoader(false, false, null, 'com_customtables', $loadTwig);

		$ct = new CT(null, false);
		$ct->Env->clean = true;
		$ct->Params->blockExternalVars = true;

		$limit = Factory::getApplication()->input->getInt('limit');
		if ($limit)
			$ct->Params->limit = $limit;

		$order = Factory::getApplication()->input->getString('order');

		if ($order)
			$ct->Params->forceSortBy = $order;

		$catalog = new Catalog($ct);

		try {
			$result = $catalog->render($layoutName);
		} catch
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
			die;
		}

		try {
			$resultArray = json_decode($result, true);
		} catch
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
			die;
		}

		if (!is_array($resultArray)) {
			$app->setHeader('status', 500);
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 500,
						'title' => 'Server Error',
						'detail' => 'JSON syntax error',
						'result' => $result
					]
				],
				'message' => 'Server error'
			]);
			die;
		}

		// Send the response
		$app->setHeader('status', 200);
		$app->setHeader('Content-Type', 'application/vnd.api+json');
		$app->sendHeaders();

		// Calculate pagination metadata
		$total_records = (int)$ct->Table->recordcount;
		$records_per_page = count($resultArray); // Actual number of records in this response
		$current_page = (int)floor($ct->LimitStart / $ct->Limit) + 1; // Convert to 1-based page index
		$total_pages = (int)ceil($total_records / $ct->Limit); // Always round up for total pages

		// Construct the response
		$response = [
			"success" => true,
			"message" => "Data retrieved successfully",
			"messages" => null,
			"metadata" => [
				"total_records" => $total_records,
				"records_per_page" => $records_per_page,
				"current_page" => $current_page,
				"total_pages" => $total_pages,
				"offset" => $ct->LimitStart
			],
			"data" => $resultArray
		];

		// Send the response
		die(json_encode($response));
	}
}