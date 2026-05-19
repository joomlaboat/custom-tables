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
use CustomTables\FileUploader;
use Joomla\CMS\MVC\View\HtmlView;
use CustomTables\CT;

// Import Joomla! libraries
jimport('joomla.application.component.view');

class CustomTablesViewFileUploader extends HtmlView
{
	function display($tpl = null)
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'FileUploader.php');

		if (ob_get_contents()) ob_end_clean();

		//Token parameter value must be sent using JS - not implemented yet
		//if (!Session::checkToken()) {
		//	echo common::ctJsonEncode(['error' => 'Invalid Token']);
		//	exit;
		//}

		//Authorization Check
		$ct = new CT(null, false);
		$ct->Params->constructJoomlaParams();

		try {

			if (empty($ct->Params->tableName)) {
				$tableId = common::inputGetInt('tableid');
				if (empty($tableId)) {
					echo common::ctJsonEncode(['error' => 'Unknown endpoint',
						'success' => false, 'message' => 'Unknown endpoint', 'short' => 'error']);

					exit;
				} else {
					$ct->getTable($tableId);
				}
			} else {
				$ct->getTable($ct->Params->tableName);
			}

			if (!$ct->Table) {
				echo common::ctJsonEncode(['error' => common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED'),
					'success' => false, 'message' => common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED'), 'short' => 'error']);

				exit;
			}

			if (!$ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT)) {
				echo common::ctJsonEncode(['error' => common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'),
					'success' => false, 'message' => common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'short' => 'error']);
				exit;
			}
		} catch (Exception $e) {
			echo common::ctJsonEncode(['error' => 'Runtime error',
				'success' => false, 'message' => $e->getMessage(), 'short' => 'error']);
			exit;
		}

		$fieldname = common::inputGetCmd('fieldname', '');
		$fileid = common::inputGetCmd($fieldname . '_fileid', '');
		$task = common::inputGetCmd('op', '');

		if ($task == 'delete') {
			$file = str_replace('/', '', common::inputPostString('name', '', 'create-edit-record'));
			$file = str_replace('..', '', $file);
			$file = str_replace('index.', '', $file);

			if ($file != '' and file_exists(CUSTOMTABLES_TEMP_PATH . $file)) {
				unlink(CUSTOMTABLES_TEMP_PATH . $file);
				echo common::ctJsonEncode(['status' => 'Deleted']);
			} else
				echo common::ctJsonEncode(['error' => 'File not found. Code: FU-1']);
		} else
			echo FileUploader::uploadFile($fileid);

		exit; //to stop rendering template and staff
	}
}
