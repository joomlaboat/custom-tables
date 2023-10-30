<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use JoomlaBasicMisc;
use LayoutProcessor;
use tagProcessor_Catalog;
use tagProcessor_CatalogTableView;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class Catalog
{
    var CT $ct;

    function __construct(CT &$ct)
    {
        $this->ct = &$ct;
    }

    function render(string|int|null $layoutName = null, $limit = 0): string
    {
        if ($this->ct->Env->frmt == 'html')
            $this->ct->loadJSAndCSS();

        if ($this->ct->Env->legacySupport) {

            $site_path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR;

            require_once($site_path . 'layout.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtag.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtableviewtag.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'generaltags.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'pagetags.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'itemtags.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'fieldtags.php');
        }

// -------------------- Table

        if ($this->ct->Table === null) {
            $this->ct->getTable($this->ct->Params->tableName);

            if ($this->ct->Table->tablename === null) {
                $this->ct->app->enqueueMessage('Catalog View: Table not selected.', 'error');
                return 'Catalog View: Table not selected.';
            }
        }

// --------------------- Filter
        $this->ct->setFilter($this->ct->Params->filter, $this->ct->Params->showPublished);

        if (!$this->ct->Params->blockExternalVars) {
            if (common::inputGetString('filter', '') and is_string(common::inputGetString('filter', '')))
                $this->ct->Filter->addWhereExpression(common::inputGetString('filter', ''));
        }

        if (!$this->ct->Params->blockExternalVars)
            $this->ct->Filter->addQueryWhereFilter();

// --------------------- Shopping Cart

        if ($this->ct->Params->showCartItemsOnly) {
            $cookieValue = common::inputCookieGet($this->ct->Params->showCartItemsPrefix . $this->ct->Table->tablename);

            if (isset($cookieValue)) {
                if ($cookieValue == '') {
                    $this->ct->Filter->where[] = $this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'] . '=0';
                } else {
                    $items = explode(';', $cookieValue);
                    $arr = array();
                    foreach ($items as $item) {
                        $pair = explode(',', $item);
                        $arr[] = $this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'] . '=' . (int)$pair[0];//id must be a number
                    }
                    $this->ct->Filter->where[] = '(' . implode(' OR ', $arr) . ')';
                }
            } else {
                //Show only shopping cart items. TODO: check the query
                $this->ct->Filter->where[] = $this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'] . '=0';
            }
        }

        if ($this->ct->Params->listing_id !== null)
            $this->ct->Filter->where[] = $this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'] . '=' . database::quote($this->ct->Params->listing_id);

// --------------------- Sorting
        $this->ct->Ordering->parseOrderByParam();

// --------------------- Limit
        if ($this->ct->Params->listing_id !== null)
            $this->ct->applyLimits(1);
        else
            $this->ct->applyLimits($limit);

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
                $pageLayoutNameString = (($layoutName ?? '') == '' ? 'InlinePageLayout' : $layoutName);
                $pageLayoutLink = '/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;
            } else {
                echo 'Layout "' . $layoutName . '" not found.';
            }
        } else {
            if ($this->ct->Env->frmt == 'csv') {
                $pageLayout = $Layouts->createDefaultLayout_CSV($this->ct->Table->fields);
            } else {

                if (!is_null($this->ct->Params->pageLayout) and $this->ct->Params->pageLayout != '') {
                    $pageLayout = $Layouts->getLayout($this->ct->Params->pageLayout);
                    $pageLayoutNameString = $this->ct->Params->pageLayout;
                    $pageLayoutLink = '/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;

                } elseif (!is_null($this->ct->Params->itemLayout) and $this->ct->Params->itemLayout != '') {
                    $itemLayout = $Layouts->getLayout($this->ct->Params->itemLayout);
                    $pageLayout = '{% block record %}' . $itemLayout . '{% endblock %}';
                    $pageLayoutNameString = 'Generated_Basic_Page_Layout';
                } else {

                    if ($this->ct->Table->fields !== null)
                        $pageLayout = $Layouts->createDefaultLayout_SimpleCatalog($this->ct->Table->fields);
                    else
                        $pageLayout = 'CustomTables: Fields not set.';

                    $pageLayoutNameString = 'Generated_Page_Layout';
                }
            }
        }

        $this->ct->LayoutVariables['layout_type'] = $Layouts->layoutType;

// -------------------- Load Records
        if (!$this->ct->getRecords()) {

            if (defined('_JEXEC'))
                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND'), 'error');

            return 'CustomTables: Records not loaded.';
        }

// -------------------- Parse Layouts
        if ($this->ct->Env->legacySupport) {

            if ($this->ct->Env->frmt == 'json') {
                $itemLayout = str_replace("\n", '', $itemLayout);
                $itemLayout = str_replace("\r", '', $itemLayout);
                $itemLayout = str_replace("\t", '', $itemLayout);
            }

            $catalogTableCode = JoomlaBasicMisc::generateRandomString();//this is temporary replace placeholder. to not parse content result again

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
        }

        if ($this->ct->Env->frmt == 'json') {

            $pathViews = CUSTOMTABLES_LIBRARIES_PATH
                . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

            require_once($pathViews . 'json.php');
            $jsonOutput = new ViewJSON($this->ct);
            die($jsonOutput->render($pageLayout));
        }

        $twig = new TwigProcessor($this->ct, $pageLayout, false, false, true, $pageLayoutNameString, $pageLayoutLink);
        $pageLayout = $twig->process();

        if ($twig->errorMessage !== null)
            $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

        if ($this->ct->Params->allowContentPlugins)
            $pageLayout = JoomlaBasicMisc::applyContentPlugins($pageLayout);

        return $pageLayout;
    }
}