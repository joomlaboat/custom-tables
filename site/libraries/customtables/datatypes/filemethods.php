<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\database;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class CustomTablesFileMethods
{
	static public function FileExtension($src, $allowedExtensions = 'doc docx pdf rtf txt xls xlsx psd ppt pptx png jpg jpeg gif webp mp3 pages'): string
	{
		$name = explode(".", strtolower($src));
		if (count($name) < 2)
			return ''; //not allowed

		$file_ext = $name[count($name) - 1];
		$extensions = explode(" ", $allowedExtensions);

		if (!in_array($file_ext, $extensions))
			return ''; //not allowed

		return $file_ext;
	}

	static public function getFileExtByID($tableName, $fileBoxName, $file_id): string
	{
		$fileBoxTableName = '#__customtables_filebox_' . $tableName . '_' . $fileBoxName;
		$query = 'SELECT file_ext FROM ' . $fileBoxTableName . ' WHERE fileid=' . (int)$file_id . ' LIMIT 1';
		$fileRows = database::loadObjectList($query);
		if (count($fileRows) != 1)
			return '';

		$rec = $fileRows[0];
		return $rec->file_ext;
	}

	static public function DeleteFileBoxFiles($fileBoxTableName, $estableid, $fileBoxName, $typeParams): void
	{
		$fileFolder = JPATH_SITE . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $typeParams);
		$query = 'SELECT fileid FROM ' . $fileBoxTableName;
		$filerows = database::loadObjectList($query);

		foreach ($filerows as $filerow) {
			CustomTablesFileMethods::DeleteExistingFileBoxFile(
				$fileFolder,
				$estableid,
				$fileBoxName,
				$filerow->fileid,
				$filerow->file_ext
			);
		}
	}

	static public function DeleteExistingFileBoxFile($filefolder, $estableid, $fileboxname, $fileid, $ext): void
	{
		$filename = $filefolder . DIRECTORY_SEPARATOR . $estableid . '_' . $fileboxname . '_' . $fileid . '.' . $ext;

		if (file_exists($filename))
			unlink($filename);
	}

	static public function base64file_decode($inputfile, $outputfile)
	{
		/* read data (binary) */
		$ifp = fopen($inputfile, "rb");
		$srcData = fread($ifp, filesize($inputfile));
		fclose($ifp);
		/* encode & write data (binary) */
		$ifp = fopen($outputfile, "wb");
		fwrite($ifp, base64_decode($srcData));
		fclose($ifp);
		/* return output filename */
		return ($outputfile);
	}


}
