<?php
/**
 * Default Template for CustomTables Component
 *
 * This template file renders the content for a detailed custom table view within the CustomTables
 * Joomla! component. It handles the loading of JavaScript and CSS, dynamically generates
 * output formats (CSV, XML, raw HTML), and manages environment-specific rendering requirements.
 *
 * Features include:
 * - **Dynamic Header Management**: Includes specific headers based on the output type
 *   (e.g., CSV, XML, or direct clean output).
 * - **File Downloads**: Allows CSV and XML file downloads with appropriate encoding
 *   and content disposition.
 * - **Page Rendering**: Displays page titles, headings, and output data (HTML by default).
 * - **Modal implementation**: Incorporates an interactive modal structure for additional content.
 *
 * Constraints:
 * - This script ensures no direct access via `defined('_JEXEC')`.
 *
 * @package     CustomTables
 * @subpackage  Templates
 * @author      Ivan Komlev
 * @link        https://joomlaboat.com
 * @copyright   (C) 2018-2025
 * @license     GNU/GPL Version 2 - https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;

common::loadJSAndCSS($this->ct->Params, $this->ct->Env, $this->ct->Table->fieldInputPrefix);

if (!empty($this->result['style']))
	Factory::getApplication()->getDocument()->addCustomTag('<style>' . $this->result['style'] . '</style>');

if (!empty($this->result['script']))
	Factory::getApplication()->getDocument()->addCustomTag('<script>' . $this->result['script'] . '</script>');

if ($this->ct->Env->frmt == 'csv') {
	/**
	 * Prepare and send CSV output for download:
	 * - Sets headers for CSV file download.
	 * - Encodes the content in UTF-16LE from UTF-8.
	 * - Ensures a clean buffer by clearing any prior content.
	 */
	$filename = CTMiscHelper::makeNewFileName(Factory::getApplication()->getDocument()->getTitle(), 'csv');

	if (ob_get_contents())
		ob_end_clean();

	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-Type: text/csv; charset=utf-8');
	header("Pragma: no-cache");
	header("Expires: 0");
	echo mb_convert_encoding($this->result['content'], 'UTF-16LE', 'UTF-8');
	die;//clean exit
} elseif ($this->ct->Env->frmt == 'xml') {
	/**
	 * Prepare and send XML output for download:
	 * - Sets headers for XML file download.
	 * - Ensures a clean output buffer to prevent invalid content.
	 */
	$filename = CTMiscHelper::makeNewFileName(Factory::getApplication()->getDocument()->getTitle(), 'xml');

	//ob_end_clean();

	//if (ob_get_contents())
	//	ob_end_clean();

	//header('Content-Disposition: attachment; filename="' . $filename . '"');
	//header('Content-Type: text/xml; charset=utf-8');
	//header("Pragma: no-cache");
	//header("Expires: 0");
	//ob_start();
	echo $this->result['content'];
	//ob_flush();
	die;//clean exit
} elseif ($this->ct->Env->clean) {
	/**
	 * Sends raw rendered output directly to the browser:
	 * - Used for clean, machine-readable data output.
	 */
	echo $this->result['content'];
	die;//clean exit
}

if ($this->ct->Params->showPageHeading) {
	/**
	 * Displays the page heading with optional class suffix for styling.
	 * - Ensures proper escaping of class and title.
	 */
	if (isset($this->ct->Params->pageClassSFX)) {
		echo '<div class="page-header' . common::escape($this->ct->Params->pageClassSFX) . '"><h2 itemprop="headline">'
			. common::translate(Factory::getApplication()->getDocument()->getTitle()) . '</h2></div>';
	} else {
		echo '<div class="page-header"><h2 itemprop="headline">'
			. common::translate(Factory::getApplication()->getDocument()->getTitle()) . '</h2></div>';
	}
}

// Output the final results
if ($this->result['success']) {
	echo $this->result['content'];
} else {
	common::enqueueMessage($this->result['message']);
}
?>

<!-- Modal content -->
<div id="ctModal" class="ctModal">
	<div id="ctModal_box" class="ctModal_content">
		<span id="ctModal_close" class="ctModal_close">&times;</span>
		<div id="ctModal_content"></div>
	</div>
</div>
<!-- end of the modal -->
