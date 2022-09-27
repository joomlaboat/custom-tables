<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @copyright (C) 2018-2022 Ivan Komlev
 * @link https://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.application.component.view'); //Important to get menu parameters
require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php');

use CustomTables\CT;
use CustomTables\Field;
use CustomTables\Fields;

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

        $this->listing_id = $this->ct->Env->jinput->getCmd("listing_id");
        $this->tableid = $this->ct->Env->jinput->getInt('tableid', 0);
        $this->fieldid = $this->ct->Env->jinput->getInt('fieldid', 0);

        $this->security = $this->ct->Env->jinput->getCmd('security', 'd');
        $this->key = $this->ct->Env->jinput->getCmd('key', '');

        $this->ct->getTable($this->tableid);
        if ($this->ct->Table->tablename == '') {
            $this->ct->app->enqueueMessage('Table not selected (79).', 'error');
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
            $this->ct->app->enqueueMessage('File View: Field not found.', 'error');
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
                $this->ct->app->enqueueMessage('File path not set.', 'error');
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
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DOWNLOAD_LINK_IS_EXPIRED'), 'error');
    }

    protected function getFilePath(): string
    {
        if (!isset($this->row[$this->field->realfieldname]))
            $this->ct->app->enqueueMessage('Real field name not set', 'error');

        $rowValue = $this->row[$this->field->realfieldname];

        if ($this->field->type == 'filelink')
            return CT_FieldTypeTag_file::getFileFolder($this->field->params[0]) . '/' . $rowValue;
        else
            return CT_FieldTypeTag_file::getFileFolder($this->field->params[1]) . '/' . $rowValue;
    }

    function render_blob_output($filename)
    {
        $query = 'SELECT ' . $this->field->realfieldname . ' FROM ' . $this->ct->Table->realtablename . ' WHERE '
            . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($this->listing_id) . ' LIMIT 1';

        $this->ct->db->setQuery($query);
        $recs = $this->ct->db->loadAssocList();

        if (count($recs) < 1) {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_FOUND'), 'error');
            return;
        }

        $content = stripslashes($recs[0][$this->field->realfieldname]);
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

    function render_file_output($filepath)
    {
        if (strlen($filepath) > 8 and str_starts_with($filepath, '/images/'))
            $file = JPATH_SITE . str_replace('/', DIRECTORY_SEPARATOR, $filepath);
        else
            $file = str_replace('/', DIRECTORY_SEPARATOR, $filepath);

        if (!file_exists($file)) {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_FOUND'), 'error');
            return;
        }

        $content = file_get_contents($file);

        $parts = explode('/', $file);
        $filename = end($parts);

        $content = $this->ProcessContentWithCustomPHP($content, $this->row);

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
