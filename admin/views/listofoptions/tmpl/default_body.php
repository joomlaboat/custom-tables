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

	$k = 0;
	$i = 0;
	$n = count( $this->items );
	$rows = &$this->items;

	foreach ($rows as $row) :


		$checked 	= JHTML::_('grid.checkedout',   $row, $i );


		?>
		<tr class="<?php echo "row$k"; ?>">
			<!--<td>
				<?php echo $i + 1 + $this->pagination->limitstart;?>
			</td>-->
			<td>
				<?php echo $checked; ?>
			</td>
			<td nowrap="nowrap">
				<?php /* if (  JTable::isCheckedOut($this->user->get('id'), $row->checked_out ) ) : ?>
				<?php echo $row->treename; ?>
				<?php else : ?>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'Edit Menu' );?>::<?php echo $row->treename; ?>">

				*/ ?>

				<a href="<?php echo JRoute::_( 'index.php?option=com_customtables&view=options&layout=edit&id='.$row->id ); ?>"><?php echo $row->treename; ?></a></span>
				<?php //endif; ?>
			</td>

			<?php
				$row_lang=(array)$row;
				$morethanonelang=false;
				foreach($this->languages as $lang)
				{
					$id='title';
					if($morethanonelang)
						$id.='_'.$lang->sef;
					else
						$morethanonelang=true; //More than one language installed

					echo '<td nowrap="nowrap">'.$row_lang[$id].'</td>';


				}

				?>

<?php /*

			<td class="order" nowrap="nowrap">
				<span><?php
//echo $row->parentid.'<br/>';
echo $this->pagination->orderUpIcon( $i, $row->parentid == 0 || $row->parentid == @$rows[$i-1]->parentid, 'orderup', 'Move Up', $this->ordering); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $n, $row->parentid == 0 || $row->parentid == @$rows[$i+1]->parentid, 'orderdown', 'Move Down', $this->ordering ); ?></span>
				<?php $disabled = $this->ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $row->ordering; ?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
*/ ?>
			<td align="center">
				<?php echo $row->isselectable; ?>
			</td>
<!--
			<td align="center">
				<?php //echo $row->id; ?>
			</td>--?>
			<!--<td align="left">
				<?php// echo $row->familytree; ?>
			</td>-->
			<td align="left">
				<?php echo $row->familytreestr; ?>
			</td>
		</tr>
		<?php
		$k = 1 - $k;
		$i++;
		?>
	<?php endforeach; ?>
