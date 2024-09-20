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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

defined('_JEXEC') or die();

class InputBox_file extends BaseInputBox
{
    function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
    {
        $processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
            . DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php';
        require_once($processor_file);

        parent::__construct($ct, $field, $row, $option_list, $attributes);
    }

    function render(): string
    {
        if (!$this->ct->isRecordNull($this->row)) {
            $file = strval($this->row[$this->field->realfieldname]);
        } else
            $file = '';

        $result = '<div class="esUploadFileBox" style="vertical-align:top;">';

        if ($this->field->type == 'blob') {

            $fileSize = intval($file);

            if ($fileSize != 0)
                $result .= $this->renderBlobAndDeleteOption($fileSize);
        } else {
            $listing_id = null;
            if ($this->row !== null)
                $listing_id = $this->row[$this->ct->Table->realidfieldname];

            $result .= $this->renderFileAndDeleteOption($file, $listing_id);
        }

        $result .= $this->renderUploader();

        $result .= '</div>';
        return $result;
    }

    protected function renderBlobAndDeleteOption(int $fileSize): string
    {
        $listing_id = $this->row[$this->ct->Table->realidfieldname];

        if ($fileSize == '')
            return '';

        $processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
            . DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'blob.php';
        require_once($processor_file);

        $result = '<div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;" id="ct_uploadedfile_box_' . $this->field->fieldname . '">';

        $filename = Value_blob::getBlobFileName($this->field, $fileSize, $this->row, $this->ct->Table->fields);
        $filename_Icon = Value_file::process($filename, $this->field, ['', 'icon-filename-link', 48, '_blank'], $listing_id, false, $fileSize);

        $result .= $filename_Icon . '<br/><br/>';

        if ($this->field->isrequired !== 1)
            $result .= '<input type="checkbox" name="' . $this->field->prefix . $this->field->fieldname . '_delete" id="' . $this->field->prefix . $this->field->fieldname . '_delete" value="true">'
                . ' Delete Data';

        $result .= '</div>';

        return $result;
    }

    protected function renderFileAndDeleteOption(string $file, ?string $listing_id): string
    {
        if ($file == '')
            return '';

        if ($this->field->params === null or count($this->field->params) == 0)
            return '<p style="background-color:red;color:white;">Folder not selected</p>';

        $result = '<div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;" id="ct_uploadedfile_box_' . $this->field->fieldname . '">';
        $filename_Icon = Value_file::process($file, $this->field, ['', 'icon-filename-link', 48, '_blank'], $listing_id);

        $result .= $filename_Icon . '<br/><br/>';

        if ($this->field->isrequired !== 1)
            $result .= '<input type="checkbox" name="' . $this->field->prefix . $this->field->fieldname . '_delete" id="' . $this->field->prefix . $this->field->fieldname . '_delete" value="true">'
                . ' Delete File';

        $result .= '</div>';

        return $result;
    }

    protected function renderUploader(): string
    {
        $result = '';

        $style = 'border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;margin:10px;';//vertical-align:top;
        $element_id = 'ct_uploadfile_box_' . $this->field->fieldname;

        if ($this->field->type == 'file')
            $fileExtensions = $this->field->params[2] ?? '';
        elseif ($this->field->type == 'blob')
            $fileExtensions = $this->field->params[1] ?? '';
        else
            return false;

        require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'uploader.php');

        $accepted_file_types = FileUploader::getAcceptedFileTypes($fileExtensions);

        if ($this->field->type == 'blob') {

            if ($this->field->params[0] == 'tiny')
                $custom_max_size = 255;
            elseif ($this->field->params[0] == 'medium')
                $custom_max_size = 16777215;
            elseif ($this->field->params[0] == 'long')
                $custom_max_size = 4294967295;
            else
                $custom_max_size = 65535;
        } else {
            $custom_max_size = (int)$this->field->params[0];

            if ($custom_max_size != 0 and $custom_max_size < 10000)
                $custom_max_size = $custom_max_size * 1000000; //to change 20 to 20MB
        }

        $max_file_size = CTMiscHelper::file_upload_max_size($custom_max_size);

        $file_id = common::generateRandomString();
        $URLString = common::UriRoot(true) . '/index.php?option=com_customtables&view=fileuploader&tmpl=component&' . $this->field->fieldname
            . '_fileid=' . $file_id
            . '&Itemid=' . $this->field->ct->Params->ItemId
            . (is_null($this->field->ct->Params->ModuleId) ? '' : '&ModuleId=' . $this->field->ct->Params->ModuleId)
            . '&fieldname=' . $this->field->fieldname;

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

            $scriptParams = [
                $this->field->id,
                '"' . $URLString . '"',
                $max_file_size,
                '"' . $accepted_file_types . '"',
                '"' . $formName . '"',
                'false',
                '"ct_fileuploader_' . $this->field->fieldname . '"',
                '"ct_eventsmessage_' . $this->field->fieldname . '"',
                '"' . $file_id . '"',
                '"' . $this->field->prefix . $this->field->fieldname . '"',
                '"ct_ubloadedfile_box_' . $this->field->fieldname . '"'
            ];

            $ct_fileuploader = '<div id="ct_fileuploader_' . $this->field->fieldname . '" style="display: inline;"></div>';
            $ct_eventsMessage = '<div id="ct_eventsmessage_' . $this->field->fieldname . '" style="display: inline;"></div>';

