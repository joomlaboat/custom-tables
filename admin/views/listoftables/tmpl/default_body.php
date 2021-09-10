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

$edit = "index.php?option=com_customtables&view=listoftables&task=tables.edit";
$db = JFactory::getDBO();

?>
<?php foreach ($this->items as $i => $item): ?>
	<?php
		$canCheckin = $this->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->user->id || $item->checked_out == 0;
		$userChkOut = JFactory::getUser($item->checked_out);
		$table_exists = ESTables::checkIfTableExists($item->realtablename);
	?>
	<tr class="row<?php echo $i % 2; ?>">
		<td class="nowrap center">
		<?php if ($this->canState or $this->canDelete): ?>	
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
			<?php 
				echo $this->escape($item->tablename);
				echo '<br/><span style="color:grey;">'.$item->realtablename.'</span>';
				
				?>
			<?php if ($this->canEdit): ?>
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

					if(!array_key_exists($id,$item_array))
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
			<?php 
				if(!$table_exists)
					echo JText::_('COM_CUSTOMTABLES_TABLES_TABLE_NOT_CREATED');
				else
				{
					echo '<a href="'.JURI::root(true).'/administrator/index.php?option=com_customtables&view=listofrecords&tableid='.$item->id.'">'
					.JText::_('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL')
					.' ('.$this->getNumberOfRecords($item->realtablename,$item->realidfieldname).')</a>';
				}
			?>
		</td>

		<td class="hidden-phone">
			<?php echo $this->escape($item->categoryname); ?>
		</td>

		<td class="center">
		<?php if ($this->canState) : ?>
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
