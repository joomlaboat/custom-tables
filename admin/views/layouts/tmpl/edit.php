<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/layouts/tmpl/edit.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Layouts;
use CustomTables\ListOfLayouts;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$document = Factory::getApplication()->getDocument();
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/fieldtypes.css" rel="stylesheet">');
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/modal.css" rel="stylesheet">');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/ajax.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams_common.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams.js"></script>');

if (CUSTOMTABLES_JOOMLA_MIN_4) {
	$wa = $document->getWebAssetManager();
	$wa->useScript('keepalive')->useScript('form.validate');
} else {
	HTMLHelper::_('behavior.formvalidation');
	HTMLHelper::_('behavior.keepalive');
}

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
		. 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoflayouts.php');

$onPageLoads = array();
$typeBoxId = "jform_layouttype";

foreach ($this->allTables as $table) {

	$ct = new CT([], true);
	$ct->getTable($table['id']);
	$list = array();
	foreach ($ct->Table->fields as $field)
		$list[] = [$field['id'], $field['fieldname']];

	echo '<div id="fieldsData' . $table['id'] . '" style="display:none;">' . common::ctJsonEncode($list) . '</div>
';
}
?>
<script>
	<?php echo 'all_tables=' . common::ctJsonEncode($this->allTables) . ';'; ?>
</script>

