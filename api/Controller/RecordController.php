<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\Layouts;
use Joomla\CMS\Factory;

class RecordController
{
	/**
	 * @throws Exception
	 *
	 * @since 3.5.0
	 */
	function execute(bool $checkToken = true)
	{
		if ($checkToken) {
			$userId = CustomTablesAPIHelpers::checkToken();

			if (!$userId)
				die;
		}

		try {
			$ct = new CT([], true);
			$ct->Env->clean = true;
		} catch (Exception $e) {
			CTMiscHelper::fireError(501, $e->getMessage());
		}

		$layoutName = common::inputGetCmd('layout');

		if (empty($layoutName)) {
			$Itemid = Factory::getApplication()->input->get('Itemid');
			if ($Itemid > 0) {
				$ct->Params->constructJoomlaParams();
				$ct->getTable($ct->Params->tableName);
			} else {
				CTMiscHelper::fireError(500, common::translate('COM_CUSTOMTABLES_ERROR_LAYOUT_NOT_FOUND'));
			}
		}

		$layout = new Layouts($ct);
		$result = $layout->renderMixedLayout($layoutName, CUSTOMTABLES_LAYOUT_TYPE_DETAILS, 'none');

		if (isset($result['error']) or !isset($result['success']) or $result['success'] === false or !isset($result['content'])) {
			CTMiscHelper::fireError(500, $result['message'] ?? 'Error');
		}

		CTMiscHelper::fireSuccess(null, $result['content'], $result['message'] ?? 'Details page ready');
	}
}