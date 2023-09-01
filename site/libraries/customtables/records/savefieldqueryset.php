<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTablesImageMethods;
use Exception;
use Joomla\CMS\Component\ComponentHelper;

use CT_FieldTypeTag_image;
use CT_FieldTypeTag_file;
use CustomTables\DataTypes\Tree;

use LayoutProcessor;
use tagProcessor_General;
use tagProcessor_Item;
use tagProcessor_If;
use tagProcessor_Page;
use tagProcessor_Value;
use CustomTables\CustomPHP\CleanExecute;
use JoomlaBasicMisc;

class SaveFieldQuerySet
{
    var CT $ct;
    public Field $field;
    var ?array $row;
    var bool $isCopy;
    var array $saveQuery;

    function __construct(CT &$ct, $row, $isCopy = false)
    {
        $this->ct = &$ct;
        $this->row = $row;
        $this->isCopy = $isCopy;
        $this->saveQuery = [];
    }

    //Return type: null|string|array

    function getSaveFieldSet($fieldRow)
    {
        $this->field = new Field($this->ct, $fieldRow, $this->row);
        $query = $this->getSaveFieldSetType();

        if ($this->field->defaultvalue != "" and (is_null($query) or is_null($this->row[$this->field->realfieldname])))
            return $this->applyDefaults($fieldRow);
        else
            return $query;
    }

    protected function getSaveFieldSetType()
    {
        $listing_id = $this->row[$this->ct->Table->realidfieldname];
        switch ($this->field->type) {
            case 'records':
                $value = self::get_record_type_value($this->ct, $this->field);
                if ($value === null) {
                    $this->row[$this->field->realfieldname] = null;
                    return null;
                } elseif ($value === '') {
                    $this->row[$this->field->realfieldname] = null;
                    return $this->field->realfieldname . '=NULL';
                }
                $this->row[$this->field->realfieldname] = $value;
                return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);

            case 'sqljoin':
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);

                if (isset($value)) {
                    $value = preg_replace("/[^A-Za-z\d\-]/", '', $value);

                    if (is_numeric($value)) {
                        if ($value == 0) {
                            $this->row[$this->field->realfieldname] = null;
                            return $this->field->realfieldname . '=NULL';
                        }
                        $this->row[$this->field->realfieldname] = $value;
                        return $this->field->realfieldname . '=' . $value;
                    } else {
                        if ($value == '') {
                            $this->row[$this->field->realfieldname] = null;
                            return $this->field->realfieldname . '=NULL';
                        }
                        $this->row[$this->field->realfieldname] = $value;
                        return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                    }
                }
                break;
            case 'radio':
                $value = $this->ct->Env->jinput->getCmd($this->field->comesfieldname);

                if (isset($value)) {
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                }
                break;

