<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
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

    function render(?string $layout = null, $limit = 0): string
    {
        if ($this->ct->Env->frmt == 'html')
            $this->ct->loadJSAndCSS();

        if ($this->ct->Env->legacySupport) {

            $site_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;

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
            if ($this->ct->Env->jinput->getString('filter', '') and is_string($this->ct->Env->jinput->getString('filter', '')))
                $this->ct->Filter->addWhereExpression($this->ct->Env->jinput->getString('filter', ''));
        }

        if (!$this->ct->Params->blockExternalVars)
            $this->ct->Filter->addQueryWhereFilter();

// --------------------- Shopping Cart

        if ($this->ct->Params->showCartItemsOnly) {
            $cookieValue = $this->ct->Env->jinput->cookie->get($this->ct->Params->showCartItemsPrefix . $this->ct->Table->tablename);

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
            $this->ct->Filter->where[] = $this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'] . '=' . $this->ct->db->quote($this->ct->Params->listing_id);

// --------------------- Sorting
        $this->ct->Ordering->parseOrderByParam();

// --------------------- Limit
        if ($this->ct->Params->listing_id !== null)
            $this->ct->applyLimits(1);
        else
            $this->ct->applyLimits($limit);

// --------------------- Layouts
        $Layouts = new Layouts($this->ct);
        $Layouts->layouttype = 0;

        $pageLayout = '';
        $itemLayout = '';

        if (!is_null($layout) and $layout != '') {
            $pageLayout = $Layouts->getLayout($layout);
        } else {
            if ($this->ct->Env->frmt == 'csv') {
                $pageLayout = $Layouts->createDefaultLayout_CSV($this->ct->Table->fields);
            } else {

                if (!is_null($this->ct->Params->pageLayout) and $this->ct->Params->pageLayout != '')
                    $pageLayout = $Layouts->getLayout($this->ct->Params->pageLayout);

                if (!is_null($this->ct->Params->itemLayout) and $this->ct->Params->itemLayout != '')
                    $itemLayout = $Layouts->getLayout($this->ct->Params->itemLayout);

                if ($pageLayout == '' and $itemLayout == '') {
                    $pageLayout = $Layouts->createDefaultLayout_SimpleCatalog($this->ct->Table->fields);
                } elseif ($pageLayout == '' and $itemLayout != '') {
                    $pageLayout = '{% block record %}' . $itemLayout . '{% endblock %}';
                }
            }
        }

        $this->ct->LayoutVariables['layout_type'] = $Layouts->layouttype;

// -------------------- Load Records
        if (!$this->ct->getRecords()) {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND'), 'error');
            return 'Table not found';
        }

// -------------------- Parse Layouts
        if ($this->ct->Env->legacySupport) {
            $catalogTableCode = JoomlaBasicMisc::generateRandomString();//this is temporary replace placeholder. to not parse content result again

            $catalogTableContent = tagProcessor_CatalogTableView::process($this->ct, $Layouts->layouttype, $pageLayout, $catalogTableCode);
            if ($catalogTableContent == '')
                $catalogTableContent = tagProcessor_Catalog::process($this->ct, $Layouts->layouttype, $pageLayout, $itemLayout, $catalogTableCode);

            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $pageLayout;
            $pageLayout = $LayoutProc->fillLayout(null, null, '');
            $pageLayout = str_replace('&&&&quote&&&&', '"', $pageLayout); // search boxes may return HTMl elements that contain placeholders with quotes like this: &&&&quote&&&&
            $pageLayout = str_replace($catalogTableCode, $catalogTableContent, $pageLayout);
        }

        $twig = new TwigProcessor($this->ct, $pageLayout);
        $pageLayout = $twig->process();

        if ($twig->errorMessage !== null)
            $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

        if ($this->ct->Params->allowContentPlugins)
            $pageLayout = JoomlaBasicMisc::applyContentPlugins($pageLayout);

        return $pageLayout;
    }
}