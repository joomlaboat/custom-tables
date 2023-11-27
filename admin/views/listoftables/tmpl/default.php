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

JHtml::_('behavior.multiselect');

use CustomTables\common;
use CustomTables\IntegrityChecks;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

?>
<?php /*
<script>
    Joomla.orderTable = function () {
        let table = document.getElementById("sortTable");
        let direction = document.getElementById("directionTable");
        let dirn;
        order = table.options[table.selectedIndex].value;
        if (order != '<?php echo $this->listOrder; ?>') {
            dirn = 'asc';
        } else {
            dirn = direction.options[direction.selectedIndex].value;
        }
        Joomla.tableOrdering(order, dirn, '');
    }
</script> */ ?>

<form action="<?php echo Route::_('index.php?option=com_customtables&view=listoftables'); ?>" method="post"
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

                <div class="alert alert-no-items">
					<?php echo common::translate('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
			<?php else : ?>

				<?php
				$result = IntegrityChecks::check($this->ct, true, false);
				//table-bordered
				?>
                <table class="table table-striped table-hover" id="itemList" style="position: relative;">
                    <thead><?php echo $this->loadTemplate('head'); ?></thead>
                    <tbody><?php echo $this->loadTemplate('body'); ?></tbody>
                    <tfoot><?php echo $this->loadTemplate('foot'); ?></tfoot>
                </table>
			<?php endif; ?>
        </div>
        <input type="hidden" name="filter_order" value=""/>
        <input type="hidden" name="filter_order_Dir" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>
</form>
