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
use CustomTables\common;
use CustomTables\FileUploader;
use Joomla\CMS\MVC\View\HtmlView;

defined('_JEXEC') or die();

// Import Joomla! libraries
jimport('joomla.application.component.view');

class CustomTablesViewFileUploader extends HtmlView
{
	function display($tpl = null)
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'FileUploader.php');

		if (ob_get_contents()) ob_end_clean();

		$fileid = common::inputGetCmd('fileid', '');
		echo FileUploader::uploadFile($fileid, 'txt html csv');

		die; //to stop rendering template and staff
	}
}
