<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\Fields;
use Joomla\CMS\HTML\HTMLHelper;

$edit = "index.php?option=com_customtables&view=listoffields&task=fields.edit&tableid=" . $this->ct->Table->tableid;
$dbPrefix = database::getDBPrefix();
$hashRealTableName = database::realTableName($this->ct->Table->realtablename);
$hashRealTableName = str_replace($dbPrefix, '#__', $hashRealTableName);

?>
<?php foreach ($this->items as $i => $item): ?>
	<?php

	$ordering = ($this->listOrder == 'a.ordering');
	$item->max_ordering = 0; //I am not sure if its used
	$user = new CTUser();
	$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
	$userChkOut = new CTUser($item->checked_out);
	?>
	<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $this->ct->Table->tableid; ?>">

		<td class="nowrap center">
			<?php if ($this->canEdit): ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo HtmlHelper::_('grid.id', $i, $item->id); ?>
					<?php else: ?>
						&#9633;
					<?php endif; ?>
				<?php else: ?>
					<?php echo HtmlHelper::_('grid.id', $i, $item->id); ?>
				<?php endif; ?>
			<?php else: ?>
				&#9633;
			<?php endif; ?>
		</td>

		<td class="order nowrap center hidden-phone">
			<?php

			if ($this->canState or $this->canDelete):
				$iconClass = '';

				if (!$this->saveOrder)
					$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'COM_CUSTOMTABLES_ORDERINGDISABLED');
				?>
				<span class="sortable-handler<?php echo $iconClass; ?>">
				<i class="icon-menu"></i>
			</span>
				<?php if ($this->saveOrder) : ?>
				<input type="text" style="display:none" name="order[]" size="5"
					   value="<?php echo $item->ordering; ?>" class="width-20 text-area-order "/>
			<?php endif; ?>
			<?php else: ?>
				&#8942;
			<?php endif; ?>
		</td>

		<td class="hidden-phone">

			<?php if ($this->canEdit): ?>
				<a href="<?php echo $edit; ?>&tableid=<?php echo $this->ct->Table->tableid; ?>&id=<?php echo $item->id; ?>"><?php echo common::escape($item->fieldname); ?></a>
				<?php if ($item->checked_out): ?>
					<?php echo HtmlHelper::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoffields.', $canCheckin); ?>
				<?php endif; ?>
			<?php else: ?>
				<?php echo common::escape($item->fieldname); ?>
			<?php endif; ?>

			<?php

			if ($this->ct->Env->advancedTagProcessor and $this->ct->Table->realtablename != '')
				echo '<br/><span style="color:grey;">' . $hashRealTableName . '.' . $item->realfieldname . '</span>';

			?>
		</td>

		<td class="nowrap">
			<div class="name">
				<ul style="list-style: none !important;margin-left:0;padding-left:0;">
					<?php
					$item_array = (array)$item;

					$moreThanOneLang = false;
					foreach ($this->languages as $lang) {
						$fieldTitle = 'fieldtitle';
						$fieldDescription = 'description';
						if ($moreThanOneLang) {
							$fieldTitle .= '_' . $lang->sef;
							$fieldDescription .= '_' . $lang->sef;

							if (!array_key_exists($fieldTitle, $item_array)) {
								Fields::addLanguageField('#__customtables_fields', 'fieldtitle', $fieldTitle);
								$item_array[$fieldTitle] = '';
							}

							if (!array_key_exists($fieldTitle, $item_array)) {
								Fields::addLanguageField('#__customtables_fields', 'description', $fieldDescription);
								$item_array[$fieldDescription] = '';
							}
						}

						echo '<li>' . (count($this->languages) > 1 ? $lang->title . ': ' : '') . '<b>' . common::escape($item_array[$fieldTitle]) . '</b></li>';

						$moreThanOneLang = true; //More than one language installed
					}
					?>
				</ul>
			</div>
		</td>
		<td class="hidden-phone">
			<?php echo common::translate($item->type); ?>
		</td>
		<td class="hidden-phone">
			<?php echo str_replace('****apos****', "'", str_replace('****quote****', '"', common::escape($item->typeparams))); ?>
		</td>
		<td class="hidden-phone">
			<?php echo common::translate($item->isrequired); ?>
		</td>
		<td class="hidden-phone">
			<?php echo common::escape($this->ct->Table->tabletitle); ?>
		</td>
		<td class="center">
			<?php if ($this->canState) : ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb'); ?>
					<?php else: ?>
						<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb'); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb'); ?>
				<?php endif; ?>
			<?php else: ?>
				<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb'); ?>
			<?php endif; ?>
		</td>
		<td class="nowrap center hidden-phone">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>