            $result .= '<input type="hidden" name="' . $this->field->prefix . $this->field->fieldname . '" id="' . $this->field->prefix . $this->field->fieldname . '" value="" ' . ($this->field->isrequired == 1 ? ' class="required"' : '') . ' />';
            $result .= '<input type="hidden" name="' . $this->field->prefix . $this->field->fieldname . '_filename" id="' . $this->field->prefix . $this->field->fieldname . '_filename" value="" />';
            $result .= '<input type="hidden" name="' . $this->field->prefix . $this->field->fieldname . '_data" id="' . $this->field->prefix . $this->field->fieldname . '_data" value="" />';

            $result .= common::translate('COM_CUSTOMTABLES_PERMITTED_FILE_TYPES') . ': ' . $accepted_file_types . '<br/>';
            $result .= common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($max_file_size);

            $joomla_params = ComponentHelper::getParams('com_customtables');
            $GoogleDriveAPIKey = $joomla_params->get('GoogleDriveAPIKey');
            $GoogleDriveClientId = $joomla_params->get('GoogleDriveClientId');

            $result .= '<div style="vertical-align: top;">';

            if ($GoogleDriveAPIKey !== '' and $GoogleDriveClientId !== '')
                $result .= '<br/><button class="ajax-file-upload" data-prefix="' . $this->field->prefix . '" data-accept="' . $accepted_file_types . '" id="CustomTablesGoogleDrivePick_' . $this->field->fieldname . '">Load from Google Drive</button>';

            $result .= $ct_fileuploader;
            $result .= '</div>';
            $result .= $ct_eventsMessage;
            $result .= '<script>ct_getUploader(' . implode(',', $scriptParams) . ')</script>';

            if ($GoogleDriveAPIKey !== '' and $GoogleDriveClientId !== '') {
                $app = Factory::getApplication();
                $document = $app->getDocument();
                $document->addCustomTag('<script src="https://apis.google.com/js/api.js"></script>');
                $document->addCustomTag('<script src="https://accounts.google.com/gsi/client"></script>');

                $result .= '
<script>
    document.getElementById("CustomTablesGoogleDrivePick_' . $this->field->fieldname . '").addEventListener("click", () => {
        event.preventDefault(); // Prevent the default action

        if (!CTEditHelper.GoogleDriveAccessToken) {
            CTEditHelper.GoogleDriveTokenClient["' . $this->field->fieldname . '"].requestAccessToken({ prompt: "consent" });
        } else {
            CTEditHelper.GoogleDriveLoadPicker("' . $this->field->fieldname . '","' . $GoogleDriveAPIKey . '", CTEditHelper.GoogleDriveAccessToken);
        }
        return false;
    });
    
    gapi.load("client", CTEditHelper.GoogleDriveInitClient("' . $this->field->fieldname . '","' . $GoogleDriveAPIKey . '","' . $GoogleDriveClientId . '"));

</script>';

            }

        } elseif (defined('WPINC')) {

            $GoogleDriveAPIKey = get_option('customtables-googledriveapikey') ?? '';
            $GoogleDriveClientId = get_option('customtables-googledriveclientid') ?? '';

            if ($GoogleDriveAPIKey !== '' and $GoogleDriveClientId !== '') {
                $result .= '<br/><button type="button" class="" data-prefix="' . $this->field->prefix . '" data-accept="' . $accepted_file_types . '" id="CustomTablesGoogleDrivePick_' . $this->field->fieldname . '">Load from Google Drive</button>';

                $result .= '<div id="ct_eventsmessage_' . $this->field->fieldname . '" style="display: inline;"></div>';

                $result .= '
<script>
    document.getElementById("CustomTablesGoogleDrivePick_' . $this->field->fieldname . '").addEventListener("click", () => {
        event.preventDefault(); // Prevent the default action

        if (!CTEditHelper.GoogleDriveAccessToken) {
            CTEditHelper.GoogleDriveTokenClient["' . $this->field->fieldname . '"].requestAccessToken({ prompt: "consent" });
        } else {
            CTEditHelper.GoogleDriveLoadPicker("' . $this->field->fieldname . '","' . $GoogleDriveAPIKey . '", CTEditHelper.GoogleDriveAccessToken);
        }
        return false;
    });
    
    window.addEventListener("load", function() {
        // Your code here
        gapi.load("client", CTEditHelper.GoogleDriveInitClient("' . $this->field->fieldname . '","' . $GoogleDriveAPIKey . '","' . $GoogleDriveClientId . '"));
    });
</script>';
            }

            $types = explode(' ', $accepted_file_types);
            $accepted_file_types_string = '.' . implode(',.', $types);

            $result .= '<input type="file" name="' . $this->field->prefix . $this->field->fieldname . '" accept="' . $accepted_file_types_string . '" max-size="' . $max_file_size . '" /><br/>';
            $result .= '<input type="hidden" name="' . $this->field->prefix . $this->field->fieldname . '_filename" id="' . $this->field->prefix . $this->field->fieldname . '_filename" value="" />';
            $result .= '<input type="hidden" name="' . $this->field->prefix . $this->field->fieldname . '_data" id="' . $this->field->prefix . $this->field->fieldname . '_data" value="" />';

            $result .= common::translate('COM_CUSTOMTABLES_PERMITTED_FILE_TYPES') . ': ' . $accepted_file_types . '<br/>';
            $result .= common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($max_file_size);
        }

        return '<div style="' . $style . '"' . ($this->field->isrequired == 1 ? ' class="inputbox required"' : '') . ' id="' . $element_id . '" '
            . 'data-type="' . $this->field->type . '" '
            . 'data-label="' . $this->field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule ?? '') . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption ?? '') . '" >' . $result . '</div>';
    }
}