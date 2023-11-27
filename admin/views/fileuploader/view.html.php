<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\common;
use Joomla\CMS\MVC\View\HtmlView;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

// Import Joomla! libraries
jimport('joomla.application.component.view');

class CustomTablesViewFileUploader extends HtmlView
{
	function display($tpl = null)
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'uploader.php');

		if (ob_get_contents()) ob_end_clean();

		$fileid = common::inputGetCmd('fileid', '');
		echo ESFileUploader::uploadFile($fileid, 'txt html');

		die; //to stop rendering template and staff
	}
}
