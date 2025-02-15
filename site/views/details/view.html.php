<?php
/**
 * CustomTablesViewDetails
 *
 * This class is part of the CustomTables Joomla! component and represents the view for displaying
 * detailed records. It initializes and uses the `CT` and `Details` classes for handling the data
 * and rendering the output in various formats such as XML, CSV, and JSON.
 *
 * @package     CustomTables
 * @subpackage  Views
 * @author      Ivan Komlev <support@joomlaboat.com>
 * @link        https://joomlaboat.com
 * @copyright   (C) 2018-2025, Ivan Komlev
 * @license     GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 * @since       1.0.0
 */

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\Layouts;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class CustomTablesViewDetails extends HtmlView
{
	var CT $ct;
	var array $result;

	function display($tpl = null)
	{
		$this->ct = new CT(null, false);
		$this->ct->Params->constructJoomlaParams();

		$app = Factory::getApplication();
		$menuParams = $app->getParams();
		$frmt = $menuParams->get('frmt') ?? null;
		if ($frmt !== null) {
			$this->ct->Env->frmt = $frmt;
			$this->ct->Env->clean = 1;
		}

		if (!empty($this->ct->Params->tableName))
			$this->ct->getTable($this->ct->Params->tableName);

		$layout = new Layouts($this->ct);
		$this->result = $layout->renderMixedLayout($this->ct->Params->detailsLayout, CUSTOMTABLES_LAYOUT_TYPE_DETAILS, 'none');

		if ($this->ct->Env->print)
			Factory::getApplication()->getDocument()->setMetaData('robots', 'noindex, nofollow');

		if ($this->ct->Env->isModal) {
			$this->ct->Env->clean = 1;
			$this->ct->Env->frmt = 'rawhtml';
		}

		if ($this->ct->Table === null) {
			$content = common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND');
			$code = 500;
		} elseif ($this->result['success']) {
			$content = $this->result['html'];
			$code = 200;
		} else {
			if ($this->ct->Env->clean)
				$content = $this->result['short'];
			else
				$content = $this->result['message'];

			$code = 500;
		}

		if ($this->ct->Env->frmt === '' or $this->ct->Env->frmt === 'html') {

			if ($code === 500) {
				common::enqueueMessage($content);
			} else
				parent::display($tpl);
		} else {
			CTMiscHelper::fireFormattedOutput($content, $this->ct->Env->frmt, $this->ct->Params->pageTitle, $code);
		}
	}
}
