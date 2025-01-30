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

//use LayoutProcessor;
//use tagProcessor_PHP;
//use Twig\Error\LoaderError;
//use Twig\Error\RuntimeError;
//use Twig\Error\SyntaxError;

class Details
{
	var CT $ct;
	var string $layoutDetailsContent;
	var int $layoutType;
	var ?string $pageLayoutNameString;
	var ?string $pageLayoutLink;

	function __construct(CT $ct)
	{
		$this->ct = $ct;
		$this->layoutType = 0;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	/*
	function load($layoutDetailsContent = null): bool
	{
		$this->ct->getTable($this->ct->Params->tableName, $this->ct->Params->userIdField);

		if ($this->ct->Table === null)
			return false;

		if (!$this->ct->getRecord())
			return false;

		$this->pageLayoutNameString = null;
		$this->pageLayoutLink = null;

		if (is_null($layoutDetailsContent)) {
			$this->layoutDetailsContent = '';

			if ($this->ct->Params->detailsLayout != '') {
				$Layouts = new Layouts($this->ct);
				$this->layoutDetailsContent = $Layouts->getLayout($this->ct->Params->detailsLayout);
				$this->pageLayoutNameString = $this->ct->Params->detailsLayout;
				$this->pageLayoutLink = common::UriRoot(true, true) . 'administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;

				if ($Layouts->layoutType === null) {
					$this->ct->errors[] = 'Layout "' . $this->ct->Params->detailsLayout . '" not found or the type is not set.';
					return false;
				}

				$this->layoutType = $Layouts->layoutType;
			} else {
				$Layouts = new Layouts($this->ct);
				$this->layoutDetailsContent = $Layouts->createDefaultLayout_Details($this->ct->Table->fields);
				$this->pageLayoutNameString = 'Default Details Layout';
				$this->pageLayoutLink = null;
			}
		} else $this->layoutDetailsContent = $layoutDetailsContent;

		$this->ct->LayoutVariables['layout_type'] = $this->layoutType;


		return true;
	}
	*/


	/*
	protected function UpdatePHPOnView(): bool
	{
		if (!isset($row[$this->ct->Table->realidfieldname]))
			return false;

		foreach ($this->ct->Table->fields as $field) {
			if ($field['type'] == 'phponview') {
				$fieldname = $field['fieldname'];
				tagProcessor_PHP::processTempValue($this->ct, $this->ct->Table->record, $fieldname, $field->params);
			}
		}
		return true;
	}
	*/

}
