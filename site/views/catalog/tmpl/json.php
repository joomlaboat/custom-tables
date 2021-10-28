<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Layouts;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'catalogtag.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'catalogtableviewtag.php');

$Layouts = new Layouts($this->Model->ct);

$pagelayout='';
$layout_catalog_name=$this->Model->params->get( 'escataloglayout' );
if($layout_catalog_name!='')
{
	$pagelayout = $Layouts->getLayout($layout_catalog_name,false); //It is safier to process layout after rendering the table
	
	$pagelayout=str_replace("\n",'',$pagelayout);
	$pagelayout=str_replace("\r",'',$pagelayout);
	$pagelayout=str_replace("\t",'',$pagelayout);
}
else
    $pagelayout='{catalog:,notable}';

$layout_item_name=$this->Model->params->get('esitemlayout');

$itemlayout='';

if($layout_item_name!='')
{
	$itemlayout = $Layouts->getLayout($layout_item_name);
	$itemlayout=str_replace("\n",'',$itemlayout);
	$itemlayout=str_replace("\r",'',$itemlayout);
	$itemlayout=str_replace("\t",'',$itemlayout);
}

$catalogtablecode=JoomlaBasicMisc::generateRandomString();//this is temporary replace place holder. to not parse content result again

$catalogtablecontent=tagProcessor_CatalogTableView::process($this->Model,$pagelayout,$this->SearchResult,$catalogtablecode);

if($catalogtablecontent=='')
{
	$this->Model->LayoutProc->layout=$itemlayout;
	$catalogtablecontent=tagProcessor_Catalog::process($this->Model,$pagelayout,$this->SearchResult,$catalogtablecode);
	
	$catalogtablecontent=str_replace("\n",'',$catalogtablecontent);
	$catalogtablecontent=str_replace("\r",'',$catalogtablecontent);
	$catalogtablecontent=str_replace("\t",'',$catalogtablecontent);
}

$this->Model->LayoutProc->layout=$pagelayout;
$pagelayout=$this->Model->LayoutProc->fillLayout();


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
