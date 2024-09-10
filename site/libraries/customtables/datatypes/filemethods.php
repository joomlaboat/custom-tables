<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

defined('_JEXEC') or die();

use CustomTables\database;
use CustomTables\MySQLWhereClause;

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
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('fileid', (int)$file_id);

        $fileRows = database::loadObjectList($fileBoxTableName, ['file_ext'], $whereClause, null, null, 1);
        if (count($fileRows) != 1)
            return '';

        $rec = $fileRows[0];
        return $rec->file_ext;
    }

    static public function DeleteFileBoxFiles($fileBoxTableName, $estableid, $fileBoxName, $typeParams): void
    {
        $fileFolder = CUSTOMTABLES_ABSPATH . str_replace('/', DIRECTORY_SEPARATOR, $typeParams);
        $whereClause = new MySQLWhereClause();
        $fileRows = database::loadObjectList($fileBoxTableName, ['fileid'], $whereClause);

        foreach ($fileRows as $fileRow) {
            CustomTablesFileMethods::DeleteExistingFileBoxFile(
                $fileFolder,
                $estableid,
                $fileBoxName,
                $fileRow->fileid,
                $fileRow->file_ext
            );
        }
    }

    static public function DeleteExistingFileBoxFile($filefolder, $estableid, $fileboxname, $fileid, $ext): void
    {
        $filename = $filefolder . DIRECTORY_SEPARATOR . $estableid . '_' . $fileboxname . '_' . $fileid . '.' . $ext;

        if (file_exists($filename))
            unlink($filename);
    }
}
