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
use CustomTables\common;
use CustomTables\CTMiscHelper;

defined('_JEXEC') or die();

common::loadJSAndCSS($this->ct->Params, $this->ct->Env, $this->ct->Table->fieldInputPrefix);
$results = $this->details->render();

if ($this->ct->Env->frmt == 'csv') {
	$filename = CTMiscHelper::makeNewFileName($this->ct->document->getTitle(), 'csv');

	if (ob_get_contents())
		ob_end_clean();

	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-Type: text/csv; charset=utf-8');
	header("Pragma: no-cache");
	header("Expires: 0");

	echo mb_convert_encoding($results, 'UTF-16LE', 'UTF-8');

	die;//clean exit
} elseif ($this->ct->Env->frmt == 'xml') {
	$filename = CTMiscHelper::makeNewFileName($this->ct->document->getTitle(), 'xml');

	ob_end_clean();

	if (ob_get_contents())
		ob_end_clean();

	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-Type: text/xml; charset=utf-8');
	header("Pragma: no-cache");
	header("Expires: 0");
	ob_start();
	echo $results;
	ob_flush();
	die;//clean exit
} elseif ($this->ct->Env->clean) {
	echo $results;
	die;//clean exit
}

if ($this->ct->Params->showPageHeading) {
	if (isset($this->ct->Params->pageClassSFX)) {
		echo '<div class="page-header' . common::escape($this->ct->Params->pageClassSFX) . '"><h2 itemprop="headline">'
			. common::translate($this->ct->document->getTitle()) . '</h2></div>';
	} else {
		echo '<div class="page-header"><h2 itemprop="headline">'
			. common::translate($this->ct->document->getTitle()) . '</h2></div>';
	}
}
echo $results;
?>

<!-- Modal content -->
<div id="ctModal" class="ctModal">
	<div id="ctModal_box" class="ctModal_content">
		<span id="ctModal_close" class="ctModal_close">&times;</span>
		<div id="ctModal_content"></div>
	</div>
</div>
<!-- end of the modal -->
