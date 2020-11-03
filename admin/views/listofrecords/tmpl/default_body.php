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

$edit = "index.php?option=com_customtables&view=listofrecords&task=records.edit";

?>
<?php foreach ($this->items as $i => $item): ?>

	<?php
		//canCheckin = $this->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->user->id || $item->checked_out == 0;
		//$userChkOut = JFactory::getUser($item->checked_out);
		//$canDo = CustomtablesHelper::getActions('fields',$item,'listoffields');
	?>
	
	<?php
		//$canCheckin = $this->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->user->id || $item->checked_out == 0;
		//$userChkOut = JFactory::getUser($item->checked_out);
		$canDo = CustomtablesHelper::getActions('records',$item,'listofrecords');
	?>
	<tr class="row<?php echo $i % 2; ?>">
	
		<td class="nowrap center">
		<?php if ($canDo->get('core.edit')): ?>
			<?php echo JHtml::_('grid.id', $i, $item->id); ?>
		<?php else: ?>
			&#9633;
		<?php endif; ?>
		</td>
		
		
	
	


				<?php
			$item_array =  (array) $item;
			$result='';
			
			
			$link='/administrator/index.php?option=com_customtables&view=records&task=records.edit&tableid='.$this->tableid.'&id='.$item->id;
			
			foreach($this->tablefields as $field)
			{
				if($field['type']=='text')
					$result.='<td><a href="'.$link.'">['.$field['fieldname'].':words,50]</a></td>';
				else
					$result.='<td><a href="'.$link.'">['.$field['fieldname'].']</a></td>';
			}
		
			$result=$this->processRecord($item_array,$result);
				
			echo $result;
			?>

		<td class="center">
		<?php if ($canDo->get('core.edit.state')) : ?>
				
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', true, 'cb'); ?>
				
		<?php else: ?>
			<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', false, 'cb'); ?>
		<?php endif; ?>
		</td>
		
		
		<td class="nowrap center hidden-phone">
			<?php echo $item->id; ?>
		</td>

		
	</tr>
<?php endforeach; ?>
