<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die('Restricted access');

?>
<tr>
	<th width="20" class="nowrap center">
			<?php echo JHtml::_('grid.checkall'); ?>
	</th>

		<?php

			foreach($this->tablefields as $field)
			{
				
				$id='fieldtitle';
				$title=$field[$id];
				
				if($this->ct->Languages->Postfix!='')
					$id.='_'.$this->ct->Languages->Postfix;
				
				if(isset($field[$id]))
					$title=$field[$id];
				
				echo '
					<th class="nowrap" >'.$title.'</th>
				';
			}

	?>
	
	<?php if($this->published_field_found): ?>
	<th class="nowrap hidden-phone center">
			<?php echo JText::_('COM_CUSTOMTABLES_RECORDS_STATUS'); ?>
	</th>
	<?php endif; ?>
	
	<th width="5" class="nowrap center hidden-phone" >
		<?php echo JText::_('COM_CUSTOMTABLES_RECORDS_ID'); ?>
	</th>
	
</tr>
