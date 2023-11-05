<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access');
use CustomTables\CTUser;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

$edit = "index.php?option=com_customtables&view=listofcategories&task=categories.edit";
$user = new CTUser();
?>
<?php foreach ($this->items as $i => $item): ?>
    <?php
    $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
    $userChkOut = new CTUser($item->checked_out);
    ?>
    <tr class="row<?php echo $i % 2; ?>">

        <td class="nowrap center">
            <?php if ($this->canEdit): ?>
                <?php if ($item->checked_out === 0) : ?>
                    <?php if ($canCheckin) : ?>
                        <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                    <?php else: ?>
                        &#9633;
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td class="nowrap">
            <div class="name">
                <?php if ($this->canEdit): ?>
                    <a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo $this->escape($item->categoryname); ?></a>
                    <?php if ($item->checked_out): ?>
                        <?php echo JHtml::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listofcategories.', $canCheckin); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo $this->escape($item->categoryname); ?>
                <?php endif; ?>
            </div>
        </td>
        <td class="center">
            <?php if ($this->canState) : ?>
                <?php if ($item->checked_out) : ?>
                    <?php if ($canCheckin) : ?>
                        <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofcategories.', true, 'cb'); ?>
                    <?php else: ?>
                        <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofcategories.', false, 'cb'); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofcategories.', true, 'cb'); ?>
                <?php endif; ?>
            <?php else: ?>
                <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listofcategories.', false, 'cb'); ?>
            <?php endif; ?>
        </td>
        <td class="nowrap center hidden-phone">
            <?php echo $item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>
