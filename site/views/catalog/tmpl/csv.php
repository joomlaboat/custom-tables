<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\TwigProcessor;

if ($this->ct->Env->legacysupport) {

    $itemlayout = str_replace("\n", '', $this->itemLayoutContent);

    $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
    require_once($path . 'layout.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtag.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtableviewtag.php');

    $catalogTableContent = tagProcessor_CatalogTableView::process($this->ct, $this->layoutType, $this->pageLayoutContent, $itemlayout, $this->catalogTableCode);

    if ($catalogTableContent == '')
        $catalogTableContent = tagProcessor_Catalog::process($this->ct, $this->layoutType, $this->pageLayoutContent, $itemlayout, $this->catalogTableCode);

    $LayoutProc = new LayoutProcessor($this->ct);
    $LayoutProc->layout = $this->pageLayoutContent;
    $pageLayoutContent = $LayoutProc->fillLayout();
    $pageLayoutContent = strip_tags(str_replace('&&&&quote&&&&', '"', trim($pageLayoutContent))); // search boxes may return HTMl elements that contain placeholders with quotes like this: &&&&quote&&&&

    $pageLayoutContent = str_replace($this->catalogTableCode, $catalogTableContent, $pageLayoutContent);
} else
    $pageLayoutContent = $this->pageLayoutContent;

$twig = new TwigProcessor($this->ct, $pageLayoutContent);
$pageLayoutContent = $twig->process();

//Clean the output
$pageLayoutContent = preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $this->pageLayoutContent);

$pageLayoutContent = str_ireplace('<th', '"<th', $pageLayoutContent);
$pageLayoutContent = str_ireplace('</th>', '</th>",', $pageLayoutContent);

$pageLayoutContent = str_ireplace('<td', '"<td', $pageLayoutContent);
$pageLayoutContent = str_ireplace('</td>', '</td>",', $pageLayoutContent);

$pageLayoutContent = str_ireplace('</tr>', '****linebrake****', $pageLayoutContent);

if ($this->ct->Params->allowContentPlugins)
    JoomlaBasicMisc::applyContentPlugins($pageLayoutContent);

if ($this->layoutType != 9) //not CSV layout
{
    $pageLayoutContent = str_replace("\n", '', $pageLayoutContent);
    $pageLayoutContent = str_replace("\r", '', $pageLayoutContent);
    $pageLayoutContent = str_replace("\t", '', $pageLayoutContent);
}

$pageLayoutContent = str_ireplace('****linebrake****', "\r" . "\n", $pageLayoutContent);

if (ob_get_contents())
    ob_end_clean();

$filename = JoomlaBasicMisc::makeNewFileName($this->ct->Params->pageTitle, 'csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: text/csv; charset=utf-16');
header("Pragma: no-cache");
header("Expires: 0");

//echo chr(255).chr(254);
//$bom = pack("CCC", 0xef, 0xbb, 0xbf);
//echo $bom.mb_convert_encoding($pageLayoutContent, 'UTF-16LE', 'UTF-8');
echo $pageLayoutContent;
die;
