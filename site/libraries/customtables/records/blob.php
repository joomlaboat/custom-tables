<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class Save_blob
{
    var CT $ct;
    public Field $field;
    var ?array $row_new;

    function __construct(CT &$ct, Field $field, ?array &$row_new)
    {
        $this->ct = &$ct;
        $this->field = $field;
        $this->row_new = &$row_new;//It's important to pass a reference because file name maybe saved to another field
    }

    /**
     * @throws Exception
     * @since 3.3.3
     */
    function saveFieldSet(): ?array
    {
        $newValue = null;

        $processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
            . DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'blob.php';
        require_once($processor_file);

        $to_delete = common::inputPostCmd($this->field->comesfieldname . '_delete', null, 'create-edit-record');
        $value = Value_blob::get_blob_value($this->field);

        $fileNameField = '';
        if (isset($this->field->params[2])) {
            $fileNameField_String = $this->field->params[2];
            $fileNameField_Row = Fields::FieldRowByName($fileNameField_String, $this->ct->Table->fields);
            $fileNameField = $fileNameField_Row['realfieldname'];
        }

        if ($to_delete == 'true' and $value === null) {
            $newValue = ['value' => null];

            if ($fileNameField != '' and !isset($this->row_new[$fileNameField]))
                $this->row_new[$fileNameField] = null;

        } elseif ($value !== null) {
            $newValue = ['value' => $value];

            if ($fileNameField != '') {
                $file_id = common::inputPostString($this->field->comesfieldname, '', 'create-edit-record');

                //Delete temporary file name parts
                //Example: ct_1702267688_PseAH3r3Cy91VhQbh6hzw7bchYW5rK51sD_001_Li-R6ong7bo_LOI_PE1214762_26032019_c1b1121b122.doc
                //Cleaned: 001_Li-Ron7gbo_LOI_PE1214762_26032019_c1b1121b122.doc
                $file_name_parts = explode('_', $file_id);
                if (count($file_name_parts) > 3 and $file_name_parts[0] == 'ct')
                    $file_name = implode('_', array_slice($file_name_parts, 3));
                else
                    $file_name = $file_id;

                $this->row_new[$fileNameField] = $file_name;
            }
        }
        return $newValue;
    }
}
