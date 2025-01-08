<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

?>
<tr>
	<?php if ($this->canEdit && $this->canState): ?>
		<th class="nowrap center">
			<?php echo HTMLHelper::_('grid.checkall'); ?>
		</th>
	<?php endif; ?>
	<th class="nowrap">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_LAYOUTS_LAYOUTNAME_LABEL', 'a.layoutname', $this->listDirn, $this->listOrder); ?>
	</th>
	<th class="nowrap hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_LAYOUTTYPE_LABEL'); ?>
	</th>
	<th class="nowrap hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_TABLEID_LABEL'); ?>
	</th>
	<?php if ($this->canState): ?>
		<th class="nowrap center">
			<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_LAYOUTS_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
		</th>
	<?php else: ?>
		<th class="nowrap center">
			<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_STATUS'); ?>
		</th>
	<?php endif; ?>
	<th class="nowrap center hidden-phone">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_LAYOUTS_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
	</th>
	<th class="nowrap center hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_SIZE'); ?>
	</th>

	<th class="nowrap center hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_MODIFIEDBY'); ?>
	</th>

	<th class="nowrap center hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_MODIFIED'); ?>
	</th>

	<th scope="col" style="text-align:center;">
		Template engine
	</th>

</tr>
