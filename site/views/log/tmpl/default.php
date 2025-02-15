<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;

$currentURL = common::curPageURL();
$cleanURL = CTMiscHelper::deleteURLQueryOption($currentURL, 'action');
$cleanURL = CTMiscHelper::deleteURLQueryOption($cleanURL, 'user');
$cleanURL = CTMiscHelper::deleteURLQueryOption($cleanURL, 'table');

$document = Factory::getApplication()->getDocument();
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/style.css" rel="stylesheet">');

?>

<script>

	function ctLogAddParams(action, user, table) {
		let params = [];
		let paramsString = "<?php echo $cleanURL; ?>";

		if (action !== 0)
			params.push("action=" + action);

		if (user !== 0)
			params.push("user=" + user);

		if (table !== 0)
			params.push("table=" + table);

		for (let i = 0; i < params.length; i++) {
			if (paramsString.indexOf("?") === -1)
				paramsString += "?";
			else
				paramsString += "&";

			paramsString += params[i];
		}
		return paramsString;
	}

	function ActionFilterChanged(o) {
		location.href = ctLogAddParams(parseInt(o.value), <?php echo (int)$this->userid;?>, <?php echo (int)$this->tableId;?>);
	}

	function UserFilterChanged(o) {
		location.href = ctLogAddParams(<?php echo (int)$this->action;?>, parseInt(o.value), <?php echo (int)$this->tableId;?>);
	}

	function TableFilterChanged(o) {
		location.href = ctLogAddParams(<?php echo (int)$this->action;?>, <?php echo (int)$this->userid;?>, parseInt(o.value));
	}
</script>

<?php
echo $this->actionSelector;
echo $this->userSelector;
echo $this->tableSelector;
?>
<div class="datagrid">
	<table>
		<thead>
		<tr>
			<th>A</th>
			<th style="text-align:left;">User</th>
			<th style="text-align:left;">Time</th>
			<th style="text-align:left;">Table</th>
			<th style="text-align:left;">Record</th>
			<th style="text-align:left;">Action</th>
		</tr>
		</thead>
		<tbody>
		<?php
		//Content
		foreach ($this->records as $rec) {
			echo $this->renderLogLine($rec);
		}
		?>
		</tbody>
	</table>
</div>