            case 'string':
            case 'filelink':
            case 'googlemapcoordinates':
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);
                if (isset($value)) {
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                }
                break;

            case 'color':
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);
                if (isset($value)) {
                    if (str_contains($value, 'rgb')) {
                        $parts = str_replace('rgba(', '', $value);
                        $parts = str_replace('rgb(', '', $parts);
                        $parts = str_replace(')', '', $parts);
                        $values = explode(',', $parts);

                        if (count($values) >= 3) {
                            $r = $this->toHex((int)$values[0]);
                            $g = $this->toHex((int)$values[1]);
                            $b = $this->toHex((int)$values[2]);
                            $value = $r . $g . $b;
                        }

                        if (count($values) == 4) {
                            $a = 255 * (float)$values[3];
                            $value .= $this->toHex($a);
                        }

                    } else
                        $value = $this->ct->Env->jinput->get($this->field->comesfieldname, '', 'ALNUM');

                    $value = strtolower($value);
                    $value = str_replace('#', '', $value);
                    if (ctype_xdigit($value) or $value == '') {
                        $this->row[$this->field->realfieldname] = $value;
                        return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                    }
                }
                break;

            case 'alias':
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);

                if (isset($value)) {
                    $value = $this->get_alias_type_value($listing_id);
                    $this->row[$this->field->realfieldname] = $value;
                    return ($value === null ? null : $this->field->realfieldname . '=' . $this->ct->db->Quote($value));
                }
                break;

            case 'multilangstring':

                $firstLanguage = true;
                $sets = [];
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $value = $this->ct->Env->jinput->getString($this->field->comesfieldname . $postfix);

                    if (isset($value)) {
                        $this->row[$this->field->realfieldname . $postfix] = $value;
                        $sets[] = $this->field->realfieldname . $postfix . '=' . $this->ct->db->Quote($value);
                    }
                }
                return (count($sets) > 0 ? $sets : null);

            case 'text':

                $value = ComponentHelper::filterText($this->ct->Env->jinput->post->get($this->field->comesfieldname, null, 'raw'));

                if (isset($value)) {
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $this->ct->db->Quote(stripslashes($value));
                }
                break;

            case 'multilangtext':

                $sets = [];
                $firstLanguage = true;
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $value = ComponentHelper::filterText($this->ct->Env->jinput->post->get($this->field->comesfieldname . $postfix, null, 'raw'));

                    if (isset($value)) {
                        $this->row[$this->field->realfieldname . $postfix] = $value;
                        $sets[] = $this->field->realfieldname . $postfix . '=' . $this->ct->db->Quote($value);
                    }
                }
                return (count($sets) > 0 ? $sets : null);

            case 'ordering':
                $value = $this->ct->Env->jinput->getInt($this->field->comesfieldname);

                if (isset($value)) // always check with isset(). null doesn't work as 0 is null somehow in PHP
                {
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'int':
                $value = $this->ct->Env->jinput->getInt($this->field->comesfieldname);

                if (!is_null($value)) // always check with isset(). null doesn't work as 0 is null somehow in PHP
                {
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'user':
                $value = $this->ct->Env->jinput->post->get($this->field->comesfieldname);

                if (isset($value)) {
                    $value = $this->ct->Env->jinput->getInt($this->field->comesfieldname);
                    $this->row[$this->field->realfieldname] = $value;

                    if ($value === null)
                        return $this->field->realfieldname . '=null';
                    else
                        return $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'userid':

                if ($this->ct->isRecordNull($this->row) or $this->isCopy) {

                    $value = $this->ct->Env->jinput->post->get($this->field->comesfieldname);

                    if ((!isset($value) or $value == 0)) {

                        if (!$this->ct->isRecordNull($this->row)) {
                            if ($this->row[$this->field->realfieldname] == null or $this->row[$this->field->realfieldname] == "")
                                $value = ($this->ct->Env->userid != 0 ? $this->ct->Env->userid : 0);
                        } else {
                            $value = ($this->ct->Env->userid != 0 ? $this->ct->Env->userid : 0);
                        }
                    }
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $value;
                }

                $value = $this->ct->Env->jinput->getInt($this->field->comesfieldname);

                if (isset($value) and $value != 0) {
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'article':
            case 'usergroup':
                $value = $this->ct->Env->jinput->getInt($this->field->comesfieldname);

                if (isset($value)) {
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'usergroups':
                $value = $this->get_usergroups_type_value();
                $this->row[$this->field->realfieldname] = $value;
                return ($value === null ? null : $this->field->realfieldname . '=' . $this->ct->db->Quote($value));

            case 'language':
                $value = $this->get_customtables_type_language();
                $this->row[$this->field->realfieldname] = $value;
                return ($value === null ? null : $this->field->realfieldname . '=' . $this->ct->db->Quote($value));

            case 'float':
                $value = $this->ct->Env->jinput->get($this->field->comesfieldname, null, 'FLOAT');

                if (isset($value)) {
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . (float)$value;
                }
                break;

            case 'image':

                $to_delete = $this->ct->Env->jinput->post->get($this->field->comesfieldname . '_delete', '', 'CMD');
                $returnValue = null;

                if ($to_delete == 'true') {
                    $this->row[$this->field->realfieldname] = null;
                    $returnValue = $this->field->realfieldname . '=NULL';

                    $ExistingImage = Tree::isRecordExist($listing_id, $this->ct->Table->realidfieldname, $this->field->realfieldname, $this->field->ct->Table->realtablename);

                    if ($ExistingImage !== null and ($ExistingImage != '' or (is_numeric($ExistingImage) and $ExistingImage > 0))) {

                        $imageMethods = new CustomTablesImageMethods;
                        $ImageFolder = CustomTablesImageMethods::getImageFolder($this->field->params);
                        $imageMethods->DeleteExistingSingleImage(
                            $ExistingImage,
                            JPATH_SITE . DIRECTORY_SEPARATOR . $ImageFolder,
                            $this->field->params[0],
                            $this->field->ct->Table->realtablename,
                            $this->field->realfieldname,
                            $this->field->ct->Table->realidfieldname);
                    }
                }

                $tempValue = $this->ct->Env->jinput->post->getString($this->field->comesfieldname);
                if ($tempValue !== null and $tempValue != '') {

                    require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_image.php');

                    $value = CT_FieldTypeTag_image::get_image_type_value($this->field, $this->ct->Table->realidfieldname, $listing_id);
                    $this->row[$this->field->realfieldname] = $value;

                    return ($value === null ? $this->field->realfieldname . '=NULL' : $this->field->realfieldname . '=' . $this->ct->db->Quote($value));
                }

                if ($returnValue !== null)
                    return $returnValue;

                break;

            case 'blob':

                $to_delete = $this->ct->Env->jinput->post->get($this->field->comesfieldname . '_delete', '', 'CMD');
                $value = CT_FieldTypeTag_file::get_blob_value($this->field);

                $fileNameField = '';
                if (isset($this->field->params[2])) {
                    $fileNameField_String = $this->field->params[2];
                    $fileNameField_Row = Fields::FieldRowByName($fileNameField_String, $this->ct->Table->fields);
                    $fileNameField = $fileNameField_Row['realfieldname'];
                }

                if ($to_delete == 'true' and $value === null) {

                    $this->row[$this->field->realfieldname] = null;

                    if ($fileNameField != '' and !$this->checkIfFieldAlreadyInTheList($fileNameField))
                        $this->row[$fileNameField] = null;

                    return $this->field->realfieldname . '=NULL';
                } else {
                    $this->row[$this->field->realfieldname] = strlen($value);

                    if ($fileNameField != '') {
                        $file_id = $this->ct->Env->jinput->post->get($this->field->comesfieldname, '', 'STRING');
                        $file_name_parts = explode('_', $file_id);
                        $file_name = implode('_', array_slice($file_name_parts, 3));
                        $this->row[$fileNameField] = $file_name;

                        $sets = array();
                        if ($value !== null and !$this->checkIfFieldAlreadyInTheList($fileNameField))
                            $sets[] = $fileNameField . '=' . $this->ct->db->Quote($file_name);

                        $sets[] = ($value === null ? null : $this->field->realfieldname . '=FROM_BASE64("' . base64_encode($value) . '")');
                        return $sets;
                    } else
                        return ($value === null ? null : $this->field->realfieldname . '=FROM_BASE64("' . base64_encode($value) . '")');
                }

            case 'file':

                $file_type_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
                require_once($file_type_file);

                $value = CT_FieldTypeTag_file::get_file_type_value($this->field, $listing_id);

                $to_delete = $this->ct->Env->jinput->post->get($this->field->comesfieldname . '_delete', '', 'CMD');

                if ($to_delete == 'true' and $value === null) {
                    $this->row[$this->field->realfieldname] = null;
                    return $this->field->realfieldname . '=NULL';
                } else {
                    $this->row[$this->field->realfieldname] = $value;
                    return ($value === null ? null : $this->field->realfieldname . '=' . $this->ct->db->Quote($value));
                }

            case 'signature':

                $value = $this->get_customtables_type_signature();
                $this->row[$this->field->realfieldname] = $value;
                return ($value === null ? null : $this->field->realfieldname . '=' . $this->ct->db->Quote($value));

            case 'multilangarticle':

                $sets = [];
                $firstLanguage = true;
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $value = $this->ct->Env->jinput->getInt($this->field->comesfieldname . $postfix);

                    if (isset($value)) {
                        $this->row[$this->field->realfieldname . $postfix] = $value;
                        $sets[] = $this->field->realfieldname . $postfix . '=' . $value;
                    }
                }

                return (count($sets) > 0 ? $sets : null);

            case 'customtables':

                $value = $this->get_customtables_type_value();
                $this->row[$this->field->realfieldname] = $value;
                return ($value === null ? null : $this->field->realfieldname . '=' . $this->ct->db->Quote($value));

            case 'email':
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);
                if (isset($value)) {
                    $value = trim($value);
                    if (Email::checkEmail($value)) {
                        $this->row[$this->field->realfieldname] = $value;
                        return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                    } else {
                        $this->row[$this->field->realfieldname] = null;
                        return $this->field->realfieldname . '=' . $this->ct->db->Quote("");//PostgreSQL compatible
                    }
                }
                break;

            case 'url':
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);
                if (isset($value)) {
                    $value = trim($value);

                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                        $this->row[$this->field->realfieldname] = $value;
                        return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                    } else {
                        $this->row[$this->field->realfieldname] = null;
                        return $this->field->realfieldname . '=' . $this->ct->db->Quote("");//PostgreSQL compatible
                    }
                }
                break;

            case 'checkbox':
                $value = $this->ct->Env->jinput->getCmd($this->field->comesfieldname);

                if ($value !== null) {
                    if ((int)$value == 1 or $value == 'on')
                        $value = 1;
                    else
                        $value = 0;

                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $value;
                } else {
                    $value = $this->ct->Env->jinput->getCmd($this->field->comesfieldname . '_off');
                    if ($value !== null) {
                        if ((int)$value == 1) {
                            $this->row[$this->field->realfieldname] = 0;
                            return $this->field->realfieldname . '=0';
                        } else {
                            $this->row[$this->field->realfieldname] = 1;
                            return $this->field->realfieldname . '=1';
                        }
                    }
                }
                break;

            case 'date':
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);
                if (isset($value)) {
                    if ($value == '' or $value == '0000-00-00') {

                        $this->row[$this->field->realfieldname] = null;

                        if (Fields::isFieldNullable($this->ct->Table->realtablename, $this->field->realfieldname))
                            return $this->field->realfieldname . '=NULL';
                        else
                            return $this->field->realfieldname . '=' . $this->ct->db->Quote('0000-00-00 00:00:00');
                    } else {
                        $this->row[$this->field->realfieldname] = $value;
                        return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                    }
                }
                break;

            case 'time':
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);
                if (isset($value)) {
                    if ($value == '') {
                        $this->row[$this->field->realfieldname] = null;
                        return $this->field->realfieldname . '=NULL';
                    } else {
                        $this->row[$this->field->realfieldname] = (int)$value;
                        return $this->field->realfieldname . '=' . (int)$value;
                    }
                }
                break;

            case 'creationtime':
                if ($this->row[$this->ct->Table->realidfieldname] == 0 or $this->row[$this->ct->Table->realidfieldname] == '' or $this->isCopy) {
                    $value = gmdate('Y-m-d H:i:s');
                    $this->row[$this->field->realfieldname] = $value;

                    return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                }
                break;

            case 'changetime':
                $value = gmdate('Y-m-d H:i:s');
                $this->row[$this->field->realfieldname] = $value;
                return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);

            case 'server':

                if (count($this->field->params) == 0)
                    $value = self::getUserIP(); //Try to get client real IP
                else
                    $value = $this->ct->Env->jinput->server->get($this->field->params[0], '', 'STRING');

                $this->row[$this->field->realfieldname] = $value;
                return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);

            case 'id':
                //get max id
                if ($this->row[$this->ct->Table->realidfieldname] == 0 or $this->row[$this->ct->Table->realidfieldname] == '' or $this->isCopy) {
                    $minid = (int)$this->field->params[0];

                    $query = 'SELECT MAX(' . $this->ct->Table->realidfieldname . ') AS maxid FROM ' . $this->ct->Table->realtablename . ' LIMIT 1';
                    $this->ct->db->setQuery($query);
                    $rows = $this->ct->db->loadObjectList();
                    if (count($rows) != 0) {
                        $value = (int)($rows[0]->maxid) + 1;
                        if ($value < $minid)
                            $value = $minid;

                        $this->row[$this->field->realfieldname] = $value;
                        return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                    }
                }
                break;

            case 'md5':

                $vlu = '';
                $fields = explode(',', $this->field->params[0]);
                foreach ($fields as $f1) {
                    if ($f1 != $this->field->fieldname) {
                        //to make sure that field exists
                        foreach ($this->ct->Table->fields as $f2) {
                            if ($f2['fieldname'] == $f1)
                                $vlu .= $this->row[$f2['realfieldname']];
                        }
                    }
                }

                if ($vlu != '') {
                    $value = md5($vlu);
                    $this->row[$this->field->realfieldname] = $value;
                    return $this->field->realfieldname . '=' . $this->ct->db->Quote($value);
                }
                break;
        }
        return null;
    }

    public static function get_record_type_value(CT $ct, Field $field): ?string
    {
        if (count($field->params) > 2) {
            $esr_selector = $field->params[2];
            $selectorPair = explode(':', $esr_selector);

            switch ($selectorPair[0]) {
                case 'single';
                    $value = $ct->Env->jinput->getInt($field->comesfieldname);

                    if (isset($value))
                        return $value;

                    break;

                case 'radio':
                case 'checkbox':
                case 'multi':

                    //returns NULL if field parameter not found - nothing to save
                    //returns empty array if nothing selected - save empty value
                    $valueArray = $ct->Env->jinput->post->get($field->comesfieldname, null, 'array');

                    if ($valueArray) {
                        return self::getCleanRecordValue($valueArray);
                    } else {
                        $value_off = $ct->Env->jinput->post->getInt($field->comesfieldname . '_off');
                        if ($value_off) {
                            return '';
                        } else {
                            return null;
                        }
                    }

                case 'multibox';
                    $valueArray = $ct->Env->jinput->post->get($field->comesfieldname, null, 'array');

                    if (isset($valueArray)) {
                        return self::getCleanRecordValue($valueArray);
                    }
                    break;
            }
        }
        return null;
    }

    protected static function getCleanRecordValue($array): string
    {
        $values = array();
        foreach ($array as $a) {
            if ((int)$a != 0)
                $values[] = (int)$a;
        }
        return ',' . implode(',', $values) . ',';
    }

    protected function toHex($n): string
    {
        $n = intval($n);
        if (!$n)
            return '00';

        $n = max(0, min($n, 255)); // make sure the $n is not bigger than 255 and not less than 0
        $index1 = (int)($n - ($n % 16)) / 16;
        $index2 = (int)$n % 16;

        return substr("0123456789ABCDEF", $index1, 1)
            . substr("0123456789ABCDEF", $index2, 1);
    }

    public function get_alias_type_value($listing_id)
    {
        $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);
        if (!isset($value))
            return null;

        $value = $this->prepare_alias_type_value($listing_id, $value);
        if ($value == '')
            return null;

        return $value;
    }

    public function prepare_alias_type_value($listing_id, $value)
    {
        $value = JoomlaBasicMisc::slugify($value);

        if ($value == '')
            return '';

        if (!$this->checkIfAliasExists($listing_id, $value, $this->field->realfieldname))
            return $value;

        $val = $this->splitStringToStringAndNumber($value);

        $value_new = $val[0];
        $i = $val[1];

        while (1) {
            if ($this->checkIfAliasExists($listing_id, $value_new, $this->field->realfieldname)) {
                //increase index
                $i++;
                $value_new = $val[0] . '-' . $i;
            } else
                break;
        }
        return $value_new;
    }

    protected function checkIfAliasExists($exclude_id, $value, $realfieldname): bool
    {
        $query = 'SELECT count(' . $this->ct->Table->realidfieldname . ') AS c FROM ' . $this->ct->Table->realtablename . ' WHERE '
            . $this->ct->Table->realidfieldname . '!=' . (int)$exclude_id . ' AND ' . $realfieldname . '=' . $this->ct->db->quote($value) . ' LIMIT 1';

        $this->ct->db->setQuery($query);

        $rows = $this->ct->db->loadObjectList();
        if (count($rows) == 0)
            return false;

        $c = (int)$rows[0]->c;

        if ($c > 0)
            return true;

        return false;
    }

    protected function splitStringToStringAndNumber($string): array
    {
        if ($string == '')
            return array('', 0);

        $pair = explode('-', $string);
        $l = count($pair);

        if ($l == 1)
            return array($string, 0);

        $c = end($pair);
        if (is_numeric($c)) {
            unset($pair[$l - 1]);
            $pair = array_values($pair);
            $val = array(implode('-', $pair), intval($c));
        } else
            return array($string, 0);

        return $val;
    }

    protected function get_usergroups_type_value(): ?string
    {
        switch ($this->field->params[0]) {
            case 'radio':
            case 'single';
                $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);
                if (isset($value))
                    return ',' . $value . ',';

                break;
            case 'multibox':
            case 'checkbox':
            case 'multi';
                $valueArray = $this->ct->Env->jinput->post->get($this->field->comesfieldname, null, 'array');
                if (isset($valueArray))
                    return ',' . implode(',', $valueArray) . ',';

                break;
        }
        return null;
    }

    protected function get_customtables_type_language(): ?string
    {
        $value = $this->ct->Env->jinput->getCmd($this->field->comesfieldname);

        if (isset($value))
            return $value;

        return null;
    }

    function checkIfFieldAlreadyInTheList($fieldName): bool
    {
        foreach ($this->saveQuery as $query) {
            $parts = explode('=', $query);

            if ($parts[0] == $fieldName)
                return true;
        }
        return false;
    }

    protected function get_customtables_type_signature(): ?string
    {
        $value = $this->ct->Env->jinput->getString($this->field->comesfieldname);

        if (isset($value)) {
            $ImageFolder = CustomTablesImageMethods::getImageFolder($this->field->params);

            $format = $this->field->params[3] ?? 'png';

            if ($format == 'svg-db') {
                return $value;
            } else {
                if ($format == 'jpeg')
                    $format = 'jpg';

                //Get new file name and avoid possible duplicate

                $i = 0;
                do {
                    $ImageID = date("YmdHis") . ($i > 0 ? $i : '');
                    //there is possible error, check all possible ext
                    $image_file = JPATH_SITE . DIRECTORY_SEPARATOR . $ImageFolder . DIRECTORY_SEPARATOR . $ImageID . '.' . $format;
                    $i++;
                } while (file_exists($image_file));

                $parts = explode(';base64,', $value);

                $decoded_binary = base64_decode($parts[1]);
                file_put_contents($image_file, $decoded_binary);

                return $ImageID;
            }
        }
        return null;
    }

    protected function get_customtables_type_value(): ?string
    {
        $optionname = $this->field->params[0];

        if ($this->field->params[1] == 'multi') {
            $value = $this->getMultiString($optionname);

            if ($value !== null) {
                if ($value != '')
                    return ',' . $value . ',';
                else
                    return '';
            }
        } elseif ($this->field->params[1] == 'single') {
            $value = $this->getComboString($optionname);

            if ($value !== null) {
                if ($value != '')
                    return ',' . $value . ',';
                else
                    return '';
            }
        }
        return null;
    }

    protected function getMultiString($parent): ?string
    {
        $prefix = $this->field->prefix . 'multi_' . $this->ct->Table->tablename . '_' . $this->field->fieldname;

        $parentId = Tree::getOptionIdFull($parent);
        $a = $this->getMultiSelector($parentId, $parent, $prefix);
        if ($a === null)
            return null;

        if (count($a) == 0)
            return '';
        else
            return implode(',', $a);

    }

    protected function getMultiSelector($parentId, $parentName, $prefix): ?array
    {
        $set = false;
        $resultList = array();

        $rows = $this->getList($parentId);
        if (count($rows) < 1)
            return $resultList;

        foreach ($rows as $row) {
            if (strlen($parentName) == 0)
                $ChildList = $this->getMultiSelector($row->id, $row->optionname, $prefix);
            else
                $ChildList = $this->getMultiSelector($row->id, $parentName . '.' . $row->optionname, $prefix);

            if ($ChildList !== null)
                $count_child = count($ChildList);
            else
                $count_child = 0;

            if ($count_child > 0) {
                $resultList = array_merge($resultList, $ChildList);
            } else {
                $value = $this->ct->Env->jinput->getString($prefix . '_' . $row->id);
                if (isset($value)) {
                    $set = true;

                    if (strlen($parentName) == 0)
                        $resultList[] = $row->optionname . '.';
                    else
                        $resultList[] = $parentName . '.' . $row->optionname . '.';
                }
            }
        }

        if (!$set)
            return null;

        return $resultList;
    }

    protected function getList($parentId)
    {
        $query = 'SELECT id, optionname FROM #__customtables_options WHERE parentid=' . (int)$parentId;
        $this->ct->db->setQuery($query);
        return $this->ct->db->loadObjectList();
    }

    protected function getComboString($parent): ?string
    {
        $prefix = $this->field->prefix . 'combotree_' . $this->ct->Table->tablename . '_' . $this->field->fieldname;
        $i = 1;
        $result = array();
        $v = '';
        $set = false;
        do {
            $value = $this->ct->Env->jinput->getCmd($prefix . '_' . $i);
            if (isset($value)) {
                if ($value != '') {
                    $result[] = $value;
                    $i++;
                }
                $set = true;
            } else
                break;

        } while ($v != '');

        if (count($result) == 0) {
            if ($set)
                return '';
            else
                return null;
        } else
            return $parent . '.' . implode('.', $result) . '.';

        // the format of the string is: ",[optionname1].[optionname2].[optionname..n].,
        // example: ,geo.usa.newyork.,
        // last "." dot is to let search by parents
        // php example: getpos(",geo.usa.",$string)
        // mysql example: instr($string, ",geo.usa.")
    }

    public static function getUserIP(): string
    {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
                $address = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($address[0]);
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    function applyDefaults($fieldRow): ?string
    {
        $this->field = new Field($this->ct, $fieldRow, $this->row);
        if (!Fields::isVirtualField($fieldRow) and $this->field->defaultvalue != "" and (!isset($this->row[$this->field->realfieldname]) or is_null($this->row[$this->field->realfieldname])) and $this->field->type != 'dummie') {

            if ($this->ct->Env->legacySupport) {
                $LayoutProc = new LayoutProcessor($this->ct);
                $LayoutProc->layout = $this->field->defaultvalue;
                $this->field->defaultvalue = $LayoutProc->fillLayout($this->row);
            }

            $twig = new TwigProcessor($this->ct, $this->field->defaultvalue);
            $value = $twig->process($this->row);

            if ($twig->errorMessage !== null)
                $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

            if ($value == '') {
                $this->row[$this->field->realfieldname] = null;
                return $this->field->realfieldname . '=NULL';
            } else {
                $this->row[$this->field->realfieldname] = $value;
                return $this->field->realfieldname . '=' . $this->ct->db->quote($value);
            }

        } elseif ($fieldRow['type'] == 'virtual') {

            $storage = $this->field->params[1] ?? '';

            if ($storage == "storedintegersigned" or $storage == "storedintegerunsigned" or $storage == "storedstring") {

                try {
                    $code = str_replace('****quote****', '"', $this->field->params[0]);
                    $code = str_replace('****apos****', "'", $code);
                    $twig = new TwigProcessor($this->ct, $code, false, false, true);
                    $value = @$twig->process($this->row);

                    if ($twig->errorMessage !== null) {
                        echo $twig->errorMessage;
                        $this->ct->app->enqueueMessage($twig->errorMessage, 'error');
                        return null;
                    }

                } catch (Exception $e) {
                    echo $e->getMessage();
                    $this->ct->app->enqueueMessage($e->getMessage(), 'error');
                    return null;
                }

                if ($storage == "storedintegersigned" or $storage == "storedintegerunsigned")
                    return $this->field->realfieldname . '=' . (int)$value;

                return $this->field->realfieldname . '=' . $this->ct->db->quote($value);
            }
        }
        return null;
    }

    public function Try2CreateUserAccount($field): bool
    {
        $uid = (int)$this->ct->Table->record[$field->realfieldname];

        if ($uid != 0) {

            $email = $this->ct->Env->user->email . '';
            if ($email != '') {
                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS'), 'error');
                return false; //all good, user already assigned.
            }
        }

        if (count($field->params) < 3) {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('User field name parameters count is less than 3.'), 'error');
            return false;
        }

        //Try to create user
        $new_parts = array();

        foreach ($field->params as $part) {

            if ($this->ct->Env->legacySupport) {
                tagProcessor_General::process($this->ct, $part, $this->ct->Table->record);
                tagProcessor_Item::process($this->ct, $part, $this->ct->Table->record, '');
                tagProcessor_If::process($this->ct, $part, $this->ct->Table->record);
                tagProcessor_Page::process($this->ct, $part);
                tagProcessor_Value::processValues($this->ct, $part, $this->ct->Table->record);
            }

            $twig = new TwigProcessor($this->ct, $part, false, false, false);
            $part = $twig->process($this->ct->Table->record);

            if ($twig->errorMessage !== null) {
                $this->ct->app->enqueueMessage($twig->errorMessage, 'error');
                return false;
            }

            $new_parts[] = $part;
        }

        $user_groups = $new_parts[0];
        $user_name = $new_parts[1];
        $user_email = $new_parts[2];

        if ($user_groups == '') {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('User group field not set.'));
            return false;
        } elseif ($user_name == '') {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('User name field not set.'));
            return false;
        } elseif ($user_email == '') {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('User email field not set.'));
            return false;
        }

        $unique_users = false;
        if (isset($new_parts[4]) and $new_parts[4] == 'unique')
            $unique_users = true;

        $existing_user_id = CTUser::CheckIfEmailExist($user_email, $existing_user, $existing_name);

        if ($existing_user_id) {
            if (!$unique_users) //allow not unique record per users
            {
                CTUser::UpdateUserField($this->ct->Table->realtablename, $this->ct->Table->realidfieldname, $field->realfieldname,
                    $existing_user_id, $this->ct->Table->record[$this->ct->Table->realidfieldname]);

                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_USER_UPDATED'));
            } else {
                $this->ct->app->enqueueMessage(
                    JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_USER_WITH_EMAIL')
                    . ' "' . $user_email . '" '
                    . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS'), 'Error');
            }
        } else {
            CTUser::CreateUser($this->ct->Table->realtablename, $this->ct->Table->realidfieldname, $user_email, $user_name,
                $user_groups, $this->ct->Table->record[$this->ct->Table->realidfieldname], $field->realfieldname);
        }
        return true;
    }

    function runUpdateQuery($saveQuery, $listing_id): void
    {
        if (count($saveQuery) > 0) {
            $query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET ' . implode(', ', $saveQuery) . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);

            $this->ct->db->setQuery($query);
            try {
                $this->ct->db->execute();
            } catch (Exception $e) {
                $this->ct->app->enqueueMessage($e->getMessage(), 'error');
            }
        }
    }

    public function checkSendEmailConditions(string $listing_id, string $condition): bool
    {
        if ($condition == '')
            return true; //if no conditions

        $this->ct->Table->loadRecord($listing_id);
        $Layouts = new Layouts($this->ct);
        $parsed_condition = $Layouts->parseRawLayoutContent($condition);
        $parsed_condition = '(' . $parsed_condition . ' ? 1 : 0)';

        $error = '';
        if ($this->ct->Env->advancedTagProcessor)
            $value = CleanExecute::execute($parsed_condition, $error);
        else
            $value = $parsed_condition;

        if ($error != '') {
            $this->ct->app->enqueueMessage($error, 'error');
            return false;
        }

        if ((int)$value == 1)
            return true;

        return false;
    }

    function sendEmailIfAddressSet(string $listing_id, array $row)//,$new_username,$new_password)
    {
        if ($this->ct->Params->onRecordAddSendEmailTo != '')
            $status = $this->sendEmailNote($listing_id, $this->ct->Params->onRecordAddSendEmailTo, $row);
        else
            $status = $this->sendEmailNote($listing_id, $this->ct->Params->onRecordSaveSendEmailTo, $row);

        if ($this->ct->Params->emailSentStatusField != '') {

            foreach ($this->ct->Table->fields as $fieldrow) {
                $fieldname = $fieldrow['fieldname'];
                if ($this->ct->Params->emailSentStatusField == $fieldname) {

                    $query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET es_' . $fieldname . '=' . $status . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($listing_id);

                    $this->ct->db->setQuery($query);
                    $this->ct->db->execute();
                    return;
                }
            }
        }
    }

    function sendEmailNote(string $listing_id, string $emails, array $row): int
    {
        $this->ct->Table->loadRecord($listing_id);

        //Prepare Email List
        $emails_raw = JoomlaBasicMisc::csv_explode(',', $emails, '"', true);

        $emails = array();
        foreach ($emails_raw as $SendToEmail) {
            $EmailPair = JoomlaBasicMisc::csv_explode(':', trim($SendToEmail));
            $Layouts = new Layouts($this->ct);
            $EmailTo = $Layouts->parseRawLayoutContent(trim($EmailPair[0]), false);

            if (isset($EmailPair[1]) and $EmailPair[1] != '')
                $Subject = $Layouts->parseRawLayoutContent($EmailPair[1]);
            else
                $Subject = 'Record added to "' . $this->ct->Table->tabletitle . '"';

            if ($EmailTo != '')
                $emails[] = array('email' => $EmailTo, 'subject' => $Subject);
        }

        $Layouts = new Layouts($this->ct);
        $message_layout_content = $Layouts->getLayout($this->ct->Params->onRecordAddSendEmailLayout);
        $note = $Layouts->parseRawLayoutContent($message_layout_content);
        $status = 0;

        foreach ($emails as $SendToEmail) {
            $EmailTo = $SendToEmail['email'];
            $Subject = $SendToEmail['subject'];

            $attachments = [];

            $options = array();
            $fList = JoomlaBasicMisc::getListToReplace('attachment', $options, $note, '{}');
            $i = 0;
            $note_final = $note;
            foreach ($fList as $fItem) {
                $filename = $options[$i];
                if (file_exists($filename)) {
                    $attachments[] = $filename;//TODO: Check the functionality
                    $vlu = '';
                } else
                    $vlu = '<p>File "' . $filename . '"not found.</p>';

                $note_final = str_replace($fItem, $vlu, $note);
                $i++;
            }

            foreach ($this->ct->Table->fields as $fieldrow) {
                if ($fieldrow['type'] == 'file') {
                    $field = new Field($this->ct, $fieldrow, $row);
                    $FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[0]);

                    $filename = $FileFolder . $this->ct->Table->record[$fieldrow['realfieldname']];
                    if (file_exists($filename))
                        $attachments[] = $filename;//TODO: Check the functionality
                }
            }

            $sent = Email::sendEmail($EmailTo, $Subject, $note_final, true, $attachments);

            if ($sent !== true) {
                //Something went wrong. Email not sent.
                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_SENDING_EMAIL') . ': ' . $EmailTo . ' (' . $Subject . ')', 'error');
                $status = 0;
            } else {
                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EMAIL_SENT_TO') . ': ' . $EmailTo . ' (' . $Subject . ')');
                $status = 1;
            }
        }
        return $status;
    }

}
