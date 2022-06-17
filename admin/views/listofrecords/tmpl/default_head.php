<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access');
use Joomla\CMS\Language\Text;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

?>
<tr>
    <th width="20" class="nowrap center">
        <?php echo JHtml::_('grid.checkall'); ?>
    </th>

    <?php if ($this->ordering_realfieldname != ''): ?>

        <th width="1%" class="nowrap center hidden-phone">
            <i class="icon-menu-2"></i>
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
						<th class="nowrap" >' . $title . '</th>
					';
        }
    }

    ?>

    <?php if ($this->ct->Table->published_field_found): ?>
        <th class="nowrap hidden-phone center">
            <?php echo Text::_('COM_CUSTOMTABLES_RECORDS_STATUS'); ?>
        </th>
    <?php endif; ?>

    <th width="5" class="nowrap center hidden-phone">
        <?php echo Text::_('COM_CUSTOMTABLES_RECORDS_ID'); ?>
    </th>

</tr>
