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

?>
<?php foreach ($this->items as $i => $item): ?>
	<?php
		$canCheckin = $this->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->user->id || $item->checked_out == 0;
		$userChkOut = JFactory::getUser($item->checked_out);
		$table_exists = ESTables::checkIfTableExists($item->realtablename);
		//$canDo = CustomtablesHelper::getActions('categories',$item,'listofcategories');
	?>
	<tr class="row<?php echo $i % 2; ?>">
	
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
					<a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo $this->escape($item->tablename); ?></a>
					<?php if ($item->checked_out): ?>
						<?php echo JHtml::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoftables.', $canCheckin); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo $this->escape($item->tablename); ?>
				<?php endif; ?>
			</div>
		</td>
		
		<td scope="row">

			<div class="name"><ul style="list-style: none !important;margin-left:0;padding-left:0;">
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
</div>

		</td>
		
		<td scope="row" class="text-center btns d-none d-md-table-cell itemnumber">
		

		
			<?php echo '<a class="btn btn-success" aria-describedby="tip-tablefields'.$item->id.'" href="'.JURI::root(true).'/administrator/index.php?option=com_customtables&view=listoffields&tableid='.$item->id.'">'
			.$item->fieldcount.'</a>'; ?>
			<div role="tooltip" id="tip-tablefields<?php echo $item->id; ?>"><?php echo JText::_('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL'); ?></div>
			
			
		</td>
		
		<td scope="row" class="text-center btns d-none d-md-table-cell itemnumber">
			<?php 
				if(!$table_exists)
					echo JText::_('COM_CUSTOMTABLES_TABLES_TABLE_NOT_CREATED');
				else
				{
					echo '<a class="btn btn-secondary" aria-describedby="tip-tablerecords'.$item->id.'" href="'.JURI::root(true).'/administrator/index.php?option=com_customtables&view=listofrecords&tableid='.$item->id.'">'
					.$this->getNumberOfRecords($item->realtablename,$item->realidfieldname).'</a>'
					.'<div role="tooltip" id="tip-tablerecords'.$item->id.'">'.JText::_('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL').'</div>';
				}
			?>
		</td>
		
		<td scope="row">
			<div class="name">
				<?php if ($this->canEdit): ?>
					<a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo $this->escape($item->categoryname); ?></a>
					<?php if ($item->checked_out): ?>
						<?php echo JHtml::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listofcategories.', $canCheckin); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo $this->escape($item->categoryname); ?>
				<?php endif; ?>
			</div>
		</td>
		
		<td class="text-center btns d-none d-md-table-cell">
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
		<td class="d-none d-md-table-cell">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>
