<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use JoomlaBasicMisc;

// no direct access
defined('_JEXEC') or die('Restricted access');

class Catalog
{
    var CT $ct;
    //var ?int $moduleId;

    function __construct(CT &$ct)//, $moduleId = null)
    {
        $this->ct = &$ct;
        //$this->ct->Env->menu_params = $menuParams;
        //$this->moduleId = $moduleId;
    }

    function render(): string
    {
        if ($this->ct->Env->frmt == 'html') {
            $this->ct->loadJSAndCSS();
        }

        if ($this->ct->Env->legacysupport) {

            $site_path=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;

            require_once($site_path . 'layout.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtag.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtableviewtag.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'generaltags.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'pagetags.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'itemtags.php');
            require_once($site_path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'fieldtags.php');
        }

// -------------------- Table

        $this->ct->getTable($this->ct->Params->tableName);

        if($this->ct->Table->tablename=='')
        {
            $this->ct->app->enqueueMessage('Catalog View: Table not selected.', 'error');
            return false;
        }

// --------------------- Filter
        $this->ct->setFilter('', $this->ct->Params->showPublished);
        $this->ct->Filter->addMenuParamFilter();

        if(!$this->ct->Params->blockExternalVars)
        {
            if($this->ct->Env->jinput->get('filter','','STRING'))
                $this->ct->Filter->addWhereExpression($this->ct->Env->jinput->get('filter','','STRING'));
        }

        if(!$this->ct->Params->blockExternalVars)
            $this->ct->Filter->addQueryWhereFilter();

// --------------------- Shopping Cart

        if($this->ct->Params->showCartItemsOnly)
        {
            $cookieValue = $this->ct->Env->jinput->cookie->get($this->ct->Params->showCartItemsPrefix.$this->ct->Table->tablename);

            if (isset($cookieValue))
            {
                if($cookieValue=='')
                {
                    $this->ct->Filter->where[] = $this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'=0';
                }
                else
                {
                    $items=explode(';',$cookieValue);
                    $arr=array();
                    foreach($items as $item)
                    {
                        $pair=explode(',',$item);
                        $arr[]=$this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'='.(int)$pair[0];//id must be a number
                    }
                    $this->ct->Filter->where[] = '('.implode(' OR ', $arr).')';
                }
            }
            else
            {
                //Show only shopping cart items. TODO: check the query
                $this->ct->Filter->where[]=$this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'=0';
            }
        }

// --------------------- Sorting
        $this->ct->Ordering->parseOrderByParam();

// --------------------- Limit
        $this->ct->applyLimits();


// --------------------- Layouts
        $Layouts = new Layouts($this->ct);
        $Layouts->layouttype = 0;

        if($this->ct->Params->pageLayout != null)
        {
            $pagelayout=$Layouts->getLayout($this->ct->Params->pageLayout);
            if($pagelayout=='')
                $pagelayout='{catalog:,notable}';
        }
        else
            $pagelayout='{catalog:,notable}';

        if($this->ct->Params->itemLayout != null)
            $itemLayout=$Layouts->getLayout($this->ct->Params->itemLayout);
        else
            $itemLayout='';

        print_r($this->ct->Params);
        echo 'list limit: '.$this->ct->Limit.'*<br/>';

// -------------------- Load Records
        $this->ct->getRecords();
// -------------------- Parse Layouts
        if ($this->ct->Env->legacysupport) {
            $catalogTableCode = JoomlaBasicMisc::generateRandomString();//this is temporary replace place holder. to not parse content result again

            $catalogTableContent = \tagProcessor_CatalogTableView::process($this->ct, $Layouts->layouttype, $pagelayout, $catalogTableCode);
            if ($catalogTableContent == '')
                $catalogTableContent = \tagProcessor_Catalog::process($this->ct, $Layouts->layouttype, $pagelayout, $itemLayout, $catalogTableCode);

            $LayoutProc = new \LayoutProcessor($this->ct);
            $LayoutProc->layout = $pagelayout;
            $pagelayout = $LayoutProc->fillLayout(array(), null, '');
            $pagelayout = str_replace('&&&&quote&&&&', '"', $pagelayout); // search boxes may return HTMl elements that contain placeholders with quotes like this: &&&&quote&&&&
            $pagelayout = str_replace($catalogTableCode, $catalogTableContent, $pagelayout);
        }

        $twig = new TwigProcessor($this->ct, $pagelayout);
        $pagelayout = $twig->process();

        if($this->ct->Params->allowContentPlugins)
            JoomlaBasicMisc::applyContentPlugins($pagelayout);

        return $pagelayout;
    }
}