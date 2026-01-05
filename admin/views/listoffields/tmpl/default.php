<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\Integrity\IntegrityFields;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('dropdown.init');

if ($this->saveOrder && !empty($this->items)) {
	$saveOrderingUrl = 'index.php?option=com_customtables&task=listoffields.saveOrderAjax&tableid=' . $this->ct->Table->tableid . '&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'fieldList', 'adminForm', strtolower($this->listDirn), $saveOrderingUrl);
}

try {
	if (common::inputGetCmd('extratask', '') == 'updateimages') {
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'extratasks' . DIRECTORY_SEPARATOR . 'extratasks.php');
		extraTasks::prepareJS($this->ct->Table);
	}
} catch (Exception $e) {
	common::enqueueMessage($e->getMessage());
}

?>

<form action="<?php echo Route::_('index.php?option=com_customtables&view=listoffields&tableid=' . $this->ct->Table->tableid); ?>"
	  method="post" name="adminForm" id="adminForm">
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
			<?php else: ?>

				<?php
				if ($this->ct->Table->tableid != 0) {
					$link = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $this->ct->Table->tableid;
					try {
						echo IntegrityFields::checkFields($this->ct, $link);
					} catch (Exception $e) {
						common::enqueueMessage($e->getMessage());
					}
				}
				//table-bordered
				?>
				<table class="table table-striped table-hover" id="fieldList" style="position: relative;">
					<thead><?php echo $this->loadTemplate('head'); ?></thead>
					<tbody><?php echo $this->loadTemplate('body'); ?></tbody>
					<tfoot><?php echo $this->loadTemplate('foot'); ?></tfoot>
				</table>
			<?php endif; ?>
		</div>

		<input type="hidden" name="filter_order" value=""/>
		<input type="hidden" name="filter_order_Dir" value=""/>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
</form>