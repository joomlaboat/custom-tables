<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/layouts/tmpl/edit.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');
$componentParams = JComponentHelper::getParams('com_customtables');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layouteditor.php');
$onPageLoads=array();

?>
<script type="text/javascript">

	// waiting spinner
	var outerDiv = jQuery('body');
	jQuery('<div id="loading"></div>')
		.css("background", "rgba(255, 255, 255, .8) url('components/com_customtables/assets/images/import.gif') 50% 15% no-repeat")
		.css("top", outerDiv.position().top - jQuery(window).scrollTop())
		.css("left", outerDiv.position().left - jQuery(window).scrollLeft())
		.css("width", outerDiv.width())
		.css("height", outerDiv.height())
		.css("position", "fixed")
		.css("opacity", "0.80")
		.css("-ms-filter", "progid:DXImageTransform.Microsoft.Alpha(Opacity = 80)")
		.css("filter", "alpha(opacity = 80)")
		.css("display", "none")
		.appendTo(outerDiv);
	jQuery('#loading').show();
	// when page is ready remove and show
	jQuery(window).load(function() {
		jQuery('#customtables_loader').fadeIn('fast');
		jQuery('#loading').hide();
		
		<?php echo 'all_tables='.$this->getAllTables().';'; ?>
	});
</script>

<div id="customtables_loader" style="display: none;">
	<form action="<?php echo JRoute::_('index.php?option=com_customtables&layout=edit&id='.(int) $this->item->id.$this->referral); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
	<?php echo JLayoutHelper::render('layouts.details_above', $this); ?>
		<div class="form-horizontal">
			<div class="row-fluid form-horizontal-desktop"></div>
			<div class="row-fluid form-horizontal-desktop">
				<div class="span12">
					<div class="control-group">
						<div style="width: 100%;position: relative;">
							<?php
							
							if($this->item->layoutcode!="")
								echo '<div class="ct_tip">TIP: Double Click on a Layout Tag to edit parameters.</div>'; ?>
						</div>
						<?php
							$textareacode='<textarea name="jform[layoutcode]" id="jform_layoutcode" filter="raw" style="width:100%" rows="30">'.$this->item->layoutcode.'</textarea>';
							$textareaid='jform_layoutcode';
							$textareatabid="layouttagbox";
							$typeboxid="jform_layouttype";
							echo renderEditor($textareacode,$textareaid,$typeboxid,$textareatabid,$onPageLoads);
						?>
					
					</div>
				</div>
			<input type="hidden" name="task" value="layouts.edit" />
			<?php echo JHtml::_('form.token'); ?>
	
			</div>
		</div>
		<div class="clearfix"></div>
		<?php echo JLayoutHelper::render('layouts.details_under', $this);
		echo render_onPageLoads($onPageLoads,$this->item->layouttype);
		$this->getMenuItems();
		?>
		
		<div id="allLayoutRaw" style="display:none;"><?php echo json_encode($this->getLayouts()); ?></div>
		<div id="dependencies_content" style="display:none;">
		
		<h3><?php echo JText::_('COM_CUSTOMTABLES_LAYOUTS_WHAT_IS_USING_IT', true); ?></h3>
		<div id="layouteditor_tagsContent0" class="dynamic_values_list dynamic_values">
		<?php 
		require_once('dependencies.php');
		echo renderDependencies($this->item); // this will be shown upon the click in the toolbar
		?>
		</div></div>
	</form>
</div>
