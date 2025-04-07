<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('jquery.framework');
jimport('joomla.html.html.bootstrap');

try {
	common::loadJSAndCSS($this->ct->Params, $this->ct->Env, $this->ct->Table->fieldInputPrefix ?? '');

	$document = Factory::getApplication()->getDocument();

	if (is_array($this->ct->LayoutVariables['styles'] ?? null)) {
		foreach ($this->ct->LayoutVariables['styles'] as $style) {
			$document->addCustomTag('<link rel="stylesheet" href="' . $style . '" type="text/css" />');
		}
	}

	if (is_array($this->ct->LayoutVariables['scripts'] ?? null)) {
		foreach ($this->ct->LayoutVariables['scripts'] as $script) {
			$document->addCustomTag('<script src="' . $script . '"></script>');
		}
	}

	if (!empty($this->result['style']))
		$document->addCustomTag('<style>' . $this->result['style'] . '</style>');

	if (!empty($this->result['script']))
		$document->addCustomTag('<script>' . $this->result['script'] . '</script>');

} catch (Exception $e) {
	common::enqueueMessage($e->getMessage());
}

if ($this->ct->Params->showPageHeading and $this->ct->Params->pageTitle !== null) {
	echo '<div class="page-header' . common::ctStripTags($this->ct->Params->pageClassSFX ?? '') . '"><h2 itemprop="headline">'
		. common::translate($this->ct->Params->pageTitle) . '</h2></div>';
}

if ($this->result['success']) {
	if (isset($this->result['html']))
		echo $this->result['html'];
	else
		common::enqueueMessage('HTML Output is empty');
} else {
	common::enqueueMessage($this->result['message']);
}

?>
<!-- Modal content -->
<div id="ctModal" class="ctModal">
	<div id="ctModal_box" class="ctModal_content">
		<span id="ctModal_close" class="ctModal_close">&times;</span>
		<div id="ctModal_content"></div>
	</div>
</div>
<!-- end of the modal -->