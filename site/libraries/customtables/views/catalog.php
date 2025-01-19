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

class Catalog
{
	var CT $ct;
	var string $layoutCodeCSS;
	var string $layoutCodeJS;

	function __construct(CT &$ct)
	{
		$this->ct = &$ct;
		$this->layoutCodeCSS = '';
		$this->layoutCodeJS = '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function render($layoutName = null, $limit = 0): string
	{
		// --------------------- Layouts
		$Layouts = new Layouts($this->ct);
		$Layouts->layoutType = 0;
		$itemLayout = '';
		$pageLayoutNameString = null;
		$pageLayoutLink = null;
		$itemLayoutNameString = null;

		if ($layoutName === '')
			$layoutName = null;

		if ($layoutName !== null) {
			$pageLayout = $Layouts->getLayout($layoutName);
			if (isset($Layouts->layoutId)) {
				$pageLayoutNameString = ($layoutName == '' ? 'InlinePageLayout' : $layoutName);
				$pageLayoutLink = common::UriRoot(true, true) . 'administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;
			} else {
				throw new Exception('Layout "' . $layoutName . '" not found.');
			}
		}

		// -------------------- Table
		if ($this->ct->Table === null) {
			$this->ct->getTable($this->ct->Params->tableName);

			if ($this->ct->Table === null) {
				throw new Exception('Catalog View: Table not selected.');
				//$this->ct->errors[] = 'Catalog View: Table not selected.';
				//return 'Catalog View: Table not selected.';
			}
		}

		// --------------------- Filter
		if ($this->ct->Filter === null) {
			$this->ct->setFilter($this->ct->Params->filter, $this->ct->Params->showPublished);
			$this->ct->Filter->addQueryWhereFilter();
		}

		//if ($this->ct->Env->frmt == 'html' and !$this->ct->Env->clean)
		//common::loadJSAndCSS($this->ct->Params, $this->ct->Env, $this->ct->Table->fieldInputPrefix);
		/*
				if ($this->ct->Env->legacySupport) {
					try {
						$site_path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR;

						require_once($site_path . 'layout.php');
						require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtag.php');
						require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtableviewtag.php');
						require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'generaltags.php');
						require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'pagetags.php');
						require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'itemtags.php');
						require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'fieldtags.php');
					} catch (Exception $e) {
						return 'Catalog Renderer. Legacy Support processing error: ' . $e->getMessage();
					}
				}
		*/
// --------------------- Shopping Cart

		if ($this->ct->Params->showCartItemsOnly) {
			$cookieValue = common::inputCookieGet($this->ct->Params->showCartItemsPrefix . $this->ct->Table->tablename);

			if (isset($cookieValue)) {
				if ($cookieValue == '') {
					$this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], 0);
				} else {
					$items = explode(';', $cookieValue);
					$whereClauseTemp = new MySQLWhereClause();
					foreach ($items as $item) {
						$pair = explode(',', $item);
						$whereClauseTemp->addOrCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], (int)$pair[0]);
					}
					$this->ct->Filter->whereClause->addNestedCondition($whereClauseTemp);
				}
			} else {
				//Show only shopping cart items. TODO: check the query
				$this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], 0);
			}
		}

		if (!empty($this->ct->Params->listing_id))
			$this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], $this->ct->Params->listing_id);

// --------------------- Sorting
		$this->ct->Ordering->parseOrderByParam();

