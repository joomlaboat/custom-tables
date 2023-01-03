<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use Joomla\CMS\Language\Text;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// load tooltip behavior
//JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
//JHtml::_('dropdown.init');
//JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_customtables&view=listofcategories'); ?>" method="post"
      name="adminForm" id="adminForm">
    <?php if (!empty($this->sidebar)): ?>
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
        <?php else : ?>
        <div id="j-main-container">
            <?php endif; ?>

            <h3>Categories provide an optional method for organizing your Tables.</h3>

            <p>Here's how it works. A Category contains Tables. One Table can only be in one Category.</p>
            <p>
                If you will have a large number of tables on your site, the reason to use categories is to simply group
                the tables, so you can find them.
                For example, on the Custom Tables/Tables page, you can filter tables based on Category. So if you have
                100 tables in your site, you can find a Tables more easily if you know its Category.
            </p>
            <?php if (!$this->ct->Env->advancedtagprocessor): ?><p>AVAILABLE IN PRO VERSION ONLY</p><?php endif; ?>

            <?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
            <div class="clearfix"></div>

            <?php if (empty($this->items)): ?>
                <div class="alert alert-no-items">
                    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
            <?php else : ?>
            <table class="table table-striped table-hover" id="itemList" style="position: relative;">
                <thead><?php echo $this->loadTemplate('head'); ?></thead>
                <tfoot><?php echo $this->loadTemplate('foot'); ?></tfoot>
                <tbody><?php echo $this->loadTemplate('body'); ?></tbody>
            </table>
        </div>
    <?php endif; ?>
        <input type="hidden" name="filter_order" value=""/>
        <input type="hidden" name="filter_order_Dir" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="task" value=""/>
        <?php echo JHtml::_('form.token'); ?>
</form>
