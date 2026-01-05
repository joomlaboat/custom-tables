<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class InputBox_imagegallery extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(): string
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables'
			. DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'imagegallery.php');

		$listing_id = $this->row[$this->ct->Table->realidfieldname] ?? null;
		$getGalleryRows = Value_imagegallery::getGalleryRows($this->ct->Table->tablename, $this->field->fieldname, $listing_id);
		$image_prefix = '';

		$imageIds = [];
		foreach ($getGalleryRows as $row)
			$imageIds[] = $row->photoid;

		$result = '<input type="hidden" id="' . $this->attributes['id'] . '_uploaded" name="' . $this->attributes['id']
			. '_uploaded" value="' . implode(',', $imageIds) . '" />';

		$imageSRCList = Value_imagegallery::getImageGallerySRCArrayWithIDs($getGalleryRows, $image_prefix, $this->field->fieldname,
			$this->field->params, $this->ct->Table->tableid, true);

		if (count($imageSRCList) > 0) {

			$backGround = "background-image: url('" . CUSTOMTABLES_MEDIA_WEBPATH . "images/icons/bg.png');";
			$result .= '<div style="padding:5px;width:100%;overflow:scroll;border:1px dotted grey;' . $backGround . '">

		<div id="' . $this->ct->Table->fieldInputPrefix . $this->field->fieldname . '_preview" class="image-preview-container">';

			foreach ($imageSRCList as $img) {

				$index = -intval($img['id']);
				$result .= '<div class="preview-item" data-file-index="' . $index . '">';
				$result .= '<img src="' . $img['src'] . '" class="preview-image" />';
				$result .= '<button type="button" title="' . common::translate('COM_CUSTOMTABLES_DELETE') . '" class="remove-btn" onclick="CTEditHelper.ImageGalleryRemoveFile(this, \'' . $this->attributes['id'] . '\', ' . $index . ')">×</button></div>';
			}

			$result .= '</div>
		</div>';

		}

		$result .= '<div class="esUploadFileBox" style="vertical-align:top;">';

		//$result .= $this->renderImageAndDeleteOption($this->field, $image['src'], $image['shortcut']);

		$result .= $this->renderUploader();
		$result .= '</div>';

		return $result;
	}

	protected function renderUploader(): string
	{
		$result = '';

		$max_file_size = CTMiscHelper::file_upload_max_size();
		//$fileId = common::generateRandomString();
		$style = '';//'border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;margin:10px;';//vertical-align:top;
		$element_id = 'ct_uploadfile_box_' . $this->field->fieldname;
		/*
				$urlString = '/index.php?option=com_customtables&view=fileuploader&tmpl=component&' . $this->field->fieldname . '_fileid=' . $fileId
					. '&Itemid=' . $this->field->ct->Params->ItemId
					. (is_null($this->field->ct->Params->ModuleId) ? '' : '&ModuleId=' . $this->field->ct->Params->ModuleId)
					. '&fieldname=' . $this->field->fieldname;
		*/
		/*
				if (defined('_JEXEC')) {
					if (common::clientAdministrator())   //since   3.2
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

					$result .= $inputBoxFieldName . $inputBoxFieldName_FileName
						. common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($max_file_size);

					$result .= $ct_fileuploader . $ct_eventsMessage
						. '<script>
						' . $ct_getUploader . '
				   </script>
				   ';

		*/
		//} elseif (defined('WPINC')) {
		$result .= '<input type="file" id="' . $this->attributes['id'] . '" name="' . $this->attributes['id'] . '[]" multiple accept=".jpg,.jpeg,.png,.gif,.svg,.webp" max-size="' . $max_file_size . '" />';
		$result .= '<div id="' . $this->attributes['id'] . '_previewNew" class="image-preview-container"></div>';

		// Initialize for your specific input
		$result .= '
<script>
            window.onload = function() {
                CTEditHelper.ImageGalleryInitImagePreviews("' . $this->attributes['id'] . '");
            };
            
</script>
';

		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$default_class = 'form-control';
		else
			$default_class = 'inputbox';

		return '<div style="' . $style . '"' . ($this->field->isrequired == 1 ? ' class="' . $default_class . ' required"' : '') . ' id="' . $element_id . '" '
			. 'data-type="' . $this->field->type . '" '
			. 'data-label="' . $this->field->title . '" '
			. 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule ?? '') . '" '
			. 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption ?? '') . '" >' . $result . '</div>';
	}
}