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
    $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
    require_once($path . 'layout.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtag.php');
    require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'catalogtableviewtag.php');
}

$itemlayout = str_replace("\n", '', $this->itemlayout);

if ($this->ct->Env->legacysupport)
    $catalogtablecontent = tagProcessor_CatalogTableView::process($this->ct, $this->layoutType, $this->pagelayout, $itemlayout, $this->catalogtablecode);
else
    $catalogtablecontent = $this->pagelayout;

$twig = new TwigProcessor($this->ct, $catalogtablecontent);
$catalogtablecontent = $twig->process();

$twig = new TwigProcessor($this->ct, $this->pagelayout);
$this->pagelayout = $twig->process();

if ($this->ct->Env->legacysupport) {
    if ($catalogtablecontent == '')
        $catalogtablecontent = tagProcessor_Catalog::process($this->ct, $this->layoutType, $this->pagelayout, $itemlayout, $this->catalogtablecode);
    else
        $catalogtablecontent = $this->pagelayout;
}

$this->pagelayout = preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $this->pagelayout);

$this->pagelayout = str_ireplace('<th', '"<th', $this->pagelayout);
$this->pagelayout = str_ireplace('</th>', '</th>",', $this->pagelayout);

$this->pagelayout = str_ireplace('<td', '"<td', $this->pagelayout);
$this->pagelayout = str_ireplace('</td>', '</td>",', $this->pagelayout);

$this->pagelayout = str_ireplace('</tr>', '****linebrake****', $this->pagelayout);

if ($this->ct->Env->legacysupport) {
    $LayoutProc = new LayoutProcessor($this->ct);
    $LayoutProc->layout = $this->pagelayout;
    $this->pagelayout = $LayoutProc->fillLayout();
    $this->pagelayout = strip_tags(str_replace('&&&&quote&&&&', '"', trim($this->pagelayout))); // search boxes may return HTMl elemnts that contain placeholders with quotes like this: &&&&quote&&&&
}

if ($this->ct->Env->menu_params->get('allowcontentplugins'))
    JoomlaBasicMisc::applyContentPlugins($this->pagelayout);

if ($this->layoutType != 9) //not CSV layout
{
    $this->pagelayout = str_replace("\n", '', $this->pagelayout);
    $this->pagelayout = str_replace("\r", '', $this->pagelayout);
    $this->pagelayout = str_replace("\t", '', $this->pagelayout);
}

$this->pagelayout = str_ireplace('****linebrake****', "\r" . "\n", $this->pagelayout);

$this->pagelayout = str_replace($this->catalogtablecode, $catalogtablecontent, $this->pagelayout);

if (ob_get_contents())
    ob_end_clean();

$filename = JoomlaBasicMisc::makeNewFileName($this->ct->Env->menu_params->get('page_title'), 'csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: text/csv; charset=utf-16');
header("Pragma: no-cache");
header("Expires: 0");

//echo chr(255).chr(254);
//$bom = pack("CCC", 0xef, 0xbb, 0xbf);
//echo $bom.mb_convert_encoding($this->pagelayout, 'UTF-16LE', 'UTF-8');
echo $this->pagelayout;
die;//clean exit
