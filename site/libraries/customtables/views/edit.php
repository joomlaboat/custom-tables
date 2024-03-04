<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use LayoutProcessor;
use tagProcessor_Edit;

class Edit
{
	var CT $ct;
	var string $layoutContent;
	var ?array $row;
	var int $layoutType;
	var ?string $pageLayoutNameString;
	var ?string $pageLayoutLink;

	function __construct(CT &$ct)
	{
		$this->ct = &$ct;
		$this->row = null;
		$this->layoutType = 0;
		$this->layoutContent = '';
		$this->pageLayoutNameString = null;
		$this->pageLayoutLink = null;
	}

	function load(): bool
	{
		if ($this->ct->Params->editLayout != '') {
			$Layouts = new Layouts($this->ct);
			$this->layoutContent = $Layouts->getLayout($this->ct->Params->editLayout);
			$this->pageLayoutNameString = $this->ct->Params->editLayout;

			if (!isset($Layouts->layoutId)) {
				$this->ct->errors[] = $this->ct->Params->editLayout . '" not found.';
				return false;
			}

			$this->pageLayoutLink = '/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;

			if ($Layouts->layoutType === null) {
				$this->ct->errors[] = 'Layout "' . $this->ct->Params->editLayout . '" not found or the type is not set.';
				return false;
			}

		} else {
			$Layouts = new Layouts($this->ct);
			$this->layoutContent = $Layouts->createDefaultLayout_Edit($this->ct->Table->fields, true);
			$this->pageLayoutNameString = 'Default Edit Layout';
			$this->pageLayoutLink = null;
		}
		$this->ct->LayoutVariables['layout_type'] = $this->layoutType;
		return true;
	}

	public function processLayout(?array $row = null): string
	{
		if ($row !== null)
			$this->row = $row;

		if ($this->ct->Env->legacySupport) {
			$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR;
			require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');
			require_once($path . 'layout.php');

			$LayoutProc = new LayoutProcessor($this->ct, $this->layoutContent);
			$this->layoutContent = $LayoutProc->fillLayout(null, null, '||', false, true);
			tagProcessor_Edit::process($this->ct, $this->layoutContent, $row, true);
		}

		$twig = new TwigProcessor($this->ct, $this->layoutContent, true);
		$result = $twig->process($this->row);

		if ($twig->errorMessage !== null)
			$this->ct->errors[] = $twig->errorMessage;

		return $result;
	}

	function render(?array $row, string $formLink, string $formName, bool $addFormTag = true): string
	{
		$result = '';

		if ($row !== null)
			$this->row = $row;

		if (!is_null($this->ct->Params->ModuleId))
			$formName .= $this->ct->Params->ModuleId;

		if (defined('_JEXEC')) {
			if ($this->ct->Env->legacySupport) {
				$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR;
				require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');
				require_once($path . 'layout.php');
			}
			if ($this->ct->Params->ModuleId === null or $this->ct->Params->ModuleId == 0) {
				HTMLHelper::_('jquery.framework');
				jimport('joomla.html.html.bootstrap');
			}
		}

		common::loadJSAndCSS($this->ct->Params, $this->ct->Env);

		if (!$this->ct->Params->blockExternalVars and $this->ct->Params->showPageHeading and $this->ct->Params->pageTitle !== null) {

			if (defined('_JEXEC'))
				$result .= '<div class="page-header' . common::ctStripTags($this->ct->Params->pageClassSFX ?? '') . '"><h2 itemprop="headline">'
					. common::translate($this->ct->Params->pageTitle) . '</h2></div>';
			else
				$result .= '<div class="page-header' . common::ctStripTags($this->ct->Params->pageClassSFX ?? '') . '"><h2 itemprop="headline">'
					. $this->ct->Params->pageTitle . '</h2></div>';
		}

		$listing_id = $this->row[$this->ct->Table->realidfieldname] ?? 0;

		if ($addFormTag) {
			$result .= '<form action="' . $formLink . '" method="post" name="' . $formName . '" id="' . $formName . '" class="form-validate form-horizontal well" '
				. 'data-tableid="' . $this->ct->Table->tableid . '" data-recordid="' . $listing_id . '" '
				. 'data-version=' . $this->ct->Env->version . '>';
		}

		if (defined('_JEXEC'))
			$result .= ($this->ct->Env->version < 4 ? '<fieldset>' : '<fieldset class="options-form">');

		//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.

		$this->ct->isEditForm = true; //These changes input box prefix

		if ($this->ct->Env->legacySupport) {
			$LayoutProc = new LayoutProcessor($this->ct, $this->layoutContent);

			//Better to run tag processor before rendering form edit elements because of IF statements that can exclude the part of the layout that contains form fields.
			$pageLayout = $LayoutProc->fillLayout($this->row, null, '||', false, true);
			tagProcessor_Edit::process($this->ct, $pageLayout, $this->row);
		} else
			$pageLayout = $this->layoutContent;

		$twig = new TwigProcessor($this->ct, $pageLayout, false, false, true, $this->pageLayoutNameString, $this->pageLayoutLink);

		try {
			$pageLayout = $twig->process($this->row);
		} catch (Exception $e) {
			die('Caught exception: ' . $e->getMessage());
		}

		if ($twig->errorMessage !== null) {
			if (defined('_JEXEC')) {
				$this->ct->errors[] = $twig->errorMessage;
			} else {
				die($twig->errorMessage);
			}
		}

		if ($this->ct->Params->allowContentPlugins)
			$pageLayout = CTMiscHelper::applyContentPlugins($pageLayout);

		$result .= $pageLayout;

		$returnTo = '';

		if (common::inputGetBase64('returnto'))
			$returnTo = common::getReturnToURL();
		elseif ($this->ct->Params->returnTo)
			$returnTo = $this->ct->Params->returnTo;

		$encodedReturnTo = common::makeReturnToURL($returnTo);

		if ($listing_id == 0) {
			$result .= '<input type="hidden" name="published" value="' . (int)$this->ct->Params->publishStatus . '" />';
		}

		$result .= '<input type="hidden" name="task" id="task" value="save" />'
			. '<input type="hidden" name="returnto" id="returnto" value="' . $encodedReturnTo . '" />'
			. '<input type="hidden" name="listing_id" id="listing_id" value="' . $listing_id . '" />';

		if (!is_null($this->ct->Params->ModuleId))
			$result .= '<input type="hidden" name="ModuleId" id="ModuleId" value="' . $this->ct->Params->ModuleId . '" />';

		if (defined('_JEXEC')) {
			$result .= (common::inputGetCmd('tmpl', '') != '' ? '<input type="hidden" name="tmpl" value="' . common::inputGetCmd('tmpl', '') . '" />' : '');
			$result .= HTMLHelper::_('form.token');

		} elseif (defined('WPINC')) {
			//$result .= wp_nonce_field('create-record', '_wpnonce_create-record'); Plugin calls it
		}

		if (defined('_JEXEC'))
			$result .= '</fieldset>';

		if ($addFormTag)
			$result .= '</form>';

		return $result;
	}
}