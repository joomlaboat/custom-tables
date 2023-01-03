<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$k = 0;
$i = 0;
$n = count($this->items);
$rows = &$this->items;

foreach ($rows as $row) :


    $checked = JHTML::_('grid.checkedout', $row, $i);


    ?>
    <tr class="<?php echo "row$k"; ?>">
        <!--<td>
				<?php echo $i + 1 + $this->pagination->limitstart; ?>
			</td>-->
        <td>
            <?php echo $checked; ?>
        </td>
        <td nowrap="nowrap">
            <?php /* if (  JTable::isCheckedOut($this->user->get('id'), $row->checked_out ) ) : ?>
				<?php echo $row->treename; ?>
				<?php else : ?>
				<span class="editlinktip hasTip" title="<?php echo Text::_( 'Edit Menu' );?>::<?php echo $row->treename; ?>">

				*/ ?>

            <a href="<?php echo JRoute::_('index.php?option=com_customtables&view=options&layout=edit&id=' . $row->id); ?>"><?php echo $row->treename; ?></a>
            <?php //endif;
            ?>
        </td>

        <?php
        $row_lang = (array)$row;
        $moreThanOneLanguage = false;
        foreach ($this->languages as $lang) {
            $id = 'title';
            if ($moreThanOneLanguage)
                $id .= '_' . $lang->sef;
            else
                $moreThanOneLanguage = true; //More than one language installed

            echo '<td nowrap="nowrap">' . $row_lang[$id] . '</td>';


        }

        ?>

        <td align="center">
            <?php echo $row->isselectable; ?>
        </td>
        <td align="left">
            <?php echo $row->familytreestr; ?>
        </td>
    </tr>
    <?php
    $k = 1 - $k;
    $i++;
    ?>
<?php endforeach; ?>
