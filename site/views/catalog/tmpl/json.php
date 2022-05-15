<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\TwigProcessor;

$itemlayout = str_replace("\n", '', $this->itemlayout);
$itemlayout = str_replace("\r", '', $itemlayout);
$itemlayout = str_replace("\t", '', $itemlayout);

if ($this->ct->Env->legacysupport) {
    $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
    require_once($path . 'layout.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtag.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtableviewtag.php');
    $catalogTableContent = tagProcessor_CatalogTableView::process($this->ct, $this->layoutType, $this->pagelayout, $this->catalogTableCode);
} else
    $catalogTableContent = $this->pagelayout;

if ($catalogTableContent == '') {
    if ($this->ct->Env->legacysupport)
        $catalogTableContent = tagProcessor_Catalog::process($this->ct, $this->layoutType, $this->pagelayout, $itemlayout, $this->catalogTableCode);
    else
        $catalogTableContent = $itemlayout;

    $catalogTableContent = str_replace("\n", '', $catalogTableContent);
    $catalogTableContent = str_replace("\r", '', $catalogTableContent);
    $catalogTableContent = str_replace("\t", '', $catalogTableContent);
}

if ($this->ct->Env->legacysupport) {
    $LayoutProc = new LayoutProcessor($this->ct);
    $LayoutProc->layout = $this->pagelayout;
    $this->pagelayout = $LayoutProc->fillLayout();

    $this->pagelayout = str_replace('&&&&quote&&&&', '"', $this->pagelayout); // search boxes may return HTML elements that contain placeholders with quotes like this: &&&&quote&&&&
    $this->pagelayout = str_replace($this->catalogTableCode, $catalogTableContent, $this->pagelayout);
}

$twig = new TwigProcessor($this->ct, $this->pagelayout);
$this->pagelayout = $twig->process();

if ($this->ct->Env->menu_params->get('allowcontentplugins'))
    JoomlaBasicMisc::applyContentPlugins($this->pagelayout);

if (ob_get_contents()) ob_end_clean();

$filename = $this->ct->Env->menu_params->get('page_title');
if(is_null($filename))
    $filename = 'ct';

$filename = JoomlaBasicMisc::makeNewFileName($filename, 'json');

header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: application/json; charset=utf-8');
header("Pragma: no-cache");
header("Expires: 0");

echo $this->pagelayout;

die;
