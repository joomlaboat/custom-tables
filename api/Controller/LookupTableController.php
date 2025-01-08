<?php

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

			$ct = new CT(null, false);
			$result = ProInputBoxTableJoin::renderTableJoinSelectorJSON_getOptions($ct, $key, false);
			CustomTablesAPIHelpers::fireSuccess(null, $result, 'Lookup Table records loaded');
		} else {
			CustomTablesAPIHelpers::fireError(400, 'Lookup Table records NOT loaded', 'Bad Request');
		}
	}
}