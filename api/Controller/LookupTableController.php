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
use CustomTables\ProInputBoxTableJoin;

class LookupTableController
{
	function execute()
	{
		$userId = CustomTablesAPIHelpers::checkToken();
		$key = common::inputGetCmd('key');
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;

		if (file_exists($path . 'tablejoin.php') and file_exists($path . 'tablejoinlist.php')) {
			require_once($path . 'tablejoin.php');
			require_once($path . 'tablejoinlist.php');

			$ct = new CT([], true);

			$result = ProInputBoxTableJoin::renderTableJoinSelectorJSON_getOptions($ct, $key, false);
			CTMiscHelper::fireSuccess(null, $result, 'Lookup Table records loaded');
		} else {
			CTMiscHelper::fireError(400, 'Lookup Table records NOT loaded', 'Bad Request');
		}
	}
}