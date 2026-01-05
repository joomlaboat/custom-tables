<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

// Import Joomla! libraries
jimport('joomla.application.component.view');

use CustomTables\common;
use CustomTables\CT;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class CustomTablesViewImportTables extends HtmlView
{
	var string $fieldInputPrefix;

	function display($tpl = null)
	{
		$ct = new CT;
		$this->fieldInputPrefix = $ct->Env->field_input_prefix ?? 'ct_';

		ToolbarHelper::title(common::translate('Custom Tables - Import Tables'), 'generic.png');
		parent::display($tpl);
	}
}
