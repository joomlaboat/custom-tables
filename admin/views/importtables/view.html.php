<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

// Import Joomla! libraries
jimport('joomla.application.component.view');

use CustomTables\common;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Version;

class CustomTablesViewImportTables extends HtmlView
{
	var $catalogview;
	var $version;

	function display($tpl = null)
	{
		$version = new Version;
		$this->version = (int)$version->getShortVersion();

		ToolbarHelper::title(common::translate('Custom Tables - Import Tables'), 'generic.png');

		parent::display($tpl);
	}
}
