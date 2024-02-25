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
use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

?>
<tr>
	<?php if ($this->canEdit && $this->canState): ?>
        <th style="width:20px;" class="nowrap center">
			<?php echo HtmlHelper::_('grid.checkall'); ?>
        </th>
	<?php endif; ?>

    <th class="nowrap hidden-phone">
		<?php echo HtmlHelper::_('grid.sort', 'COM_CUSTOMTABLES_TABLES_TABLENAME_LABEL', 'a.tablename', $this->listDirn, $this->listOrder); ?>
    </th>

    <th class="nowrap">
		<?php echo common::translate('COM_CUSTOMTABLES_TABLES_TABLETITLE_LABEL'); ?>
    </th>

    <th class="nowrap hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL'); ?>
    </th>
    <th class="nowrap hidden-phone">
		<?php echo common::translate('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL'); ?>
    </th>

    <th class="nowrap hidden-phone">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_TABLES_TABLECATEGORY_LABEL', 'a.tablecategory', $this->listDirn, $this->listOrder); ?>
    </th>

    <th style="width:10px;" class="nowrap center">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_TABLES_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
    </th>

    <th style="width:5px;" class="nowrap center hidden-phone">
		<?php echo HTMLHelper::_('grid.sort', 'COM_CUSTOMTABLES_TABLES_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
