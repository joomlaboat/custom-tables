<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			1st July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		default_body.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

$edit = "index.php?option=com_customtables&view=listofrecords&task=records.edit";

?>
<?php foreach ($this->items as $i => $item): ?>
	<?php
		//$canCheckin = $this->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->user->id || $item->checked_out == 0;
		//$userChkOut = JFactory::getUser($item->checked_out);
		$canDo = CustomtablesHelper::getActions('records',$item,'listofrecords');
	?>
	<tr class="row<?php echo $i % 2; ?>">
		<td class="order nowrap center hidden-phone">
		<?php if ($canDo->get('core.edit.state')): ?>
			<?php
			/*
				if ($this->saveOrder)
				{
					$iconClass = ' inactive';
				}
				else
				{
					$iconClass = ' inactive tip-top hasTooltip';// title="' . JHtml::tooltipText('JORDERINGDISABLED');
				}
				*/
			/*
			?>
			<span class="sortable-handler<?php //echo $iconClass; ?>">
				<i class="icon-menu"></i>
			</span>
			<?php *///if ($this->saveOrder) : 
				//<input type="text" style="display:none" name="order[]" size="5"
				//value="<?php echo $item->ordering; class="width-20 text-area-order " />
	 //endif; ?>
		<?php else: ?>
			&#8942;
		<?php endif; ?>
		</td>
		<td class="nowrap center">
		<?php if ($canDo->get('core.edit')): ?>
				<?php /*if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					<?php else: ?>
						&#9633;
					<?php endif; ?>
				<?php else:*/ ?>
					<?php //echo JHtml::_('grid.id', $i, $item->id); ?>
				<?php //endif; ?>
		<?php else: ?>
			&#9633;
		<?php endif; ?>
		</td>

				<?php
			$item_array =  (array) $item;
			$result='';
			foreach($this->tablefields as $field)
				$result.='<td>['.$field['fieldname'].']</td>';
		
			$result=$this->processRecord($item_array,$result);
				
			echo $result;
			?>
				


		<td class="center">
		<?php //if ($canDo->get('core.edit.state')) : ?>
				<?php /*if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', true, 'cb'); ?>
					<?php else: ?>
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', false, 'cb'); ?>
					<?php endif; ?>
				<?php else:*/ ?>
					<?php // echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', true, 'cb'); ?>
					
					<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', false, 'cb'); ?>
				<?php // endif; ?>
		<?php //else: ?>
			<?php // echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', false, 'cb'); ?>
		<?php // endif; ?>
		</td>
		<td class="nowrap center hidden-phone">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>
