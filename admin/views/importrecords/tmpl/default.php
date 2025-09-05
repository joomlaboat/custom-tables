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
use Joomla\CMS\HTML\HTMLHelper;

// load tooltip behavior
if (!CUSTOMTABLES_JOOMLA_MIN_4) {
	HTMLHelper::_('behavior.tooltip');
}

HTMLHelper::_('behavior.formvalidator');
$document = Factory::getApplication()->getDocument();

if (CUSTOMTABLES_JOOMLA_MIN_4) {
	$document->addCustomTag('<script src="' . common::UriRoot(true) . '/media/vendor/jquery/js/jquery.min.js"></script>');
}

$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/uploadfile.css" rel="stylesheet">');
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/style.css" rel="stylesheet">');
$document->addCustomTag('<script src="' . CUSTOMTABLES_LIBRARIES_WEBPATH . 'js/jquery.form.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_LIBRARIES_WEBPATH . 'js/jquery.uploadfile.min.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/uploader.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/edit.js"></script>');

$fileId = common::generateRandomString();
$max_file_size = CTMiscHelper::file_upload_max_size();

echo '<form method="post" action="" id="esFileUploaderForm_Tables">';
echo '<h2>Import Records</h2>';
echo '<p>This function allows for the importation of table records from .csv files.</p>';

$urlString = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=fileuploader&tmpl=component&fileid=' . $fileId;
echo '

    
    <div id="ct_uploadedfile_box_file"></div>
	<div id="fileuploader"></div>
	<div id="eventsmessage"></div>

	<script>
		let ctTranslationScriptObject = ' . json_encode(common::getLocalizeScriptArray()) . ';
		let ctFieldInputPrefix = "' . $this->fieldInputPrefix . '";
	
		if (typeof window.CTEditHelper === "undefined") {
			window.CTEditHelper = new CustomTablesEdit("Joomla",' . (explode('.', CUSTOMTABLES_JOOMLA_VERSION)[0]) . ',null,"' . common::UriRoot(false, true) . '");
		}
	
		ct_getUploader(1,"' . $urlString . '",' . $max_file_size . ',"csv","esFileUploaderForm_Tables",true,"fileuploader","eventsmessage","' . $fileId . '","filetosubmit","ct_uploadedfile_box_file")
	</script>

    <input type="hidden" id="filetosubmit" name="filetosubmit" value="" checked="checked" />
    <input type="hidden" id="filetosubmit_filename" name="filetosubmit_filename" value="" />
	<input type="hidden" name="fileid" value="' . $fileId . '" />
	<input type="hidden" name="option" value="com_customtables" />
	<!--<input type="hidden" name="controller" value="importtables" />-->
	<input type="hidden" name="task" value="importrecords.importrecords" />
' . common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($max_file_size) . '
    ' . HTMLHelper::_('form.token') . '
	</form>
';
