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

$itemlayout=str_replace("\n",'',$this->itemlayout);

$catalogtablecontent=tagProcessor_CatalogTableView::process($this->Model,$this->pagelayout,$this->SearchResult,$this->catalogtablecode);

if($catalogtablecontent=='')
{
	$this->Model->LayoutProc->layout=$itemlayout;
	
	$catalogtablecontent=tagProcessor_Catalog::process($this->Model,$this->pagelayout,$this->SearchResult,$this->catalogtablecode);
	$catalogtablecontent=str_replace("\n",'',$catalogtablecontent);
	$catalogtablecontent=str_replace("\r",'',$catalogtablecontent);
	$catalogtablecontent=str_replace("\t",'',$catalogtablecontent);
	
	//$catalogtablecontent=trim(strip_tags(html_entity_decode($catalogtablecontent)));//header
}

//$this->pagelayout=trim(strip_tags(html_entity_decode($this->pagelayout)));//header

$this->Model->LayoutProc->layout=$this->pagelayout;
$this->pagelayout=$this->Model->LayoutProc->fillLayout();

$this->pagelayout=str_replace('&&&&quote&&&&','"',$this->pagelayout); // search boxes may return HTMl elemnts that contain placeholders with quotes like this: &&&&quote&&&&
$this->pagelayout=str_replace($this->catalogtablecode,$catalogtablecontent,$this->pagelayout);

LayoutProcessor::applyContentPlugins($this->pagelayout);

if (ob_get_contents()) ob_end_clean();

$filename = JoomlaBasicMisc::makeNewFileName($this->Model->params->get('page_title'),'csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Type: text/csv; charset=utf-8');
header("Pragma: no-cache");
header("Expires: 0");

echo chr(255).chr(254).mb_convert_encoding($this->pagelayout, 'UTF-16LE', 'UTF-8');
die;//clean exit
