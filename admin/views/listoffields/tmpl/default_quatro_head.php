<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.multiselect');

?>
<tr>

    <?php if ($this->canState && $this->canDelete): ?>
        <th class="w-1 text-center">
            <?php echo HTMLHelper::_('grid.checkall'); ?>
        </th>
    <?php endif; ?>

    <?php if ($this->canEdit): ?>
        <th scope="col" class="w-1 text-center d-none d-md-table-cell">
            <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $this->listDirn, $this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
        </th>
    <?php endif; ?>


    <th scope="col">
        <?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_FIELDS_FIELDNAME_LABEL', 'a.fieldname', $this->listDirn, $this->listOrder); ?>
    </th>

    <th scope="col">
        <?php echo Text::_('COM_CUSTOMTABLES_FIELDS_FIELDTITLE_LABEL'); ?>
    </th>

    <th scope="col">
        <?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_FIELDS_TYPE_LABEL', 'a.type', $this->listDirn, $this->listOrder); ?>
    </th>
    <th scope="col">
        <?php echo Text::_('COM_CUSTOMTABLES_FIELDS_TYPEPARAMS_LABEL'); ?>
    </th>

    <th scope="col">
        <?php echo Text::_('COM_CUSTOMTABLES_FIELDS_ISREQUIRED_LABEL'); ?>
    </th>

    <th scope="col">
        <?php echo Text::_('COM_CUSTOMTABLES_FIELDS_TABLEID_LABEL'); ?>
    </th>

    <th scope="col" class="text-center d-none d-md-table-cell">
        <?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
    </th>

    <th scope="col" class="w-12 d-none d-xl-table-cell">
        <?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
