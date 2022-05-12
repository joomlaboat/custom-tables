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

use CustomTables\Layouts;

$itemlayout = str_replace("\n", '', $this->itemlayout);
$itemlayout = str_replace("\r", '', $itemlayout);
$itemlayout = str_replace("\t", '', $itemlayout);

if ($this->ct->Env->legacysupport) {
    $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
    require_once($path . 'layout.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtag.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtableviewtag.php');
    $catalogtablecontent = tagProcessor_CatalogTableView::process($this->ct, $this->layoutType, $this->pagelayout, $this->catalogtablecode);
} else
    $catalogtablecontent = $this->pagelayout;

if ($catalogtablecontent == '') {
    if ($this->ct->Env->legacysupport)
        $catalogtablecontent = tagProcessor_Catalog::process($this->ct, $this->layoutType, $this->pagelayout, $itemlayout, $this->catalogtablecode);
    else
        $catalogtablecontent = $itemlayout;

    $catalogtablecontent = str_replace("\n", '', $catalogtablecontent);
    $catalogtablecontent = str_replace("\r", '', $catalogtablecontent);
    $catalogtablecontent = str_replace("\t", '', $catalogtablecontent);
}

if ($this->ct->Env->legacysupport) {
    $LayoutProc = new LayoutProcessor($this->ct);
    $LayoutProc->layout = $this->pagelayout;
    $this->pagelayout = $LayoutProc->fillLayout();

    $this->pagelayout = str_replace('&&&&quote&&&&', '"', $this->pagelayout); // search boxes may return HTML elemnts that contain placeholders with quotes like this: &&&&quote&&&&
    $this->pagelayout = str_replace($this->catalogtablecode, $catalogtablecontent, $this->pagelayout);
}

if ($this->ct->Env->menu_params->get('allowcontentplugins'))
    JoomlaBasicMisc::applyContentPlugins($this->pagelayout);

if (ob_get_contents()) ob_end_clean();

$filename = JoomlaBasicMisc::makeNewFileName($this->ct->Env->menu_params->get('page_title'), 'json');

header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: application/json; charset=utf-8');
header("Pragma: no-cache");
header("Expires: 0");

echo $this->pagelayout;

die;
