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
use CustomTables\CT;
use CustomTables\Edit;
use Joomla\CMS\MVC\View\HtmlView;
use CustomTables\ctProHelpers;

jimport('joomla.html.pane');
jimport('joomla.application.component.view'); //Important to get menu parameters

class CustomTablesViewEditItem extends HtmlView
{
	var CT $ct;
	var ?array $row;
	var string $formLink;
	var Edit $editForm;

	function display($tpl = null): bool
	{
		$this->ct = new CT(null, false);
		$Model = $this->getModel();
		$Model->load($this->ct);

		if (!$this->ct->CheckAuthorization(1)) {
			//not authorized
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
			echo common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED');
			return false;
		}

		if (empty($this->ct->Table)) {
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED'));
			echo common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED');
			return false;
		}

		if (!isset($this->ct->Table->fields) or !is_array($this->ct->Table->fields)) {
			common::enqueueMessage('Fields not set');
			echo common::translate('Fields not set');
			return false;
		}

		if ($this->ct->Env->frmt == 'json')


			require_once('tmpl' . DIRECTORY_SEPARATOR . 'json.php');
		else {

			$this->formLink = $this->ct->Env->WebsiteRoot . 'index.php?option=com_customtables&amp;view=edititem' . ($this->ct->Params->ItemId != 0 ? '&amp;Itemid=' . $this->ct->Params->ItemId : '');
			if (!is_null($this->ct->Params->ModuleId))
				$this->formLink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

			$this->editForm = new Edit($this->ct);
			if (!$this->editForm->load()) {
				if (count($this->ct->errors) > 0)
					common::enqueueMessage('Errors: ' . implode(',', $this->ct->errors));

				echo common::translate('Errors:' . implode(',', $this->ct->errors));
				return false;
			}

			if ($this->ct->getRecord($this->ct->Params->listing_id)) {
				$this->row = $this->ct->Table->record;

				if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers'))
					$this->row = ctProHelpers::getSpecificVersionIfSet($this->ct, $this->row);
			} else
				$this->row = null;

			parent::display($tpl);
		}
		return true;
	}
}
