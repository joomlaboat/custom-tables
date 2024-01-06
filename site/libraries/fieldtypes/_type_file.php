<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\Fields;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;

if (file_exists(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'uploader.php'))
	require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'uploader.php');

class CT_FieldTypeTag_file
{
	/*
	static public function get_file_type_value(CustomTables\Field $field, $listing_id): ?string
	{
		if ($field->type == 'filelink')
			$FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[0]);
		else
			$FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[1]);

		$file_id = common::inputPost($field->comesfieldname, '', 'STRING');

		$filepath = str_replace('/', DIRECTORY_SEPARATOR, $FileFolder);
		if (substr($filepath, 0, 1) == DIRECTORY_SEPARATOR)
			$filepath = JPATH_SITE . $filepath;
		else
			$filepath = JPATH_SITE . DIRECTORY_SEPARATOR . $filepath;

		if ($listing_id == 0) {
			$value = CT_FieldTypeTag_file::UploadSingleFile('', $file_id, $field, JPATH_SITE . $FileFolder);
		} else {
			$ExistingFile = $field->ct->Table->getRecordFieldValue($listing_id, $field->realfieldname);

			$to_delete = common::inputPost($field->comesfieldname . '_delete', '', 'CMD');

			if ($to_delete == 'true') {
				$value = true;
				if ($ExistingFile != '' and !CT_FieldTypeTag_file::checkIfTheFileBelongsToAnotherRecord($ExistingFile, $field)) {
					$filename_full = $filepath . DIRECTORY_SEPARATOR . $ExistingFile;

					if (file_exists($filename_full))
						unlink($filename_full);
				}
			}
			$value = CT_FieldTypeTag_file::UploadSingleFile($ExistingFile, $file_id, $field, JPATH_SITE . $FileFolder);
		}
		return $value;
	}
	*/

	static public function get_blob_value(CustomTables\Field $field): ?string
	{
		$file_id = common::inputPost($field->comesfieldname, '', 'STRING');

		if ($file_id == '')
			return null;

		$uploadedFile = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $file_id;

		if (!file_exists($uploadedFile))
			return null;

		$mime = mime_content_type($uploadedFile);

		$parts = explode('.', $uploadedFile);
		$fileExtension = end($parts);

		if ($mime == 'application/zip' and $fileExtension != 'zip') {
			//could be docx, xlsx, pptx
			ESFileUploader::checkZIPfile_X($uploadedFile, $fileExtension);
		}

		$fileData = addslashes(common::getStringFromFile($uploadedFile));

		unlink($uploadedFile);
		return $fileData;
	}

	public static function CheckIfFile2download(&$segments, &$vars): bool
	{
		$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
		require_once($path . 'loader.php');
		CTLoader();

		if (str_contains(end($segments), '.')) {

			//could be a file
			$parts = explode('.', end($segments));
			if (count($parts) >= 2 and strlen($parts[0]) > 0 and strlen($parts[1]) > 0) {

				//probably a file
				$allowedExtensions = explode(' ', 'bin gslides doc docx pdf rtf txt xls xlsx psd ppt pptx mp3 wav ogg jpg bmp ico odg odp ods swf xcf jpeg png gif webp svg ai aac m4a wma flv mpg wmv mov flac txt avi csv accdb zip pages');
				$ext = end($parts);
				if (in_array($ext, $allowedExtensions)) {
					$vars['view'] = 'files';
					$vars['key'] = $segments[0];

					$processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
					require_once($processor_file);

					CT_FieldTypeTag_file::process_file_link(end($segments));
					$vars["listing_id"] = common::inputGetInt("listing_id", 0);
					$vars['fieldid'] = common::inputGetInt('fieldid', 0);
					$vars['security'] = common::inputGetCmd('security', 0);//security level letter (d,e,f,g,h,i)
					$vars['tableid'] = common::inputGetInt('tableid', 0);
					return true;
				}
			}
		}
		return false;
	}

