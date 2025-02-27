<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use CustomTables\IntegrityChecks;

HTMLHelper::_('behavior.multiselect');

?>
<form action="<?php echo Route::_('index.php?option=com_customtables&view=listoftables'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?php
				// Search tools bar
				echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
				?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span
								class="visually-hidden"><?php echo common::translate('INFO'); ?></span>
						<?php echo common::translate('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>

					<?php

					try {
						$result = IntegrityChecks::check($this->ct, true, false);
					} catch (Exception $e) {
						$result = [$e->getMessage()];
					}

					if (count($result) > 0)
						echo '<ol><li>' . implode('</li><li>', $result) . '</li></ol>';

					//Joomla strips html links unfortunately, so we cannot use the enqueueMessage function
					//Factory::getApplication()->enqueueMessage('<ol><li>'.implode('</li><li>',$result).'</li></ol>','notice');
					?>

					<table class="table" id="userList">
						<caption class="visually-hidden">
							<?php echo common::translate('COM_USERS_USERS_TABLE_CAPTION'); ?>,
							<span id="orderedBy"><?php echo common::translate('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo common::translate('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<?php include('default_quatro_head.php'); ?>
						</thead>
						<tbody>
						<?php echo $this->loadTemplate('quatro_body'); ?>
						</tbody>
					</table>

					<?php // load the pagination. ?>
					<?php echo $this->pagination->getListFooter(); ?>

					<?php // Load the batch processing form if user is allowed ?>
					<?php /* if ($loggeduser->authorise('core.create', 'com_customtables','categories')
						&& $loggeduser->authorise('core.edit', 'com_customtables','categories')
						&& $loggeduser->authorise('core.edit.state', 'com_customtables','categories')) : ?>
						<?php echo HTMLHelper::_(
							'bootstrap.renderModal',
							'collapseModal',
							array(
								'title'  => common::translate('COM_CUSTOMTABLES_BATCH_OPTIONS'),
								'footer' => $this->loadTemplate('batch_footer'),
							),
							$this->loadTemplate('batch_body')
						); ?>
					<?php endif; */ ?>
				<?php endif; ?>

				<input type="hidden" name="task" value="">
				<input type="hidden" name="boxchecked" value="0">
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>

<?php

/*

<script>
    let custom_tables_category = parseInt("<?php echo $this->activeFilters['tablecategory']; ?>");

    if (custom_tables_category !== 0) {
        let obj = document.getElementById("filter_tablecategory");
        obj.value = custom_tables_category + '';
    }
</script>
*/
?>
