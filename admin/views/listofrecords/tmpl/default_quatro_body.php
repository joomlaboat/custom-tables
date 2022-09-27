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

use Joomla\CMS\Language\Text;

use CustomTables\TwigProcessor;

$recordLayout = '';

foreach ($this->ct->Table->fields as $field) {
    if ($field['type'] != 'dummy' and $field['type'] != 'log' and $field['type'] != 'ordering') {
        if ($field['type'] == 'text' or $field['type'] == 'multilangtext' or $field['type'] == 'string' or $field['type'] == 'multilangstring')
            $recordLayout .= '<td><a href="****link****">{{ ' . $field['fieldname'] . '("words",20) }}</a></td>';
        else
            $recordLayout .= '<td><a href="****link****">{{ ' . $field['fieldname'] . '}}</a></td>';
    }
}

$twig = new TwigProcessor($this->ct, $recordLayout);

?>

<?php foreach ($this->items as $i => $item):
    $item_array = (array)$item;
    ?>

    <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $this->ct->Table->tableid; ?>">

        <?php if ($this->canState or $this->canDelete): ?>
            <td scope="row" class="text-center">
                <?php echo JHtml::_('grid.id', $i, $item_array[$this->ct->Table->realidfieldname]); ?>
            </td>
        <?php endif; ?>

        <?php if ($this->ordering_realfieldname != ''): ?>
            <td class="text-center d-none d-md-table-cell">
                <?php
                $iconClass = '';
                if (!$this->saveOrder)
                    $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                ?>

                <span class="sortable-handler<?php echo $iconClass; ?>">
				<span class="icon-ellipsis-v" aria-hidden="true"></span>
			</span>
                <?php if ($this->saveOrder) :

                    $order_value = (int)$item_array[$this->ordering_realfieldname];
                    if ($order_value == 0)
                        $order_value = $item_array[$this->ct->Table->realidfieldname];

                    ?>
                    <input type="text" name="order[]" size="5" value="<?php echo $order_value; ?>"
                           class="width-20 text-area-order hidden">
                <?php endif; ?>
            </td>
        <?php endif; ?>

        <?php
        $result = '';

        $link = JURI::root(false) . 'administrator/index.php?option=com_customtables&view=records&task=records.edit&tableid=' . $this->ct->Table->tableid . '&id=' . $item_array[$this->ct->Table->realidfieldname];

        $result = $twig->process($item_array);

        echo str_replace('****link****', $link, $result);

        ?>

        <?php if ($this->ct->Table->published_field_found): ?>
            <td class="text-center btns d-none d-md-table-cell">
                <?php if ($this->canState) : ?>
                    <?php echo JHtml::_('jgrid.published', $item->listing_published, $i, 'listofrecords.', true, 'cb'); ?>
                <?php else: ?>
                    <?php echo JHtml::_('jgrid.published', $item->listing_published, $i, 'listofrecords.', false, 'cb'); ?>
                <?php endif; ?>
            </td>
        <?php endif; ?>

        <td class="d-none d-md-table-cell">
            <?php echo $item_array[$this->ct->Table->realidfieldname]; ?>
        </td>
    </tr>
<?php endforeach; ?>
