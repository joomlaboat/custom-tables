<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\ListOfTables;
use CustomTables\Fields;
use Joomla\CMS\HTML\HTMLHelper;

$edit = "index.php?option=com_customtables&view=listoftables&task=tables.edit";
$dbPrefix = database::getDBPrefix();

foreach ($this->items as $i => $item): ?>
	<?php

	try {
		$user = new CTUser();
		$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
		$userChkOut = new CTUser($item->checked_out);
		$table_exists = TableHelper::checkIfTableExists($item->realtablename);
	} catch (Exception $e) {
		$user = null;
		$canCheckin = false;
		$userChkOut = null;
		$table_exists = true;
	}

	?>
	<tr class="row<?php echo $i % 2; ?>">
		<?php if ($this->canState or $this->canDelete): ?>
			<td class="text-center firstColumn">
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo HtmlHelper::_('grid.id', $i, $item->id); ?>
						<?php /*<input type="checkbox" id="cb' . $i . '" name="cid[]" value="' . $item->id . '"
                               class="form-check-input"/> */ ?>
					<?php else: ?>
						&#9633;
					<?php endif; ?>
				<?php else: ?>
					<?php echo HtmlHelper::_('grid.id', $i, $item->id); ?>
					<?php /*<input type="checkbox" id="cb' . $i . '" name="cid[]" value="' . $item->id . '"
                           class="form-check-input"/> */ ?>
				<?php endif; ?>

				<span class="tableMobileLabel">
				<?php
				if ($item->checked_out)
					echo HtmlHelper::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoftables.', $canCheckin);
				else
					echo '<a aria-describedby="tip-tableedit' . $item->id . '" href="' . $edit . '&id=' . $item->id . '"><i class="fa fa-pencil"></i>Edit Table</a>';
				?>
				</span>
			</td>
		<?php endif; ?>

		<td class="text-center btns d-none d-md-table-cell itemnumber hiddenColumnInMobile">
			<?php if ($this->canEdit):

				if ($item->checked_out)
					echo HtmlHelper::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoftables.', $canCheckin);
				else
					echo '<a aria-describedby="tip-tableedit' . $item->id . '" href="' . $edit . '&id=' . $item->id . '"><i class="fa fa-pencil"></i>Edit Table</a>';

				?>
				<div role="tooltip" class="tableDesktopLabel"
					 id="tip-tableedit<?php echo $item->id; ?>"><?php echo common::translate('Edit Table Details'); ?>1
				</div>
			<?php else: ?>
				<?php echo '<a class="btn btn" aria-describedby="tip-tableedit' . $item->id . '" href="#">...</a>'; ?>
				<div role="tooltip" class="tableDesktopLabel"
					 id="tip-tableedit<?php echo $item->id; ?>"><?php echo common::translate('Edit Table Details'); ?>2
				</div>
			<?php endif; ?>
		</td>

		<td>
			<div class="name">
				<?php if ($this->canEdit): ?>
					<a href="<?php echo common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $item->id; ?>"
					   aria-describedby="tip-fieldcount<?php echo $item->id; ?> . '"><?php echo common::escape($item->tablename); ?>

						<?php //
						?>

					</a>

					<div role="tooltip" class="tableDesktopLabel"
						 id="tip-fieldcount<?php echo $item->id; ?>"><?php echo $item->fieldcount . ' ' . common::translate('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL'); ?></div>
				<?php else: ?>
					<?php echo common::escape($item->tablename); ?>
				<?php endif; ?>

				<?php
				if ($this->ct->Env->advancedTagProcessor) {
					$hashRealTableName = database::realTableName($item->realtablename);
					$hashRealTableName = str_replace($dbPrefix, '#__', $hashRealTableName);

					echo '<br/><span style="color:grey;">' . $hashRealTableName . '</span>';
				}
				?>


				<?php if (!empty($item->categoryname)): ?>
					<p>
						<?php if ($this->canEdit): ?>

							<?php $categoryTables = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoftables&categoryid=' . $item->tablecategory; ?>

							<a href="<?php echo $categoryTables; ?>">
								<span><?php echo common::translate('COM_CUSTOMTABLES_TABLES_TABLECATEGORY_LABEL'); ?>: </span>
								<?php echo common::escape($item->categoryname ?? ''); ?></a>
						<?php else: ?>
							<span><?php echo common::translate('COM_CUSTOMTABLES_TABLES_TABLECATEGORY_LABEL'); ?>:
					<?php echo common::escape($item->categoryname ?? ''); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>

			</div>
		</td>

		<td>
			<div class="name">
				<ul style="list-style: none !important;margin-left:0;padding-left:0;">
					<?php
					$item_array = (array)$item;

					$moreThanOneLang = false;

					foreach ($this->languages as $lang) {
						$tableTitle = 'tabletitle';
						$tableDescription = 'description';
						if ($moreThanOneLang) {
							$tableTitle .= '_' . $lang->sef;
							$tableDescription .= '_' . $lang->sef;

							if (!array_key_exists($tableTitle, $item_array)) {
								try {
									Fields::addLanguageField('#__customtables_tables', 'tabletitle', $tableTitle);
									$item_array[$tableTitle] = '';
								} catch (Exception $e) {
									common::enqueueMessage($e->getMessage());
								}
							}

							if (!array_key_exists($tableTitle, $item_array)) {
								try {
									Fields::addLanguageField('#__customtables_tables', 'description', $tableDescription);
									$item_array[$tableDescription] = '';
								} catch (Exception $e) {
									common::enqueueMessage($e->getMessage());
								}
							}
						}

						echo '<li>' . (count($this->languages) > 1 ? $lang->title . ': ' : '') . '<b>' . common::escape($item_array[$tableTitle]) . '</b></li>';

						$moreThanOneLang = true; //More than one language installed
					}
					?>
				</ul>
			</div>
		</td>

		<td class="text-center btns d-none d-md-table-cell itemnumber">
			<?php echo '<a class="btn btn-success" aria-describedby="tip-tablefields' . $item->id . '" href="' . common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $item->id . '">'
					. $item->fieldcount . '<span class="tableMobileLabel">&nbsp;' . common::translate('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL') . '</span></a>'; ?>
			<div role="tooltip" class="tableDesktopLabel"
				 id="tip-tablefields<?php echo $item->id; ?>"><?php echo common::translate('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL'); ?></div>
		</td>

		<td class="text-center btns d-none d-md-table-cell itemnumber">
			<?php
			if (!$table_exists)
				echo common::translate('COM_CUSTOMTABLES_TABLES_TABLE_NOT_CREATED');
			elseif (!empty($item->customtablename) and empty($item->customidfield))
				echo common::translate('COM_CUSTOMTABLES_TABLES_ID_FIELD_NOT_SET');
			else {
				$recCount = listOfTables::getNumberOfRecords($item->realtablename);
				try {
					echo '<a class="btn btn-secondary" aria-describedby="tip-tablerecords' . $item->id . '" href="' . common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listofrecords&tableid=' . $item->id . '">'
							. $recCount . '<span class="tableMobileLabel">&nbsp;' . common::translate('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL') . '</span></a>'
							. '<div role="tooltip" class="tableDesktopLabel" id="tip-tablerecords' . $item->id . '">' . $recCount . ' ' . common::translate('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL') . '</div>';
				} catch (Exception $e) {
					common::enqueueMessage($e->getMessage());
				}
			}
			?>
		</td>

		<?php /*
		<td>
			<div class="name">
				<?php if ($this->canEdit): ?>

					<?php $categoryTables = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoftables&categoryid=' . $item->tablecategory; ?>

					<a href="<?php echo $categoryTables; ?>">
						<span class="tableMobileLabel">&nbsp;<?php echo common::translate('COM_CUSTOMTABLES_TABLES_TABLECATEGORY_LABEL'); ?>: </span>
						<?php echo common::escape($item->categoryname ?? ''); ?></a>
				<?php else: ?>
					<span class="tableMobileLabel">&nbsp;<?php echo common::translate('COM_CUSTOMTABLES_TABLES_TABLECATEGORY_LABEL'); ?>: </span>
					<?php echo common::escape($item->categoryname ?? ''); ?>
				<?php endif; ?>
			</div>
		</td>
		*/ ?>

		<td class="text-center btns d-none d-md-table-cell">
			<?php if ($this->canState) : ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php
						echo HTMLHelper::_('jgrid.published', $item->published, $i, 'listoftables.', true, 'cb');
						//echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoftables.', true, 'cb'); ?>
					<?php else: ?>
						<?php
						echo HTMLHelper::_('jgrid.published', $item->published, $i, 'listoftables.', false, 'cb');
						//echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoftables.', false, 'cb'); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php
					echo HTMLHelper::_('jgrid.published', $item->published, $i, 'listoftables.', true, 'cb');
					//echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoftables.', true, 'cb'); ?>
				<?php endif; ?>
			<?php else: ?>
				<?php
				echo HTMLHelper::_('jgrid.published', $item->published, $i, 'listoftables.', false, 'cb');
				//echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoftables.', false, 'cb'); ?>
			<?php endif; ?>
			<span class="tableMobileLabel">&nbsp;# <?php echo $item->id; ?></span>
		</td>
		<td class="d-none d-md-table-cell hiddenColumnInMobile">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>
