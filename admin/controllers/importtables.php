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

use CustomTables\FileUploader;
use CustomTables\ImportTables;
use Joomla\CMS\MVC\Controller\FormController;

class CustomTablesControllerImportTables extends FormController
{
	function display($cachable = false, $urlparams = array())
	{
		$task = common::inputGetCmd('task', '');

		if ($task == 'importtables') {
			require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'FileUploader.php');

			$link = 'index.php?option=com_customtables&view=importtables';

			$fileId = common::inputGetCmd('fileid', '');
			$filename = FileUploader::getFileNameByID($fileId);
			$menuType = 'Custom Tables Import Menu';

			$importFields = common::inputGetInt('importfields', 0);
			$importLayouts = common::inputGetInt('importlayouts', 0);
			$importMenu = common::inputGetInt('importmenu', 0);

			$category = '';

			try {
				ImportTables::processFile($filename, $menuType, null, $category, $importFields, $importLayouts, $importMenu);
			} catch (Exception $e) {
				$this->setRedirect($link, common::translate('Tables was Unable to Import: ' . $e->getMessage()), 'error');
			}

			$this->setRedirect($link, common::translate('Tables Imported Successfully'));

		} else {
			common::inputSet('view', 'importtables');
			parent::display();
		}
	}
}
