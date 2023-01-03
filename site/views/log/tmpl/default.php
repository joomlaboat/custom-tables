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

$currenturl = JoomlaBasicMisc::curPageURL();
$cleanurl = JoomlaBasicMisc::deleteURLQueryOption($currenturl, 'action');
$cleanurl = JoomlaBasicMisc::deleteURLQueryOption($cleanurl, 'user');

if (strpos($cleanurl, "?") === false)
    $cleanurl .= '?';
else
    $cleanurl .= '&';

echo '
<script>
function ActionFilterChanged(o){
	location.href="' . $cleanurl . 'user=' . $this->userid . '&table=' . $this->tableid . '&action="+o.value;
}

function UserFilterChanged(o){
	location.href="' . $cleanurl . 'action=' . $this->action . '&table=' . $this->tableid . '&user="+o.value;
}

function TableFilterChanged(o){
	location.href="' . $cleanurl . 'action=' . $this->action . '&user=' . $this->userid . '&table="+o.value;
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
    . '<th>User</th>'
    . '<th>Time</th>'
    . '<th>Table</th>'
    . '<th>Record</th>'
    . '<th>Action</th>'
    . '</tr>'
    . '</thead>'
    . '<tbody>';
//Content
foreach ($this->records as $rec) {
    echo $this->renderLogLine($rec);
}
echo '</tbody></table>'
    . '</div>';



