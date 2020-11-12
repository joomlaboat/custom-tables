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
defined('_JEXEC') or die('Restricted Access');
?>


		<tr>
			<!--<th width="20">
				<?php echo JText::_( 'NUM' ); ?>
			</th>-->
			<th width="20">
				<input type="checkbox" name="checkall-toggle" value="" title="Check All" onclick="Joomla.checkAll(this)" />
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',   'Option Name', 'name', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			
				<?php
			
			foreach($this->LanguageList as $lang)
			{
				?>
				
				<th class="title">
				<?php echo JHTML::_('grid.sort',   'Option Title ('.$lang->caption.')', 'title_'.$lang->id, @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				
				<?php
			}

			?>
			
			<!--
			<th width="8%" nowrap="nowrap">
				<?php //echo JHTML::_('grid.sort',   'Order by', 'm.ordering', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				<?php //echo JHTML::_('grid.order',  $this->items ); ?>
				<?php //able) echo JHTML::_('grid.order',  $this->items ); ?>
				<?php //echo JHTML::_('grid.sort',   'Itemid', 'id', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			-->
			
			<th width="8%" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',   'Is Selectable', 'm.isselectable', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				
			</th>

			<!--
			<th width="1%" nowrap="nowrap">
				Family Tree #
			</th>-->
			<th width="1%" nowrap="nowrap">
				Family Tree
			</th>
		</tr>
	
