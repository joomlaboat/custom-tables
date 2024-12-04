<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage importtables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\FileUploader;
use CustomTables\ImportTables;
use CustomTables\MySQLWhereClause;

use Joomla\CMS\MVC\Model\ListModel;

class CustomTablesModelImportTables extends ListModel
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
	function importTables(&$msg): bool
	{
		$fileId = common::inputGetCmd('fileid', '');
		$filename = FileUploader::getFileNameByID($fileId);
		$menuType = 'Custom Tables Import Menu';

		$importFields = common::inputGetInt('importfields', 0);
		$importLayouts = common::inputGetInt('importlayouts', 0);
		$importMenu = common::inputGetInt('importmenu', 0);

		$category = '';
		return ImportTables::processFile($filename, $menuType, $msg, $category, $importFields, $importLayouts, $importMenu);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getLanguageByCODE($code): int
	{
		//Example: $code='en-GB';
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('language', $code);

		$rows = database::loadObjectList('#__customtables_languages', ['id'], $whereClause, null, null, 1);
		if (count($rows) != 1)
			return -1;

		return $rows[0]->id;
	}
}
