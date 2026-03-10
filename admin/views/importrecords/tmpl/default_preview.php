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
$fields[] = array('id' => "", 'fieldname' => ' - ' . Text::_('COM_CUSTOMTABLES_SELECT'));
foreach ($this->ct->Table->fields as $field) {
	$fields[] = array('id' => $field['fieldname'], 'fieldname' => $field['fieldtitle']);
}
$fields[] = array('id' => '#', 'fieldname' => ' - ' . Text::_('COM_CUSTOMTABLES_RECORDS_ID'));

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
		let control_name = 'field_map_' + index;
		let obj = document.getElementById(control_name);
		if (obj) {
			if (obj.value !== "") {
				document.querySelectorAll('td[data-column="' + index + '"]').forEach(function (el) {
					el.classList.replace('field-disabled', 'field-enabled');
				});
			} else {
				document.querySelectorAll('td[data-column="' + index + '"]').forEach(function (el) {
					el.classList.replace('field-enabled', 'field-disabled');
				});
			}
		}
	}
</script>
<?php $divClass = count($this->previewData['records']) >= 20 ? "table-fade" : ""; ?>
<div class="<?php echo $divClass; ?>">
	<table class="table" id="recordPreview">
		<thead>
		<?php foreach ($this->previewData['fields'] as $field): ?>

			<th class="nowrap">

				<?php
				$control_name = 'field_map_' . $index;


				echo HTMLHelper::_('select.genericlist', $fields, $control_name, 'onchange="fieldChanged(' . $index . ')" class="' . $default_class . '"',
						'id', 'fieldname', $field); ?>
			</th>


			<?php
			$index += 1;
		endforeach; ?>

		</thead>
		<tbody>
		<?php

		foreach ($this->previewData['records'] as $i => $record): ?>

			<tr class="row<?php echo $i % 2; ?>">

				<?php
				$col = 0;
				foreach ($record as $value):

					$fileName = $this->previewData['fields'][$col];
					if (empty($fileName))
						$class = 'field-disabled';
					else
						$class = 'field-enabled';

					?>
					<td class="nowrap <?php echo $class; ?>"
						data-column="<?php echo $col; ?>"><?php echo $value; ?></td>

					<?php
					$col += 1;
				endforeach; ?>
			</tr>
		<?php endforeach; ?>

		</tbody>
	</table>
</div>
