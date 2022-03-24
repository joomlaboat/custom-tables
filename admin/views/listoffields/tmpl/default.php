<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// load tooltip behavior
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

use CustomTables\Integrity\IntegrityFields;

if ($this->saveOrder && !empty($this->items))
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

<form action="<?php echo JRoute::_('index.php?option=com_customtables&view=listoffields&tableid='.$this->tableid); ?>" method="post" name="adminForm" id="adminForm">
<?php if(!empty( $this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif; ?>
<?php if (empty($this->items)): ?>
	<?php echo $this->loadTemplate('toolbar');?>
    <div class="alert alert-no-items">
        <?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
    </div>
<?php else : ?>
		<?php echo $this->loadTemplate('toolbar');?>
		
		<?php
		if($this->tableid!=0)
		{
			$link=JURI::root().'administrator/index.php?option=com_customtables&view=listoffields&tableid='.$this->tableid;
			echo IntegrityFields::checkFields($this->ct,$link);
		}
		?>
		
		<table class="table table-striped" id="fieldsList" style="position: relative;">
			<thead><?php echo $this->loadTemplate('head');?></thead>
			<tbody><?php echo $this->loadTemplate('body');?></tbody>
			<tfoot><?php echo $this->loadTemplate('foot');?></tfoot>
		</table>
		<?php //Load the batch processing form. ?>
        <?php /* if ($this->canCreate && $this->canEdit) : ?>
            <?php echo JHtml::_(
                'bootstrap.renderModal',
                'collapseModal',
                array(
                    'title' => JText::_('COM_CUSTOMTABLES_LISTOFFIELDS_BATCH_OPTIONS'),
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

<?php echo JHtml::_('form.token'); ?>
</form>

	<?php /*
  <!-- Modal content -->
  <div id="ctModal" class="ctModal">
  <div id="ctModal_box" class="ctModal_content">
    <span id="ctModal_close" class="ctModal_close">&times;</span>
	<div id="ctModal_content"></div>
  </div>
	</div>
  <!-- end of the modal -->
  
  */ ?>