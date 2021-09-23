<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'itemtags.php');

	if(strpos($this->Model->LayoutProc->layout,'{toolbar')===false)
		$toolbar_array=array();
	else
		$toolbar_array= tagProcessor_Item::getToolbar($this->Model,$this->row);

	$this->Model->LayoutProc->toolbar_array=$toolbar_array;
	$results = $this->Model->LayoutProc->fillLayout($this->row);

	if($this->params->get( 'allowcontentplugins' ))
		LayoutProcessor::applyContentPlugins($results);

	$mydoc = JFactory::getDocument();

        if($this->Model->ct->Env->clean)
        {
			if($this->Model->ct->Env->frmt=='csv')
			{
				$filename = JoomlaBasicMisc::makeNewFileName($mydoc->getTitle(),'csv');

				if (ob_get_contents())
					ob_end_clean();

				header('Content-Disposition: attachment; filename="'.$filename.'"');
				header('Content-Type: text/csv; charset=utf-8');
				header("Pragma: no-cache");
				header("Expires: 0");

				echo mb_convert_encoding($results, 'UTF-16LE', 'UTF-8');

				die ;//clean exit

			}
			elseif($this->Model->ct->Env->frmt=='xml')
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
		die ;//clean exit
        }

if ($this->Model->params->get( 'show_page_heading', 1 ) ) : ?>
<div class="page-header<?php echo $this->escape($this->Model->params->get('pageclass_sfx')); ?>">
	<h2 itemprop="headline"><?php echo JoomlaBasicMisc::JTextExtended($mydoc->getTitle()); ?></h2>
</div>
<?php endif;

echo $results;
