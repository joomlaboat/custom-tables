<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @copyright (C) 2018-2026. Ivan Komlev
 * @link https://joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.view'); //Important to get menu parameters

use CustomTables\Value_file;
use Joomla\CMS\MVC\View\HtmlView;

class CustomTablesViewFiles extends HtmlView
{
	function display($tpl = null)
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php');

		$fileOutput = new Value_file();
		$fileOutput->display();
	}
}
