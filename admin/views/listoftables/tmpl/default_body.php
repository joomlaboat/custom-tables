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
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\Fields;
use CustomTables\ListOfTables;
use Joomla\CMS\HTML\HTMLHelper;

$edit = "index.php?option=com_customtables&view=listoftables&task=tables.edit";
$dbPrefix = database::getDBPrefix();

foreach ($this->items as $i => $item): ?>
	<?php
	$user = new CTUser();

	try {
		$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
		$userChkOut = new CTUser($item->checked_out);
		$table_exists = TableHelper::checkIfTableExists($item->realtablename);
	} catch (Exception $e) {
		common::enqueueMessage($e->getMessage());
		break;
	}

	?>
	<tr class="row<?php echo $i % 2; ?>">
		<td class="nowrap center">
			<?php if ($this->canState or $this->canDelete): ?>
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

		<td class="hidden-phone"><a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>">
				<?php
				echo common::escape($item->tablename);

				if ($this->ct->Env->advancedTagProcessor) {
					$hashRealTableName = database::realTableName($item->realtablename);
					$hashRealTableName = str_replace($dbPrefix, '#__', $hashRealTableName);

					echo '<br/><span style="color:grey;">' . $hashRealTableName . '</span>';
				}
				?>
				<?php if ($this->canEdit): ?>
					<?php if ($item->checked_out): ?>
						<?php echo HtmlHelper::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoftables.', $canCheckin); ?>
					<?php endif; ?>
				<?php endif; ?>
			</a>
		</td>

		<td class="nowrap">
			<div class="name">
				<ul style="list-style: none;margin-left:0;">
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
								} catch (Exception $e) {
									common::enqueueMessage($e->getMessage());
								}
								$item_array[$tableTitle] = '';
							}

							if (!array_key_exists($tableTitle, $item_array)) {
								try {
									Fields::addLanguageField('#__customtables_tables', 'description', $tableDescription);
								} catch (Exception $e) {
									common::enqueueMessage($e->getMessage());
								}
								$item_array[$tableDescription] = '';
							}
						}

						echo '<li>' . (count($this->languages) > 1 ? $lang->title . ': ' : '') . '<b>' . common::escape($item_array[$tableTitle]) . '</b></li>';

						$moreThanOneLang = true; //More than one language installed
					}

					?>
				</ul>
			</div>
		</td>

		<td class="hidden-phone">
			<?php echo '<a href="' . common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $item->id . '">'
				. common::translate('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL')
				. ' (' . $item->fieldcount . ')</a>'; ?>
		</td>

		<td class="hidden-phone">
			<?php
			if (!$table_exists)
				echo common::translate('COM_CUSTOMTABLES_TABLES_TABLE_NOT_CREATED');
			else {
				try {
					echo '<a href="' . common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listofrecords&tableid=' . $item->id . '">'
						. common::translate('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL')
						. ' (' . listOfTables::getNumberOfRecords($item->realtablename) . ')</a>';
				} catch (Exception $e) {
					common::enqueueMessage($e->getMessage());
				}
			}
			?>
		</td>

		<td class="hidden-phone">
			<?php echo common::escape($item->categoryname); ?>
		</td>

		<td class="center">
			<?php if ($this->canState) : ?>
				<?php if ($item->checked_out) : ?>
					<?php if ($canCheckin) : ?>
						<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoftables.', true, 'cb'); ?>
					<?php else: ?>
						<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoftables.', false, 'cb'); ?>
					<?php endif; ?>
				<?php else: ?>
					<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoftables.', true, 'cb'); ?>
				<?php endif; ?>
			<?php else: ?>
				<?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'listoftables.', false, 'cb'); ?>
			<?php endif; ?>
		</td>
		<td class="nowrap center hidden-phone">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>
