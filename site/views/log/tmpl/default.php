<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

$currentURL = JoomlaBasicMisc::curPageURL();
$cleanURL = JoomlaBasicMisc::deleteURLQueryOption($currentURL, 'action');
$cleanURL = JoomlaBasicMisc::deleteURLQueryOption($cleanURL, 'user');

if (strpos($cleanURL, "?") === false)
    $cleanURL .= '?';
else
    $cleanURL .= '&';

echo '
<script>
function ActionFilterChanged(o){
	location.href="' . $cleanURL . 'user=' . $this->userid . '&table=' . $this->tableId . '&action="+o.value;
}

function UserFilterChanged(o){
	location.href="' . $cleanURL . 'action=' . $this->action . '&table=' . $this->tableId . '&user="+o.value;
}

function TableFilterChanged(o){
	location.href="' . $cleanURL . 'action=' . $this->action . '&user=' . $this->userid . '&table="+o.value;
}
</script>';


echo $this->actionSelector;
echo $this->userSelector;
echo $this->tableSelector;

echo '<div class="datagrid">'
    . '<table>'
    . '<thead>'
    . '<tr>'
    . '<th>A</th>'
    . '<th style="text-align:left;">User</th>'
    . '<th style="text-align:left;">Time</th>'
    . '<th style="text-align:left;">Table</th>'
    . '<th style="text-align:left;">Record</th>'
    . '<th style="text-align:left;">Action</th>'
    . '</tr>'
    . '</thead>'
    . '<tbody>';
//Content
foreach ($this->records as $rec) {
    echo $this->renderLogLine($rec);
}
echo '</tbody></table>'
    . '</div>';



