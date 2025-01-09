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

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Layouts;

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