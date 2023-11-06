<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\common;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

echo '<h5>' . common::translate('COM_CUSTOMTABLES_FOUND') . ': ' . $this->record_count . ' ' . common::translate('COM_CUSTOMTABLES_RESULT_S') . '</h5>';

echo '<form action="" method="post" name="escatalogform" id="escatalogform">
		<input type="hidden" name="option" value="com_customtables" />
		<input type="hidden" name="view" value="structure" />
        <input type="hidden" name="task" id="task" value="" />
        ';

if ($this->record_count > 5) {
    echo '<table><tr height=30>'
        . '<td>' . common::translate('COM_CUSTOMTABLES_SHOW') . ': ' . $this->pagination->getLimitBox("") . '</td>'
        . '<td>' . $this->pagination->getPagesLinks("") . '<br/></td>'
        . '<td></td>'
        . '</tr></table><hr/>';
}

$catalogResult = '<table>';
$Itemid = common::inputGetInt('Itemid', 0);

$tr = 0;
$number_of_columns = 3;

$content_width = 100;
$column_width = floor($content_width / $number_of_columns);
$aLink = 'index.php?option=com_customtables&view=catalog&Itemid=' . $Itemid . '&essearchbar=true&establename=' . $this->ct->Table->tablename;

foreach ($this->rows as $row) {
    if ($tr == 0)
        $catalogResult .= '<tr>';

    $catalogResult .= '<td style="width:' . $column_width . '%;vertical-valign:top;text-align:left;">';

    if ($this->linkable)
        $catalogResult .= '<a href="' . $aLink . '&es_' . $this->esfieldname . '_1=' . $row['optionname'] . '">' . $row['optiontitle'] . '</a>';
    else
        $catalogResult .= $row['optiontitle'] . '';

    $catalogResult .= '</td>';

    $tr++;
    if ($tr == $number_of_columns) {
        $catalogResult .= '</tr>';

        if ($this->row_break)
            $catalogResult .= '<tr><td colspan="' . $number_of_columns . '"><hr /></td></tr>';

        $tr = 0;
    }
}

$catalogResult .= '</tbody>

    </table>';

if ($this->ct->Params->allowContentPlugins)
    $catalogResult = JoomlaBasicMisc::applyContentPlugins($catalogResult);

echo $catalogResult;

if ($this->record_count > 5) {
    echo '<p></p>
		<hr>
			<table>
				<tbody>
					<tr>
						<td>' . $this->pagination->getPagesLinks("") . '<br/></td>
					</tr>
				</tbody>
			</table>
		';
}

echo '</form>';
