<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>


<tr>
    <th style="width:20px;">
        <input type="checkbox" name="checkall-toggle" value="" title="Check All" onclick="Joomla.checkAll(this)"/>
    </th>
    <th class="title">
        <?php echo JHTML::_('grid.sort', 'Option Name', 'name', @$this->lists['order_Dir'], @$this->lists['order']); ?>
    </th>

    <?php

    foreach ($this->languages as $lang) {
        ?>

        <th class="title">
            <?php echo JHTML::_('grid.sort', 'Option Title (' . $lang->caption . ')', 'title_' . $lang->id, @$this->lists['order_Dir'], @$this->lists['order']); ?>
        </th>

        <?php
    }

    ?>

    <th width="8%" nowrap="nowrap">
        <?php echo JHTML::_('grid.sort', 'Is Selectable', 'm.isselectable', @$this->lists['order_Dir'], @$this->lists['order']); ?>

    </th>

    <th style="width:1%;" nowrap="nowrap">
        Family Tree
    </th>
</tr>
	
