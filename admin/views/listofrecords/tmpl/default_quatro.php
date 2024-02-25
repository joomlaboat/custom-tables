<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

//HTMLHelper::_('behavior.multiselect');
$saveOrderingUrl = null;

if ($this->saveOrder and $this->ordering_realfieldname != '') {
	$saveOrderingUrl = 'index.php?option=com_customtables&task=listofrecords.ordering&tableid=' . $this->ct->Table->tableid . '&tmpl=component';
	HTMLHelper::_('draggablelist.draggable');
}

?>
<form action="<?php echo Route::_('index.php?option=com_customtables&view=listofrecords&tableid=' . $this->ct->Table->tableid); ?>"
      method="post" name="adminForm" id="adminForm">

    <script>

        function atLeastOnCheckBoxSelected() {

            let cidObject = document.getElementsByName('cid[]');

            for (let i = 0; i < cidObject.length; i++) {
                if (cidObject[i].checked) {
                    return true;
                }
            }
            return false;
        }

        Joomla.submitbutton = function (task) {

            if (task == 'listofrecords.exportcsv') {

                if (atLeastOnCheckBoxSelected()) {
                    Joomla.submitform(task);
                } else {
                    if (confirm(common::translate(('Do you want to export all records?')))
                        Joomla.submitform(task);
                    else
                        return false;
                }
            }

            Joomla.submitform(task);
            return true;
        }
    </script>

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
                    <table class="table" id="userList">
                        <caption class="visually-hidden">
							<?php echo common::translate('COM_USERS_USERS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo common::translate('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo common::translate('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
						<?php include('default_quatro_head.php'); ?>
                        </thead>
                        <tbody<?php if ($this->saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($this->listDirn); ?>" data-nested="true"<?php endif; ?>>
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
