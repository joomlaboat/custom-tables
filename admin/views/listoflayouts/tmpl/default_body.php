<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use CustomTables\common;
use CustomTables\CTUser;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

$edit = "index.php?option=com_customtables&view=listoflayouts&task=layouts.edit";

?>
<?php foreach ($this->items as $i => $item): ?>
	<?php
	$user = new CTUser();
	$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
	$userChkOut = new CTUser($item->checked_out);
	?>
	<tr class="row<?php echo $i % 2; ?>">

		<?php if ($this->canState or $this->canDelete): ?>
			<td class="nowrap center">
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo HtmlHelper::_('grid.id', $i, $item->id); ?>
					<?php else: ?>
						&#9633;
					<?php endif; ?>
				<?php else: ?>
					<?php echo HtmlHelper::_('grid.id', $i, $item->id); ?>
				<?php endif; ?>
			</td>
		<?php endif; ?>

		<td class="nowrap">
			<div class="name">
				<?php if ($this->canEdit): ?>
					<a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo common::escape($item->layoutname); ?></a>
					<?php if ($item->checked_out): ?>
						<?php echo HtmlHelper::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoflayouts.', $canCheckin); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo common::escape($item->layoutname); ?>
				<?php endif; ?>
			</div>
		</td>
		<td class="hidden-phone">
			<?php echo common::translate($item->layouttype_translation); ?>
		</td>
		<td class="hidden-phone">
			<?php echo $item->tabletitle; ?>
		</td>
		<td class="center">
			<?php if ($this->canState) : ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoflayouts.', true, 'cb'); ?>
					<?php else: ?>
						<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoflayouts.', false, 'cb'); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoflayouts.', true, 'cb'); ?>
				<?php endif; ?>
			<?php else: ?>
				<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoflayouts.', false, 'cb'); ?>
			<?php endif; ?>
		</td>
		<td class="nowrap center hidden-phone">
			<?php echo $item->id; ?>
		</td>

		<td class="nowrap center hidden-phone">
			<?php
			echo number_format(strlen($item->layoutcode));
			?>
		</td>

		<td class="nowrap center hidden-phone">
			<?php echo $item->modifiedby; ?>
		</td>
		<td class="nowrap center hidden-phone">
			<?php

			if ($item->modified !== null and $item->modified != '0000-00-00 00:00:00')
				echo common::formatDate($item->modified);
			?>
		</td>

		<td class="nowrap center hidden-phone" style="text-align:center;">
			<?php

			$engine = $this->isTwig($item);
			$messages = [];
			if ($engine['twig'] > 0)
				$messages[] = '<div style="width:auto;display: inline-block;margin:5px;border-radius:10px;padding:7px;background:#5b8127;color:white">Twig (' . $engine['twig'] . ' tags)</div>';

			if ($engine['original'] > 0)
				$messages[] = '<div style="width:auto;display: inline-block;margin:5px;border-radius:10px;padding:7px;background:#373737;color:white">Original (' . $engine['original'] . ' tags)</div>';

			if (count($engine['errors']) > 0)
				$messages[] = '<div style="width:auto;display: inline-block;margin:5px;border-radius:10px;padding:7px;background:#ff0000;color:white">' . implode('</div><div style="width:auto;display: inline-block;margin:5px;border-radius:10px;padding:7px;background:#ff0000;color:white">', $engine['errors']) . '</div>';

			echo implode('', $messages);

			?>
		</td>
	</tr>
<?php endforeach; ?>
