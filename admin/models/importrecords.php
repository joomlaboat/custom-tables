<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage importtables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\FileUploader;
use CustomTables\ImportCSV;
use Joomla\CMS\MVC\Model\ListModel;

class CustomTablesModelImportRecords extends ListModel
{
	var CT $ct;

	function __construct()
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'uploader.php');

		parent::__construct();
	}

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	function importRecords(&$msg): bool
	{
		$fileId = common::inputGetCmd('fileid', '');
		$filename = FileUploader::getFileNameByID($fileId);

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
			. 'helpers' . DIRECTORY_SEPARATOR . 'ImportCSV.php');

		$tableId = common::inputGetInt('tableid', 0);

		try {
			$msg = ImportCSV::importCSVFile($filename, $tableId);
		} catch (Exception $e) {
			$msg = $e->getMessage();
		}

		if ($msg !== null)
			common::enqueueMessage($msg);

		unlink($filename);

		return true;
	}
}
