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
	}
}