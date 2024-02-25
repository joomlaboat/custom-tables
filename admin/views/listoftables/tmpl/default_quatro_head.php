<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC')) die('Restricted access');

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.multiselect');

?>
<tr>
	<?php if ($this->canEdit && $this->canState): ?>
        <th style="width:20px;" class="nowrap center">
			<?php echo HtmlHelper::_('grid.checkall'); ?>
			<?php /* <input type="checkbox" id="check-all" class="form-check-input" onclick="Joomla.isChecked(this.checked);"/> */ ?>
        </th>
	<?php endif; ?>

    <th scope="col">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_TABLENAME_LABEL', 'a.tablename', $this->listDirn, $this->listOrder);
		//a.tablename but not tablename is important to make the sort by box have the same selection as pressed on table head field name
		?>
    </th>

    <th scope="col">
		<?php echo common::translate('COM_CUSTOMTABLES_TABLES_TABLETITLE_LABEL'); ?>
    </th>

    <th scope="col" class="text-center">
		<?php echo common::translate('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL'); ?>
    </th>
    <th scope="col" class="text-center">
		<?php echo common::translate('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL'); ?>
    </th>

    <th scope="col">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_TABLECATEGORY_LABEL', 'a.tablecategory', $this->listDirn, $this->listOrder); ?>
    </th>

    <th scope="col" class="text-center d-none d-md-table-cell">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
    </th>

    <th scope="col" class="w-12 d-none d-xl-table-cell">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
