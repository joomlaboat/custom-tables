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

use CustomTablesImageMethods;
use Exception;

class FileMethods
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

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function getFileExtByID($tableName, $fileBoxName, $file_id): string
	{
		$fileBoxTableName = '#__customtables_filebox_' . $tableName . '_' . $fileBoxName;
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('fileid', (int)$file_id);

		$fileRows = database::loadObjectList($fileBoxTableName, ['file_ext'], $whereClause, null, null, 1);
		if (count($fileRows) != 1)
			return '';

		$rec = $fileRows[0];
		return $rec->file_ext;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function DeleteFileBoxFiles($fileBoxTableName, string $tableId, string $fileBoxName, array $params): void
	{
		$fileFolderArray = CustomTablesImageMethods::getImageFolder($params, 'filebox');
		$whereClause = new MySQLWhereClause();
		$fileRows = database::loadObjectList($fileBoxTableName, ['fileid'], $whereClause);

		foreach ($fileRows as $fileRow) {
			self::DeleteExistingFileBoxFile(
				$fileFolderArray['path'],
				$tableId,
				$fileBoxName,
				(string)$fileRow->fileid,
				$fileRow->file_ext
			);
		}
	}

	static public function DeleteExistingFileBoxFile(string $fileFolder, string $tableId, string $fileBoxName, string $fileid, string $ext): void
	{
		$filename = $fileFolder . DIRECTORY_SEPARATOR . $tableId . '_' . $fileBoxName . '_' . $fileid . '.' . $ext;

		if (file_exists($filename))
			unlink($filename);
	}
}
