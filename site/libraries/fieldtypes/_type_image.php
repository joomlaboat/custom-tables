<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\DataTypes\Tree;
use CustomTables\Field;
use Joomla\CMS\Factory;

class CT_FieldTypeTag_image
{
    static public function getImageSRCLayoutView(array $option_list, ?string $rowValue, array $params, string &$imageSrc, string &$imageTag): bool
    {
        if ($rowValue !== null and $rowValue !== '' and is_numeric($rowValue) and intval($rowValue) < 0)
            $rowValue = -intval($rowValue);

        $conf = Factory::getConfig();
        $sitename = $conf->get('config.sitename');

        $option = $option_list[0];
        $ImageFolder_ = CustomTablesImageMethods::getImageFolder($params);
        $ImageFolderWeb = str_replace(DIRECTORY_SEPARATOR, '/', $ImageFolder_);
        $ImageFolder = str_replace('/', DIRECTORY_SEPARATOR, $ImageFolder_);
        $imageSrc = '';
        $imageTag = '';

        if ($option == '' or $option == '_esthumb' or $option == '_thumb') {
            $prefix = '_esthumb';

            $imageFileExtension = 'jpg';
            $imageFileWeb = JURI::root() . $ImageFolderWeb . '/' . $prefix . '_' . $rowValue . '.' . $imageFileExtension;
            $imageFile = $ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFileExtension;
            if (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile)) {
                $imageTag = '<img src="' . $imageFileWeb . '" width="150" height="150" alt="' . $sitename . '" title="' . $sitename . '" />';
                $imageSrc = $imageFileWeb;
                return true;
            }
            return false;
        } elseif ($option == '_original') {
            $prefix = '_original';
            $imageName = $ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue;

            $imgMethods = new CustomTablesImageMethods;

            $imageFileExtension = $imgMethods->getImageExtension(JPATH_SITE . DIRECTORY_SEPARATOR . $imageName);

            if ($imageFileExtension != '') {
                $imageFileWeb = JURI::root() . $ImageFolderWeb . '/' . $prefix . '_' . $rowValue . '.' . $imageFileExtension;
                $imageTag = '<img src="' . $imageFileWeb . '" alt="' . $sitename . '" title="' . $sitename . '" />';

                $imageSrc = $imageFileWeb;
                return true;
            }
            return false;
        }

        $prefix = $option;
        $imgMethods = new CustomTablesImageMethods;
        $imageName = $ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue;

        $imageFileExtension = $imgMethods->getImageExtension(JPATH_SITE . DIRECTORY_SEPARATOR . $imageName);
        //--- WARNING - ERROR -- REAL EXT NEEDED - IT COMES FROM OPTIONS
        $imageFile = JURI::root() . $ImageFolderWeb . '/' . $prefix . '_' . $rowValue . '.' . $imageFileExtension;
        $imageSizes = $imgMethods->getCustomImageOptions($params[0]);

