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
$document->addScript(JURI::root(true).'/components/com_customtables/js/base64.js');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/catalog.js" type="text/javascript"></script>');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/ajax.js"></script>');
$document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/style.css" type="text/css" rel="stylesheet" >');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'itemtags.php');

	$results = $this->ct->LayoutProc->fillLayout($this->row);
	
	if($this->ct->Env->advancedtagprocessor)
	{
		$twig = new TwigProcessor($this->ct, $results);
		$results = $twig->process($this->row);
	}
	
	if($this->ct->Env->menu_params->get( 'allowcontentplugins' ))
		LayoutProcessor::applyContentPlugins($results);

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
				$filename = JoomlaBasicMisc::makeNewFileName($mydoc->getTitle(),'xml');

				if (ob_get_contents())
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
