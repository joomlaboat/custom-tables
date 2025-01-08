<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access');
use CustomTables\common;
use CustomTables\CTUser;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

$edit = "index.php?option=com_customtables&view=listofcategories&task=categories.edit";
$user = new CTUser();
?>
<?php foreach ($this->items as $i => $item): ?>
	<?php
	$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
	$userChkOut = new CTUser($item->checked_out);
	?>
	<tr class="row<?php echo $i % 2; ?>">

		<?php if ($this->canState or $this->canDelete): ?>
			<td class="text-center">
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

		<td>
			<div class="name">
				<?php if ($this->canEdit): ?>
					<a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo common::escape($item->categoryname); ?></a>
					<?php if ($item->checked_out): ?>
						<?php echo HtmlHelper::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listofcategories.', $canCheckin); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo common::escape($item->categoryname); ?>
				<?php endif; ?>
			</div>
		</td>

		<td class="text-center btns d-none d-md-table-cell itemnumber">
			<?php echo '<a class="btn btn-success" aria-describedby="tip-category-tables' . $item->id . '" href="' . common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoftables&categoryid=' . $item->id . '">'
				. $item->table_count . '</a>'; ?>
			<div role="tooltip"
				 id="tip-category-tables<?php echo $item->id; ?>"><?php echo common::translate('COM_CUSTOMTABLES_TABLES'); ?></div>
		</td>

		<td class="text-center btns d-none d-md-table-cell itemnumber">
			<?php
			//' . common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listofmenus&categoryid=' . $item->id . '

			echo '<a class="btn btn-secondary" aria-describedby="tip-tablefields' . $item->id . '" href="#">'
				. ($item->menu_count) . '</a>'; ?>
			<div role="tooltip"
				 id="tip-tablefields<?php echo $item->id; ?>"><?php echo common::translate('COM_CUSTOMTABLES_MENUS'); ?></div>
		</td>

		<td class="text-center btns d-none d-md-table-cell">
			<?php if ($this->canState) : ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listofcategories.', true, 'cb'); ?>
					<?php else: ?>
						<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listofcategories.', false, 'cb'); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listofcategories.', true, 'cb'); ?>
				<?php endif; ?>
			<?php else: ?>
				<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listofcategories.', false, 'cb'); ?>
			<?php endif; ?>
		</td>
		<td class="d-none d-md-table-cell">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>
