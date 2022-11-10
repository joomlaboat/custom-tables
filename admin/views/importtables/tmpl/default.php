<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// load tooltip behavior
if ($this->version < 4) {
    JHtml::_('behavior.tooltip');
}

JHtml::_('behavior.formvalidator');
$document = Factory::getDocument();

if ($this->version >= 4) {
    $document->addCustomTag('<script src="' . JURI::root(true) . '/media/vendor/jquery/js/jquery.min.js"></script>');
}

$document->addCustomTag('<link href="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/css/uploadfile.css" rel="stylesheet">');
$document->addCustomTag('<link href="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/css/style.css" rel="stylesheet">');


$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/jquery.form.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/jquery.uploadfile.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/uploader.js"></script>');

$fileid = $this->generateRandomString();
$max_file_size = JoomlaBasicMisc::file_upload_max_size();

echo '<form method="post" action="" id="esFileUploaderForm_Tables">';
echo '<h2>Import Tables</h2>';

echo '<p>This may import Table Structure from .txt (json encoded) file.</p>';


$urlstr = JURI::root(true) . '/administrator/index.php?option=com_customtables&view=fileuploader&tmpl=component&fileid=' . $fileid;
echo '

    
    <div id="ct_uploadedfile_box_file"></div>
	<div id="fileuploader"></div>
	<div id="eventsmessage"></div>
    

	<script>
        //UploadFileCount=1;
		ct_getUploader(1,"' . $urlstr . '",' . $max_file_size . ',"txt html","esFileUploaderForm_Tables",true,"fileuploader","eventsmessage","' . $fileid . '","filetosubmit","ct_uploadedfile_box_file");//null);

	</script>
    <ul style="list-style: none;">
        <li><input type="checkbox" name="importfields" value="1" checked="checked" /> Import Table Fields</li>
        <li><input type="checkbox" name="importlayouts" value="1" checked="checked" /> Import Layouts</li>
        <li><input type="checkbox" name="importmenu" value="1" checked="checked" /> Import Menu</li>

    </ul>

    <input type="hidden" id="filetosubmit" name="filetosubmit" value="" checked="checked" />
	<input type="hidden" name="fileid" value="' . $fileid . '" />
	<input type="hidden" name="option" value="com_customtables" />
	<!--<input type="hidden" name="controller" value="importtables" />-->
	<input type="hidden" name="task" value="importtables.importtables" />
' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE') . ': ' . JoomlaBasicMisc::formatSizeUnits($max_file_size) . '
    ' . JHtml::_('form.token') . '
	</form>
	';
