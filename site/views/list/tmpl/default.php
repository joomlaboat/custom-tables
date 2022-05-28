<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

?>

<div style="width:100%;overflow: scroll;">
    <form action="index.php" method="post" name="adminForm">

        <table width="100%">
            <tr>
                <td align="left">
                    <h2>CustomTables - Structure</h2>
                </td>
                <td nowrap="nowrap" align="right">
                    <a href="index.php?option=com_customtables&view=list&task=edit"><img
                                src="<?php echo JURI::root(true); ?>"/components/com_customtables/libraries/customtables/media/images/icons/new.png"
                        alt="New" title="New" /></a>
                    <a href="#"
                       onclick="javascript:if (document.adminForm.boxchecked.value==0){alert('Please first make a selection from the list');}else{ Joomla.submitbutton('remove')}"
                       class="toolbar"><img src="<?php echo JURI::root(true); ?>"/components/com_customtables/libraries/customtables/media/images/icons/delete.png"
                        alt="Delete" title="Delete" /></a>

                </td>

            </tr>
        </table>


        <table>
            <tr>
                <td align="left">
                    <?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILTER'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $this->lists['search']; ?>"
                           class="text_area" onchange="document.adminForm.submit();"/>
                    <button onclick="this.form.submit();"><?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT_GO'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.getElementById('levellimit').value='10';this.form.getElementById('filter_state').value='';this.form.submit();"><?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT_RESET'); ?></button>
                </td>
                <td nowrap="nowrap">
                    <?php

                    echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ROOT_PARENT') . ':&nbsp;';
                    echo $this->lists['rootparent'] . '&nbsp;';

                    echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_MAX_LEVELS') . ':&nbsp;';
                    echo $this->lists['levellist'] . '&nbsp;';

                    ?>
                </td>
            </tr>
        </table>

        <table class="adminlist">
            <thead>
            <tr>
                <th width="20">
                    <?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NUM'); ?>
                </th>
                <th width="20">
                    <input type="checkbox" name="toggle" value=""
                           onclick="checkAll(<?php echo count($this->items); ?>);"/>
                </th>
                <th class="title">
                    <?php echo JHTML::_('grid.sort', 'Name', 'name', @$this->lists['order_Dir'], @$this->lists['order']); ?>
                </th>

                <?php


                $firstlanguage = true;
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstlanguage) {
                        $postfix = '';
                        $firstlanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    echo '<th class="title" nowrap="nowrap">';
                    echo JHTML::_('grid.sort', $lang->caption, 'title' . $postfix, @$this->lists['order_Dir'], @$this->lists['order']);
                    echo '</th>';

                }

                ?>


                <th width="8%" nowrap="nowrap">
                    <?php echo JHTML::_('grid.sort', 'Order by', 'm.ordering', @$this->lists['order_Dir'], @$this->lists['order']); ?>
                    <?php if ($this->ordering) echo JHTML::_('grid.order', $this->items); ?>
                </th>


            </tr>
            </thead>
            <tfoot>
            <tr>
                <td colspan="12">
                    <div class="pagination" style="margin-top:20px;">
                        <?php echo $this->pagination->getListFooter(); ?>
                    </div>
                </td>
            </tr>
            </tfoot>
            <tbody>
            <?php
            $k = 0;
            $i = 0;
            $n = count($this->items);
            $rows = $this->items;
            //$filter_rootparent = $mainframe->getUserStateFromRequest( "com_customtables.filter_rootparent",'filter_rootparent','','int' );
            //&rootid='.$filter_rootparent.'
            foreach ($rows as $row) :

                $checked = JHTML::_('grid.checkedout', $row, $i);


                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $i + 1 + $this->pagination->limitstart; ?>
                    </td>
                    <td>
                        <?php echo $checked; ?>
                    </td>
                    <td nowrap="nowrap">
                        <?php if (JTable::isCheckedOut($this->user->get('id'), $row->checked_out)) : ?>
                            <?php echo $row->treename; ?>
                        <?php else : ?>
                            <span class="editlinktip hasTip"
                                  title="<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT_MENU'); ?>::<?php echo $row->treename; ?>">
				<a href="<?php echo JRoute::_('index.php?option=com_customtables&view=list&task=edit&cid[]=' . $row->id); ?>"><?php echo $row->treename; ?></a></span>
                        <?php endif; ?>
                    </td>

                    <?php

                    $row_array = (array)$row;

                    $firstlanguage = true;
                    foreach ($this->ct->Languages->LanguageList as $lang) {
                        if ($firstlanguage) {
                            $postfix = '';
                            $firstlanguage = false;
                        } else
                            $postfix = '_' . $lang->sef;

                        $vlu = $row_array['title' . $postfix];

                        echo '<td nowrap="nowrap">' . $vlu . '</td>';
                    }

                    ?>


                    <td class="order" nowrap="nowrap">
				<span><?php
                    echo $this->pagination->orderUpIcon($i, $row->parentid == 0 || $row->parentid == @$rows[$i - 1]->parentid, 'orderup', 'Move Up', $this->ordering); ?></span>
                        <span><?php echo $this->pagination->orderDownIcon($i, $n, $row->parentid == 0 || $row->parentid == @$rows[$i + 1]->parentid, 'orderdown', 'Move Down', $this->ordering); ?></span>


                        <?php $disabled = $this->ordering ? '' : 'disabled="disabled"'; ?>
                        <input type="text" name="order[]" size="5"
                               value="<?php echo $row->ordering; ?>" <?php echo $disabled ?> class="text_area"
                               style="text-align: center"/>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
                $i++;
                ?>
            <?php endforeach; ?>
            </tbody>
        </table>

        <input type="hidden" name="option" value="com_customtables"/>
        <input type="hidden" name="view" value="list"/>
        <input type="hidden" name="task" value="view"/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>"/>
        <input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>"/>
        <input type="hidden" name="Itemid"
               value="<?php echo Factory::getApplication()->input->get('Itemid', 0, 'INT'); ?>"/>

        <?php echo JHTML::_('form.token'); ?>
    </form>

</div>
