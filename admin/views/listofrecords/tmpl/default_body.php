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

	<tr class="row<?php echo $i % 2; ?>">
	
		<td class="nowrap center">
		<?php if ($this->canEdit): ?>
			<?php echo JHtml::_('grid.id', $i, $item->listing_id); ?>
		<?php endif; ?>
		</td>
		
		<?php
			$item_array =  (array) $item;
			$result='';
			
			$link=JURI::root(false).'administrator/index.php?option=com_customtables&view=records&task=records.edit&tableid='.$this->ct->Table->tableid.'&id='.$item->listing_id;
			
			foreach($this->tablefields as $field)
			{
				if($field['type'] != 'dummy' and $field['type'] != 'log')
				{
					if($field['type']=='text')
						$result.='<td><a href="'.$link.'">['.$field['fieldname'].':words,50]</a></td>';
					else
						$result.='<td><a href="'.$link.'">['.$field['fieldname'].']</a></td>';
				}
			}
		
			$result=$this->processRecord($item_array,$result);
				
			echo $result;
			?>

		<?php if($this->ct->Table->published_field_found): ?>
		<td class="center">
			<?php if ($this->canState) : ?>
					<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', true, 'cb'); ?>
			<?php else: ?>
				<?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofrecords.', false, 'cb'); ?>
			<?php endif; ?>
		</td>
		<?php endif; ?>

		<td class="nowrap center hidden-phone">
			<?php echo $item->listing_id; ?>s
		</td>
	</tr>
<?php endforeach; ?>
