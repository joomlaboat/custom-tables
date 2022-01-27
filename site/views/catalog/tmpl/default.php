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

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'catalogtag.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'catalogtableviewtag.php');

$html_format=false;
if($this->ct->Env->frmt=='html' or $this->ct->Env->frmt=='')
    $html_format=true;

if($html_format and $this->listing_id == '') //there is no need to have a header if we are loading a single record.
{
	$this->ct->loadJSAndCSS();
    LayoutProcessor::renderPageHeader($this->ct);
}

//Process general tags before catalog tags to prepare headers for CSV etc output
if($html_format)
{
	$catalogtablecontent=tagProcessor_CatalogTableView::process($this->ct,$this->pagelayout,$this->catalogtablecode);
	if($catalogtablecontent=='')
	{
		$this->ct->LayoutProc->layout=$this->itemlayout;
		$catalogtablecontent=tagProcessor_Catalog::process($this->ct,$this->pagelayout,$this->catalogtablecode);
	}
	
	if($this->listing_id!='') //for reload single record functionality
		die($catalogtablecontent);
	
	$this->ct->LayoutProc->layout=$this->pagelayout;
	$this->pagelayout=$this->ct->LayoutProc->fillLayout();
}
else
{
	$catalogtablecontent=tagProcessor_CatalogTableView::process($this->ct,$this->pagelayout,$this->catalogtablecode);

	if($catalogtablecontent=='')
	{
		$this->ct->LayoutProc->layout=$itemlayout;
		$catalogtablecontent=tagProcessor_Catalog::process($this->ct,$this->pagelayout,$this->catalogtablecode);
	}

	$this->ct->LayoutProc->layout=$this->pagelayout;
	$this->pagelayout=$this->ct->LayoutProc->fillLayout();
}

$twig = new TwigProcessor($this->ct, $this->pagelayout);

$this->pagelayout = $twig->process();

$this->pagelayout=str_replace('&&&&quote&&&&','"',$this->pagelayout); // search boxes may return HTML elemnts that contain placeholders with quotes like this: &&&&quote&&&&
$this->pagelayout=str_replace($this->catalogtablecode,$catalogtablecontent,$this->pagelayout);

if($html_format)
    LayoutProcessor::applyContentPlugins($this->pagelayout);

if($this->ct->Env->frmt=='xml')
{
	if (ob_get_contents()) ob_end_clean();
	
    $filename = JoomlaBasicMisc::makeNewFileName($this->ct->Env->menu_params->get('page_title'),'xml');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Type: text/xml; charset=utf-8');
    header("Pragma: no-cache");
    header("Expires: 0");
	echo $this->pagelayout;
	die;//clean exit
}
elseif($this->ct->Env->clean==1)
{
    if (ob_get_contents()) ob_end_clean();
    echo $this->pagelayout;
	die ;//clean exit
}
else
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