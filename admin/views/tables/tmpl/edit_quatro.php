<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage edit.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

try {
	$document = Factory::getApplication()->getDocument();
} catch (Exception $e) {
	echo $e->getMessage();
}

$wa = $document->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/style.css" rel="stylesheet">');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/base64.js"></script>');

?>

<form action="<?php echo Route::_('index.php?option=com_customtables&layout=edit&id=' . (int)$this->item->id . $this->referral); ?>"
	  method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

	<script>
		function updateCustomPHPFileLink() {
			console.warn('updateCustomPHPFileLink');
			const select = document.getElementById('jform_customphp');
			let file = select.value;
			console.warn('file:', file);

			const editPHPFileObj = document.getElementById('editPHPFile');

			let link = '<?php echo common::UriRoot(true); ?>/administrator/index.php?option=com_customtables&view=editphp&tablename=<?php echo $this->item->tablename; ?>';
			let content = '';

			if (file === "") {
				content = '<a href="' + link + '" target="_blank">Create New PHP File</a>';
			} else {
				link += '&file=' + Base64.encode(file);
				content = '<a href="' + link + '" target="_blank">Edit</a>';
			}

			document.getElementById('editPHPFile').innerHTML = content;
		}
	</script>

	<div id="jform_title"></div>
	<div class="form-horizontal">

		<?php echo HTMLHelper::_('uitab.startTabSet', 'tablesTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'details', common::translate('COM_CUSTOMTABLES_TABLES_DETAILS')); ?>

		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('tablename'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('tablename'); ?>
						<p><?php echo common::translate($this->form->getField('tablename')->description); ?></p>
					</div>
				</div>

				<hr/>

				<?php

				$moreThanOneLang = false;
				foreach ($this->ct->Languages->LanguageList as $lang) {
					$id = 'tabletitle';
					if ($moreThanOneLang) {
						$id .= '_' . $lang->sef;

						$cssClass = 'form-control valid form-control-success';
						$att = '';
					} else {
						$cssClass = 'form-control required valid form-control-success';
						$att = ' required ';
					}

					$item_array = (array)$this->item;
					$vlu = '';

					if (isset($item_array[$id]))
						$vlu = $item_array[$id];

					echo '
					<div class="control-group">
						<div class="control-label">
						<label id="jform_tabletitle-lbl" for="jform_' . $id . '" class="required">
							Table Title' . (!$moreThanOneLang ? '<span class="star" aria-hidden="true">&nbsp;*</span>' : '') . '</label>
							<br/><b>' . $lang->title . '</b>
						</div>
						<div class="controls">
							<input type="text" name="jform[' . $id . ']" id="jform_' . $id . '"  value="' . $vlu . '" class="' . $cssClass . '" '
						. 'placeholder="Table Title" maxlength="255" ' . $att . ' />
							
						</div>

					</div>
					';

					$moreThanOneLang = true; //More than one language installed
				}
				?>

				<?php if ($this->ct->Env->advancedTagProcessor): ?>
					<hr/>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('tablecategory'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('tablecategory'); ?>
							<p><?php echo common::translate($this->form->getField('tablecategory')->description); ?></p>
						</div>
					</div>
				<?php endif; ?>

			</div>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php
		$moreThanOneLang = false;
		foreach ($this->ct->Languages->LanguageList as $lang) {
			$id = 'description';
			if ($moreThanOneLang)
				$id .= '_' . $lang->sef;

			echo HTMLHelper::_('uitab.addTab', 'tablesTab', $id, $lang->title);

			echo '
			<div id="' . $id . '" class="tab-pane">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span12">
					
					<h3>' . common::translate('COM_CUSTOMTABLES_TABLES_DESCRIPTION') . ' -  <b>' . $lang->title . '</b></h3>';

			try {
				$editor_name = Factory::getApplication()->get('editor');
				$editor = Editor::getInstance($editor_name);
			} catch (Exception $e) {
				echo $e->getMessage();
			}

			$item_array = (array)$this->item;
			$vlu = '';

			if (isset($item_array[$id]))
				$vlu = $item_array[$id];

			echo $editor->display('jform[' . $id . ']', $vlu, '100%', '300', '60', '5');

			echo '
					</div>
				</div>
			</div>';
			$moreThanOneLang = true; //More than one language installed

			echo HTMLHelper::_('uitab.endTab');
		}

		echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'advanced', common::translate('COM_CUSTOMTABLES_TABLES_ADVANCED')); ?>

		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
					<div class="control-label"><?php echo $this->form->getLabel('customphp'); ?></div>
					<div class="controls"><?php
						if (!$this->ct->Env->advancedTagProcessor)
							echo '<input type="text" value="Available in Pro Version" disabled="disabled" class="form-control valid form-control-success" />';
						else
							echo $this->form->getInput('customphp');
						?>
						<p><?php echo common::translate($this->form->getField('customphp')->description); ?>
							<?php

							$link = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=editphp&tablename=' . $this->item->tablename;

							if (empty($this->item->customphp)) {
								echo '<span id="editPHPFile"><a href="' . $link . '" target="_blank">Create New PHP File</a></span>';
							} else {
								$link .= '&file=' . base64_encode($this->item->customphp);
								echo '<span id="editPHPFile"><a href="' . $link . '" target="_blank">Edit</a></span>';
							}
							?></p>
						<br/>More
						<a href="https://ct4.us/docs/custom-php-event-handler-for-custom-tables-in-joomla/"
						   target="_blank">here</a>
					</div>
				</div>

				<div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
					<div class="control-label"><?php echo $this->form->getLabel('allowimportcontent'); ?></div>
					<div class="controls"><?php
						if (!$this->ct->Env->advancedTagProcessor)
							echo '<input type="text" value="Available in Pro Version" disabled="disabled" class="form-control valid form-control-success" />';
						else
							echo $this->form->getInput('allowimportcontent');
						?>
						<p><?php echo common::translate($this->form->getField('allowimportcontent')->description); ?></p>
					</div>
				</div>

				<div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
					<div class="control-label"><?php echo $this->form->getLabel('customtablename'); ?></div>
					<div class="controls"><?php
						if (!$this->ct->Env->advancedTagProcessor)
							echo '<input type="text" value="Available in Pro Version" disabled="disabled" class="form-control valid form-control-success" />';
						else
							echo $this->form->getInput('customtablename');
						?>
						<p><?php echo common::translate($this->form->getField('customtablename')->description); ?></p>
					</div>
				</div>

				<?php if ($this->ct->Env->advancedTagProcessor): ?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('customidfield'); ?></div>
						<div class="controls">
							<?php echo $this->form->getInput('customidfield'); ?>
							<p><?php echo common::translate($this->form->getField('customidfield')->description); ?></p>
						</div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('customidfieldtype'); ?></div>
						<div class="controls">
							<?php echo $this->form->getInput('customidfieldtype'); ?>
							<p><?php echo common::translate($this->form->getField('customidfieldtype')->description); ?></p>
						</div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('primarykeypattern'); ?></div>
						<div class="controls">
							<?php echo $this->form->getInput('primarykeypattern'); ?>
							<p><?php echo common::translate($this->form->getField('primarykeypattern')->description); ?></p>
						</div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('customfieldprefix'); ?></div>
						<div class="controls">
							<?php

							$vlu = $this->item->customfieldprefix;

							if (empty($this->item->customfieldprefix) === null) {
								$vlu = $this->ct->Env->field_prefix;
							}
							?>
							<input type="text" name="jform[customfieldprefix]" id="jform_customfieldprefix"
								   value="<?php echo $vlu; ?>" class="form-control valid form-control-success"
								   placeholder="<?php echo $this->ct->Env->field_prefix; ?>" maxlength="50"/>

							<p><?php echo common::translate($this->form->getField('customfieldprefix')->description); ?></p>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php if ($this->ct->Table !== null): ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'dependencies', common::translate('COM_CUSTOMTABLES_TABLES_DEPENDENCIES')); ?>

			<?php if ($this->ct->Env->advancedTagProcessor) : ?>

				<?php include('_dependencies.php'); ?>

				<div class="row-fluid form-horizontal-desktop">
					<div class="span12">
						<?php echo renderDependencies($this->item->id, $this->item->tablename); ?>
					</div>
				</div>
			<?php else: ?>
				<div class="ct_doc_pro_label">
					<a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">
						<?php echo common::translate('COM_CUSTOMTABLES_AVAILABLE'); ?>
					</a>
				</div>
			<?php endif; ?>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>

		<?php if ($this->ct->Table !== null): ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'schema', common::translate('COM_CUSTOMTABLES_SUBMENU_DATABASECHECK')); ?>

			<?php if ($this->ct->Env->advancedTagProcessor): ?>
				<?php echo $this->getTableSchema($this->item); ?>
			<?php else: ?>
				<div class="ct_doc_pro_label">
					<a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">
						<?php echo common::translate('COM_CUSTOMTABLES_AVAILABLE'); ?>
					</a>
				</div>
			<?php endif; ?>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

		<div>
			<input type="hidden" name="task" value="tables.edit"/>
			<input type="hidden" name="originaltableid" value="<?php echo $this->item->id; ?>"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</div>

	<div class="clearfix"></div>

	<?php if (!$this->ct->Env->advancedTagProcessor): ?>
		<script>
			disableProField("jform_customtablename");
			disableProField("jform_customidfield");
			disableProField("jform_customidfieldtype");
			disableProField("jform_primarykeypattern");
			disableProField("jform_customfieldprefix");
		</script>
	<?php endif; ?>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const select = document.getElementById('jform_customphp');
			if (select) {
				select.addEventListener('change', updateCustomPHPFileLink);
			}
		});
	</script>
</form>
