<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com 
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			1st July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		default.php
	@author			Ivan Komlev <https://joomlaboat.com>	
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');




// load tooltip behavior
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

//if ($this->saveOrder)
//{
	//$saveOrderingUrl = 'index.php?option=com_customtables&task=listofrecords.saveOrderAjax&tableid='.$this->tableid.'&tmpl=component';
	//JHtml::_('sortablelist.sortable', 'recordsList', 'adminForm', strtolower($this->listDirn), $saveOrderingUrl);
//}

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
<?php

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'_checktable.php');

?>
<form action="<?php echo JRoute::_('index.php?option=com_customtables&view=listofrecords&tableid='.$this->tableid); ?>" method="post" name="adminForm" id="adminForm">
<?php if(!empty( $this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif; ?>
<?php if (empty($this->items)): ?>
	<?php //echo $this->loadTemplate('toolbar');?>
    <div class="alert alert-no-items">
        <?php //echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
    </div>
<?php else : ?>
		<?php //echo $this->loadTemplate('toolbar');?>
		
		<table class="table table-striped" id="recordsList">
			<thead><?php echo $this->loadTemplate('head');?></thead>
			<tfoot><?php //echo $this->loadTemplate('foot');?></tfoot>
			<tbody><?php echo $this->loadTemplate('body');?></tbody>
		</table>
		<?php //Load the batch processing form. ?>
        <?php /* if ($this->canCreate && $this->canEdit) : ?>
            <?php echo JHtml::_(
                'bootstrap.renderModal',
                'collapseModal',
                array(
                    'title' => JText::_('COM_CUSTOMTABLES_LISTOFRECORDS_BATCH_OPTIONS'),
                    'footer' => $this->loadTemplate('batch_footer')
                ),
                $this->loadTemplate('batch_body')
            ); ?>
        <?php endif; */ ?>
		
		
		<input type="hidden" name="filter_order" value="" />
		<input type="hidden" name="filter_order_Dir" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		
	</div>
<?php endif; ?>

<input type="hidden" name="option" value="com_customtables" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="tableid" value="<?php echo $this->tableid; ?>" />
<input type="hidden" name="tablename" value="<?php echo $this->tablename; ?>" />



<?php echo JHtml::_('form.token'); ?>
</form>
