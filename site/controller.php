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
use CustomTables\CTMiscHelper;
use CustomTables\Value_file;
use Joomla\CMS\Factory;
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

			try {
				$fileOutput = new Value_file();
				$fileOutput->process_file_link($file);
				$fileOutput->display();
			} catch (Exception $e) {
				common::enqueueMessage($e->getMessage());
			}
		}

		// Make sure we have the default view
		if (common::inputGetCmd('view') == '') {
			common::inputSet('view', 'catalog');
			parent::display();
		} else {
			$view = common::inputGetCmd('view');

			switch ($view) {
				case 'edit' :
				case 'lookuptable' :
				case 'record' :
				case 'records' :
					$controller = ucwords($view);
					$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR
						. 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
						. 'Controller' . DIRECTORY_SEPARATOR . $controller . 'Controller.php';

					if (file_exists($path)) {

						require_once JPATH_SITE . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR
							. 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'CustomTablesAPIHelpers.php';

						require_once $path;
						$className = $controller . 'Controller';

						$do = new $className;

						try {
							$task = common::inputGetCmd('task');
							$do->execute(false, $task);
						} catch (Throwable $e) {
							echo $e->getFile() . '<br/>' . $e->getLine() . '<br/>' . $e->getMessage() . '<br/>' . $e->getTraceAsString();
							CTMiscHelper::fireError(500, $e->getMessage());
						}
					} else {
						$app = Factory::getApplication();
						CTMiscHelper::fireError(404, 'Controller [' . $controller . '] not found.', $app->get('offline_message', 'Controller not found.'));
					}

					die;

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
