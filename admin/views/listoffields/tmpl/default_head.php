<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access
use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

?>
<tr>
	<?php if ($this->canEdit && $this->canState): ?>
		<th style="width:20px;" class="nowrap center">
			<?php echo HTMLHelper::_('grid.checkall'); ?>
		</th>

		<th style="width:1%" class="nowrap center hidden-phone">
			<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $this->listDirn, $this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
			<?php //echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $this->listDirn, $this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
			<?php //echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
		</th>

	<?php else: ?>
		<th style="width:20px;" class="nowrap center">
			&#9632;
		</th>

		<th style="width:20px;" class="nowrap center hidden-phone">
			&#9662;
		</th>

	<?php endif; ?>

	<th class="nowrap hidden-phone">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_FIELDNAME_LABEL', 'a.fieldname', $this->listDirn, $this->listOrder); ?>
	</th>
	<th class="nowrap">
		<?php echo common::translate('COM_CUSTOMTABLES_FIELDS_FIELDTITLE_LABEL'); ?>
	</th>

	<th class="nowrap hidden-phone">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_TYPE_LABEL', 'a.type', $this->listDirn, $this->listOrder); ?>
	</th>
	<th class="nowrap hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_FIELDS_TYPEPARAMS_LABEL'); ?>
	</th>
	<th class="nowrap hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_FIELDS_ISREQUIRED_LABEL'); ?>
	</th>
	<th class="nowrap hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_FIELDS_TABLEID_LABEL'); ?>
	</th>
	<?php if ($this->canState): ?>
		<th style="width:10px;" class="nowrap center">
			<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
		</th>
	<?php else: ?>
		<th style="width:10px;" class="nowrap center">
			<?php echo common::translate('COM_CUSTOMTABLES_FIELDS_STATUS'); ?>
		</th>
	<?php endif; ?>
	<th style="width:5px;" class="nowrap center hidden-phone">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
	</th>
</tr>
