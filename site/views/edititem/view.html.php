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

use CustomTables\CT;
use CustomTables\Layouts;
use Joomla\CMS\MVC\View\HtmlView;
use CustomTables\ctProHelpers;

jimport('joomla.html.pane');
jimport('joomla.application.component.view'); //Important to get menu parameters

class CustomTablesViewEditItem extends HtmlView
{
	var CT $ct;
	var $result;

	function display($tpl = null): bool
	{
		$this->ct = new CT(null, false);
		$this->ct->Params->constructJoomlaParams();

		if (!empty($this->ct->Params->tableName))
			$this->ct->getTable($this->ct->Params->tableName);

		$layout = new Layouts($this->ct);

		$this->result = $layout->renderMixedLayout($this->ct->Params->editLayout, CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM);
		if ($this->result['success']) {
			if ($this->ct->Env->isModal)
				die($this->result['html']);
			elseif ($this->ct->Env->clean)
				die($this->result['short']);
		} else {
			if ($this->ct->Env->isModal)
				die($this->result['message']);
			elseif ($this->ct->Env->clean)
				die($this->result['short']);
		}

		parent::display($tpl);
		return true;
	}
}
