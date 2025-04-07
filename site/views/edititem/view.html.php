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
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\Layouts;
use Joomla\CMS\Factory;
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

		$app = Factory::getApplication();
		$menuParams = $app->getParams();//TODO: Probably unnecessary
		$frmt = $menuParams->get('frmt') ?? null;
		if ($frmt !== null) {
			$this->ct->Env->frmt = $frmt;
			$this->ct->Env->clean = 1;
		}

		if (!empty($this->ct->Params->tableName))
			$this->ct->getTable($this->ct->Params->tableName);

		$layout = new Layouts($this->ct);

		try {
			$this->result = $layout->renderMixedLayout($this->ct->Params->editLayout, CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM);
		} catch (Throwable $exception) {
			$this->result['success'] = false;
			$this->result['message'] = $exception->getMessage();
		}

		$content = '';

		if ($this->ct->Table === null) {
			$content = common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND');
			$code = 500;
		} elseif ($this->result['success']) {
			if ($this->ct->Env->isModal)
				$content = $this->result['html'];
			elseif ($this->ct->Env->clean)
				$content = $this->result['short'];

			$code = 200;
		} else {
			if ($this->ct->Env->isModal)
				$content = $this->result['message'];
			elseif ($this->ct->Env->clean)
				$content = $this->result['short'];

			$code = 500;
		}

		if ($this->ct->Env->frmt === '' or $this->ct->Env->frmt === 'html') {
			parent::display($tpl);
		} else {
			CTMiscHelper::fireFormattedOutput($content, $this->ct->Env->frmt, $this->ct->Params->pageTitle, $code);
		}
		return true;
	}
}
