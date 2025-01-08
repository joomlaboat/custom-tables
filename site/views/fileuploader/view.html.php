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
use Joomla\CMS\MVC\View\HtmlView;

// Import Joomla! libraries
jimport('joomla.application.component.view');

class CustomTablesViewFileUploader extends HtmlView
{
	function display($tpl = null)
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'uploader.php');

		if (ob_get_contents()) ob_end_clean();

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

		die; //to stop rendering template and staff
	}
}
