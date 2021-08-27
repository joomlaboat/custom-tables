<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'catalogtag.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'catalogtableviewtag.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layouts.php');

$document = JFactory::getDocument();
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/catalog_187.js" type="text/javascript"></script>');
$document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/style.css" type="text/css" rel="stylesheet" >');

$html_format=false;
if($this->Model->frmt=='html' or $this->Model->frmt=='')
    $html_format=true;

if($html_format)
    LayoutProcessor::renderPageHeader($this->Model);

$pagelayout='';
$layout_catalog_name=$this->Model->params->get( 'escataloglayout' );
if($layout_catalog_name!='')
{
    $layouttype=0;
	$pagelayout=ESLayouts::getLayout($layout_catalog_name,$layouttype,$processLayoutTag = false); //It is safier to process layout after rendering the table

    if($layouttype==8)
        $this->Model->frmt='xml';
    elseif($layouttype==9)
        $this->Model->frmt='csv';
    elseif($layouttype==10)
        $this->Model->frmt='json';
}
else
    $pagelayout='{catalog:,notable}';

$layout_item_name=$this->Model->params->get('esitemlayout');

$itemlayout='';

if($layout_item_name!='')
{
    $layouttype=0;
	$itemlayout=ESLayouts::getLayout($layout_item_name,$layouttype);
}

$catalogtablecode=JoomlaBasicMisc::generateRandomString();//this is temporary replace place holder. to not parse content result again

//Process general tags before catalog tags to prepare headers for CSV etc output
if($html_format)
{
	$catalogtablecontent=tagProcessor_CatalogTableView::process($this->Model,$pagelayout,$this->SearchResult,$catalogtablecode);
	if($catalogtablecontent=='')
	{
		$this->Model->LayoutProc->layout=$itemlayout;
		$catalogtablecontent=tagProcessor_Catalog::process($this->Model,$pagelayout,$this->SearchResult,$catalogtablecode);
	}
	
	$this->Model->LayoutProc->layout=$pagelayout;
	$pagelayout=$this->Model->LayoutProc->fillLayout(array(), null, '');

}
else
{


	$catalogtablecontent=tagProcessor_CatalogTableView::process($this->Model,$pagelayout,$this->SearchResult,$catalogtablecode);
	if($catalogtablecontent=='')
	{
		$this->Model->LayoutProc->layout=$itemlayout;
		$catalogtablecontent=tagProcessor_Catalog::process($this->Model,$pagelayout,$this->SearchResult,$catalogtablecode);
	}

	$this->Model->LayoutProc->layout=$pagelayout;
	$pagelayout=$this->Model->LayoutProc->fillLayout(array(), null, '');
}



$pagelayout=str_replace('&&&&quote&&&&','"',$pagelayout); // search boxes may return HTMl elemnts that contain placeholders with quotes like this: &&&&quote&&&&

$pagelayout=str_replace($catalogtablecode,$catalogtablecontent,$pagelayout);

if($html_format)
    LayoutProcessor::applyContentPlugins($pagelayout);

if($this->Model->frmt=='xml')
{
	if (ob_get_contents()) ob_end_clean();
	
    $filename = JoomlaBasicMisc::makeNewFileName($this->Model->params->get('page_title'),'xml');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Type: text/xml; charset=utf-8');
    header("Pragma: no-cache");
    header("Expires: 0");
	echo $pagelayout;
	die ;//clean exit
}
elseif($this->Model->frmt=='csv')
{
	if (ob_get_contents()) ob_end_clean();

	$filename = JoomlaBasicMisc::makeNewFileName($this->Model->params->get('page_title'),'csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Type: text/csv; charset=utf-8');
    header("Pragma: no-cache");
    header("Expires: 0");

    echo chr(255).chr(254).mb_convert_encoding($pagelayout, 'UTF-16LE', 'UTF-8');
    die ;//clean exit
}
elseif($this->Model->clean==1)
{
    if (ob_get_contents()) ob_end_clean();
    echo $pagelayout;
	die ;//clean exit
}
else
	echo $pagelayout;