	public static function process_file_link($filename): void
	{
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

		common::inputSet('key', $key);

		$key_params = $key_parts[count($key_parts) - 1];

		//TODO: improve it. Get $security from layout, somehow
		//security letters tells what method used
		$security = 'd';//Time Limited (8-24 minutes)

		if (str_contains($key_params, 'b')) $security = 'b';//Blob - Not limited
		elseif (str_contains($key_params, 'e')) $security = 'e';//Time Limited (1.5 - 4 hours)
		elseif (str_contains($key_params, 'f')) $security = 'f';//Time/Host Limited (8-24 minutes)
		elseif (str_contains($key_params, 'g')) $security = 'g';//Time/Host Limited (1.5 - 4 hours)
		elseif (str_contains($key_params, 'h')) $security = 'h';//Time/Host/User Limited (8-24 minutes)
		elseif (str_contains($key_params, 'i')) $security = 'i';//Time/Host/User Limited (1.5 - 4 hours)

		common::inputSet('security', $security);

		$key_params_a = explode($security, $key_params);
		if (count($key_params_a) != 2)
			CT_FieldTypeTag_file::wrong();

		$listing_id = $key_params_a[0];
		common::inputSet("listing_id", $listing_id);

		if (isset($key_params_a[1])) {
			$fieldid = $key_params_a[1];
			common::inputSet('fieldid', $fieldid);
		}

		if (isset($key_params_a[2])) {
			$tableid = $key_params_a[2];
			common::inputSet('tableid', $tableid);
		}
	}

	public static function wrong(): bool
	{
		Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
		return false;
	}

	public static function UploadSingleFile($ExistingFile, $file_id, $field, $FileFolder): ?string
	{
		if ($field->type == 'file')
			$fileExtensions = $field->params[2] ?? '';
		elseif ($field->type == 'blob')
			$fileExtensions = $field->params[1] ?? '';
		else
			return null;

		if ($file_id != '') {
			$accepted_file_types = explode(' ', ESFileUploader::getAcceptedFileTypes($fileExtensions));

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

			$uploadedFile = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $file_id;

			$is_base64encoded = common::inputGetCmd('base64encoded', '');
			if ($is_base64encoded == "true") {
				$src = $uploadedFile;

				$file = common::inputPost($field->comesfieldname, '', 'STRING');
				$dst = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'decoded_' . basename($file['name']);
				common::base64file_decode($src, $dst);
				$uploadedFile = $dst;
			}

			if ($ExistingFile != '' and !CT_FieldTypeTag_file::checkIfTheFileBelongsToAnotherRecord($ExistingFile, $field)) {
				//Delete Old File
				$filename_full = $FileFolder . DIRECTORY_SEPARATOR . $ExistingFile;

				if (file_exists($filename_full))
					unlink($filename_full);
			}

			if (!file_exists($uploadedFile))
				return null;

			$mime = mime_content_type($uploadedFile);

			$parts = explode('.', $uploadedFile);
			$fileExtension = end($parts);
			if ($mime == 'application/zip' and $fileExtension != 'zip') {
				//could be docx, xlsx, pptx
				$mime = ESFileUploader::checkZIPfile_X($uploadedFile, $fileExtension);
			}

			if (in_array($mime, $accepted_filetypes)) {

				$new_filename = CT_FieldTypeTag_file::getCleanAndAvailableFileName($file_id, $FileFolder);
				$new_filename_path = str_replace('/', DIRECTORY_SEPARATOR, $FileFolder . DIRECTORY_SEPARATOR . $new_filename);

				if (@copy($uploadedFile, $new_filename_path)) {
					unlink($uploadedFile);

					//Copied
					return $new_filename;
				} else {
					unlink($uploadedFile);

					//Cannot copy
					return null;
				}
			} else {
				unlink($uploadedFile);
				return null;
			}
		}
		return null;
	}

	public static function checkIfTheFileBelongsToAnotherRecord(string $filename, CustomTables\Field $field): bool
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($field->realfieldname, $filename);
		$col = database::loadColumn($field->ct->Table->realtablename, ['COUNT(*) AS c'], $whereClause, null, null, 2);
		if (count($col) == 0)
			return false;

