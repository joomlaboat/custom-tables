<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

?>

<?php if($this->version < 4): ?>
	<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
	</div>
<?php endif; ?>
	
	<div id="j-main-container" class="ct_doc">
    	
	<?php if($this->version >= 4):
	
		echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'fieldtypes', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'fieldtypes', Text::_('COM_CUSTOMTABLES_TABLEFIELDTYPES')); ?>
			<?php if($this->internal_use): ?>
			<h3><?php echo JText::_('COM_CUSTOMTABLES_TABLEFIELDTYPES'); ?></h3>
			<?php endif; ?>
			
			<?php echo JText::_('COM_CUSTOMTABLES_TABLEFIELDTYPES_DESC'); ?>
			<?php echo $this->getFieldTypes(); ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
			
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'layouttags', Text::_('COM_CUSTOMTABLES_LAYOUTTAGS')); ?>
			<?php if($this->internal_use): ?>
			<h3><?php echo JText::_('COM_CUSTOMTABLES_LAYOUTTAGS'); ?></h3><br/>
			<?php endif; ?>
			<?php echo $this->getLayoutTags(); ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
			
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'more_about', Text::_('COM_CUSTOMTABLES_MOREABOUT')); ?>
			<a href="https://joomlaboat.com/custom-tables" target="_blank" style="color:#51A351;"><?php echo JText::_('COM_CUSTOMTABLES_MOREABOUT'); ?></a>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
			
		<?php echo HTMLHelper::_('uitab.endTabSet'); 
	else: ?>
	
	<ul class="nav nav-tabs">
		<li class="active"><a href="#fieldtypes" data-toggle="tab"><?php echo JText::_('COM_CUSTOMTABLES_TABLEFIELDTYPES'); ?></a></li>
		<li><a href="#layouttags" data-toggle="tab"><?php echo JText::_('COM_CUSTOMTABLES_LAYOUTTAGS'); ?></a></li>
		<li><a href="https://joomlaboat.com/custom-tables" target="_blank" style="color:#51A351;"><?php echo JText::_('COM_CUSTOMTABLES_MOREABOUT'); ?></a></li>
	</ul>
                
    <div class="tab-content">
		<div class="tab-pane active" id="fieldtypes">
		
		<?php if($this->internal_use): ?>
		<h3><?php echo JText::_('COM_CUSTOMTABLES_TABLEFIELDTYPES'); ?></h3>
		<?php endif; ?>
		
		<?php echo JText::_('COM_CUSTOMTABLES_TABLEFIELDTYPES_DESC'); ?>
		
		<?php echo $this->getFieldTypes(); ?></div>
		
		
		<div class="tab-pane" id="layouttags">
		<?php if($this->internal_use): ?>
		<h3><?php echo JText::_('COM_CUSTOMTABLES_LAYOUTTAGS'); ?></h3>
		<?php endif; ?>
			
		<?php echo $this->getLayoutTags(); ?></div>
	</div>
	
	<?php
	endif;
	?>
	
</div>
   
    
