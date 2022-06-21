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
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}
$layout = $this->ct->Env->jinput->getCmd('layout');
$pageLayoutContent = $this->catalog->render($layout);
$pageLayoutContent = preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $pageLayoutContent);
/*
$pageLayoutContent = str_ireplace('<th', '"<th', $pageLayoutContent);
$pageLayoutContent = str_ireplace('</th>', '</th>",', $pageLayoutContent);

$pageLayoutContent = str_ireplace('<td', '"<td', $pageLayoutContent);
$pageLayoutContent = str_ireplace('</td>', '</td>",', $pageLayoutContent);

$pageLayoutContent = str_ireplace('</tr>', '****linebrake****', $pageLayoutContent);
*/
if ($this->ct->Params->allowContentPlugins)
    JoomlaBasicMisc::applyContentPlugins($pageLayoutContent);
/*
if ($this->layoutType != 9) //not CSV layout
{
    $pageLayoutContent = str_replace("\n", '', $pageLayoutContent);
    $pageLayoutContent = str_replace("\r", '', $pageLayoutContent);
    $pageLayoutContent = str_replace("\t", '', $pageLayoutContent);
}
*/
//$pageLayoutContent = str_ireplace('****linebrake****', "\r" . "\n", $pageLayoutContent);

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
