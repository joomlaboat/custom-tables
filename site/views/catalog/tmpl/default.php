<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
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

if ($this->ct->Env->frmt and $this->listing_id == '') //there is no need to have a header if we are loading a single record.
{
    $this->ct->loadJSAndCSS();

    if ($this->ct->Env->menu_params->get('show_page_heading', 1)) {
        $title = JoomlaBasicMisc::JTextExtended($this->ct->Env->menu_params->get('page_title'));
        echo '
		<div class="page-header' . strip_tags($this->ct->Env->menu_params->get('pageclass_sfx')) . '">
			<h2 itemprop="headline">' . $title . '</h2>
		</div>
		';
    }
}

if ($this->ct->Env->legacysupport) {
    $catalogtablecontent = tagProcessor_CatalogTableView::process($this->ct, $this->layoutType, $this->pagelayout, $this->catalogtablecode);
    if ($catalogtablecontent == '')
        $catalogtablecontent = tagProcessor_Catalog::process($this->ct, $this->layoutType, $this->pagelayout, $this->itemlayout, $this->catalogtablecode);
} else
    $catalogtablecontent = $this->pagelayout;

if ($this->listing_id != '') //for reload single record functionality
    die($catalogtablecontent);

if ($this->ct->Env->legacysupport) {
    $LayoutProc = new LayoutProcessor($this->ct);
    $LayoutProc->layout = $this->pagelayout;
    $this->pagelayout = $LayoutProc->fillLayout();

    $this->pagelayout = str_replace('&&&&quote&&&&', '"', $this->pagelayout); // search boxes may return HTML elemnts that contain placeholders with quotes like this: &&&&quote&&&&
    $this->pagelayout = str_replace($this->catalogtablecode, $catalogtablecontent, $this->pagelayout);
}

$twig = new TwigProcessor($this->ct, $this->pagelayout);
$this->pagelayout = $twig->process();

if ($this->ct->Env->frmt and $this->ct->Env->menu_params->get('allowcontentplugins'))
    JoomlaBasicMisc::applyContentPlugins($this->pagelayout);

if ($this->ct->Env->frmt == 'xml') {
    if (ob_get_contents()) ob_end_clean();

    $filename = JoomlaBasicMisc::makeNewFileName($this->ct->Env->menu_params->get('page_title'), 'xml');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Type: text/xml; charset=utf-8');
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $this->pagelayout;
    die;//clean exit
} elseif ($this->ct->Env->frmt == 'json') {
    if (ob_get_contents()) ob_end_clean();

    $filename = JoomlaBasicMisc::makeNewFileName($this->ct->Env->menu_params->get('page_title'), 'json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Type: application/json; charset=utf-8');
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $this->pagelayout;
    die;//clean exit
} elseif ($this->ct->Env->clean == 1) {
    if (ob_get_contents()) ob_end_clean();
    echo $this->pagelayout;
    die;//clean exit
} else
    echo $this->pagelayout;

?>

<!-- Modal content -->
<div id="ctModal" class="ctModal">
    <div id="ctModal_box" class="ctModal_content">
        <span id="ctModal_close" class="ctModal_close">&times;</span>
        <div id="ctModal_content"></div>
    </div>
</div>
<!-- end of the modal -->