// --------------------- Limit
		if (!empty($this->ct->Params->listing_id))
			$this->ct->applyLimits(1);
		else
			$this->ct->applyLimits($limit);

		// --------------------- Layouts
		if ($layoutName === null) {
			if ($this->ct->Env->frmt == 'csv') {
				$pageLayout = $Layouts->createDefaultLayout_CSV($this->ct->Table->fields);
			} else {

				if (!is_null($this->ct->Params->pageLayout) and $this->ct->Params->pageLayout != '') {

					if (empty($this->ct->Params->pageLayout)) {
						$this->ct->errors[] = 'Catalog Layout not selected.';
						return '';
					}

					$pageLayout = $Layouts->getLayout($this->ct->Params->pageLayout);//Get Layout by name
					if (isset($Layouts->layoutId)) {

						if (!empty($Layouts->layoutCodeCSS)) {
							$this->layoutCodeCSS = $Layouts->layoutCodeCSS;
						}

						if (!empty($Layouts->layoutCodeJS))
							$this->layoutCodeJS = $Layouts->layoutCodeJS;

						$pageLayoutNameString = $this->ct->Params->pageLayout;
						$pageLayoutLink = common::UriRoot(true, true) . 'administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;
					} else {
						$this->ct->errors[] = 'Layout "' . $this->ct->Params->pageLayout . '" not found.';
						return '';
					}

				} elseif (!is_null($this->ct->Params->itemLayout) and $this->ct->Params->itemLayout != '') {
					$itemLayout = $Layouts->getLayout($this->ct->Params->itemLayout);

					if (isset($Layouts->layoutId)) {
						if (!empty($Layouts->layoutCodeCSS))
							$this->layoutCodeCSS .= $Layouts->layoutCodeCSS;

						if (!empty($Layouts->layoutCodeJS))
							$this->layoutCodeJS .= $Layouts->layoutCodeJS;
					}

					$pageLayout = '{% block record %}' . $itemLayout . '{% endblock %}';
					$pageLayoutNameString = 'Generated_Basic_Page_Layout';
				} else {

					if ($this->ct->Table->fields !== null)
						$pageLayout = $Layouts->createDefaultLayout_SimpleCatalog($this->ct->Table->fields);
					else
						$pageLayout = 'CustomTables: Fields not set.';

					$pageLayoutNameString = 'Generated_Simple_Catalog_Layout';
				}
			}
		}

		$this->ct->LayoutVariables['layout_type'] = $Layouts->layoutType;

		// -------------------- Load Records
		try {
			$recordsLoaded = $this->ct->getRecords();
		} catch (Exception $e) {
			return $e->getMessage();
		}

		if (!$recordsLoaded) {
			if (defined('_JEXEC'))
				$this->ct->errors[] = common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND');

			return 'CustomTables: Records not loaded.';
		}

		// -------------------- Parse Layouts
		/*
		if ($this->ct->Env->legacySupport) {

			if ($this->ct->Env->frmt == 'json') {
				$itemLayout = str_replace("\n", '', $itemLayout);
				$itemLayout = str_replace("\r", '', $itemLayout);
				$itemLayout = str_replace("\t", '', $itemLayout);
			}

			$catalogTableCode = common::generateRandomString();//this is temporary replace placeholder. to not parse content result again

			$catalogTableContent = tagProcessor_CatalogTableView::process($this->ct, $Layouts->layoutType, $pageLayout, $catalogTableCode);
			if ($catalogTableContent == '') {
				$catalogTableContent = tagProcessor_Catalog::process($this->ct, $Layouts->layoutType, $pageLayout, $itemLayout, $catalogTableCode);

				if ($this->ct->Env->frmt == 'json') {
					$catalogTableContent = str_replace("\n", '', $catalogTableContent);
					$catalogTableContent = str_replace("\r", '', $catalogTableContent);
					$catalogTableContent = str_replace("\t", '', $catalogTableContent);
				}
			}

			$LayoutProc = new LayoutProcessor($this->ct);
			$LayoutProc->layout = $pageLayout;
			$pageLayout = $LayoutProc->fillLayout(null, null, '');
			$pageLayout = str_replace('&&&&quote&&&&', '"', $pageLayout); // search boxes may return HTMl elements that contain placeholders with quotes like this: &&&&quote&&&&
			$pageLayout = str_replace($catalogTableCode, $catalogTableContent, $pageLayout);
		}*/

		if ($this->ct->Env->frmt == 'json') {

			$pathViews = CUSTOMTABLES_LIBRARIES_PATH
				. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

			require_once($pathViews . 'json.php');
			$jsonOutput = new ViewJSON($this->ct);
			die($jsonOutput->render($pageLayout));
		}

		$twig = null;

		try {
			$twig = new TwigProcessor($this->ct, $pageLayout, false, false, true, $pageLayoutNameString, $pageLayoutLink);
			if (count($this->ct->errors) > 0)
				return 'There is an error in rendering the catalog page:' . implode(', ', $this->ct->errors);

			$pageLayout = $twig->process();
		} catch (Exception $e) {
			$this->ct->errors[] = $e->getMessage();
		}

		if ($twig->errorMessage !== null) {
			$this->ct->errors[] = $twig->errorMessage;
			return 'There is an error in rendering the catalog page :' . implode(', ', $this->ct->errors);
		}

		if ($this->ct->Params->allowContentPlugins)
			$pageLayout = CTMiscHelper::applyContentPlugins($pageLayout);

		if ($this->ct->Env->clean == 0) {

			if (isset($this->ct->LayoutVariables['style']))
				$this->layoutCodeCSS = ($this->layoutCodeCSS ?? '') . $this->ct->LayoutVariables['style'];

			if (!empty($this->layoutCodeCSS)) {
				$twig = new TwigProcessor($this->ct, $this->layoutCodeCSS, false);
				$this->layoutCodeCSS = $twig->process($this->ct->Table->record ?? null);
			}

			if (isset($this->ct->LayoutVariables['script']))
				$this->layoutCodeJS = ($this->layoutCodeJS ?? '') . $this->ct->LayoutVariables['script'];

			if (!empty($this->layoutCodeJS)) {
				$twig = new TwigProcessor($this->ct, $this->layoutCodeJS, false);
				$this->layoutCodeJS = $twig->process($this->ct->Table->record ?? null);
			}
		}

		return $pageLayout;
	}
}