<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\Value_file;
use Joomla\CMS\MVC\Controller\BaseController;

class CustomTablesController extends BaseController
{
	function display($cacheable = false, $urlparams = array())
	{
		$file = common::inputGetString('file');
		if ($file != '') {
			//Load file instead

			require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
				. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php');

			$fileOutput = new Value_file();
			$fileOutput->process_file_link($file);
			$fileOutput->display();

			if (count($fileOutput->ct->errors) > 0) {
				echo '<p>File Error: ' . implode(', ', $fileOutput->ct->errors) . '</p>';
			}
		}

		// Make sure we have the default view
		if (common::inputGetCmd('view') == '') {
			common::inputSet('view', 'catalog');
			parent::display();
		} else {
			$view = common::inputGetCmd('view');

			switch ($view) {
				case 'log' :
					require_once('controllers/log.php');
					break;

				case 'edititem' :
					require_once('controllers/save.php');
					break;

				case ($view == 'home' || $view == 'catalog') :
					require_once('controllers/catalog.php');
					break;

				case 'editphotos' :
					require_once('controllers/editphotos.php');
					break;

				case 'editfiles' :
					require_once('controllers/editfiles.php');
					break;

				case 'files':
				case 'fileuploader':
				case 'chatgpt' :
					parent::display();
					break;

				case 'details' :
					require_once('controllers/details.php');
					break;
			}
		}
	}
}
