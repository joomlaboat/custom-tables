<?php

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Layouts;
use Joomla\CMS\Factory;

class RecordController
{
	function execute()
	{
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
			CustomTablesAPIHelpers::fireError(500, $result['message'] ?? 'Error');
		}

		CustomTablesAPIHelpers::fireSuccess(null, $result['html'], $result['message'] ?? 'Details page ready');
	}
}