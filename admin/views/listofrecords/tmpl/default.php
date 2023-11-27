<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
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

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

if ($this->ordering_realfieldname != '') {
	$saveOrderingUrl = 'index.php?option=com_customtables&task=listofrecords.ordering&tableid=' . $this->ct->Table->tableid . '&tmpl=component';
	JHtml::_('sortablelist.sortable', 'recordsList', 'adminForm', 'asc', $saveOrderingUrl);
}

?>

<script type="text/javascript">
    Joomla.orderTable = function () {
        let table = document.getElementById("sortTable");
        let directionObject = document.getElementById("directionTable");
        let direction;
        let order = table.options[table.selectedIndex].value;
        if (order != '<?php echo $this->listOrder; ?>') {
            direction = 'asc';
        } else {
            direction = directionObject.options[directionObject.selectedIndex].value;
        }
        Joomla.tableOrdering(order, direction, '');
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_customtables&view=listofrecords'); ?>" method="post"
      name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
    <div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
		<?php else : ?>
        <div id="j-main-container">
			<?php endif; ?>

			<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
            <div class="clearfix"></div>

			<?php if (empty($this->items)): ?>
				<?php //echo $this->loadTemplate('toolbar');?>
                <div class="alert alert-no-items">
					<?php echo common::translate('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
			<?php else : ?>

                <table class="table table-striped table-hover" id="itemList" style="position: relative;">
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
                    'title' => common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_BATCH_OPTIONS'),
                    'footer' => $this->loadTemplate('batch_footer')
                ),
                $this->loadTemplate('batch_body')
            ); ?>
        <?php endif; */ ?>


                <input type="hidden" name="filter_order" value=""/>
                <input type="hidden" name="filter_order_Dir" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>


			<?php endif; ?>
        </div>

        <input type="hidden" name="option" value="com_customtables"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="tableid" value="<?php echo $this->ct->Table->tableid; ?>"/>

		<?php echo HTMLHelper::_('form.token'); ?>
</form>
