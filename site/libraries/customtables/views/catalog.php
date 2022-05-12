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
    var $moduleId;

    function __construct(CT &$ct, $menuParams, $moduleId = null)
    {
        $this->ct = &$ct;
        $this->ct->Env->menu_params = $menuParams;
        $this->moduleId = $moduleId;
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

// --------------------- ItemId
        $forceItemId=$this->ct->Env->menu_params->get('forceitemid');
        if(isset($forceItemId) and $forceItemId!='')
        {
            //Find Itemid by alias
            if(((int)$forceItemId)>0)
                $this->ct->Env->Itemid=$forceItemId;
            else
            {
                if($forceItemId!="0")
                    $this->ct->Env->Itemid=(int)JoomlaBasicMisc::FindItemidbyAlias($forceItemId);//Accepts menu Itemid and alias
                else
                    $this->ct->Env->Itemid=$this->ct->Env->jinput->get('Itemid',0,'INT');
            }
        }
        else
            $this->ct->Env->Itemid = $this->ct->Env->jinput->get('Itemid',0,'INT');

// -------------------- Table

        $this->ct->getTable($this->ct->Env->menu_params->get( 'establename' ));

        if($this->ct->Table->tablename=='')
        {
            $this->ct->app->enqueueMessage('Catalog View: Table not selected.', 'error');
            return false;
        }

// --------------------- Filter
        $this->ct->setFilter('', $this->ct->Env->menu_params->get('showpublished'));
        $this->ct->Filter->addMenuParamFilter();

// --------------------- Sorting
        $this->ct->Ordering->parseOrderByParam(true,$this->ct->Env->menu_params,$this->ct->Env->Itemid);

// --------------------- Limit
        $this->ct->applyLimits();


// --------------------- Layouts
        $Layouts = new Layouts($this->ct);
        $Layouts->layouttype = 0;

        if($this->ct->Env->menu_params->get( 'ct_pagelayout' )!=null)
        {
            $pagelayout=$Layouts->getLayout($this->ct->Env->menu_params->get( 'ct_pagelayout' ));
            if($pagelayout=='')
                $pagelayout='{catalog:,notable}';
        }
        else
            $pagelayout='{catalog:,notable}';

        if($this->ct->Env->menu_params->get( 'ct_itemlayout' )!=null)
            $itemLayout=$Layouts->getLayout($this->ct->Env->menu_params->get( 'ct_itemlayout' ));
        else
            $itemLayout='';

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
            $pagelayout = str_replace('&&&&quote&&&&', '"', $pagelayout); // search boxes may return HTMl elemnts that contain placeholders with quotes like this: &&&&quote&&&&
            $pagelayout = str_replace($catalogTableCode, $catalogTableContent, $pagelayout);
        }

        $twig = new TwigProcessor($this->ct, $pagelayout);
        $pagelayout = $twig->process();

        if($this->ct->Env->menu_params->get( 'allowcontentplugins' )==1)
            JoomlaBasicMisc::applyContentPlugins($pagelayout);

        return $pagelayout;
    }
}