<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @copyright (C) 2018-2023 Ivan Komlev
 * @link https://joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.application.component.view'); //Important to get menu parameters
require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php');

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\Fields;
use Joomla\CMS\Factory;

class CustomTablesViewFiles extends JViewLegacy
{
    var CT $ct;

    var ?array $row;
    var string $key;
    var string $security;
    var string $listing_id;
    var int $tableid;
    var int $fieldid;
    var Field $field;

    function display($tpl = null)
    {
        $this->ct = new CT;

        $this->listing_id = common::inputGetCmd("listing_id");
        $this->tableid = common::inputGetInt('tableid', 0);
        $this->fieldid = common::inputGetInt('fieldid', 0);

        $this->security = common::inputGetCmd('security', 'd');
        $this->key = common::inputGetCmd('key', '');

        $this->ct->getTable($this->tableid);
        if ($this->ct->Table->tablename === null) {
            $this->ct->errors[] = 'Table not selected (79).';
            return;
        }

        $fieldrow = null;
        foreach ($this->ct->Table->fields as $f) {
            if ($f['id'] == $this->fieldid) {
                $fieldrow = $f;
                break;
            }
        }

        if (is_null($fieldrow)) {
            $this->ct->errors[] = 'File View: Field not found.';
            return;
        }

        $this->row = $this->ct->Table->loadRecord($this->listing_id);
        $this->field = new Field($this->ct, $fieldrow, $this->row);

        if ($this->field->type == 'blob') {

            if (isset($this->field->params[2])) {
                $fileNameField_String = $this->field->params[2];
                $fileNameField_Row = Fields::FieldRowByName($fileNameField_String, $this->ct->Table->fields);
                $fileNameField = $fileNameField_Row['realfieldname'];
                $filepath = $this->row[$fileNameField];
            } else {
                $filepath = 'blob-' . strtolower(str_replace(' ', '', JoomlaBasicMisc::formatSizeUnits((int)$this->row[$this->field->realfieldname]))) . '.bin';
            }

        } else {
            $filepath = $this->getFilePath();
            if ($filepath == '')
                $this->ct->errors[] = 'File path not set.';
        }

        $key = $this->key;
        $test_key = CT_FieldTypeTag_file::makeTheKey($filepath, $this->security, $this->listing_id, $this->fieldid, $this->tableid);

        if ($key == $test_key) {
            if ($this->field->type == 'blob') {
                if (isset($this->field->params[2]))
                    $this->render_blob_output($filepath);
                else
                    $this->render_blob_output('');
            } else
                $this->render_file_output($filepath);
        } else
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_DOWNLOAD_LINK_IS_EXPIRED');
    }

    protected function getFilePath(): string
    {
        if (!isset($this->row[$this->field->realfieldname]))
            $this->ct->errors[] = 'Real field name not set';

        $rowValue = $this->row[$this->field->realfieldname];

        if ($this->field->type == 'filelink')
            return CT_FieldTypeTag_file::getFileFolder($this->field->params[0]) . '/' . $rowValue;
        else
            return CT_FieldTypeTag_file::getFileFolder($this->field->params[1]) . '/' . $rowValue;
    }

    function render_blob_output($filename)
    {
        $query = 'SELECT ' . $this->field->realfieldname . ' FROM ' . $this->ct->Table->realtablename . ' WHERE '
            . $this->ct->Table->realidfieldname . '=' . database::quote($this->listing_id) . ' LIMIT 1';

        $rows = database::loadAssocList($query);

        if (count($rows) < 1) {
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_FILE_NOT_FOUND');
            return;
        }

        $content = stripslashes($rows[0][$this->field->realfieldname]);
        $content = $this->ProcessContentWithCustomPHP($content, $this->row);

        if (ob_get_contents()) ob_end_clean();

        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);

        if ($filename == '') {
            $file_extension = JoomlaBasicMisc::mime2ext($mime);
            $filename = 'blob.' . $file_extension;
        }

        @header('Content-Type: ' . $mime);
        @header("Pragma: public");
        @header("Expires: 0");
        @header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        @header("Cache-Control: public");
        @header("Content-Description: File Transfer");
        @header("Content-Transfer-Encoding: binary");
        @header("Content-Disposition: attachment; filename=\"" . $filename . "\"");

        echo $content;

        die;//clean exit
    }

    function ProcessContentWithCustomPHP($content, $row)
    {
        $serverTagProcessorFile = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR
            . 'customtables' . DIRECTORY_SEPARATOR . 'protagprocessor' . DIRECTORY_SEPARATOR . 'servertags.php';

        if (!file_exists($serverTagProcessorFile))
            return $content;

        if (!isset($this->field->params[4]))
            return $content;

        $customPHPFile = $this->field->params[4];

        if ($customPHPFile != '') {
            $parts = explode('/', $customPHPFile); //just a security check
            if (count($parts) > 1)
                return $content;

            $file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'customphp' . DIRECTORY_SEPARATOR . $customPHPFile;
            if (file_exists($file)) {
                require_once($file);
                $function_name = 'CTProcessFile_' . str_replace('.php', '', $customPHPFile);

                if (function_exists($function_name))
                    return call_user_func($function_name, $content, $row, $this->ct->Table->tableid, $this->fieldid);
            }
        }
        return $content;
    }

    function render_file_output($filepath): bool
    {
        if (strlen($filepath) > 8 and str_starts_with($filepath, '/images/'))
            $file = JPATH_SITE . str_replace('/', DIRECTORY_SEPARATOR, $filepath);
        else
            $file = str_replace('/', DIRECTORY_SEPARATOR, $filepath);

        if (!file_exists($file)) {
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_FILE_NOT_FOUND');
            return false;
        }

        $content = file_get_contents($file);

        $parts = explode('/', $file);
        $filename = end($parts);

        try {
            $content = $this->ProcessContentWithCustomPHP($content, $this->row);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        if (ob_get_contents()) ob_end_clean();

        $mt = mime_content_type($file);

        @header('Content-Type: ' . $mt);
        @header("Pragma: public");
        @header("Expires: 0");
        @header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        @header("Cache-Control: public");
        @header("Content-Description: File Transfer");
        @header("Content-Transfer-Encoding: binary");
        @header("Content-Disposition: attachment; filename=\"" . $filename . "\"");

        echo $content;

        die;//clean exit
    }
}
