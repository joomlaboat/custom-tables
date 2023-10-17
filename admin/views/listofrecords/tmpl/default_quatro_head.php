<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.multiselect');

?>
<tr>
    <?php if ($this->canEdit && $this->canState): ?>
        <th width="20" class="nowrap center">
            <?php echo JHtml::_('grid.checkall'); ?>
        </th>
    <?php endif; ?>

    <?php if ($this->ordering_realfieldname != ''): ?>
        <th scope="col" class="w-1 text-center d-none d-md-table-cell">
            <?php echo HTMLHelper::_('searchtools.sort', '', 'custom', $this->listDirn, $this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
        </th>
    <?php endif; ?>

    <?php

    foreach ($this->ct->Table->fields as $field) {
        if ($field['type'] != 'dummy' and $field['type'] != 'log' and $field['type'] != 'ordering') {
            $id = 'fieldtitle';
            $title = $field[$id];

            if ($this->ct->Languages->Postfix != '')
                $id .= '_' . $this->ct->Languages->Postfix;

            if (isset($field[$id]))
                $title = $field[$id];

            echo '
				<th scope="col">' . $title . '</th>
	';
        }
    }

    ?>

    <?php if ($this->ct->Table->published_field_found): ?>
        <th class="nowrap hidden-phone center" style="text-align:center;">
            <?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_RECORDS_STATUS', 'published', $this->listDirn, $this->listOrder); ?>
        </th>
    <?php endif; ?>

    <th width="5" class="nowrap center hidden-phone">
        <?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_RECORDS_ID', 'id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
