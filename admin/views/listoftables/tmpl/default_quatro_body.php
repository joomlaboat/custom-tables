<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access');
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\ListOfTables;
use CustomTables\Fields;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$edit = "index.php?option=com_customtables&view=listoftables&task=tables.edit";
$dbPrefix = database::getDBPrefix();

foreach ($this->items as $i => $item): ?>
	<?php
	$user = new CTUser();
	$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
	$userChkOut = new CTUser($item->checked_out);
	$table_exists = TableHelper::checkIfTableExists($item->realtablename);
	//$canDo = CustomtablesHelper::getActions('categories',$item,'listofcategories');
	?>
    <tr class="row<?php echo $i % 2; ?>">

		<?php if ($this->canState or $this->canDelete): ?>
            <td class="text-center">
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
            </td>
		<?php endif; ?>

        <td>
            <div class="name">
				<?php if ($this->canEdit): ?>
                    <a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo common::escape($item->tablename); ?></a>
					<?php if ($item->checked_out):

						//echo //$this->renderCheckedOutStatus($item);
						echo HtmlHelper::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoftables.', $canCheckin);
					endif; ?>
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
								Fields::addLanguageField('#__customtables_tables', 'tabletitle', $tableTitle);
								$item_array[$tableTitle] = '';
							}

							if (!array_key_exists($tableTitle, $item_array)) {
								Fields::addLanguageField('#__customtables_tables', 'description', $tableDescription);
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

        <td class="text-center btns d-none d-md-table-cell itemnumber">


			<?php echo '<a class="btn btn-success" aria-describedby="tip-tablefields' . $item->id . '" href="' . Uri::root(true) . '/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $item->id . '">'
				. $item->fieldcount . '</a>'; ?>
            <div role="tooltip"
                 id="tip-tablefields<?php echo $item->id; ?>"><?php echo common::translate('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL'); ?></div>


        </td>

        <td class="text-center btns d-none d-md-table-cell itemnumber">
			<?php
			if (!$table_exists)
				echo common::translate('COM_CUSTOMTABLES_TABLES_TABLE_NOT_CREATED');
            elseif (($item->customtablename !== null and $item->customtablename != '') and ($item->customidfield === null or $item->customidfield == ''))
				echo common::translate('COM_CUSTOMTABLES_TABLES_ID_FIELD_NOT_SET');
			else {
				echo '<a class="btn btn-secondary" aria-describedby="tip-tablerecords' . $item->id . '" href="' . Uri::root(true) . '/administrator/index.php?option=com_customtables&view=listofrecords&tableid=' . $item->id . '">'
					. listOfTables::getNumberOfRecords($item->realtablename, $item->realidfieldname) . '</a>'
					. '<div role="tooltip" id="tip-tablerecords' . $item->id . '">' . common::translate('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL') . '</div>';
			}
			?>
        </td>

        <td>
            <div class="name">
				<?php if ($this->canEdit): ?>
                    <a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo common::escape($item->categoryname); ?></a>
				<?php else: ?>
					<?php echo common::escape($item->categoryname); ?>
				<?php endif; ?>
            </div>
        </td>

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
        </td>
        <td class="d-none d-md-table-cell">
			<?php echo $item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>
