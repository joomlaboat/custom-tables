<?php

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Layouts;
use Joomla\CMS\Factory;

class RecordController
{
	function execute()
	{
		//$app = Factory::getApplication();
		$userId = CustomTablesAPIHelpers::checkToken();

		if (!$userId)
			die;

		$ct = @ new CT(null, false);
		$ct->Env->clean = true;

		$layoutName = common::inputGetCmd('layout');

		$ct->Env->clean = true;
		$ct->Params->blockExternalVars = false;

		$layout = new Layouts($ct);
		$result = $layout->renderMixedLayout($layoutName, null, 1, true);

		if (isset($result['error']) or !isset($result['success']) or $result['success'] === false or !isset($result['html'])) {
			CustomTablesAPIHelpers::fireError($result['message'] ?? 'Error');
			// Handle invalid request method
			/*
			$app = Factory::getApplication();
			$app->setHeader('status', 500);
			echo json_encode([
				'success' => false,
				'data' => null,
				'errors' => [
					[
						'code' => 500,
						'title' => $result['message'] ?? 'Error'
					]
				],
				'message' => $result['message'] ?? 'Error'
			]);
			die;
			*/
		}

		/*
		$j = [];
		if (isset($result['html'])) {
			try {
				$j = json_decode($result['html'], true, 512, JSON_THROW_ON_ERROR);
			} catch (Exception $e) {
				$result = ['success' => false, 'message' => $e->getMessage()];
			}
		}
		*/

		CustomTablesAPIHelpers::fireSuccess(null, $result['html'], $result['message'] ?? 'Details page ready');
		/*
		$app->setHeader('status', 200);
		$app->sendHeaders();

		echo json_encode([
			'success' => true,
			'data' => $j,
			'message' => $result['message'] ?? 'Details page ready'
		], JSON_PRETTY_PRINT);
		die;
		*/
	}
}