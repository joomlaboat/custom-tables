<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

use CustomTables\Fields;

$edit = "index.php?option=com_customtables&view=listoffields&task=fields.edit&tableid=" . $this->tableid;

?>
<?php foreach ($this->items as $i => $item): ?>
    <?php
    $canCheckin = $this->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->user->id || $item->checked_out == 0;
    $userChkOut = Factory::getUser($item->checked_out);
    ?>
    <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $this->tableid; ?>">

        <?php if ($this->canState or $this->canDelete): ?>

            <td class="text-center">
                <?php if ($item->checked_out) : ?>
                    <?php if ($canCheckin) : ?>
                        <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->fieldname); ?>
                    <?php else: ?>
                        &#9633;
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->fieldname); ?>
                <?php endif; ?>
            </td>
        <?php endif; ?>

        <?php if ($this->canEdit): ?>

            <td class="text-center d-none d-md-table-cell">

                <?php
                $iconClass = '';
                if (!$this->saveOrder)
                    $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                ?>

                <span class="sortable-handler<?php echo $iconClass; ?>">
				<span class="icon-ellipsis-v" aria-hidden="true"></span>
			</span>
                <?php if ($this->saveOrder) : ?>
                    <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>"
                           class="width-20 text-area-order hidden">
                <?php endif; ?>
            </td>
        <?php endif; ?>

        <td>
            <div class="name">
                <?php if ($this->canEdit): ?>
                    <a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo $this->escape($item->fieldname); ?></a>
                    <?php if ($item->checked_out): ?>
                        <?php echo JHtml::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoffields.', $canCheckin); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo $this->escape($item->fieldname); ?>
                <?php endif; ?>


                <?php

                if ($this->customtablename != '')
                    echo '<br/><span style="color:grey;">' . $this->customtablename . '.' . $item->customfieldname . '</span>';

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

                        echo '<li>' . (count($this->languages) > 1 ? $lang->title . ': ' : '') . '<b>' . $this->escape($item_array[$fieldTitle]) . '</b></li>';

                        $moreThanOneLang = true; //More than one language installed
                    }
                    ?>
                </ul>
            </div>
        </td>

        <td>
            <?php echo Text::_($item->type); ?>
        </td>
        <td>
            <?php echo $this->escape($item->typeparams); ?>
        </td>
        <td>
            <?php echo Text::_($item->isrequired); ?>
        </td>
        <td>
            <?php echo $this->escape($item->tabletitle); ?>
        </td>

        <td class="text-center btns d-none d-md-table-cell">
            <?php if ($this->canState) : ?>
                <?php if ($item->checked_out) : ?>
                    <?php if ($canCheckin) : ?>
                        <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb'); ?>
                    <?php else: ?>
                        <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb'); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb'); ?>
                <?php endif; ?>
            <?php else: ?>
                <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb'); ?>
            <?php endif; ?>
        </td>
        <td class="d-none d-md-table-cell">
            <?php echo $item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>
