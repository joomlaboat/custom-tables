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
use CustomTables\CTMiscHelper;
use CustomTables\Layouts;

class RecordController
{
	/**
	 * @throws Exception
	 *
	 * @since 3.5.0
	 */
	function execute()
	{
		$userId = CustomTablesAPIHelpers::checkToken();

		if (!$userId)
			die;

		$ct = new CT([], true);
		$ct->Env->clean = true;
		$layoutName = common::inputGetCmd('layout');
		$ct->Env->clean = true;
		$layout = new Layouts($ct);
		$result = $layout->renderMixedLayout($layoutName, CUSTOMTABLES_LAYOUT_TYPE_DETAILS, 'none');

		if (isset($result['error']) or !isset($result['success']) or $result['success'] === false or !isset($result['html'])) {
			CTMiscHelper::fireError(500, $result['message'] ?? 'Error');
		}

		CTMiscHelper::fireSuccess(null, $result['html'], $result['message'] ?? 'Details page ready');
	}
}