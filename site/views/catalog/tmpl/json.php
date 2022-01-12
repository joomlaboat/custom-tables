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
$itemlayout=str_replace("\r",'',$itemlayout);
$itemlayout=str_replace("\t",'',$itemlayout);

$catalogtablecontent=tagProcessor_CatalogTableView::process($this->ct,$this->pagelayout,$this->catalogtablecode);

if($catalogtablecontent=='')
{
	$this->ct->LayoutProc->layout=$itemlayout;
	$catalogtablecontent=tagProcessor_Catalog::process($this->ct,$this->pagelayout,$this->catalogtablecode);
	
	$catalogtablecontent=str_replace("\n",'',$catalogtablecontent);
	$catalogtablecontent=str_replace("\r",'',$catalogtablecontent);
	$catalogtablecontent=str_replace("\t",'',$catalogtablecontent);
}

$this->ct->LayoutProc->layout=$this->pagelayout;
$this->pagelayout=$this->ct->LayoutProc->fillLayout();

$this->pagelayout=str_replace('&&&&quote&&&&','"',$this->pagelayout); // search boxes may return HTML elemnts that contain placeholders with quotes like this: &&&&quote&&&&
$this->pagelayout=str_replace($this->catalogtablecode,$catalogtablecontent,$this->pagelayout);

LayoutProcessor::applyContentPlugins($this->pagelayout);

//if (ob_get_contents()) ob_end_clean();

$filename = JoomlaBasicMisc::makeNewFileName($this->ct->Env->menu_params->get('page_title'),'json');

//header('Content-Disposition: attachment; filename="'.$filename.'"');
//header('Content-Type: application/json; charset=utf-8');
//header("Pragma: no-cache");
//header("Expires: 0");

echo $this->pagelayout;

die;
