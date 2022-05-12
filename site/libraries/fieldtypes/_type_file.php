<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Field;

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'uploader.php');

class CT_FieldTypeTag_file
{
    public static function process($filename, CustomTables\Field $field, $option_list, $record_id, $filename_only = false)
    {
        if ($filename == '')
            return '';

        if ($field->type == 'filelink')
            $FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[0]);
        else
            $FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[1]);

        $filepath = $FileFolder . '/' . $filename;

        if (!isset($option_list[2]))
            $icon_size = '32';
        else
            $icon_size = $option_list[2];

        if ($icon_size != "16" and $icon_size != "32" and $icon_size != "48")
            $icon_size = '32';

        $parts = explode('.', $filename);
        $fileExtension = end($parts);
        $icon = '/components/com_customtables/libraries/customtables/media/images/fileformats/' . $icon_size . 'px/' . $fileExtension . '.png';
        $icon_File_Path = JPATH_SITE . $icon;
        if (!file_exists($icon_File_Path))
            $icon = '';

        $how_to_process = $option_list[0];

        if ($how_to_process != '')
            $filepath = CT_FieldTypeTag_file::get_private_file_path($filename, $how_to_process, $filepath, $record_id, $field->id, $field->ct->Table->tableid, $filename_only);


        $target = '';
        if (isset($option_list[3])) {
            if ($option_list[3] == '_blank')
                $target = ' target="_blank"';
            if ($option_list[3] == 'savefile') {
                if (!str_contains($filepath, '?'))
                    $filepath .= '?';
                else
                    $filepath .= '&';

                $filepath .= 'savefile=1'; //Will add HTTP Header: @header("Content-Disposition: attachment; filename=\"".$filename."\"");
            }
        }

        $output_format = '';
        if (isset($option_list[1]))
            $output_format = $option_list[1];

        switch ($output_format) {

            case '':
            case 'link':
                //Link Only
                return $filepath;

            case 'icon-filename-link':
                //Clickable Icon and File Name
                return '<a href="' . $filepath . '"' . $target . '>'
                    . ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : '')
                    . '<span>' . $filename . '</span></a>';

            case 'icon-link':
                //Clickable Icon
                return '<a href="' . $filepath . '"' . $target . '>' . ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : $filename) . '</a>';//show file name if icon not available

            case 'filename-link':
                //Clickable File Name
                return '<a href="' . $filepath . '"' . $target . '>' . $filename . '</a>';

            case 'link-anchor':
                //Clickable Link
                return '<a href="' . $filepath . '"' . $target . '>' . $filepath . '</a>';

            case 'icon':
                return ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : '');//show nothing is icon not available

            case 'link-to-icon':
                return $icon;//show nothing if icon not available

            case 'filename':
                return $filename;

            case 'extension':
                return $fileExtension;

            default:
                return $filepath;
        }
    }

    static protected function get_security_letter($how_to_process): string
    {
        switch ($how_to_process) {
            case 'timelimited':
                return 'd';

            case 'timelimited_longterm':
                return 'e';

            case 'hostlimited':
                return 'f';

            case 'hostlimited_longterm':
                return 'g';

            case 'private':
                return 'h';

            case 'private_longterm':
                return 'i';

            default:
                return '';
        }
    }

    static protected function get_private_file_path($rowValue, $how_to_process, $filepath, $recid, $fieldid, $tableid, $filename_only = false): string
    {
        $security = CT_FieldTypeTag_file::get_security_letter($how_to_process);

        //make the key
        $key = CT_FieldTypeTag_file::makeTheKey($filepath, $security, $recid, $fieldid, $tableid);

        $currentURL = JoomlaBasicMisc::curPageURL();
        $currentURL = JoomlaBasicMisc::deleteURLQueryOption($currentURL, 'returnto');

        //prepare new file name that includes the key
        $fna = explode('.', $rowValue);
        $filetype = $fna[count($fna) - 1];
        array_splice($fna, count($fna) - 1);
        $filename = implode('.', $fna);
        $filepath = $filename . '_' . $key . '.' . $filetype;

        if (!$filename_only) {
            if (str_contains($currentURL, '?')) {
                $filepath = $currentURL . '&file=' . $filepath;
            } else {
                if ($currentURL[strlen($currentURL) - 1] != '/')
                    $filepath = $currentURL . '/' . $filepath;
                else
                    $filepath = $currentURL . $filepath;
            }
        }

        return $filepath;
    }

    static public function get_file_type_value(CustomTables\Field &$field, $listing_id)
    {
        $jinput = JFactory::getApplication()->input;

        if ($field->type == 'filelink')
            $FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[0]);
        else
            $FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[1]);

        $file_id = $jinput->post->get($field->comesfieldname, '', 'STRING');

        $value_found = false;

        $filepath = str_replace('/', DIRECTORY_SEPARATOR, $FileFolder);
        if(substr($filepath,0,1) == DIRECTORY_SEPARATOR)
            $filepath = JPATH_SITE . $filepath;
        else
            $filepath = JPATH_SITE . DIRECTORY_SEPARATOR . $filepath;

        if ($listing_id == 0) {
            $value = CT_FieldTypeTag_file::UploadSingleFile('', $file_id, $field, JPATH_SITE . $FileFolder);
        } else {
            $to_delete = $jinput->post->get($field->comesfieldname . '_delete', '', 'CMD');

            $ExistingFile = $field->ct->Table->getRecordFieldValue($listing_id, $field->realfieldname);

            if ($to_delete == 'true') {
                if ($ExistingFile != '' and !CT_FieldTypeTag_file::checkIfTheFileBelongsToAnotherRecord($ExistingFile, $field)) {
                    $filename_full = $filepath . DIRECTORY_SEPARATOR . $ExistingFile;

                    if (file_exists($filename_full))
                        unlink($filename_full);
                }

                $value_found = true;
            }

            $value = CT_FieldTypeTag_file::UploadSingleFile($ExistingFile, $file_id, $field, JPATH_SITE . $FileFolder);
        }
        if ($value)
            $value_found = true;

        if ($value_found)
            return $value;

        return null;
    }

    protected static function UploadSingleFile($ExistingFile, $file_id, $field, $FileFolder)//,$realtablename='-options')
    {
        $fileextensions = $field->params[2] ?? '';

        if ($file_id != '') {
            $accepted_file_types = explode(' ', ESFileUploader::getAcceptedFileTypes($fileextensions));

            $accepted_filetypes = array();

            foreach ($accepted_file_types as $filetype) {
                $mime = ESFileUploader::get_mime_type('1.' . $filetype);
                $accepted_filetypes[] = $mime;

                if ($filetype == 'docx')
                    $accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                elseif ($filetype == 'xlsx')
                    $accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                elseif ($filetype == 'pptx')
                    $accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            }

            $uploadedfile = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $file_id;

            $is_base64encoded = JFactory::getApplication()->input->get('base64encoded', '', 'CMD');
            if ($is_base64encoded == "true") {
                $src = $uploadedfile;

                $jinput = JFactory::getApplication()->input;
                $file = $jinput->post->get($field->comesfieldname, '', 'STRING');
                $dst = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'decoded_' . basename($file['name']);
                CustomTablesFileMethods::base64file_decode($src, $dst);
                $uploadedfile = $dst;
            }

            if ($ExistingFile != '' and !CT_FieldTypeTag_file::checkIfTheFileBelongsToAnotherRecord($ExistingFile, $field)) {
                //Delete Old File
                $filename_full = $FileFolder . DIRECTORY_SEPARATOR . $ExistingFile;

                if (file_exists($filename_full))
                    unlink($filename_full);
            }

            if (!file_exists($uploadedfile))
                return false;

            $mime = mime_content_type($uploadedfile);

            $parts = explode('.', $uploadedfile);
            $fileextension = end($parts);
            if ($mime == 'application/zip' and $fileextension != 'zip') {
                //could be docx, xlsx, pptx
                $mime = ESFileUploader::checkZIPfile_X($uploadedfile, $fileextension);
            }

            if (in_array($mime, $accepted_filetypes)) {

                $new_filename = CT_FieldTypeTag_file::getCleanAndAvailableFileName($file_id, $FileFolder);
                $new_filename_path = str_replace('/', DIRECTORY_SEPARATOR, $FileFolder . DIRECTORY_SEPARATOR . $new_filename);

                if (@copy($uploadedfile, $new_filename_path)) {
                    unlink($uploadedfile);

                    //Copied
                    return $new_filename;
                } else {
                    unlink($uploadedfile);

                    //Cannot copy
                    return false;
                }
            } else {
                unlink($uploadedfile);
                return false;
            }
        }
        return false;
    }

    static protected function checkIfTheFileBelongsToAnotherRecord(string $filename, CustomTables\Field $field): bool
    {
        $db = JFactory::getDBO();
        $query = 'SELECT * FROM ' . $field->ct->Table->realtablename . ' WHERE ' . $field->realfieldname . '=' . $db->quote($filename) . ' LIMIT 2';

        $db->setQuery($query);
        $db->execute();

        return $db->getNumRows() > 1;
    }

    static protected function getCleanAndAvailableFileName(string $filename, string $FileFolder): string
    {
        $parts = explode('_', $filename);
        if (count($parts) < 4)
            return '';

        $parts[0] = '';
        $parts[1] = '';
        $parts[2] = '';

        $new_filename = trim(implode(' ', $parts));

        //Clean Up file name
        $filename_raw = strtolower($new_filename);
        $filename_raw = str_replace(' ', '_', $filename_raw);
        $filename_raw = str_replace('-', '_', $filename_raw);
        $filename = preg_replace("/[^a-z0-9._]/", "", $filename_raw);

        $i = 0;
        $filename_new = $filename;
        while(1) {

            if (file_exists($FileFolder . DIRECTORY_SEPARATOR . $filename_new)) {
                //increase index
                $i++;
                $filename_new = str_replace('.', '-' . $i . '.', $filename);
            } else
                break;
        }

        return $filename_new;
    }

    public static function renderFileFieldBox(&$ct, array &$fieldrow, array &$row, $class): string
    {
        $field = new Field($ct, $fieldrow);

        if (count($row) > 0 and $row[$ct->Table->realidfieldname] != '' and (is_numeric($row[$ct->Table->realidfieldname]) and $row[$ct->Table->realidfieldname] != 0)) {
            $file = strval($row[$field->realfieldname]);
        }
        else
            $file = '';

        $result = '<div class="esUploadFileBox" style="vertical-align:top;">';

        $result .= CT_FieldTypeTag_file::renderFileAndDeleteOption($file, $field);
        $result .= CT_FieldTypeTag_file::renderUploader($field);

        $result .= '</div>';
        return $result;
    }

    protected static function renderFileAndDeleteOption(string $file, &$field): string
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

        $image_src = JURI::root(true) . '/components/com_customtables/libraries/customtables/media/images/fileformats/48px/' . $file_extension . '.png';

        $result = '
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;" id="ct_uploadedfile_box_' . $field->fieldname . '">';

        $result .= '<a href="' . $link . '" target="_blank" title="' . $file . '"><img src="' . $image_src . '" width="48" alt="' . $file . '" /></a><br/>';

        if (!$field->isrequired)
            $result .= '<input type="checkbox" name="' . $field->prefix . $field->fieldname . '_delete" id="' . $field->prefix . $field->fieldname . '_delete" value="true">'
                . ' Delete File';

        $result .= '
                </div>';

        return $result;
    }

    protected static function renderUploader(&$field): string
    {
        $file_extensions = $field->params[2] ?? '';

        $accepted_file_types = ESFileUploader::getAcceptedFileTypes($file_extensions);

        $custom_max_size = (int)$field->params[0];
        if ($custom_max_size != 0 and $custom_max_size < 10000)
            $custom_max_size = $custom_max_size * 1000000; //to change 20 to 20MB

        $max_file_size = JoomlaBasicMisc::file_upload_max_size($custom_max_size);

        $file_id = JoomlaBasicMisc::generateRandomString();

        return '
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;">
                
                	<div id="ct_fileuploader_' . $field->fieldname . '"></div>
                    <div id="ct_eventsmessage_' . $field->fieldname . '"></div>
                	<script>
                        UploadFileCount=1;

                    	let urlstr' . $field->id .' ="' . JURI::root(true) . '/index.php?option=com_customtables&view=fileuploader&tmpl=component&' . $field->fieldname
            . '_fileid=' . $file_id . '&Itemid=' . $field->ct->Env->Itemid . '&fieldname=' . $field->fieldname . '";
                    	
						ct_getUploader(' . $field->id . ',urlstr' . $field->id .',' . $max_file_size . ',"' . $accepted_file_types . '","eseditForm",false,"ct_fileuploader_' . $field->fieldname . '","ct_eventsmessage_'
            . $field->fieldname . '","' . $file_id . '","' . $field->prefix . $field->fieldname . '","ct_ubloadedfile_box_' . $field->fieldname . '")

                    </script>
                    <input type="hidden" name="' . $field->prefix . $field->fieldname . '" id="' . $field->prefix . $field->fieldname . '" value="" />
                    ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_FILE_TYPES') . ': ' . $accepted_file_types . '<br/>
					' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE') . ': ' . JoomlaBasicMisc::formatSizeUnits($max_file_size) . '
                </div>
                ';
    }

    public static function getFileFolder(string $folder): string
    {
        if ($folder == '')
            $folder = '/images';    //default folder
        elseif ($folder[0] == '/') {

            //delete trailing slash if found
            $p = substr($folder, strlen($folder) - 1, 1);
            if ($p == '/')
                $folder = substr($folder, 0, strlen($folder) - 1);
        } else {
            $folder = '/' . $folder;
            if (strlen($folder) > 8)//add /images to relative path
            {
                $p = substr($folder, 0, 8);
                if ($p != '/images/')
                    $folder = '/images' . $folder;
            } else {
                $folder = '/images' . $folder;
            }

            //delete trailing slash if found
            $p = substr($folder, strlen($folder) - 1, 1);
            if ($p == '/')
                $folder = substr($folder, 0, strlen($folder) - 1);
        }

        $folderPath = JPATH_SITE . str_replace('/', DIRECTORY_SEPARATOR, $folder); //relative path

        //Create folder if not exists
        if (!file_exists($folderPath))
            mkdir($folderPath, 0755, true);

        return $folder;
    }

    public static function wrong(): bool
    {
        JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
        return false;
    }

    public static function process_file_link($filename): void
    {

        $jinput = JFactory::getApplication()->input;
        $parts = explode('.', $filename);

        if (count($parts) == 1)
            CT_FieldTypeTag_file::wrong();

        array_splice($parts, count($parts) - 1);
        $filename_without_ext = implode('.', $parts);

        $parts2 = explode('_', $filename_without_ext);
        $key = $parts2[count($parts2) - 1];

        $key_parts = explode('c', $key);

        if (count($key_parts) == 1)
            CT_FieldTypeTag_file::wrong();

        $jinput->set('key', $key);

        $key_params = $key_parts[count($key_parts) - 1];

        //TODO: improve it. Get $security from layout, somehow
        //security letters tells what method used
        $security = 'd';//Time Limited (8-24 minutes)

        if (str_contains($key_params, 'e')) $security = 'e';//Time Limited (1.5 - 4 hours)
        elseif (str_contains($key_params, 'f')) $security = 'f';//Time/Host Limited (8-24 minutes)
        elseif (str_contains($key_params, 'g')) $security = 'g';//Time/Host Limited (1.5 - 4 hours)
        elseif (str_contains($key_params, 'h')) $security = 'h';//Time/Host/User Limited (8-24 minutes)
        elseif (str_contains($key_params, 'i')) $security = 'i';//Time/Host/User Limited (1.5 - 4 hours)

        $jinput->set('security', $security);

        $key_params_a = explode($security, $key_params);
        if (count($key_params_a) != 2)
            CT_FieldTypeTag_file::wrong();

        $listing_id = $key_params_a[0];
        $jinput->set("listing_id", $listing_id);

        if (isset($key_params_a[1])) {
            $fieldid = $key_params_a[1];
            $jinput->set('fieldid', $fieldid);
        }

        if (isset($key_params_a[2])) {
            $tableid = $key_params_a[2];
            $jinput->set('tableid', $tableid);
        }
    }

    public static function makeTheKey(string $filepath, string $security, string $rec_id, string $fieldid, string $tableid): string
    {
        $user = JFactory::getUser();
        $username = $user->get('username');
        $current_user_id = (int)$user->get('id');

        $t = time();
        //prepare augmented timer
        $secs = 1000;
        if ($security == 'e' or $security == 'g' or $security == 'i')
            $secs = 10000;

        $tplus = floor(($t + $secs) / $secs) * $secs;
        $ip = $_SERVER['REMOTE_ADDR'];

        //get secs key char
        $sep = $security;//($secs==1000 ? 'a' : 'b');
        $m2 = 'c' . $rec_id . $sep . $fieldid . $sep . $tableid;

        $m = '';

        //calculate MD5
        if ($security == 'd' or $security == 'e')
            $m = md5($filepath . $tplus);
        elseif ($security == 'f' or $security == 'g')
            $m = md5($filepath . $tplus . $ip);
        elseif ($security == 'h' or $security == 'i')
            $m = md5($filepath . $tplus . $ip . $username . $current_user_id);

        //replace rear part of the hash
        $m3 = substr($m, 0, strlen($m) - strlen($m2));
        return $m3 . $m2;
    }
}
