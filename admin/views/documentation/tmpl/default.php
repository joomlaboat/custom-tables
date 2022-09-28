<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

?>

<?php if ($this->version < 4): ?>
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>
<?php endif; ?>

<div id="j-main-container" class="ct_doc">

    <?php if ($this->version >= 4):

        echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'fieldtypes', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'fieldtypes', CustomTables\common::translate('COM_CUSTOMTABLES_TABLEFIELDTYPES')); ?>
        <?php if ($this->documentation->internal_use): ?>
        <h3><?php echo CustomTables\common::translate('COM_CUSTOMTABLES_TABLEFIELDTYPES'); ?></h3>
    <?php endif; ?>

        <?php echo CustomTables\common::translate('COM_CUSTOMTABLES_TABLEFIELDTYPES_DESC'); ?>
        <?php echo $this->documentation->getFieldTypes(); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>


        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'layouttags', CustomTables\common::translate('COM_CUSTOMTABLES_LAYOUTTAGS')); ?>
        <?php if ($this->documentation->internal_use): ?>
        <h3><?php echo CustomTables\common::translate('COM_CUSTOMTABLES_LAYOUTTAGS'); ?></h3><br/>
    <?php endif; ?>
        <?php echo $this->documentation->getLayoutTags(); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>


        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'menuitems', CustomTables\common::translate('COM_CUSTOMTABLES_LAYOUTS_MENUS')); ?>

        <?php if ($this->documentation->internal_use) {
        echo '<h3>' . CustomTables\common::translate('COM_CUSTOMTABLES_LAYOUTS_MENUS') . '</h3>';
    } else {
        echo '
        # Add/Edit Record<br/>![Menu item - Records](https://joomlaboat.com/images/components/ct/menu-items/edit.png)<br/><br/>
# Record Details<br/>![Menu item - Records](https://joomlaboat.com/images/components/ct/menu-items/details.png)<br/><br/>
# Records<br/>![Menu item - Records](https://joomlaboat.com/images/components/ct/menu-items/records.png)<br/><br/>
        # ' . CustomTables\common::translate('COM_CUSTOMTABLES_LAYOUTS_MENUS') . '<br/><br/>';
    }
        ?>

        <?php echo CustomTables\common::translate('Complete list of Menu Item parameters, not all of them used in Add/Edit menu item type or Record Details. '); ?>
        <br/>

        <?php
        if ($this->documentation->internal_use) {
            echo '<img alt="Menu items - Parameters" src="https://joomlaboat.com/images/components/ct/menu-items/menu-items.png" /><br/><br/>';
        } else {
            echo '![Menu items - Parameters](https://joomlaboat.com/images/components/ct/menu-items/menu-items.png)<br/><br/>';
        }

        ?>
        <?php echo $this->documentation->getMenuItems(); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>


        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'more_about', CustomTables\common::translate('COM_CUSTOMTABLES_MOREABOUT')); ?>
        <?php echo CustomTables\common::translate('COM_CUSTOMTABLES_SUPPORT'); ?>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet');
    else: ?>

        <ul class="nav nav-tabs">
            <li class="active"><a href="#fieldtypes"
                                  data-toggle="tab"><?php echo CustomTables\common::translate('COM_CUSTOMTABLES_TABLEFIELDTYPES'); ?></a>
            </li>
            <li><a href="#layouttags"
                   data-toggle="tab"><?php echo CustomTables\common::translate('COM_CUSTOMTABLES_LAYOUTTAGS'); ?></a>
            </li>
            <li><a href="#more_about" target="_blank" data-toggle="tab">
                    <?php echo CustomTables\common::translate('COM_CUSTOMTABLES_MOREABOUT'); ?></a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="fieldtypes">

                <?php if ($this->documentation->internal_use): ?>
                    <h3><?php echo CustomTables\common::translate('COM_CUSTOMTABLES_TABLEFIELDTYPES'); ?></h3>
                <?php endif; ?>

                <?php echo CustomTables\common::translate('COM_CUSTOMTABLES_TABLEFIELDTYPES_DESC'); ?>

                <?php echo $this->documentation->getFieldTypes(); ?></div>

            <div class="tab-pane" id="layouttags">
                <?php if ($this->documentation->internal_use): ?>
                    <h3><?php echo CustomTables\common::translate('COM_CUSTOMTABLES_LAYOUTTAGS'); ?></h3>
                <?php endif; ?>

                <?php echo $this->documentation->getLayoutTags(); ?></div>

            <div class="tab-pane" id="more_about">
                <?php echo CustomTables\common::translate('COM_CUSTOMTABLES_SUPPORT'); ?>
            </div>

        </div>

    <?php endif; ?>

</div>
   
    
