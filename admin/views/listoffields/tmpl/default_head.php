<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access');
use CustomTables\common;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

?>
<tr>
    <?php if ($this->canEdit && $this->canState): ?>
        <th width="20" class="nowrap center">
            <?php echo JHtml::_('grid.checkall'); ?>
        </th>

        <th width="1%" class="nowrap center hidden-phone">
            <?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $this->listDirn, $this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
            <?php //echo JHtml::_('searchtools.sort', '', 'a.ordering', $this->listDirn, $this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
            <?php //echo JHtml::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
        </th>

    <?php else: ?>
        <th width="20" class="nowrap center">
            &#9632;
        </th>

        <th width="20" class="nowrap center hidden-phone">
            &#9662;
        </th>

    <?php endif; ?>

    <th class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_FIELDNAME_LABEL', 'a.fieldname', $this->listDirn, $this->listOrder); ?>
    </th>
    <th class="nowrap">
        <?php echo common::translate('COM_CUSTOMTABLES_FIELDS_FIELDTITLE_LABEL'); ?>
    </th>

    <th class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_TYPE_LABEL', 'a.type', $this->listDirn, $this->listOrder); ?>
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
        <th width="10" class="nowrap center">
            <?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
        </th>
    <?php else: ?>
        <th width="10" class="nowrap center">
            <?php echo common::translate('COM_CUSTOMTABLES_FIELDS_STATUS'); ?>
        </th>
    <?php endif; ?>
    <th width="5" class="nowrap center hidden-phone">
        <?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