		return $col[0] > 1;
	}

	protected static function getCleanAndAvailableFileName(string $filename, string $FileFolder): string
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
		//$filename = preg_replace("/[^a-z\d._]/", "", $filename_raw);
		$filename = preg_replace("/[^\p{L}\d._]/u", "", $filename_raw);

		$i = 0;
		$filename_new = $filename;
		while (1) {

			if (file_exists($FileFolder . DIRECTORY_SEPARATOR . $filename_new)) {
				//increase index
				$i++;
				$filename_new = str_replace('.', '-' . $i . '.', $filename);
			} else
				break;
		}
		return $filename_new;
	}


	public static function getBlobFileName(Field $field, int $valueSize, array $row, array $fields)
	{
		$filename = '';
		if (isset($field->params[2]) and $field->params[2] != '') {
			$fileNameField_String = $field->params[2];
			$fileNameField_Row = Fields::FieldRowByName($fileNameField_String, $fields);
			$fileNameField = $fileNameField_Row['realfieldname'];
			$filename = $row[$fileNameField];
		}

		if ($filename == '') {

			$file_extension = 'bin';
			$content = stripslashes($row[$field->realfieldname . '_sample']);
			$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);
			$mime_file_extension = JoomlaBasicMisc::mime2ext($mime);
			if ($mime_file_extension !== null)
				$file_extension = $mime_file_extension;

			if ($valueSize == 0)
				$filename = '';
			else
				$filename = 'blob-' . strtolower(str_replace(' ', '', JoomlaBasicMisc::formatSizeUnits($valueSize))) . '.' . $file_extension;
		}
		return $filename;
	}

	public static function process($filename, CustomTables\Field $field, $option_list, $record_id, $filename_only = false, int $file_size = 0)
	{
		if ($filename == '')
			return '';

		if ($field->type == 'filelink') {
			$FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[0] ?? '');
			$filepath = $FileFolder . '/' . $filename;
		} elseif ($field->type == 'blob')
			$filepath = $filename;
		else {

			$FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[1] ?? '');
			$filepath = $FileFolder . '/' . $filename;

			$full_filepath = JPATH_SITE . ($filepath[0] == '/' ? '' : '/') . $filepath;
			if (file_exists($full_filepath))
				$file_size = filesize($full_filepath);
		}

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

		$how_to_process = $option_list[0] ?? '';

		if ($record_id === null) {
			$filepath = null;
		} else {
			if ($how_to_process != '') {
				$filepath = CT_FieldTypeTag_file::get_private_file_path($filename, $how_to_process, $filepath, $record_id, $field->id, $field->ct->Table->tableid, $filename_only);
			} elseif ($field->type == 'blob') {
				$how_to_process = 'blob';//Not secure but BLOB
				$filepath = CT_FieldTypeTag_file::get_private_file_path($filename, $how_to_process, $filepath, $record_id, $field->id, $field->ct->Table->tableid, $filename_only);
			}
		}

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

			case 'file-size':
				return JoomlaBasicMisc::formatSizeUnits($file_size);

			default:
				return $filepath;
		}
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

	static protected function get_private_file_path(string $rowValue, string $how_to_process, string $filepath, string $recId, int $fieldid, int $tableid, bool $filename_only = false): string
	{
		$security = CT_FieldTypeTag_file::get_security_letter($how_to_process);

		//make the key
		$key = CT_FieldTypeTag_file::makeTheKey($filepath, $security, $recId, $fieldid, $tableid);

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

	static protected function get_security_letter(string $how_to_process): string
	{
		switch ($how_to_process) {

			case 'blob':
				return 'b';

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

	public static function makeTheKey(string $filepath, string $security, string $recId, string $fieldid, string $tableid): string
	{
		$user = new CTUser();
		$username = $user->username;
		$current_user_id = $user->id;

		$t = time();
		//prepare augmented timer
		$secs = 1000;
		if ($security == 'e' or $security == 'g' or $security == 'i')
			$secs = 10000;

		$tplus = floor(($t + $secs) / $secs) * $secs;
		$ip = $_SERVER['REMOTE_ADDR'];

		//get secs key char
		$sep = $security;//($secs==1000 ? 'a' : 'b');
		$m2 = 'c' . $recId . $sep . $fieldid . $sep . $tableid;

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
