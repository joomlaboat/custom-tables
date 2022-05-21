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

if ($this->ct->Env->legacysupport) {

    $itemlayout = str_replace("\n", '', $this->itemLayoutContent);
    $itemlayout = str_replace("\r", '', $itemlayout);
    $itemlayout = str_replace("\t", '', $itemlayout);

    $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
    require_once($path . 'layout.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtag.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtableviewtag.php');
    $catalogTableContent = tagProcessor_CatalogTableView::process($this->ct, $this->layoutType, $this->pageLayoutContent, $this->catalogTableCode);

    if ($catalogTableContent == '') {
        $catalogTableContent = tagProcessor_Catalog::process($this->ct, $this->layoutType, $this->pageLayoutContent, $itemlayout, $this->catalogTableCode);

        $catalogTableContent = str_replace("\n", '', $catalogTableContent);
        $catalogTableContent = str_replace("\r", '', $catalogTableContent);
        $catalogTableContent = str_replace("\t", '', $catalogTableContent);
    }

    $LayoutProc = new LayoutProcessor($this->ct);
    $LayoutProc->layout = $this->pageLayoutContent;
    $pageLayoutContent = $LayoutProc->fillLayout();

    $pageLayoutContent = str_replace('&&&&quote&&&&', '"', $pageLayoutContent); // search boxes may return HTML elements that contain placeholders with quotes like this: &&&&quote&&&&
    $pageLayoutContent = str_replace($this->catalogTableCode, $catalogTableContent, $pageLayoutContent);
}
else
    $pageLayoutContent = $this->pageLayoutContent;

$twig = new TwigProcessor($this->ct, $pageLayoutContent);
$pageLayoutContent = $twig->process();

if ($this->ct->Params->allowContentPlugins)
    JoomlaBasicMisc::applyContentPlugins($pageLayoutContent);

if (ob_get_contents()) ob_end_clean();

$filename = $this->ct->Params->pageTitle;
if (is_null($filename))
    $filename = 'ct';

$filename = JoomlaBasicMisc::makeNewFileName($filename, 'json');

header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: application/json; charset=utf-8');
header("Pragma: no-cache");
header("Expires: 0");

echo $pageLayoutContent;

die;
