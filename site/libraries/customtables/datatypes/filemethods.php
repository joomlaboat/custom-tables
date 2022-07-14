<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class CustomTablesFileMethods
{
    static public function FileExtenssion($src, $allowedExtensions = 'doc docx pdf rtf txt xls xlsx psd ppt pptx png jpg jpeg gif webp mp3 pages')
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

    static public function getFileExtByID($tableName, $fileboxname, $file_id)
    {
        $db = Factory::getDBO();

        $fileBoxTableName = '#__customtables_filebox_' . $tableName . '_' . $fileboxname;
        $query = 'SELECT file_ext FROM ' . $fileBoxTableName . ' WHERE fileid=' . (int)$file_id . ' LIMIT 1';
        $db->setQuery($query);
        $fileRows = $db->loadObjectList();
        if (count($fileRows) != 1)
            return '';

        $rec = $fileRows[0];
        return $rec->file_ext;
    }

    static public function DeleteFileBoxFiles($fileBoxTableName, $estableid, $fileBoxName, $typeparams)
    {
        $fileFolder = JPATH_SITE . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $typeparams);

        $db = Factory::getDBO();

        $query = 'SELECT fileid FROM ' . $fileBoxTableName;
        $db->setQuery($query);

        $filerows = $db->loadObjectList();

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

    static public function DeleteExistingFileBoxFile($filefolder, $estableid, $fileboxname, $fileid, $ext)
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
