<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.multiselect');

?>
<tr>
	<?php if ($this->canState or $this->canDelete): ?>
		<th class="w-1 text-center">
			<?php echo HTMLHelper::_('grid.checkall'); ?>
		</th>
	<?php endif; ?>

	<th scope="col">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_CATEGORIES_CATEGORYNAME_LABEL', 'a.categoryname', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col" class="w-12 d-none d-xl-table-cell text-center">
		<?php echo common::translate('COM_CUSTOMTABLES_TABLES'); ?>
	</th>

	<th scope="col" class="w-12 d-none d-xl-table-cell text-center">
		<?php echo common::translate('COM_CUSTOMTABLES_MENUS'); ?>
	</th>

	<th scope="col" class="text-center d-none d-md-table-cell">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_CATEGORIES_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col" class="w-12 d-none d-xl-table-cell">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_CATEGORIES_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
	</th>
</tr>
