<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use CT_FieldTypeTag_file;
use ESFileUploader;
use Joomla\CMS\Uri\Uri;

class InputBox_file extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(): string
	{
		$file_type_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
		require_once($file_type_file);

		if (!$this->ct->isRecordNull($this->row)) {
			$file = strval($this->row[$this->field->realfieldname]);
		} else
			$file = '';

		$result = '<div class="esUploadFileBox" style="vertical-align:top;">';

		if ($this->field->type == 'blob') {

			$fileSize = intval($file);

			if ($fileSize != 0)
				$result .= self::renderBlobAndDeleteOption($fileSize, $this->field, $this->row, $this->ct->Table->fields, $this->row[$this->ct->Table->realidfieldname]);
		} else
			$result .= self::renderFileAndDeleteOption($file, $this->field);

		$result .= self::renderUploader($this->ct, $this->field);

		$result .= '</div>';
		return $result;
	}

	protected static function renderBlobAndDeleteOption(int $fileSize, $field, $row, $fields, $listing_id): string
	{
		if ($fileSize == '')
			return '';

		$result = '
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;" id="ct_uploadedfile_box_' . $field->fieldname . '">';

		$filename = CT_FieldTypeTag_file::getBlobFileName($field, $fileSize, $row, $fields);

		$filename_Icon = CT_FieldTypeTag_file::process($filename, $field, ['', 'icon-filename-link', 48], $listing_id, false, $fileSize);

		$result .= $filename_Icon . '<br/><br/>';

		if ($field->isrequired !== 1)
			$result .= '<input type="checkbox" name="' . $field->prefix . $field->fieldname . '_delete" id="' . $field->prefix . $field->fieldname . '_delete" value="true">'
				. ' Delete Data';

		$result .= '
                </div>';

		return $result;
	}

	protected static function renderFileAndDeleteOption(string $file, $field): string
	{
		if ($file == '')
			return '';

		if ($field->type == 'filelink')
			$FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[0]);
		else
			$FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[1]);

		$link = $FileFolder . '/' . $file;

		$parts = explode('.', $file);
		$file_extension = end($parts);

		$image_src = CUSTOMTABLES_MEDIA_WEBPATH . 'images/fileformats/48px/' . $file_extension . '.png';

		$result = '
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;" id="ct_uploadedfile_box_' . $field->fieldname . '">';

		$result .= '<a href="' . $link . '" target="_blank" title="' . $file . '"><img src="' . $image_src . '" style="width:48px;" alt="' . $file . '" /></a><br/>';

		if ($field->isrequired !== 1)
			$result .= '<input type="checkbox" name="' . $field->prefix . $field->fieldname . '_delete" id="' . $field->prefix . $field->fieldname . '_delete" value="true">'
				. ' Delete File';

		$result .= '
                </div>';

		return $result;
	}

	protected static function renderUploader(CT $ct, Field $field): string
	{
		if ($field->type == 'file')
			$fileExtensions = $field->params[2] ?? '';
		elseif ($field->type == 'blob')
			$fileExtensions = $field->params[1] ?? '';
		else
			return false;

		$accepted_file_types = ESFileUploader::getAcceptedFileTypes($fileExtensions);

		if ($field->type == 'blob') {

			if ($field->params[0] == 'tiny')
				$custom_max_size = 255;
			elseif ($field->params[0] == 'medium')
				$custom_max_size = 16777215;
			elseif ($field->params[0] == 'long')
				$custom_max_size = 4294967295;
			else
				$custom_max_size = 65535;
		} else {
			$custom_max_size = (int)$field->params[0];

			if ($custom_max_size != 0 and $custom_max_size < 10000)
				$custom_max_size = $custom_max_size * 1000000; //to change 20 to 20MB
		}

		$max_file_size = CTMiscHelper::file_upload_max_size($custom_max_size);

		$file_id = common::generateRandomString();
		$urlstr = Uri::root(true) . '/index.php?option=com_customtables&view=fileuploader&tmpl=component&' . $field->fieldname
			. '_fileid=' . $file_id
			. '&Itemid=' . $field->ct->Params->ItemId
			. (is_null($field->ct->Params->ModuleId) ? '' : '&ModuleId=' . $field->ct->Params->ModuleId)
			. '&fieldname=' . $field->fieldname;

		if ($ct->app->getName() == 'administrator')   //since   3.2
			$formName = 'adminForm';
		else {
			if ($ct->Env->isModal)
				$formName = 'ctEditModalForm';
			else {
				$formName = 'ctEditForm';
				$formName .= $ct->Params->ModuleId;
			}
		}

		$scriptParams = [
			$field->id,
			'"' . $urlstr . '"',
			$max_file_size,
			'"' . $accepted_file_types . '"',
			'"' . $formName . '"',
			'false',
			'"ct_fileuploader_' . $field->fieldname . '"',
			'"ct_eventsmessage_' . $field->fieldname . '"',
			'"' . $file_id . '"',
			'"' . $field->prefix . $field->fieldname . '"',
			'"ct_ubloadedfile_box_' . $field->fieldname . '"'
		];

		$script = '<script>ct_getUploader(' . implode(',', $scriptParams) . ')</script>';

		return '<div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;" '
			. 'data-type="' . $field->type . '" '
			. 'data-label="' . $field->title . '" '
			. 'data-valuerule="' . str_replace('"', '&quot;', $field->valuerule) . '" '
			. 'data-valuerulecaption="' . str_replace('"', '&quot;', $field->valuerulecaption) . '" >'
			. '<div id="ct_fileuploader_' . $field->fieldname . '"></div>'
			. '<div id="ct_eventsmessage_' . $field->fieldname . '"></div>'
			. $script
			. '<input type="hidden" name="' . $field->prefix . $field->fieldname . '" id="' . $field->prefix . $field->fieldname . '" value="" />'
			. '<input type="hidden" name="' . $field->prefix . $field->fieldname . '_filename" id="' . $field->prefix . $field->fieldname . '_filename" value="" />'
			. common::translate('COM_CUSTOMTABLES_PERMITTED_FILE_TYPES') . ': ' . $accepted_file_types . '<br/>'
			. common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($max_file_size)
			. '</div>';
	}
}