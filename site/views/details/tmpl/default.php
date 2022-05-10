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

$document = JFactory::getDocument();
$document->addScript(JURI::root(true).'/components/com_customtables/libraries/customtables/media/js/base64.js');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/libraries/customtables/media/js/catalog.js" type="text/javascript"></script>');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/libraries/customtables/media/js/ajax.js"></script>');
$document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/libraries/customtables/media/css/style.css" type="text/css" rel="stylesheet" >');

if($this->ct->Env->legacysupport)
{
	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'itemtags.php');
	$LayoutProc = new LayoutProcessor($this->ct);
	$LayoutProc->layout = $this->layout_details;
	$this->layout_details = $LayoutProc->fillLayout($this->row);
}
	
$twig = new TwigProcessor($this->ct, $this->layout_details);
$results = $twig->process($this->row);
	
if($this->ct->Env->menu_params->get( 'allowcontentplugins' ))
	JoomlaBasicMisc::applyContentPlugins($results);

if($this->ct->Env->clean)
{
	if($this->ct->Env->frmt=='csv')
	{
		$filename = JoomlaBasicMisc::makeNewFileName($mydoc->getTitle(),'csv');

		if (ob_get_contents())
			ob_end_clean();

		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Type: text/csv; charset=utf-8');
		header("Pragma: no-cache");
		header("Expires: 0");

		echo mb_convert_encoding($results, 'UTF-16LE', 'UTF-8');

		die;//clean exit
	}
	elseif($this->ct->Env->frmt=='xml')
	{
		$filename = JoomlaBasicMisc::makeNewFileName($document->getTitle(),'xml');

		if(ob_get_contents())
			ob_end_clean();

		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Type: text/xml; charset=utf-8');
		header("Pragma: no-cache");
		header("Expires: 0");
	}

	echo $results;
	die;//clean exit
}

if ($this->ct->Env->menu_params->get( 'show_page_heading', 1 ) ) : ?>
<div class="page-header<?php echo $this->escape($this->ct->Env->menu_params->get('pageclass_sfx')); ?>">
	<h2 itemprop="headline"><?php echo JoomlaBasicMisc::JTextExtended($document->getTitle()); ?></h2>
</div>
<?php endif;

echo $results;
