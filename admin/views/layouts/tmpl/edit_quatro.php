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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

$document = Factory::getApplication()->getDocument();

$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/fieldtypes.css" rel="stylesheet">');
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/modal.css" rel="stylesheet">');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/ajax.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams_common.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams_j4.js"></script>');

$wa = $document->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoflayouts.php');

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

	<?php echo HTMLHelper::_('uitab.startTabSet', 'layouteditorTabs', ['active' => $this->active_tab, 'recall' => true, 'breakpoint' => 768]); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'layouteditorTabs', 'general', common::translate('COM_CUSTOMTABLES_LAYOUTS_GENERAL')); ?>
	<div class="row-fluid form-horizontal-desktop">
		<div class="span12">

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('layoutname'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('layoutname'); ?>
					<p class="field-description">
						<?php echo common::translate($this->form->getField('layoutname')->description); ?>
					</p>
				</div>
			</div>

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('layouttype'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('layouttype'); ?>
					<p class="field-description">
						<?php echo common::translate($this->form->getField('layouttype')->description); ?>
					</p>
				</div>
			</div>

			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('tableid'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('tableid'); ?>
					<p class="field-description">
						<?php echo common::translate($this->form->getField('tableid')->description); ?>
					</p>
				</div>
			</div>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'layouteditorTabs', 'layoutcode-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_HTML')); ?>

	<?php
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

	?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php

	echo HTMLHelper::_('uitab.addTab', 'layouteditorTabs', 'layoutmobile-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_HTML_MOBILE')); ?>
	<?php
	if ($this->ct->Env->advancedTagProcessor) {
		$layoutCode = $this->item->layoutmobile ?? '';
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
	?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'layouteditorTabs', 'layoutcss-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_CSS')); ?>
	<?php
	if ($this->ct->Env->advancedTagProcessor) {
		$layoutCode = $this->item->layoutcss ?? '';
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
	?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'layouteditorTabs', 'layoutjs-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_JS')); ?>
	<?php
	if ($this->ct->Env->advancedTagProcessor) {
		$layoutCode = $this->item->layoutjs ?? '';
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
	}
	?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'layouteditorTabs', 'params-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_FILTERS')); ?>
	<?php
	if ($this->ct->Env->advancedTagProcessor) {
		?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('filter'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('filter'); ?>
						<p class="field-description">
							<?php echo htmlspecialchars(common::translate($this->form->getField('filter')->description)); ?>
						</p>
					</div>

				</div>
			</div>
		</div>
		<?php
	} else {
		echo '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';
	}
	?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>


	<?php echo HTMLHelper::_('uitab.addTab', 'layouteditorTabs', 'params-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_PERMISSIONS')); ?>
	<?php
	if ($this->ct->Env->advancedTagProcessor) {
		?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('addusergroups'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('addusergroups'); ?>
						<p class="field-description">
							<?php echo common::translate($this->form->getField('addusergroups')->description); ?>
						</p>
					</div>

				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('editusergroups'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('editusergroups'); ?>
						<p class="field-description">
							<?php echo common::translate($this->form->getField('editusergroups')->description); ?>
						</p>
					</div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('publishusergroups'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('publishusergroups'); ?>
						<p class="field-description">
							<?php echo common::translate($this->form->getField('publishusergroups')->description); ?>
						</p>
					</div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('deleteusergroups'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('deleteusergroups'); ?>
						<p class="field-description">
							<?php echo common::translate($this->form->getField('deleteusergroups')->description); ?>
						</p>
					</div>
				</div>

			</div>
		</div>
		<?php
	} else {
		echo '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';
	}
	?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'layouteditorTabs', 'params-tab', common::translate('COM_CUSTOMTABLES_LAYOUTS_PARAMS')); ?>
	<?php
	if ($this->ct->Env->advancedTagProcessor) {
		?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('publishstatus'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('publishstatus'); ?>
						<p class="field-description">
							<?php echo common::translate($this->form->getField('publishstatus')->description); ?>
						</p>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('mimetype'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('mimetype'); ?>
						<p class="field-description">
							<?php echo common::translate($this->form->getField('mimetype')->description); ?>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	} else {
		echo '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';
	}
	?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="task" value="layouts.edit"/>
	<?php echo HTMLHelper::_('form.token'); ?>

	<div class="clearfix"></div>
	<?php
	echo $this->layoutEditor->render_onPageLoads($onPageLoads);
	$this->getMenuItems();
	?>

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