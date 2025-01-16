<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access');
use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

?>
<tr>
	<th style="width:20px;" class="nowrap center">
		<?php echo HTMLHelper::_('grid.checkall'); ?>
	</th>

	<?php if ($this->ordering_realfieldname != ''): ?>

		<th style="width:1%;" class="nowrap center hidden-phone">
			<i class="icon-menu-2"></i>
		</th>

	<?php endif; ?>

	<?php

	foreach ($this->ct->Table->fields as $field) {
		if ($field['type'] != 'dummy' and $field['type'] != 'log' and $field['type'] != 'ordering') {
			$id = 'fieldtitle';
			$title = $field[$id];

			if ($this->ct->Languages->Postfix != '')
				$id .= $this->ct->Languages->Postfix;

			if (isset($field[$id]))
				$title = $field[$id];

			echo '<th class="nowrap" >' . $title . '</th>';
		}
	}

	?>

	<?php if ($this->ct->Table->published_field_found): ?>
		<th class="nowrap hidden-phone center">
			<?php echo common::translate('COM_CUSTOMTABLES_RECORDS_STATUS'); ?>
		</th>
	<?php endif; ?>

	<th style="width:5px;" class="nowrap center hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_RECORDS_ID'); ?>
	</th>

</tr>
