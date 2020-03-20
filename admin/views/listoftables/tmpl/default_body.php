<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		default_body.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

$edit = "index.php?option=com_customtables&view=listoftables&task=tables.edit";

?>
<?php foreach ($this->items as $i => $item): ?>
	<?php
		$canCheckin = $this->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->user->id || $item->checked_out == 0;
		$userChkOut = JFactory::getUser($item->checked_out);
		$canDo = CustomtablesHelper::getActions('tables',$item,'listoftables');
	?>
	<tr class="row<?php echo $i % 2; ?>">
		<td class="order nowrap center hidden-phone">
		<?php if ($canDo->get('core.edit.state')): ?>
			<?php
				if ($this->saveOrder)
				{
					$iconClass = ' inactive';
				}
				else
				{
					$iconClass = ' inactive tip-top" hasTooltip" title="' . JHtml::tooltipText('JORDERINGDISABLED');
				}
			?>
			<span class="sortable-handler<?php echo $iconClass; ?>">
				<i class="icon-menu"></i>
			</span>
			<?php if ($this->saveOrder) : ?>
				<input type="text" style="display:none" name="order[]" size="5"
				value="<?php echo $item->ordering; ?>" class="width-20 text-area-order " />
			<?php endif; ?>
		<?php else: ?>
			&#8942;
		<?php endif; ?>
		</td>
		<td class="nowrap center">
		<?php if ($canDo->get('core.edit')): ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					<?php else: ?>
						&#9633;
					<?php endif; ?>
				<?php else: ?>
					<?php echo JHtml::_('grid.id', $i, $item->id); ?>
				<?php endif; ?>
		<?php else: ?>
			&#9633;
		<?php endif; ?>
		</td>
		
				<td class="hidden-phone"><a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>">
			<?php echo $this->escape($item->tablename); ?>
			<?php if ($canDo->get('core.edit')): ?>
								<?php if ($item->checked_out): ?>
						<?php echo JHtml::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoftables.', $canCheckin); ?>
					<?php endif; ?>
			<?php endif; ?>
			</a>
		</td>
		
<td class="nowrap"><div class="name"><ul style="list-style: none;margin-left:0;">
		<?php

				$item_array =  (array) $item;

				$morethanonelang=false;
				
				foreach($this->languages as $lang)
				{
					$id='tabletitle';
					if($morethanonelang)
						$id.='_'.$lang->sef;

					
					if(!isset($item_array[$id]))
					{
						JFactory::getApplication()->enqueueMessage(
							JText::_('COM_CUSTOMTABLES_ERROR_LANGTABLETTILENOTFOUND' ), 'Error');
			
						$id='tabletitle';
					}
					
					$tabletitle=$item_array[$id];

					
					echo '<li>';
					
					if(count($this->languages))
						echo $lang->title.': ';
				
					echo '<b>'.$this->escape($tabletitle).'</b></li>'; 


					$morethanonelang=true; //More than one language installed
				}

				?>
				</ul>
</div></td>








		<td class="hidden-phone">
			<?php echo '<a href="'.JURI::root(true).'/administrator/index.php?option=com_customtables&view=listoffields&tableid='.$item->id.'">'
			.JText::_('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL')
			.' ('.$item->fieldcount.')</a>'; ?>
		</td>
		
		<td class="hidden-phone">
			<?php echo '<a href="'.JURI::root(true).'/administrator/index.php?option=com_customtables&view=listofrecords&tableid='.$item->id.'">'
			.JText::_('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL')
			.' ('.$this->getNumberOfRecords($item->tablename).')</a>'; ?>
		</td>

		<td class="hidden-phone">
			<?php echo $this->escape($item->categoryname); ?>
		</td>

		<td class="center">
		<?php if ($canDo->get('core.edit.state')) : ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoftables.', true, 'cb'); ?>
					<?php else: ?>
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoftables.', false, 'cb'); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoftables.', true, 'cb'); ?>
				<?php endif; ?>
		<?php else: ?>
			<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoftables.', false, 'cb'); ?>
		<?php endif; ?>
		</td>
		<td class="nowrap center hidden-phone">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>
