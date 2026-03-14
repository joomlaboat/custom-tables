<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// load tooltip behavior
if (!CUSTOMTABLES_JOOMLA_MIN_4) {
	HTMLHelper::_('behavior.tooltip');
}

HTMLHelper::_('behavior.formvalidator');
$document = Factory::getApplication()->getDocument();

$fields = [];
$fields[] = array('id' => "_ignore", 'fieldname' => ' - ' . Text::_('COM_CUSTOMTABLES_SELECT'));
foreach ($this->ct->Table->fields as $field) {
	$fields[] = array('id' => $field['fieldname'], 'fieldname' => $field['fieldtitle']);
}
$fields[] = array('id' => '_id', 'fieldname' => ' - ' . Text::_('COM_CUSTOMTABLES_RECORDS_ID'));

$default_class = 'form-select';
$index = 0;
?>
<style>

	html[data-bs-theme="light"] .field-enabled {
		color: black !important;
	}

	html[data-bs-theme="light"] .field-disabled {
		color: lightgray !important;
		text-decoration: line-through;
	}

	html[data-bs-theme="dark"] .field-enabled {
		color: white !important;
		font-weight: bold;

	}

	html[data-bs-theme="dark"] .field-disabled {
		color: #222222 !important;
		text-decoration: line-through;
	}

	.table-fade {
		position: relative;
		/*max-height: 300px;  visible portion
		overflow: hidden;
		*/
	}

	.table-fade::after {
		content: "";
		position: absolute;
		bottom: 0;
		left: 0;
		width: 100%;
		height: 70%;
		pointer-events: none;
	}

	/* light mode */
	html[data-bs-theme="light"] .table-fade::after {
		background: linear-gradient(to bottom, rgba(255, 255, 255, 0), rgba(255, 255, 255, 1));
	}

	/* dark mode */
	html[data-bs-theme="dark"] .table-fade::after {
		background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 1));
	}


</style>
<script>
	const CSVImport = new CustomTablesCSVImport("canvas", null, [], []);

	function fieldChanged(index) {

		let objStartFrom = document.getElementById('start_from');
		let startFrom = objStartFrom.value;

		let control_name = 'field_map_' + index;
		let obj = document.getElementById(control_name);
		if (obj) {

			let rows = document.querySelectorAll('td[data-column="' + index + '"]');

			if (obj.value !== "" && obj.value !== "_ignore") {

				for (let i = 0; i < rows.length; i++) {
					let el = rows[i];
					if (i < startFrom - 1)
						el.classList.replace('field-enabled', 'field-disabled');
					else
						el.classList.replace('field-disabled', 'field-enabled');
				}

			} else {

				for (let i = 0; i < rows.length; i++) {
					let el = rows[i];
					el.classList.replace('field-enabled', 'field-disabled');
				}

			}
		}
	}

	function startFromChanged() {
		let obj = document.getElementById('start_from');
		if (obj) {
			let startFrom = obj.value;
			console.log("startFrom", startFrom);

			const table = document.getElementById('recordPreview');

			if (table) {

				let cols = <?php echo count($this->previewData['fields']); ?>;
				for (let i = 0; i < cols; i++) {
					fieldChanged(i);
				}

			}
		}


	}