        foreach ($imageSizes as $img) {
            if ($img[0] == $option) {
                if ($imageFile != '') {
                    $imageTag = '<img src="' . $imageFile . '" ' . ($img[1] > 0 ? 'width="' . $img[1] . '"' : '') . ' ' . ($img[2] > 0 ? 'height="' . $img[2] . '"' : '') . ' alt="' . $sitename . '" title="' . $sitename . '" />';
                    $imageSrc = $imageFile;
                    return true;
                }
            }
        }
        return false;
    }

    static public function get_image_type_value(Field $field, string $realidfieldname, ?string $listing_id): ?string
    {
        $imageMethods = new CustomTablesImageMethods;
        $ImageFolder = CustomTablesImageMethods::getImageFolder($field->params);
        $jinput = Factory::getApplication()->input;
        $fileId = $jinput->post->getString($field->comesfieldname);

        if ($listing_id == null or $listing_id == '' or (is_numeric($listing_id) and intval($listing_id) < 0)) {
            $value = $imageMethods->UploadSingleImage('', $fileId, $field->realfieldname, JPATH_SITE . DIRECTORY_SEPARATOR
                . $ImageFolder, $field->params, $field->ct->Table->realtablename, $field->ct->Table->realidfieldname);
        } else {

            $ExistingImage = Tree::isRecordExist($listing_id, $realidfieldname, $field->realfieldname, $field->ct->Table->realtablename);
            $value = $imageMethods->UploadSingleImage($ExistingImage, $fileId, $field->realfieldname,
                JPATH_SITE . DIRECTORY_SEPARATOR . $ImageFolder, $field->params, $field->ct->Table->realtablename, $field->ct->Table->realidfieldname);
        }

        if ($value == "-1" or $value == "2") {
            // -1 if file extension not supported
            // 2 if file already exists
            Factory::getApplication()->enqueueMessage('Could not upload image file.', 'error');
            $value = null;
        }
        return $value;
    }

    public static function renderImageFieldBox(Field $field, string $prefix, ?array $row): string
    {
        $ImageFolder = CustomTablesImageMethods::getImageFolder($field->params);
        $imageFile = '';
        $isShortcut = false;
        $imageSRC = CT_FieldTypeTag_image::getImageSRC($row, $field->realfieldname, $ImageFolder, $imageFile, $isShortcut);
        $result = '<div class="esUploadFileBox" style="vertical-align:top;">';

        if ($imageFile != '')
            $result .= CT_FieldTypeTag_image::renderImageAndDeleteOption($field, $prefix, $imageSRC, $isShortcut);

        $result .= CT_FieldTypeTag_image::renderUploader($field, $prefix);
        $result .= '</div>';
        return $result;
    }

    public static function getImageSRC(?array $row, string $realFieldName, string $ImageFolder, string &$imageFile, bool &$isShortcut): string
    {
        $isShortcut = false;
        if (isset($row[$realFieldName]) and $row[$realFieldName] !== false and $row[$realFieldName] !== '' and $row[$realFieldName] !== '0') {
            $img = $row[$realFieldName];

            if (is_numeric($img) and intval($img) < 0) {
                $isShortcut = true;
                $img = intval($img);
            }

            $imageFile_ = $ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $img;
            $imageSrc_ = str_replace(DIRECTORY_SEPARATOR, '/', $ImageFolder) . '/_esthumb_' . $img;
        } else {
            $imageFile_ = '';
            $imageSrc_ = '';
        }

        if (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile_ . '.jpg')) {
            $imageFile = $imageFile_ . '.jpg';
            $imageSrc = $imageSrc_ . '.jpg';
        } elseif (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile_ . '.png')) {
            $imageFile = $imageFile_ . '.png';
            $imageSrc = $imageSrc_ . '.png';
        } elseif (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile_ . '.webp')) {
            $imageFile = $imageFile_ . '.webp';
            $imageSrc = $imageSrc_ . '.webp';
        } else {
            $imageFile = '';
            $imageSrc = '';
        }

        return JURI::root() . $imageSrc;
    }

    protected static function renderImageAndDeleteOption(Field $field, string $prefix, string $imageSrc, bool $isShortcut): string
    {
        $result = '<div style="" id="ct_uploadedfile_box_' . $field->fieldname . '">'
            . '<img src="' . $imageSrc . '" alt="Uploaded Image" width="150" id="ct_uploadfile_box_' . $field->fieldname . '_image" /><br/>';

        if (!$field->isrequired)
            $result .= '<input type="checkbox" name="' . $prefix . $field->fieldname . '_delete" id="' . $prefix . $field->fieldname . '_delete" value="true">'
                . ' Delete ' . ($isShortcut ? 'Shortcut' : 'Image');

        $result .= '</div>';

        return $result;
    }

    protected static function renderUploader($field, $prefix): string
    {
        $max_file_size = JoomlaBasicMisc::file_upload_max_size();
        $fileId = JoomlaBasicMisc::generateRandomString();
        $style = 'border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;margin:10px;';//vertical-align:top;
        $element_id = 'ct_uploadfile_box_' . $field->fieldname;

        $urlString = JURI::root(true) . '/index.php?option=com_customtables&view=fileuploader&tmpl=component&' . $field->fieldname . '_fileid=' . $fileId
            . '&Itemid=' . $field->ct->Params->ItemId
            . (is_null($field->ct->Params->ModuleId) ? '' : '&ModuleId=' . $field->ct->Params->ModuleId)
            . '&fieldname=' . $field->fieldname;

        $ct_getUploader = 'ct_getUploader(' . $field->id . ',"' . $urlString . '",' . $max_file_size . ',"jpg jpeg png gif svg webp","eseditForm",false,"ct_fileuploader_'
            . $field->fieldname . '","ct_eventsmessage_' . $field->fieldname . '","' . $fileId . '","' . $prefix . $field->fieldname . '","ct_ubloadedfile_box_' . $field->fieldname . '");';

        $ct_fileuploader = '<div id="ct_fileuploader_' . $field->fieldname . '"></div>';
        $ct_eventsmessage = '<div id="ct_eventsmessage_' . $field->fieldname . '"></div>';

        $inputBoxFieldName = '<input type="hidden" name="' . $prefix . $field->fieldname . '" id="' . $prefix . $field->fieldname . '" value="" ' . ($field->isrequired ? ' class="required"' : '') . ' />';
        $inputBoxFieldName_FileName = '<input type="hidden" name="' . $prefix . $field->fieldname . '_filename" id="' . $prefix . $field->fieldname . '_filename" value="" />';

        return '<div style="' . $style . '"' . ($field->isrequired ? ' class="inputbox required"' : '') . ' id="' . $element_id . '" '
            . 'data-label="' . $field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $field->valuerulecaption) . '" >'
            . $ct_fileuploader . $ct_eventsmessage
            . '<script>
                ' . $ct_getUploader . '
           </script>
           ' . $inputBoxFieldName . $inputBoxFieldName_FileName . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . JoomlaBasicMisc::formatSizeUnits($max_file_size) . '</div>';
    }

    protected static function renderUploaderLimitations(): string
    {
        $max_file_size = JoomlaBasicMisc::file_upload_max_size();

        return '
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;">
				' . JoomlaBasicMisc::JTextExtended("MIN SIZE") . ': 10px x 10px<br/>
				' . JoomlaBasicMisc::JTextExtended("MAX SIZE") . ': 1000px x 1000px<br/>
				' . JoomlaBasicMisc::JTextExtended("COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE") . ': ' . JoomlaBasicMisc::formatSizeUnits($max_file_size) . '<br/>
				' . JoomlaBasicMisc::JTextExtended("FORMAT") . ': JPEG, GIF, PNG, WEBP
				</div>';
    }

    //Drupal has this implemented fairly elegantly:
    //https://stackoverflow.com/questions/1.6.1.1/php-get-actual-maximum-upload-size

    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
}
