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

use CustomTables\CT;
use CustomTables\Details;
use Joomla\CMS\MVC\View\HtmlView;

class CustomTablesViewDetails extends HtmlView
{
	var CT $ct;
	var Details $details;

	function display($tpl = null)
	{
		$this->ct = new CT(null, false);
		$this->ct->Params->constructJoomlaParams();
		$this->details = new Details($this->ct);

		if ($this->ct->Env->print)
			$this->ct->document->setMetaData('robots', 'noindex, nofollow');

		if ($this->details->load()) {

			if ($this->details->layoutType == 8)
				$this->ct->Env->frmt = 'xml';
			elseif ($this->details->layoutType == 9)
				$this->ct->Env->frmt = 'csv';
			elseif ($this->details->layoutType == 10)
				$this->ct->Env->frmt = 'json';

			parent::display($tpl);
		}
	}
}