</script>
<form method="post" action="" name="adminForm" id="adminForm">
	<?php $divClass = count($this->previewData['records']) >= 20 ? "table-fade" : ""; ?>

	<div class="control-group">
		<div class="control-label">
			<label id="jform_tabletitle-lbl" for="separator" class="required">Separator:</label>
		</div>
		<div class="controls">
			<?php
			$control_name = 'separator';
			$separators = [['id' => 'comma', 'label' => 'Comma ,'], ['id' => 'semicolon', 'label' => 'Semicolon ;'], ['id' => 'tab', 'label' => 'Tab'], ['id' => 'space', 'label' => 'Space']];
			$separator = common::inputGetCmd('separator', $this->previewData['separator'] ?? 'comma');

			echo HTMLHelper::_('select.genericlist', $separators, $control_name, 'name="' . $control_name . '" class="' . $default_class . '"',
					'id', 'label', $separator);
			?>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label">
			<label id="jform_tabletitle-lbl" for="enclosure" class="required">String Delimiter
				(Enclosure):</label>
		</div>
		<div class="controls">
			<?php
			$control_name = 'enclosure';
			$enclosures = [['id' => 'quote', 'label' => 'Quote "'], ['id' => 'apostrophe', 'label' => 'Apostrophe \'']];
			$enclosure = common::inputGetCmd('enclosure', 'quote');

			echo HTMLHelper::_('select.genericlist', $enclosures, $control_name, 'name="' . $control_name . '" class="' . $default_class . '"',
					'id', 'label', $enclosure);
			?>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label">
			<label id="jform_tabletitle-lbl" for="start_from" class="required">Start from row:</label>
		</div>
		<div class="controls">
			<?php
			$control_name = 'start_from';
			$numbers = [['id' => 1, 'label' => '1'], ['id' => 2, 'label' => '2']];
			$start_from = common::inputGetInt('start_from', $this->previewData['start_from'] ?? 1);

			echo HTMLHelper::_('select.genericlist', $numbers, $control_name, 'name="' . $control_name . '" onchange="startFromChanged(' . $index . ')"  class="' . $default_class . '"',
					'id', 'label', $start_from);
			?>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label">
			<label id="jform_tabletitle-lbl" for="update_insert" class="required">Update / Insert Record:</label>
		</div>
		<div class="controls">
			<?php
			$control_name = 'update_insert';
			$options = [
					['id' => 'insert_match_id', 'label' => 'Insert / Update if Record ID matches'],
					['id' => 'insert_match_column', 'label' => 'Insert / Update if first column matches'],
					['id' => 'insert_ignore_id', 'label' => 'Insert / Ignore Record ID'],
			];
			$update_insert = common::inputGetCmd('update_insert', 'insert');

			echo HTMLHelper::_('select.genericlist', $options, $control_name, 'name="' . $control_name . '" class="' . $default_class . '"',
					'id', 'label', $update_insert);
			?>
		</div>
	</div>

	<div class="<?php echo $divClass; ?>">
		<table class="table" id="recordPreview">
			<thead>
			<?php foreach ($this->previewData['fields'] as $field): ?>

				<th class="nowrap">

					<?php
					$control_name = 'field_map_' . $index;

					echo HTMLHelper::_('select.genericlist', $fields, $control_name, 'name="' . $control_name . '" onchange="fieldChanged(' . $index . ')" class="' . $default_class . '"',
							'id', 'fieldname', $field); ?>
				</th>


				<?php
				$index += 1;
			endforeach; ?>

			</thead>
			<tbody>
			<?php

			$row = 1;
			foreach ($this->previewData['records'] as $i => $record): ?>

				<tr class="row<?php echo $i % 2; ?>">

					<?php
					$col = 0;
					foreach ($record as $value):

						if ($row < $start_from) {
							$class = 'field-disabled';
						} else {
							$fieldName = $this->previewData['fields'][$col];
							if (empty($fieldName) or $fieldName == '_ignore')
								$class = 'field-disabled';
							else
								$class = 'field-enabled';
						}

						?>
						<td class="nowrap <?php echo $class; ?>"
							data-column="<?php echo $col; ?>"><?php echo $value; ?></td>

						<?php
						$col += 1;
					endforeach; ?>
				</tr>
				<?php
				$row += 1;
			endforeach; ?>

			</tbody>
		</table>
	</div>

	<input type="hidden" name="tableid" value="<?php echo $this->tableId; ?>"/>
	<input type="hidden" name="fileid" value="<?php echo $this->fileId; ?>"/>
	<input type="hidden" name="task" value="importrecords.preview_import"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>