<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

// load tooltip behavior
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

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
                100 tables in your site, you can find a Tables more easily if you know its Category.</p>
            </p>
            <?php if (!$this->ct->Env->advancedtagprocessor): ?><p>AVAILABE IN PRO VERSION ONLY</p><?php endif; ?>

            <?php if (empty($this->items)): ?>
                <?php echo $this->loadTemplate('toolbar'); ?>
                <div class="alert alert-no-items">
                    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
            <?php else : ?>
            <?php echo $this->loadTemplate('toolbar'); ?>

            <table class="table table-striped" id="categoriesList">
                <thead><?php echo $this->loadTemplate('head'); ?></thead>
                <tfoot><?php echo $this->loadTemplate('foot'); ?></tfoot>
                <tbody><?php echo $this->loadTemplate('body'); ?></tbody>
            </table>
            <?php //Load the batch processing form. ?>
            <?php /* if ($this->canCreate && $this->canEdit) : ?>
            <?php echo JHtml::_(
                'bootstrap.renderModal',
                'collapseModal',
                array(
                    'title' => Text::_('COM_CUSTOMTABLES_LISTOFCATEGORIES_BATCH_OPTIONS'),
                    'footer' => $this->loadTemplate('batch_footer')
                ),
                $this->loadTemplate('batch_body')
            ); ?>
        <?php endif; */ ?>
            <input type="hidden" name="filter_order" value=""/>
            <input type="hidden" name="filter_order_Dir" value=""/>
            <input type="hidden" name="boxchecked" value="0"/>
        </div>
    <?php endif; ?>
        <input type="hidden" name="task" value=""/>
        <?php echo JHtml::_('form.token'); ?>
</form>
