<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\HTML\HTMLHelper;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Edit
{
	var CT $ct;
	var string $layoutContent;
	var ?array $row;
	var int $layoutType;
	var ?string $pageLayoutNameString;
	var ?string $pageLayoutLink;

	function __construct(CT $ct)
	{
		$this->ct = $ct;
		$this->row = null;
		$this->layoutType = 0;
		$this->layoutContent = '';
		$this->pageLayoutNameString = null;
		$this->pageLayoutLink = null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function load(): bool
	{
		if (!empty($this->ct->Params->editLayout)) {
			$Layouts = new Layouts($this->ct);

			$this->layoutContent = $Layouts->getLayout($this->ct->Params->editLayout);
			if (isset($Layouts->layoutId)) {
				$this->layoutType = $Layouts->layoutType;
				$this->pageLayoutNameString = $this->ct->Params->editLayout;
			} else {
				throw new Exception('Layout "' . $this->ct->Params->editLayout . '" not found.');
			}

			$this->pageLayoutLink = common::UriRoot(true, true) . 'administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;

			if ($Layouts->layoutType === null)
				throw new Exception('Layout "' . $this->ct->Params->editLayout . '" not found or the type is not set.');

		} else {
			$Layouts = new Layouts($this->ct);
			$this->layoutContent = $Layouts->createDefaultLayout_Edit($this->ct->Table->fields);
			$this->pageLayoutNameString = 'Default Edit Layout';
			$this->pageLayoutLink = null;
		}
		$this->ct->LayoutVariables['layout_type'] = $this->layoutType;
		return true;
	}

	/**
	 * @throws SyntaxError
	 * @throws RuntimeError
	 * @throws LoaderError
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function processLayout(?array $row = null): string
	{
		if ($row !== null)
			$this->row = $row;

		try {
			$twig = new TwigProcessor($this->ct, $this->layoutContent, true);
			$result = $twig->process($this->row);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		return $result;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(?array $row, $formLink, string $formName, bool $addFormTag = true): string
	{
		$result = '';

		if ($row !== null)
			$this->row = $row;

		if ($this->ct->Env->clean == 0) {
			//common::loadJSAndCSS($this->ct->Params, $this->ct->Env, $this->ct->Table->fieldInputPrefix);

			if (!$this->ct->Params->blockExternalVars and $this->ct->Params->showPageHeading and $this->ct->Params->pageTitle !== null) {

				if (defined('WPINC')) {
					$result .= '<div class="page-header' . common::ctStripTags($this->ct->Params->pageClassSFX ?? '') . '"><h2 itemprop="headline">'
						. $this->ct->Params->pageTitle . '</h2></div>';
				}
			}
		}

		$listing_id = $this->row[$this->ct->Table->realidfieldname] ?? 0;

		if ($this->ct->Env->clean == 0) {
			if ($addFormTag) {

				$additionalParameter = ' enctype="multipart/form-data"';

				$result .= '<form action="' . $formLink . '" method="post" name="' . $formName . '" id="' . $formName . '" class="form-validate form-horizontal well" '
					. 'data-tableid="' . $this->ct->Table->tableid . '" data-recordid="' . $listing_id . '" '
					. 'data-version=' . CUSTOMTABLES_JOOMLA_VERSION . $additionalParameter . '>';
			}

			if (defined('_JEXEC'))
				$result .= (CUSTOMTABLES_JOOMLA_MIN_4 ? '<fieldset class="options-form">' : '<fieldset>');
		}

		//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.

		$this->ct->isEditForm = true; //These changes input box prefix
		$pageLayout = $this->layoutContent;

		$twig = new TwigProcessor($this->ct, $pageLayout, false, false, true, $this->pageLayoutNameString, $this->pageLayoutLink);

		try {
			$pageLayout = @$twig->process($this->row);
		} catch (Exception $e) {
			if ($this->ct->Env->debug)
				throw new Exception($e->getMessage() . '<br/>' . $e->getFile() . '<br/>' . $e->getLine());
			else
				throw new Exception($e->getMessage());
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

		if ($this->ct->Env->clean == 0) {

			$taskObjectName = 'task' . ($this->ct->Params->ModuleId ?? '');
			$returnToObjectName = 'returnto' . ($this->ct->Params->ModuleId ?? '');
			$listingIdObjectName = 'listing_id' . ($this->ct->Params->ModuleId ?? '');

			$result .= '<input type="hidden" name="' . $taskObjectName . '" id="' . $taskObjectName . '" value="save" />';
			$result .= '<input type="hidden" name="' . $returnToObjectName . '" id="' . $returnToObjectName . '" value="' . $encodedReturnTo . '" />';
			$result .= '<input type="hidden" name="' . $listingIdObjectName . '" id="' . $listingIdObjectName . '" value="' . $listing_id . '" />';

			if (defined('_JEXEC')) {
				if (is_null($this->ct->Params->ModuleId))
					$result .= (common::inputGetCmd('tmpl', '') != '' ? '<input type="hidden" name="tmpl" value="' . common::inputGetCmd('tmpl', '') . '" />' : '');
				else
					$result .= '<input type="hidden" name="ModuleId" id="ModuleId" value="' . $this->ct->Params->ModuleId . '" />';

				$result .= HTMLHelper::_('form.token');
			} elseif (defined('WPINC')) {
				$result .= '<!-- token -->';
			}

			if (defined('_JEXEC'))
				$result .= '</fieldset>';

			if ($addFormTag)
				$result .= '</form>';
		}

		return $result;
	}
}