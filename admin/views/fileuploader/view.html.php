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

// Import Joomla! libraries
jimport('joomla.application.component.view');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Session\Session;

use CustomTables\common;
use CustomTables\FileUploader;

class CustomTablesViewFileUploader extends HtmlView
{
	function display($tpl = null)
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'FileUploader.php');

		if (ob_get_contents()) ob_end_clean();

		if (!Session::checkToken('get')) {
			echo common::ctJsonEncode(['error' => 'Invalid Token']);
			exit;
		}

		$fieldname = common::inputGetCmd('fieldname');
		if (!empty($fieldname)) {
			$fileid = common::inputGetCmd($fieldname . '_fileid', '');
			echo FileUploader::uploadFile($fileid);
		} else {
			$fileid = common::inputGetCmd('fileid', '');
			echo FileUploader::uploadFile($fileid, 'txt html csv');
		}

		exit; //to stop rendering template and staff
	}
}
