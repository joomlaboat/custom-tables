<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die();

HTMLHelper::_('behavior.multiselect');

?>

<form action="<?php echo Route::_('index.php?option=com_customtables&view=listofcategories'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif; ?>

			<?php echo common::translate("COM_CUSTOMTABLES_CATEGORIES_DESCRIPTION"); ?>

			<?php if (!$this->ct->Env->advancedTagProcessor): ?>
				<p><?php echo common::translate('COM_CUSTOMTABLES_AVAILABLE'); ?></p><?php endif; ?>

			<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
			<div class="clearfix"></div>

			<?php if (empty($this->items)): ?>
				<div class="alert alert-no-items">
					<?php echo common::translate('JGLOBAL_NO_MATCHING_RESULTS'); ?>
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
		<?php echo HTMLHelper::_('form.token'); ?>
</form>
