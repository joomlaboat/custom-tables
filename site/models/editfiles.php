<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Field;
use CustomTables\Fields;

use Joomla\CMS\Factory;

jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'tables');

class CustomTablesModelEditFiles extends JModelLegacy
{
    var CT $ct;
    var ?array $row;
    var $filemethods;
    var $fileboxname;
    var $FileBoxTitle;
    var $fileboxfolder;
    var $fileboxfolderweb;
    var int $maxfilesize;
    var $fileboxtablename;
    var string $allowedExtensions;
    var Field $field;

    function __construct()
    {
        $this->ct = new CT;
        $this->ct->setParams();

        parent::__construct();

        $this->allowedExtensions = 'doc docx pdf txt xls xlsx psd ppt pptx webp png mp3 jpg jpeg csv accdb';

        $this->maxfilesize = JoomlaBasicMisc::file_upload_max_size();
        $this->filemethods = new CustomTablesFileMethods;

        $this->ct->getTable($this->ct->Params->tableName, null);

        if ($this->ct->Table->tablename == '') {
            Factory::getApplication()->enqueueMessage('Table not selected (63).', 'error');
            return false;
        }

        if (!$this->ct->Env->jinput->getCmd('fileboxname'))
            return false;

        $this->fileboxname = $this->ct->Env->jinput->getCmd('fileboxname');

        $this->row = $this->ct->Table->loadRecord($this->ct->Params->listing_id);

        if (!$this->getFileBox())
            return false;

        $this->fileboxtablename = '#__customtables_filebox_' . $this->ct->Table->tablename . '_' . $this->fileboxname;

        parent::__construct();
        return true;
    }

    function getFileBox(): bool
    {
        $fieldrow = Fields::FieldRowByName($this->fileboxname, $this->ct->Table->fields);
        $this->field = new Field($this->ct, $fieldrow, $this->row);

        $this->fileboxfolderweb = 'images/' . $this->field->params[1];

        $this->fileboxfolder = JPATH_SITE . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $this->fileboxfolderweb);
        //Create folder if not exists
        if (!file_exists($this->fileboxfolder))
            mkdir($this->fileboxfolder, 0755, true);

        $this->FileBoxTitle = $this->field->title;

        return true;
    }

    function getFileList()
    {
        // get database handle
        $db = Factory::getDBO();
        $query = 'SELECT fileid, file_ext FROM ' . $this->fileboxtablename . ' WHERE listingid=' . $this->ct->Params->listing_id . ' ORDER BY fileid';
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    function delete(): bool
    {
        $db = Factory::getDBO();

        $fileids = $this->ct->Env->jinput->getString('fileids', '');
        $file_arr = explode('*', $fileids);

        foreach ($file_arr as $fileid) {
            if ($fileid != '') {
                $file_ext = CustomTablesFileMethods::getFileExtByID($this->ct->Table->tablename, $this->ct->Table->tableid, $this->fileboxname, $fileid);

                CustomTablesFileMethods::DeleteExistingFileBoxFile($this->fileboxfolder, $this->ct->Table->tableid, $this->fileboxname, $fileid, $file_ext);

                $query = 'DELETE FROM ' . $this->fileboxtablename . ' WHERE listingid=' . $this->ct->Params->listing_id . ' AND fileid=' . $fileid;
                $db->setQuery($query);
                $db->execute();
            }
        }

        $this->ct->Table->saveLog($this->ct->Params->listing_id, 9);

        return true;
    }

    function add(): bool
    {
        $file = $this->ct->Env->jinput->files->get('uploadedfile'); //not zip -  regular Joomla input method will be used

        $uploadedfile = "tmp/" . basename($file['name']);

        if (!move_uploaded_file($file['tmp_name'], $uploadedfile))
            return false;


        if ($this->ct->Env->jinput->getCmd('base64ecnoded', '') == "true") {
            $src = $uploadedfile;
            $dst = "tmp/decoded_" . basename($file['name']);
            CustomTablesFileMethods::base64file_decode($src, $dst);
            $uploadedfile = $dst;
        }

        //Save to DB

        $file_ext = CustomTablesFileMethods::FileExtenssion($uploadedfile, $this->allowedExtensions);
        //or $allowed_ext.indexOf($file_ext)==-1
        if ($file_ext == '') {
            //unknown file extension (type)
            unlink($uploadedfile);

            return false;
        }

        $fileid = $this->addFileRecord($file_ext);

        //es Thumb
        $newfilename = $this->fileboxfolder . DIRECTORY_SEPARATOR . $this->ct->Table->tableid . '_' . $this->fileboxname . '_' . $fileid . "." . $file_ext;

        if (!copy($uploadedfile, $newfilename)) {
            unlink($uploadedfile);
            return false;
        }

        unlink($uploadedfile);

        $this->ct->Table->saveLog($this->ct->Params->listing_id, 8);
        return true;
    }


    function addFileRecord($file_ext): int
    {
        $db = Factory::getDBO();

        $query = 'INSERT ' . $this->fileboxtablename . ' SET '
            . 'file_ext="' . $file_ext . '", '
            . 'ordering=0, '
            . 'title="", '
            . 'listingid=' . $this->ct->Params->listing_id;

        $db->setQuery($query);
        $db->execute();

        $query = ' SELECT fileid FROM ' . $this->fileboxtablename . ' WHERE listingid=' . $this->ct->Params->listing_id . ' ORDER BY fileid DESC LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) == 1) {
            return $rows[0]->fileid;
        }

        return -1;
    }
}
