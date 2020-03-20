<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/
	@version		1.6.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		edit.php
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
   
    
