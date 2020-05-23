<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// load tooltip behavior
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

echo '<div id="j-sidebar-container" class="span2">';
echo $this->sidebar; ?>
</div>
	
	<div id="j-main-container" class="ct_doc">
    
    
    <ul class="nav nav-tabs">
						<li class="active"><a href="#fieldtypes" data-toggle="tab"><?php echo JText::_('COM_CUSTOMTABLES_TABLEFIELDTYPES'); ?></a></li>
						<li><a href="#layouttags" data-toggle="tab"><?php echo JText::_('COM_CUSTOMTABLES_LAYOUTTAGS'); ?></a></li>
	
						<li><a href="https://joomlaboat.com/custom-tables" target="_blank" style="color:#51A351;"><?php echo JText::_('COM_CUSTOMTABLES_MOREABOUT'); ?></a></li>

	</ul>
                
    <div class="tab-content">

						<!-- Begin Tabs -->
						<div class="tab-pane active" id="fieldtypes">
						<h3><?php echo JText::_('COM_CUSTOMTABLES_TABLEFIELDTYPES'); ?></h3>
						<?php echo JText::_('COM_CUSTOMTABLES_TABLEFIELDTYPES_DESC'); ?>
						<hr/>
						
						<?php echo $this->getFieldTypes(); ?></div>

						<div class="tab-pane" id="layouttags"><?php echo $this->getLayoutTags(); ?></div>

	</div>
</div>
   
    
