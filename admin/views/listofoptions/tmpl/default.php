<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access

defined('_JEXEC') or die('Restricted access');

echo '<div id="j-sidebar-container" class="span2">';
echo $this->sidebar;
echo '</div>';

$input	= JFactory::getApplication()->input;
?>

<form action="<?php echo JRoute::_('index.php?option=com_customtables'); ?>" method="post" name="adminForm" id="adminForm">
<?php
	$s = $input->getString( 'search');
?>
	<div id="j-main-container" >
		<div id="filter-bar" class="btn-toolbar">

			<div class="filter-search btn-group pull-left">
				<label for="search" class="element-invisible">Search title.</label>
				<input type="text" name="search" placeholder="Search title." id="search" value="<?php echo $s; ?>" title="Search title." />
			</div>
			<div class="btn-group pull-left hidden-phone">
				<button class="btn tip hasTooltip" type="submit" title="Search"><i class="icon-search"></i></button>
				<button class="btn tip hasTooltip" type="button" onclick="document.id('search').value='';this.form.submit();" title="Clear"><i class="icon-remove"></i></button>
			</div>
		</div>
	
		<table class="table table-striped" id="optionsList">
            <thead><?php echo $this->loadTemplate('head');?></thead>
				<?php //<tfoot>echo $this->loadTemplate('foot');</tfoot>?>
            <tbody><?php echo $this->loadTemplate('body');?></tbody>
		</table>
	</div>

	<input type="hidden" name="option" value="com_customtables" />
	<input type="hidden" name="view" value="listofoptions" />
	<input type="hidden" name="task" value="view" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
</div>
<p><a href="index.php?option=com_customtables&view=listofoptions&task=RefreshFamily">Refresh Family Tree</a></p>
