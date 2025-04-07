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

use CustomTables\Catalog;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;

class RecordsController
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

		$layoutName = Factory::getApplication()->input->get('layout');

		$ct = new CT([], true);
		$ct->Env->clean = true;

		$limit = Factory::getApplication()->input->getInt('limit');
		if ($limit)
			$ct->Params->limit = $limit;

		$order = Factory::getApplication()->input->getString('order');

		if ($order)
			$ct->Params->forceSortBy = $order;

		$catalog = new Catalog($ct);

		$result = '';

		try {
			$result = $catalog->render($layoutName);
		} catch
		(Exception $e) {
			CTMiscHelper::fireError(500, 'Server Error');
		}

		try {
			$resultArray = json_decode($result, true);
		} catch
		(Exception $e) {
			CTMiscHelper::fireError(500, 'Server Error');
		}

		if (!is_array($resultArray)) {
			CTMiscHelper::fireError(500, 'Server Error');
		}

		// Calculate pagination metadata
		$total_records = $ct->Table->recordcount;
		$records_per_page = count($resultArray); // Actual number of records in this response
		$current_page = (int)floor($ct->LimitStart / $ct->Limit) + 1; // Convert to 1-based page index
		$total_pages = (int)ceil($total_records / $ct->Limit); // Always round up for total pages

		CTMiscHelper::fireSuccess(null, $resultArray, 'Data retrieved successfully', [
			"total_records" => $total_records,
			"records_per_page" => $records_per_page,
			"current_page" => $current_page,
			"total_pages" => $total_pages,
			"offset" => $ct->LimitStart
		]);
	}
}