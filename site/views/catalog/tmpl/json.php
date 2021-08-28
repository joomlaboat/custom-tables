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

$pagelayout='';
$layout_catalog_name=$this->Model->params->get( 'escataloglayout' );
if($layout_catalog_name!='')
{
    $layouttype=0;
	$pagelayout=ESLayouts::getLayout($layout_catalog_name,$layouttype,$processLayoutTag = false); //It is safier to process layout after rendering the table
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

$catalogtablecontent=tagProcessor_CatalogTableView::process($this->Model,$pagelayout,$this->SearchResult,$catalogtablecode);
if($catalogtablecontent=='')
{
	$this->Model->LayoutProc->layout=$itemlayout;
	$catalogtablecontent=tagProcessor_Catalog::process($this->Model,$pagelayout,$this->SearchResult,$catalogtablecode);
}

$this->Model->LayoutProc->layout=$pagelayout;
$pagelayout=$this->Model->LayoutProc->fillLayout(array(), null, '');


$pagelayout=str_replace('&&&&quote&&&&','"',$pagelayout); // search boxes may return HTMl elemnts that contain placeholders with quotes like this: &&&&quote&&&&
$pagelayout=str_replace($catalogtablecode,$catalogtablecontent,$pagelayout);

LayoutProcessor::applyContentPlugins($pagelayout);

if (ob_get_contents()) ob_end_clean();

$filename = JoomlaBasicMisc::makeNewFileName($this->Model->params->get('page_title'),'json');

header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Type: application/json; charset=utf-8');
header("Pragma: no-cache");
header("Expires: 0");

echo $pagelayout;

die;