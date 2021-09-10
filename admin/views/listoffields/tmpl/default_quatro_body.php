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

$edit = "index.php?option=com_customtables&view=listoffields&task=fields.edit&tableid=".$this->tableid;

?>
<?php foreach ($this->items as $i => $item): ?>
	<?php
		$canCheckin = $this->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->user->id || $item->checked_out == 0;
		$userChkOut = JFactory::getUser($item->checked_out);
	?>
	<tr class="row<?php echo $i % 2; ?>">
		
		<?php if ($this->canEdit): ?>	
		<td class="order nowrap center hidden-phone">
			<?php
				if ($this->saveOrder)
				{
					$iconClass = ' inactive';
				}
				else
				{
					$iconClass = ' inactive tip-top hasTooltip';// title="' . JHtml::tooltipText('JORDERINGDISABLED');
				}
			?>
			<span class="sortable-handler<?php echo $iconClass; ?>">
				<i class="icon-menu"></i>
			</span>
			<?php if ($this->saveOrder) : ?>
				<input type="text" style="display:none" name="order[]" size="5"
				value="<?php echo $item->ordering; ?>" class="width-20 text-area-order " />
			<?php endif; ?>
		</td>
		<?php endif; ?>
		
		<?php if ($this->canState or $this->canDelete): ?>	
		
		<td class="text-center">
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					<?php else: ?>
						&#9633;
					<?php endif; ?>
				<?php else: ?>
					<?php echo JHtml::_('grid.id', $i, $item->id); ?>
				<?php endif; ?>
		</td>
		<?php endif; ?>
		
		<td scope="row">
			<div class="name">
				<?php if ($this->canEdit): ?>
					<a href="<?php echo $edit; ?>&tableid=<?php echo $item->tableid; ?>&id=<?php echo $item->id; ?>"><?php echo $this->escape($item->fieldname); ?></a>
					<?php if ($item->checked_out): ?>
						<?php echo JHtml::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoffields.', $canCheckin); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo $this->escape($item->fieldname); ?>
				<?php endif; ?>
				
				
				<?php 
				
				if($this->customtablename!='')
					echo '<br/><span style="color:grey;">'.$this->customtablename.'.'.$item->customfieldname.'</span>';
				
				?>
				
			</div>
		</td>
		
		<td scope="row">

			<div class="name"><ul style="list-style: none !important;margin-left:0;padding-left:0;">
		<?php

				$item_array =  (array) $item;

				$morethanonelang=false;
				
				foreach($this->languages as $lang)
				{
					$id='fieldtitle';
					if($morethanonelang)
						$id.='_'.$lang->sef;

					if(!array_key_exists($id,$item_array))
					{
						JFactory::getApplication()->enqueueMessage(
							JText::_('COM_CUSTOMTABLES_ERROR_LANGFIELDTTILENOTFOUND' ), 'Error');
			
						$id='fieldtitle';
					}
					
					$fieldtitle=$item_array[$id];

					
					echo '<li>';
					
					if(count($this->languages))
						echo $lang->title.': ';
				
					echo '<b>'.$this->escape($fieldtitle).'</b></li>'; 


					$morethanonelang=true; //More than one language installed
				}

				?>
				</ul>
</div>

		</td>
		
		
		<td scope="row">
			<?php echo JText::_($item->type); ?>
		</td>
		<td scope="row">
			<?php echo $this->escape($item->typeparams); ?>
		</td>
		<td scope="row">
			<?php echo JText::_($item->isrequired); ?>
		</td>
		<td scope="row">
			<?php echo $this->escape($item->tabletitle); ?>
		</td>
				
		<td class="text-center btns d-none d-md-table-cell">
		<?php if ($this->canState) : ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb'); ?>
					<?php else: ?>
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb'); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb'); ?>
				<?php endif; ?>
		<?php else: ?>
			<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb'); ?>
		<?php endif; ?>
		</td>
		<td class="d-none d-md-table-cell">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>
