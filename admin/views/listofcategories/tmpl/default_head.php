<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access');
use Joomla\CMS\Language\Text;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

?>
<tr>
    <?php if ($this->canEdit && $this->canState): ?>
        <th width="20" class="nowrap center">
            <?php echo JHtml::_('grid.checkall'); ?>
        </th>
    <?php else: ?>
        <th width="20" class="nowrap center hidden-phone">
            &#9662;
        </th>
        <th width="20" class="nowrap center">
            &#9632;
        </th>
    <?php endif; ?>
    <th class="nowrap">
        <?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_CATEGORIES_CATEGORYNAME_LABEL', 'a.categoryname', $this->listDirn, $this->listOrder); ?>
    </th>
    <?php if ($this->canState): ?>
        <th width="10" class="nowrap center">
            <?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_CATEGORIES_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
        </th>
    <?php else: ?>
        <th width="10" class="nowrap center">
            <?php echo Text::_('COM_CUSTOMTABLES_CATEGORIES_STATUS'); ?>
        </th>
    <?php endif; ?>
    <th width="5" class="nowrap center hidden-phone">
        <?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_CATEGORIES_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
