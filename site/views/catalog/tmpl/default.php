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

if ($this->ct->Env->frmt == 'html') {
	if (empty($this->ct->Params->listing_id)) //there is no need to have a header if we are loading a single record.
	{
		if ($this->ct->Params->showPageHeading) {

			if ($this->ct->Params->pageTitle) {
				$title = common::translate($this->ct->Params->pageTitle);
				echo '<div class="page-header' . ($this->ct->Params->pageClassSFX ?? '') . '"><h2 itemprop="headline">' . $title . '</h2></div>';
			}
		}
	}
}

try {
	if (empty($this->ct->Params->listing_id)) {
		common::loadJSAndCSS($this->ct->Params, $this->ct->Env, $this->ct->Table->fieldInputPrefix);

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

		if (!empty($this->catalog->layoutCodeCSS))
			$document->addCustomTag('<style>' . $this->catalog->layoutCodeCSS . '</style>');

		if (!empty($this->catalog->layoutCodeJS))
			$document->addCustomTag('<script>' . $this->catalog->layoutCodeJS . '</script>');
	}

} catch (Exception $e) {
	try {
		Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}

echo $this->content;

try {
	if (common::inputGetInt('clean', 0) == 1 or !empty(common::inputGetCmd('listing_id')))
		exit;//Clean exit, single record loaded.
} catch (Exception $e) {
	echo $e->getMessage();
}

if ($this->ct->Env->frmt == 'html') {
	if (isset($this->ct->LayoutVariables['ordering_field_type_found']) and $this->ct->LayoutVariables['ordering_field_type_found']) {

		if ($this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT)) {

			$saveOrderingUrl = 'index.php?option=com_customtables&view=catalog&task=ordering&tableid=' . $this->ct->Table->tableid . '&tmpl=component&clean=1';
			if (CUSTOMTABLES_JOOMLA_MIN_4) {
				HTMLHelper::_('draggablelist.draggable');
			} else {
				HTMLHelper::_('sortablelist.sortable', 'ctTable_' . $this->ct->Table->tableid, 'ctTableForm_' . $this->ct->Table->tableid, 'asc', $saveOrderingUrl);
			}
		}
	}

	if (empty($this->ct->Params->listing_id)) //there is no need to have a header if we are loading a single record.
	{
		echo '<!-- Modal content -->
<div id="ctModal" class="ctModal">
	<div id="ctModal_box" class="ctModal_content">
		<span id="ctModal_close" class="ctModal_close">&times;</span>
		<div id="ctModal_content"></div>
		</div>
</div>
<!-- end of the modal -->';
	} else {
		exit;//Render single record only
	}
}