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

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
//JHtml::_('behavior.tooltip');
// < 4


if($this->version >= 4)
{
	$wa = $this->document->getWebAssetManager();
	$wa->useScript('keepalive')
		->useScript('form.validate');
}
else
{
	//JHtml::_('formbehavior.chosen', 'select');
	JHtml::_('behavior.keepalive');
	JHtml::_('behavior.formvalidation');
}
	//->useScript('com_banners.admin-banner-edit');

//JHtml::_('formbehavior.chosen', 'select');
//JHtml::_('behavior.keepalive');
//$componentParams = JComponentHelper::getParams('com_customtables');
?>

<script type="text/javascript">
/*
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
	});
	*/
</script>

<!--<div id="customtables_loader" style="display: none;">-->
<form action="<?php echo JRoute::_('index.php?option=com_customtables&layout=edit&id='.(int) $this->item->id.$this->referral); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
<div class="form-horizontal">

	<?php //echo JHtml::_('bootstrap.startTabSet', 'categoriesTab', array('active' => 'general')); ?>

	<?php //echo JHtml::_('bootstrap.addTab', 'categoriesTab', 'general', JText::_('COM_CUSTOMTABLES_CATEGORIES_GENERAL', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				<?php echo JLayoutHelper::render('categories.general_left', $this); ?>
			</div>
		</div>
	<?php //echo JHtml::_('bootstrap.endTab'); ?>

	<?php //echo JHtml::_('bootstrap.endTabSet'); ?>

	<div>
		<input type="hidden" name="task" value="categories.edit" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</div>
</form>
<!--</div>-->
