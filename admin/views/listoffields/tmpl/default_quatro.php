<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\String\PunycodeHelper;

use CustomTables\Integrity\IntegrityFields;

$loggeduser = Factory::getUser();

if ($this->saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_customtables&task=listoffields.saveOrderAjax&tableid='.$this->tableid.'&tmpl=component';
	JHtml::_('sortablelist.sortable', 'fieldsList', 'adminForm', strtolower($this->listDirn), $saveOrderingUrl);
}

$input	= JFactory::getApplication()->input;

if($input->getCmd('extratask','')=='updateimages')
{
	$path=JPATH_SITE . DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR
		.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'extratasks'.DIRECTORY_SEPARATOR;

	require_once($path.'extratasks.php');

	extraTasks::prepareJS();
}


?>
<script type="text/javascript">
	Joomla.orderTable = function()
	{
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $this->listOrder; ?>')
		{
			dirn = 'asc';
		}
		else
		{
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
	
</script>

<form action="<?php echo Route::_('index.php?option=com_customtables&view=listoffields&tableid='.$this->tableid); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?php
				// Search tools bar
				echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
				?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
				
				<?php
		if($this->tableid!=0)
		{
			$link=JURI::root().'administrator/index.php?option=com_customtables&view=listoffields&tableid='.$this->tableid;
			echo IntegrityFields::checkFields($this->tableid,$this->tablename,$this->tabletitle,$this->customtablename,$link);
		}
		?>
				
				
					<table class="table" id="userList">
						<!--<caption class="visually-hidden">
							<?php echo Text::_('COM_USERS_USERS_TABLE_CAPTION'); ?>,
							<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						-->
						<thead>
							<?php include('default_quatro_head.php');?>
						</thead>
						<tbody>
							<?php echo $this->loadTemplate('quatro_body');?>
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
								'title'  => Text::_('COM_CUSTOMTABLES_BATCH_OPTIONS'),
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
