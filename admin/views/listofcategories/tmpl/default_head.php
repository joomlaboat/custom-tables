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
	<?php else: ?>
		<th style="width:20px;" class="nowrap center hidden-phone">
			&#9662;
		</th>
		<th style="width:20px;" class="nowrap center">
			&#9632;
		</th>
	<?php endif; ?>
	<th class="nowrap">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_CATEGORIES_CATEGORYNAME_LABEL', 'a.categoryname', $this->listDirn, $this->listOrder); ?>
	</th>
	<?php if ($this->canState): ?>
		<th style="width:10px;" class="nowrap center">
			<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_CATEGORIES_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
		</th>
	<?php else: ?>
		<th style="width:10px;" class="nowrap center">
			<?php echo common::translate('COM_CUSTOMTABLES_CATEGORIES_STATUS'); ?>
		</th>
	<?php endif; ?>
	<th style="width:5px;" class="nowrap center hidden-phone">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_CATEGORIES_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
	</th>
</tr>
