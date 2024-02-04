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

use CT_FieldTypeTag_image;
use CustomTablesImageMethods;
use Joomla\CMS\Uri\Uri;

class InputBox_image extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(): string
	{
		$ImageFolder = CustomTablesImageMethods::getImageFolder($this->field->params);
		$imageFile = '';
		$isShortcut = false;
		$imageSRC = CT_FieldTypeTag_image::getImageSRC($this->row, $this->field->realfieldname, $ImageFolder, $imageFile, $isShortcut);
		$result = '<div class="esUploadFileBox" style="vertical-align:top;">';

		if ($imageFile != '')
			$result .= $this->renderImageAndDeleteOption($this->field, $imageSRC, $isShortcut);

		$result .= $this->renderUploader();
		$result .= '</div>';
		return $result;
	}

	protected function renderImageAndDeleteOption(Field $field, string $imageSrc, bool $isShortcut): string
	{
		$prefix = $this->ct->Env->field_input_prefix . (!$this->ct->isEditForm ? $this->row[$this->ct->Table->realidfieldname] . '_' : '');

		$result = '<div style="" id="ct_uploadedfile_box_' . $field->fieldname . '">'
			. '<img src="' . $imageSrc . '" alt="Uploaded Image" style="width:150px;" id="ct_uploadfile_box_' . $field->fieldname . '_image" /><br/>';

		if ($field->isrequired !== 1)
			$result .= '<input type="checkbox" name="' . $prefix . $field->fieldname . '_delete" id="' . $prefix . $field->fieldname . '_delete" value="true">'
				. ' Delete ' . ($isShortcut ? 'Shortcut' : 'Image');

		$result .= '</div>';

		return $result;
	}

	protected function renderUploader(): string
	{
		$max_file_size = CTMiscHelper::file_upload_max_size();
		$fileId = common::generateRandomString();
		$style = 'border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;margin:10px;';//vertical-align:top;
		$element_id = 'ct_uploadfile_box_' . $this->field->fieldname;

		$urlString = Uri::root(true) . '/index.php?option=com_customtables&view=fileuploader&tmpl=component&' . $this->field->fieldname . '_fileid=' . $fileId
			. '&Itemid=' . $this->field->ct->Params->ItemId
			. (is_null($this->field->ct->Params->ModuleId) ? '' : '&ModuleId=' . $this->field->ct->Params->ModuleId)
			. '&fieldname=' . $this->field->fieldname;

		if ($this->ct->app->getName() == 'administrator')   //since   3.2
			$formName = 'adminForm';
		else {
			if ($this->ct->Env->isModal)
				$formName = 'ctEditModalForm';
			else {
				$formName = 'ctEditForm';
				$formName .= $this->ct->Params->ModuleId;
			}
		}

		$ct_getUploader = 'ct_getUploader(' . $this->field->id . ',"' . $urlString . '",' . $max_file_size . ',"jpg jpeg png gif svg webp","' . $formName . '",false,"ct_fileuploader_'
			. $this->field->fieldname . '","ct_eventsmessage_' . $this->field->fieldname . '","' . $fileId . '","'
			. $this->attributes['id'] . '","ct_ubloadedfile_box_' . $this->field->fieldname . '");';

		$ct_fileuploader = '<div id="ct_fileuploader_' . $this->field->fieldname . '"></div>';
		$ct_eventsMessage = '<div id="ct_eventsmessage_' . $this->field->fieldname . '"></div>';

		$inputBoxFieldName = '<input type="hidden" name="' . $this->attributes['id'] . '" id="' . $this->attributes['id'] . '" value="" ' . ($this->field->isrequired == 1 ? ' class="required"' : '') . ' />';
		$inputBoxFieldName_FileName = '<input type="hidden" name="' . $this->attributes['id'] . '_filename" id="' . $this->attributes['id'] . '_filename" value="" />';

		return '<div style="' . $style . '"' . ($this->field->isrequired == 1 ? ' class="inputbox required"' : '') . ' id="' . $element_id . '" '
			. 'data-type="' . $this->field->type . '" '
			. 'data-label="' . $this->field->title . '" '
			. 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
			. 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" >'
			. $ct_fileuploader . $ct_eventsMessage
			. '<script>
                ' . $ct_getUploader . '
           </script>
           ' . $inputBoxFieldName . $inputBoxFieldName_FileName
			. common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($max_file_size) . '</div>';
	}
}