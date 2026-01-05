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
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.multiselect');

?>
<tr>
	<?php if ($this->canEdit && $this->canState): ?>
		<th style="width:20px;" class="nowrap center">
			<?php echo HTMLHelper::_('grid.checkall'); ?>
		</th>
	<?php endif; ?>

	<th scope="col">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_LAYOUTS_LAYOUTNAME_LABEL', 'a.layoutname', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_LAYOUTTYPE_LABEL'); ?>
	</th>

	<th scope="col" class="text-left d-none d-md-table-cell">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_LAYOUTS_TABLEID_LABEL', 't.tablename', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col" class="text-center d-none d-md-table-cell">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_LAYOUTS_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col" class="w-12 d-none d-xl-table-cell">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_LAYOUTS_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_SIZE'); ?>
	</th>

	<th scope="col">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_MODIFIEDBY'); ?>
	</th>

	<th scope="col">
		<?php echo common::translate('COM_CUSTOMTABLES_LAYOUTS_MODIFIED'); ?>
	</th>

	<th scope="col">
		Template engine
	</th>
</tr>
