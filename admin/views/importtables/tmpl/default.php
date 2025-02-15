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

if (CUSTOMTABLES_JOOMLA_MIN_4)
	$document->addCustomTag('<script src="' . common::UriRoot(true) . '/media/vendor/jquery/js/jquery.min.js"></script>');

$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/uploadfile.css" rel="stylesheet">');
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/style.css" rel="stylesheet">');
$document->addCustomTag('<script src="' . CUSTOMTABLES_LIBRARIES_WEBPATH . 'js/jquery.form.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_LIBRARIES_WEBPATH . 'js/jquery.uploadfile.min.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/uploader.js"></script>');

$fileId = common::generateRandomString();
$max_file_size = CTMiscHelper::file_upload_max_size();

echo '<form method="post" action="" id="esFileUploaderForm_Tables">';
echo '<h2>' . common::translate('COM_CUSTOMTABLES_DASHBOARD_IMPORTTABLES') . '</h2>';
echo '<p>' . common::translate('COM_CUSTOMTABLES_IMPORTTABLES_DESCRIPTION') . '</p>';

$urlString = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=fileuploader&tmpl=component&fileid=' . $fileId;
echo '

    
    <div id="ct_uploadedfile_box_file"></div>
	<div id="fileuploader"></div>
	<div id="eventsmessage"></div>

	<script>
		ct_getUploader(1,"' . $urlString . '",' . $max_file_size . ',"txt html","esFileUploaderForm_Tables",true,"fileuploader","eventsmessage","' . $fileId . '","filetosubmit","ct_uploadedfile_box_file")
	</script>
    <ul style="list-style: none;">
        <li><input type="checkbox" name="importfields" value="1" checked="checked" /> ' . common::translate('COM_CUSTOMTABLES_IMPORTTABLES_TABLE_FIELDS') . '</li>
        <li><input type="checkbox" name="importlayouts" value="1" checked="checked" /> ' . common::translate('COM_CUSTOMTABLES_IMPORTTABLES_LAYOUTS') . '</li>
        <li><input type="checkbox" name="importmenu" value="1" checked="checked" /> ' . common::translate('COM_CUSTOMTABLES_IMPORTTABLES_MENU') . '</li>

    </ul>
    <input type="hidden" id="filetosubmit" name="filetosubmit" value="" checked="checked" />
    <input type="hidden" id="filetosubmit_filename" name="filetosubmit_filename" value="" />
	<input type="hidden" name="fileid" value="' . $fileId . '" />
	<input type="hidden" name="option" value="com_customtables" />
	<!--<input type="hidden" name="controller" value="importtables" />-->
	<input type="hidden" name="task" value="importtables.importtables" />
' . common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($max_file_size) . '
    ' . HTMLHelper::_('form.token') . '
	</form>
';