<form action="<?php echo Route::_('index.php?option=com_customtables&layout=edit&id=' . (int)$this->item->id . $this->referral); ?>"
	  method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

	<?php echo HTMLHelper::_('bootstrap.startTabSet', 'layoutsTab', array('active' => $this->active_tab)); ?>

	<?php echo HTMLHelper::_('bootstrap.addTab', 'layoutsTab', 'general', common::translate('COM_CUSTOMTABLES_LAYOUTS_GENERAL')); ?>

	<div class="row-fluid form-horizontal-desktop">
		<div class="span12">

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('layoutname'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('layoutname'); ?></div>
			</div>

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('layouttype'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('layouttype'); ?></div>
			</div>

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('tableid'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('tableid'); ?></div>
			</div>
		</div>
	</div>

	<?php echo HTMLHelper::_('bootstrap.endTab');

	echo HTMLHelper::_('bootstrap.addTab', 'layoutsTab', 'layoutcode-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_HTML'));

	$layoutCode = $this->item->layoutcode;
	if ($this->item->id != 0 and $this->ct->Env->folderToSaveLayouts !== null) {
		$layouts = new Layouts($this->ct);
		$content = $layouts->getLayoutFileContent($this->item->id, $this->item->layoutname, $layoutCode, $this->item->modified_timestamp, $this->item->layoutname . '.html', 'layoutcode');
		if ($content != null)
			$layoutCode = $content;
	}
	echo $this->renderTextArea($this->item->layoutcode, 'layoutcode', $typeBoxId, $onPageLoads, 'layouteditor');
	if ($this->ct->Env->folderToSaveLayouts !== null)
		echo '<div class="layoutFilePath">Path: ' . $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $this->item->layoutname . '.html</div>';

	echo HTMLHelper::_('bootstrap.endTab');

	echo HTMLHelper::_('bootstrap.addTab', 'layoutsTab', 'layoutmobile-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_HTML_MOBILE'));

	if ($this->ct->Env->advancedTagProcessor) {
		$layoutCode = $this->item->layoutmobile;
		if ($this->item->id != 0 and $this->ct->Env->folderToSaveLayouts !== null) {
			$layouts = new Layouts($this->ct);
			$content = $layouts->getLayoutFileContent($this->item->id, $this->item->layoutname, $layoutCode, $this->item->modified_timestamp, $this->item->layoutname . '_mobile.html', 'layoutmobile');
			if ($content != null)
				$layoutCode = $content;
		}
		echo $this->renderTextArea($layoutCode, 'layoutmobile', $typeBoxId, $onPageLoads, 'layouteditor');

		if ($this->ct->Env->folderToSaveLayouts !== null)
			echo '<div class="layoutFilePath">Path: ' . $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $this->item->layoutname . '_mobile.html</div>';
	} else {
		echo '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';
	}

	echo HTMLHelper::_('bootstrap.endTab');

	echo HTMLHelper::_('bootstrap.addTab', 'layoutsTab', 'layoutcss-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_CSS'));

	if ($this->ct->Env->advancedTagProcessor) {
		$layoutCode = $this->item->layoutcss;
		if ($this->item->id != 0 and $this->ct->Env->folderToSaveLayouts !== null) {
			$layouts = new Layouts($this->ct);
			$content = $layouts->getLayoutFileContent($this->item->id, $this->item->layoutname, $layoutCode, $this->item->modified_timestamp, $this->item->layoutname . '.css', 'layoutcss');
			if ($content != null)
				$layoutCode = $content;
		}
		echo $this->renderTextArea($this->item->layoutcss, 'layoutcss', $typeBoxId, $onPageLoads, 'css');

		if ($this->ct->Env->folderToSaveLayouts !== null)
			echo '<div class="layoutFilePath">Path: ' . $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $this->item->layoutname . '.css</div>';
	} else {
		echo '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';
	}

	echo HTMLHelper::_('bootstrap.endTab');

	echo HTMLHelper::_('bootstrap.addTab', 'layoutsTab', 'layoutjs-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_JS'));

	if ($this->ct->Env->advancedTagProcessor) {
		$layoutCode = $this->item->layoutjs;
		if ($this->item->id != 0 and $this->ct->Env->folderToSaveLayouts !== null) {
			$layouts = new Layouts($this->ct);
			$content = $layouts->getLayoutFileContent($this->item->id, $this->item->layoutname, $layoutCode, $this->item->modified_timestamp, $this->item->layoutname . '.js', 'layoutjs');
			if ($content != null)
				$layoutCode = $content;
		}
		echo $this->renderTextArea($this->item->layoutjs, 'layoutjs', $typeBoxId, $onPageLoads, 'javascript');

		if ($this->ct->Env->folderToSaveLayouts !== null)
			echo '<div class="layoutFilePath">Path: ' . $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $this->item->layoutname . '.js</div>';

	} else {
		echo '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';
		echo common::translate('COM_CUSTOMTABLES_AVAILABLE');
	}

	echo HTMLHelper::_('bootstrap.endTab');

	echo HTMLHelper::_('bootstrap.addTab', 'layoutsTab', 'filters-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_FILTERS'));

	if ($this->ct->Env->advancedTagProcessor): ?>

		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('filter'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('filter'); ?></div>
				</div>

			</div>
		</div>

	<?php else: ?>
		<div class="ct_doc_pro_label">
			<a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">
				<?php echo common::translate('COM_CUSTOMTABLES_AVAILABLE'); ?>
			</a>
		</div>';
	<?php endif; ?>

	<?php

	echo HTMLHelper::_('bootstrap.endTab');

	echo HTMLHelper::_('bootstrap.addTab', 'layoutsTab', 'permissions-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_PERMISSIONS'));

	if ($this->ct->Env->advancedTagProcessor): ?>

		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('addusergroups'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('addusergroups'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('editusergroups'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('editusergroups'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('publishusergroups'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('publishusergroups'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('deleteusergroups'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('deleteusergroups'); ?></div>
				</div>

			</div>
		</div>

	<?php else: ?>
		<div class="ct_doc_pro_label">
			<a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">
				<?php echo common::translate('COM_CUSTOMTABLES_AVAILABLE'); ?>
			</a>
		</div>';
	<?php endif; ?>

	<?php

	echo HTMLHelper::_('bootstrap.endTab');

	echo HTMLHelper::_('bootstrap.addTab', 'layoutsTab', 'params-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_PARAMS'));

	if ($this->ct->Env->advancedTagProcessor): ?>

	<div class="row-fluid form-horizontal-desktop">
		<div class="span12">

			<div class="control-group">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('publishstatus'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('publishstatus'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('mimetype'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('mimetype'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('stealth'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('stealth'); ?></div>
				</div>

			</div>
		</div>

		<?php else: ?>
			<div class="ct_doc_pro_label">
				<a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">
					<?php echo common::translate('COM_CUSTOMTABLES_AVAILABLE'); ?>
				</a>
			</div>';
		<?php endif; ?>

		<?php
		echo HTMLHelper::_('bootstrap.endTabSet');
		echo HTMLHelper::_('form.token');
		echo $this->layoutEditor->render_onPageLoads($onPageLoads);

		$this->getMenuItems();
		?>
		<input type="hidden" name="task" value="layouts.edit"/>

		<div id="allLayoutRaw"
			 style="display:none;"><?php echo common::ctJsonEncode(ListOfLayouts::getLayouts()); ?></div>
		<div id="dependencies_content" style="display:none;">
			<h3><?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_WHAT_IS_USING_IT'); ?></h3>
			<div id="layouteditor_tagsContent0" class="dynamic_values_list dynamic_values">
				<?php

				if ($this->item !== null and $this->item->layoutname !== null) {
					require('dependencies.php');
					echo renderDependencies($this->item); // this will be shown upon the click in the toolbar
				}
				?>
			</div>
		</div>
</form>