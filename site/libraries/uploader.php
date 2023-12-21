<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Fields;
use Joomla\CMS\Factory;

class ESFileUploader
{
	public static function getFileNameByID($fileId): string
	{
		$dir = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
		$files = scandir($dir);

		$lookFor = '_' . $fileId . '_';
		foreach ($files as $file) {
			if (str_contains($file, $lookFor))
				return $dir . $file;
		}
		return '';
	}

	public static function uploadFile($fileId, $filetypes_str_argument = ""): string
	{
		$filetypes_str = ESFileUploader::getAcceptedFileTypes($filetypes_str_argument);

		$accepted_types = ESFileUploader::getAcceptableMimeTypes($filetypes_str);

		ESFileUploader::deleteOldFiles();

		$output_dir = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
		$t = time();
		$file = ESFileUploader::getfile_SafeMIME($fileId);

		$accepted_types = ESFileUploader::getAcceptableMimeTypes($filetypes_str);

		if (isset($file['name'])) {
			$ret = array();
			$parts = explode('.', $file['name']);
			$fileExtension = end($parts);

			//	This is for custom errors;

			$error = $file["error"];

			//You need to handle  both cases
			//If Any browser does not support serializing of multiple files using FormData()
			if (!is_array($file['name'])) //single file
			{
				$mime = mime_content_type($file["tmp_name"]);

				if ($mime == 'application/zip' and $fileExtension != 'zip') {
					//could be docx, xlsx, pptx
					$mime = ESFileUploader::checkZIPfile_X($file["tmp_name"], $fileExtension);
				}

				if (in_array($mime, $accepted_types)) {

					$fileName = ESFileUploader::normalizeString($file['name']);
					$newFileName = $output_dir . 'ct_' . $t . '_' . $fileId . '_' . $fileName;

					if (common::inputGetCmd('task') == 'importcsv') {
						require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables'
							. DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'importcsv.php');

						move_uploaded_file($file["tmp_name"], $newFileName);

						$msg = importCSVfile($newFileName, common::inputGetInt('tableid', 0));
						if ($msg != '' and $msg != 'success')
							$ret = ['error' => $msg];
						else
							$ret = ['status' => 'success', 'filename' => 'ct_' . $t . '_' . $fileId . '_' . $fileName
								, 'originalfilename' => $file['name']];

					} else {
						if (move_uploaded_file($file["tmp_name"], $newFileName))
							$ret = ['status' => 'success', 'filename' => 'ct_' . $t . '_' . $fileId . '_' . $fileName
								, 'originalfilename' => $file['name']];
						else
							$ret = ['error' => 'Unable to upload the file.'];
					}
				} else {
					unlink($file["tmp_name"]);
					$msg = 'File type (' . $mime . ') not permitted.';
					if ($filetypes_str != '')
						$msg .= ' ' . common::translate('COM_CUSTOMTABLES_PERMITTED_TYPES') . ' ' . $filetypes_str;//implode(', ', $accepted_types);

					$ret = ['error' => $msg];
				}
			}
			return json_encode($ret);
		} else
			return json_encode(['error' => common::translate('COM_CUSTOMTABLES_FILE_IS_EMPTY')]);
	}

	public static function getAcceptedFileTypes($fileExtensions): string
	{
		$allowedExtensions = 'doc docx pdf rtf txt xls xlsx psd ppt pptx odg odp ods odt pages'
			. ' xcf ai txt avi csv accdb htm html'
			. ' jpg bmp ico jpeg png webp gif svg ai'//Images
			. ' zip'//Archive
			. ' aac flac mp3 wav ogg'//Audio
			. ' mp4 m4a m4p m4b m4r m4v wma flv mpg 3gp wmv mov';//Video

		$allowedExtensionsArray = explode(' ', $allowedExtensions);
		$file_formats = array();

		if ($fileExtensions != '') {
			$file_formats_ = explode(' ', $fileExtensions);
			foreach ($file_formats_ as $f) {
				if (in_array($f, $allowedExtensionsArray))
					$file_formats[] = $f;
			}
		} else
			$file_formats = $allowedExtensionsArray;

		return implode(' ', $file_formats);
	}

	protected static function getAcceptableMimeTypes($filetypes_str = ""): array
	{
		if ($filetypes_str == '') {
			$fieldname = common::inputGetCmd('fieldname', '');
			$tableRow = ESFileUploader::getTableRawByItemid();
			$tableId = $tableRow['id'];
			$fieldRow = Fields::getFieldAssocByName($fieldname, $tableId);
			if ($fieldRow === null)
				return [];

			if ($fieldRow['type'] == 'image')
				return array('image/gif', 'image/png', 'image/jpeg', 'image/svg+xml', 'image/webp');

			$fieldParams = $fieldRow['typeparams'];
			$parts = JoomlaBasicMisc::csv_explode(',', $fieldParams);

			if (!isset($parts[2]))
				return [];

			$filetypes_str = $parts[2];
		}
		$filetypes = explode(' ', $filetypes_str);

		$accepted_filetypes = array();

		foreach ($filetypes as $filetype) {
			$mime = ESFileUploader::get_mime_type('1.' . $filetype);
			$accepted_filetypes[] = $mime;

			if ($filetype == 'docx')
				$accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
			elseif ($filetype == 'xlsx')
				$accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			elseif ($filetype == 'pptx')
				$accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
			elseif ($filetype == 'csv') {
				$accepted_filetypes[] = 'application/csv';
				$accepted_filetypes[] = 'text/plain';
			}
		}

		return $accepted_filetypes;
	}

	protected static function getTableRawByItemid()
	{
		$app = Factory::getApplication();
		$Itemid = common::inputGetInt('Itemid', 0);

		$menuItem = $app->getMenu()->getItem($Itemid);
		// Get params for menuItem

		$esTable = new ESTables;
		$tableName = $menuItem->params->get('tableName');
		if ($tableName === null)
			return 0;

		return $esTable->getTableRowByNameAssoc($tableName);
	}

	public static function get_mime_type($filename): string
	{
		$filename_parts = explode('.', $filename);
		$filename_extension = strtolower(end($filename_parts));

		$mimeType = array(
			'txt' => 'text/plain',
			'csv' => 'text/csv',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',

			// images
			'png' => 'image/png',
			'webp' => 'image/webp',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed', //not allowed
			'exe' => 'application/x-msdownload', //not allowed
			'msi' => 'application/x-msdownload', //not allowed
			'cab' => 'application/vnd.ms-cab-compressed', //not allowed

			// audio
			'mp3' => 'audio/mpeg',
			'flac' => 'audio/flac',
			'aac' => 'audio/aac',
			'wav' => 'audio/wav',
			'ogg' => 'audio/ogg',

			// video
			'mp4' => 'video/mp4',
			'm4a' => 'video/mp4',
			'm4p' => 'video/mp4',
			'm4b' => 'video/mp4',
			'm4r' => 'video/mp4',
			'm4v' => 'video/mp4',
			'flv' => 'video/x-flv',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',
			'3gp' => 'video/3gpp',
			'avi' => 'video/x-msvideo',
			'mpg' => 'video/mpeg',
			'wmv' => 'video/x-ms-wmv',

			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',

			// ms office
			'doc' => 'application/msword',
			'rtf' => 'text/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			'docx' => 'application/msword',
			'xlsx' => 'application/vnd.ms-excel',
			'pptx' => 'application/vnd.ms-powerpoint',


			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',

			// apple
			'pages' => 'application/vnd.apple.pages'
		);

		return $mimeType[$filename_extension] ?? 'application/octet-stream';
	}

	protected static function deleteOldFiles(): void
	{
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp';

		$oldFiles = scandir($path);

		foreach ($oldFiles as $oldFile) {
			if ($oldFile != '.' and $oldFile != '..') {
				$filename = $path . DIRECTORY_SEPARATOR . $oldFile;

				if (!str_contains($oldFile, '.htm') and file_exists($filename)) {
					$parts = explode('_', $oldFile);
					if ($parts[0] == 'ct' and count($parts) >= 4) {
						$t = (int)$parts[1];

						$now = time();
						$o = $now - $t;
						if ($o > 3600)//delete files uploaded more than an hour ago.
							unlink($filename);
					}
				}
			}
		}
	}

	public static function getfile_SafeMIME($fileId)
	{
		$ct = new CT;
		if ($ct->Env->advancedTagProcessor) {
			//This will let PRO version users to upload zip files, please note that it will check if the file is zip or not (mime type).
			//If not then regular Joomla input method will be used

			if (!isset($_FILES[$fileId])) {
				require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables'
					. DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'importcsv.php');

				if ($ct->Env->clean)
					die(json_encode(['error' => 'Failed to open file.']));
				else
					return [];
			}

			$file = $_FILES[$fileId];

			$mime = mime_content_type($file["tmp_name"]);//read mime type

			if ($mime != 'application/zip')//if not zip file
			{
				$file = common::inputFiles($fileId); //not zip -  regular Joomla input method will be used

				if (!is_array($file) or count($file) == 0) //regular joomla input method blocked custom table structure json file, because it may contain javascript
				{
					$file = $_FILES[$fileId];//get file instance using php method - not safe, but we will validate it later

					$handle = fopen($file["tmp_name"], "rb");
					if (FALSE === $handle) {
						if ($ct->Env->clean)
							die(json_encode(['error' => 'Failed to open file.']));
						else
							return [];
					}

					$magicNumber = '<customtablestableexport>';//to prove that this is Custom Tables Structure JSON file.
					$l = strlen($magicNumber);
					$file_content = fread($handle, $l);
					fclose($handle);

					if (!($mime == 'text/plain' and $file_content == $magicNumber)) {
						if ($ct->Env->clean)
							die(json_encode(['error' => 'Illegal mime type (' . $mime . ') or content.']));
						else
							return [];
					}
				}
			}
		} else
			$file = common::inputFiles($fileId);

		return $file;
	}

	public static function checkZIPfile_X($fileNamePath, $fileExtension)
	{
		//Checks the file zip archive is actually a docx or xlsx or pptx
		//https://www.filesignatures.net/index.php?page=all&currentpage=6&order=EXT
		//504B0304 - zip

		/*
		$magicnumbers=array(
			'docx' => ["504B030414000600"],
			'xlsx' => [0x504B0304,0x504B030414000600],
			'pptx' => [0x504B0304,0x504B030414000600]
		);
		*/

		$magicnumbers = array(hex2bin("504B030414000600"), hex2bin("504B030414000800"));

		$l = strlen($magicnumbers[0]);

		$handle = fopen($fileNamePath, "rb");
		if (FALSE === $handle) {
			exit("Failed to open file.");
		}

		$content = fread($handle, $l);
		fclose($handle);

		$c = substr($content, 0, $l);
		if ($c == $magicnumbers[0] or $c == $magicnumbers[1]) {
			if ($fileExtension == 'docx')
				return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
			elseif ($fileExtension == 'xlsx')
				return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			elseif ($fileExtension == 'pptx')
				return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
		}

		return 'application/zip';
	}

	public static function normalizeString($str = ''): string
	{
		//String sanitizer for filename
		//https://stackoverflow.com/a/1.2.636
		$str = strip_tags($str);
		$str = preg_replace('/[\r\n\t ]+/', ' ', $str);
		$str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
		//$str = strtolower($str);
		$str = html_entity_decode($str, ENT_QUOTES, "utf-8");
		$str = htmlentities($str, ENT_QUOTES, "utf-8");
		//$str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
		$str = str_replace(' ', '-', $str);
		//$str = rawurlencode($str);
		return str_replace('%', '-', $str);
	}
}
