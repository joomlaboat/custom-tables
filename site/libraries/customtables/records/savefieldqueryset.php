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
use LayoutProcessor;
use tagProcessor_General;
use tagProcessor_Item;
use tagProcessor_If;
use tagProcessor_Page;
use tagProcessor_Value;
use CustomTables\CustomPHP;

class SaveFieldQuerySet
{
    var CT $ct;
    public Field $field;
    var ?array $row_old;
    var ?array $row_new;
    var bool $isCopy;

    function __construct(CT &$ct, $row, $isCopy = false)
    {
        $this->ct = &$ct;
        $this->row_old = $row;
        $this->row_new = [];
        $this->isCopy = $isCopy;
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    function getSaveFieldSet($fieldRow): void
    {
        $this->field = new Field($this->ct, $fieldRow, $this->row_old);
        $this->getSaveFieldSetType();

        if ($this->field->defaultvalue != "" and !isset($this->row_old[$this->field->realfieldname]))
            $this->applyDefaults($fieldRow);
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function getSaveFieldSetType(): void
    {
        if ($this->row_old !== null and key_exists($this->ct->Table->realidfieldname, $this->row_old))
            $listing_id = $this->row_old[$this->ct->Table->realidfieldname];
        else
            $listing_id = null;

        switch ($this->field->type) {
            case 'records':
                $value = self::get_record_type_value($this->field);
                if ($value === null) {
                    return;
                } elseif ($value === '') {
                    $this->setNewValue(null);
                    return;
                }
                $this->setNewValue($value);
                return;

            case 'sqljoin':
                $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');

                if (isset($value)) {
                    $value = preg_replace("/[^A-Za-z\d\-]/", '', $value);

                    if ($value === null or $value == '') {
                        $this->setNewValue(null);
                        return;
                    }

                    if (is_numeric($value)) {
                        if ($value == 0) {
                            $this->setNewValue(null);
                            return;
                        }
                    }
                    $this->setNewValue($value);
                    return;
                }
                break;

            case 'googlemapcoordinates':
            case 'filelink':
            case 'string':
            case 'radio':
                $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');

                if (isset($value)) {
                    $this->setNewValue($value);
                    return;
                }
                break;

            case 'color':
                $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');
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
                        $value = common::inputPostAlnum($this->field->comesfieldname, '', 'create-edit-record');

                    $value = strtolower($value);
                    $value = str_replace('#', '', $value);
                    if (ctype_xdigit($value) or $value == '') {
                        $this->setNewValue($value);
                        return;
                    }
                }
                break;

            case 'alias':
                $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');

                if (isset($value)) {
                    $value = $this->get_alias_type_value($listing_id);
                    $this->setNewValue($value);
                    return;
                }
                break;

            case 'multilangstring':

                $firstLanguage = true;
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $value = common::inputPostString($this->field->comesfieldname . $postfix, null, 'create-edit-record');

                    if (isset($value)) {
                        $this->row_old[$this->field->realfieldname . $postfix] = $value;
                        $this->row_new[$this->field->realfieldname . $postfix] = $value;
                    }
                }
                return;

            case 'text':
                $value = common::inputPostRaw($this->field->comesfieldname, null, 'create-edit-record');

                if (isset($value)) {
                    $this->setNewValue(common::filterText($value));
                    return;
                }
                break;

            case 'multilangtext':

                $firstLanguage = true;
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $value = common::inputPostRaw($this->field->comesfieldname . $postfix, null, 'create-edit-record');

                    if ($value !== null) {
                        $value = common::filterText($value);
                        $this->row_old[$this->field->realfieldname . $postfix] = $value;
                        $this->row_new[$this->field->realfieldname . $postfix] = $value;
                    }
                }
                return;

            case 'ordering':
                $value = common::inputPostInt($this->field->comesfieldname, null, 'create-edit-record');

                if (isset($value)) // always check with isset(). null doesn't work as 0 is null somehow in PHP
                {
                    $this->setNewValue($value);
                    return;
                }
                break;

            case 'int':
                $value = common::inputPostInt($this->field->comesfieldname, null, 'create-edit-record');

                if (!is_null($value)) // always check with isset(). null doesn't work as 0 is null somehow in PHP
                {
                    $this->setNewValue($value);
                    return;
                }
                break;

            case 'user':
                $value = common::inputPostInt($this->field->comesfieldname, null, 'create-edit-record');

                if (isset($value)) {
                    $value = common::inputPostInt($this->field->comesfieldname, null, 'create-edit-record');

                    if ($value == 0)
                        $value = null;

                    $this->setNewValue($value);
                }
                break;

            case 'userid':

                if ($this->ct->isRecordNull($this->row_old) or $this->isCopy) {

                    $value = common::inputPostInt($this->field->comesfieldname, null, 'create-edit-record');

                    if ((!isset($value) or $value == 0)) {

                        if ($value == 0)
                            $value = null;

                        if (!$this->ct->isRecordNull($this->row_old)) {
                            if ($this->row_old[$this->field->realfieldname] == null or $this->row_old[$this->field->realfieldname] == "")
                                $value = ($this->ct->Env->user->id != 0 ? $this->ct->Env->user->id : 0);
                        } else {
                            $value = ($this->ct->Env->user->id != 0 ? $this->ct->Env->user->id : 0);
                        }
                    }
                    $this->setNewValue($value);
                    return;
                }

                $value = common::inputPostInt($this->field->comesfieldname, null, 'create-edit-record');
                if ($value == 0)
                    $value = null;

                if (isset($value) and $value != 0) {
                    $this->setNewValue($value);
                    return;
                }
                break;

            case 'article':

            case 'usergroup':
                if (defined('_JEXEC'))
                    $value = common::inputPostInt($this->field->comesfieldname);
                elseif (defined('WPINC'))
                    $value = common::inputPostCmd($this->field->comesfieldname, null, 'create-edit-record');
                else
                    return;

                if (isset($value)) {
                    $this->setNewValue($value);
                    return;
                }
                break;

            case 'usergroups':

                require_once 'usergroups.php';
                $usergroups = new Save_usergroups($this->ct, $this->field, $this->row_new);
                $value = $usergroups->saveFieldSet();

                //This way it will be clear if the value changed or not. If $this->newValue = null means that value not changed.
                if ($value !== null and is_array($value))
                    $this->setNewValue($value['value']);

                return;

            case 'language':
                $value = $this->get_customtables_type_language();
                $this->setNewValue($value);
                return;

            case 'float':
                $value = common::inputPostFloat($this->field->comesfieldname, null, 'create-edit-record');

                if (isset($value)) {
                    $this->setNewValue($value);
                    return;
                }
                break;

            case 'image':

                require_once 'image.php';
                $image = new Save_image($this->ct, $this->field);
                $value = $image->saveFieldSet($listing_id);

                //This way it will be clear if the value changed or not. If $this->newValue = null means that value not changed.
                if ($value !== null and is_array($value))
                    $this->setNewValue($value['value']);

                return;

            case 'blob':

                require_once 'blob.php';
                $image = new Save_blob($this->ct, $this->field, $this->row_new);
                $value = $image->saveFieldSet($listing_id);

                //This way it will be clear if the value changed or not. If $this->newValue = null means that value not changed.
                if ($value !== null and is_array($value))
                    $this->setNewValue($value['value']);

                return;

            case 'file':

                require_once 'file.php';
                $image = new Save_file($this->ct, $this->field, $this->row_new);
                $value = $image->saveFieldSet($listing_id);

                //This way it will be clear if the value changed or not. If $this->newValue = null means that value not changed.
                if ($value !== null and is_array($value))
                    $this->setNewValue($value['value']);

                return;

            case 'signature':

                if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers')) {
                    $value = ctProHelpers::get_customtables_type_signature($this->field->comesfieldname, $this->field->params, $this->field->params[3] ?? 'png');
                    $this->setNewValue($value);
                }
                return;

            case 'email':
                $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');

                if ($value !== null) {
                    $value = trim($value);
                    if (Email::checkEmail($value))
                        $this->setNewValue($value);
                    else
                        $this->setNewValue(null);
                }
                return;

            case 'url':
                $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');
                if ($value !== null) {
                    $value = trim($value);
                    if (filter_var($value, FILTER_VALIDATE_URL))
                        $this->setNewValue($value);
                    else
                        $this->setNewValue(null);
                }
                return;

            case 'checkbox':
                $value = common::inputPostCmd($this->field->comesfieldname, null, 'create-edit-record');

                if ($value !== null) {
                    if ((int)$value == 1 or $value == 'on')
                        $value = 1;
                    else
                        $value = 0;

                    $this->setNewValue($value);
                } else {
                    $value = common::inputPostCmd($this->field->comesfieldname . '_off', null, 'create-edit-record');
                    if ($value !== null) {
                        if ((int)$value == 1)
                            $this->setNewValue(0);
                        else
                            $this->setNewValue(1);
                    }
                }
                return;

            case 'date':
                $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');

                if (isset($value)) {
                    if ($value == '' or $value == '0000-00-00') {

                        if (Fields::isFieldNullable($this->ct->Table->realtablename, $this->field->realfieldname)) {
                            $this->setNewValue(null);
                        } else {
                            $this->setNewValue('0000-00-00 00:00:00');
                        }
                    } else {
                        $this->setNewValue($value);
                    }
                }
                return;

            case 'time':
                $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');
                if (isset($value)) {
                    if ($value == '') {
                        $this->setNewValue(null);
                    } else {
                        $this->setNewValue((int)$value);
                    }
                }
                return;

            case 'creationtime':
                if ($this->row_old[$this->ct->Table->realidfieldname] == 0 or $this->row_old[$this->ct->Table->realidfieldname] == '' or $this->isCopy)
                    $this->setNewValue(common::currentDate());
                return;

            case 'changetime':
                $this->setNewValue(common::currentDate());
                return;

            case 'server':

                if ($this->field->params === null or count($this->field->params) == 0)
                    $value = self::getUserIP(); //Try to get client real IP
                else
                    $value = common::inputServer($this->field->params[0], '', 'STRING');

                $this->setNewValue($value);
                return;

            case 'id':
                //get max id
                if ($this->row_old[$this->ct->Table->realidfieldname] == 0 or $this->row_old[$this->ct->Table->realidfieldname] == '' or $this->isCopy) {
                    $min_id = (($this->field->params !== null and count($this->field->params) > 0) ? (int)$this->field->params[0] : 0);
                    $whereClause = new MySQLWhereClause();
                    $rows = database::loadObjectList($this->ct->Table->realtablename, [['MAX', $this->ct->Table->realtablename, $this->field->realfieldname]], $whereClause, null, null, 1);

                    if (count($rows) != 0) {
                        $value = (int)($rows[0]->vlu) + 1;
                        if ($value < $min_id)
                            $value = $min_id;

                        $this->setNewValue($value);
                    }
                }
                return;

            case 'md5':

                $vlu = '';
                $fields = explode(',', ($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] : '');
                foreach ($fields as $f1) {
                    if ($f1 != $this->field->fieldname) {
                        //to make sure that field exists
                        foreach ($this->ct->Table->fields as $f2) {
                            if ($f2['fieldname'] == $f1)
                                $vlu .= $this->row_old[$f2['realfieldname']];
                        }
                    }
                }

                if ($vlu != '') {
                    $value = md5($vlu);
                    $this->setNewValue($value);
                }
        }
    }

    /**
     * @throws Exception
     * @since 3.0.0
     */
    public static function get_record_type_value(Field $field): ?string
    {
        if (count($field->params) > 2) {
            $esr_selector = $field->params[2];
            $selectorPair = explode(':', $esr_selector);

            switch ($selectorPair[0]) {
                case 'single';
                    $value = common::inputPostInt($field->comesfieldname, null, 'create-edit-record');

                    if (isset($value))
                        return $value;

                    break;

                case 'radio':
                case 'checkbox':
                case 'multi':

                    //returns NULL if field parameter not found - nothing to save
                    //returns empty array if nothing selected - save empty value
                    $valueArray = common::inputPost($field->comesfieldname, null, 'array');

                    if ($valueArray) {
                        return self::getCleanRecordValue($valueArray);
                    } else {
                        $value_off = common::inputPostInt($field->comesfieldname . '_off');
                        if ($value_off) {
                            return '';
                        } else {
                            return null;
                        }
                    }

                case 'multibox';
                    $valueArray = common::inputPost($field->comesfieldname, null, 'array');

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

    function setNewValue($value): void
    {
        //Original value but modified during the process
        $this->row_old[$this->field->realfieldname] = $value;
        //row_new is empty at the beginning and if record needs to be updated new item with the key is added.
        $this->row_new[$this->field->realfieldname] = $value;
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

    /**
     * @throws Exception
     * @since 3.0.0
     */
    public function get_alias_type_value($listing_id)
    {
        $value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');
        if (!isset($value))
            return null;

        $value = $this->prepare_alias_type_value($listing_id, $value);
        if ($value == '')
            return null;

        return $value;
    }

    /**
     * @throws Exception
     * @since 3.0.0
     */
    public function prepare_alias_type_value(?string $listing_id, string $value)
    {
        $value = CTMiscHelper::slugify($value);

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

    /**
     * @throws Exception
     * @since 3.0.0
     */
    protected function checkIfAliasExists(?string $exclude_id, string $value, string $realfieldname): bool
    {
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition($this->ct->Table->realidfieldname, $exclude_id, '!=');
        $whereClause->addCondition($realfieldname, $value);

        $rows = database::loadObjectList($this->ct->Table->realtablename, ['COUNT_ROWS'], $whereClause, null, null, 1);
        if (count($rows) == 0)
            return false;

        $c = (int)$rows[0]->record_count;

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

    /**
     * @throws Exception
     * @since 3.0.0
     */
    protected function get_customtables_type_language(): ?string
    {
        $value = common::inputPostCmd($this->field->comesfieldname, null, 'create-edit-record');

        if (isset($value))
            return $value;

        return null;
    }

    public static function getUserIP(): string
    {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $forwardAddress = common::getServerParam('HTTP_X_FORWARDED_FOR');
            if (!empty($forwardAddress)) {
                if (str_contains($forwardAddress, ',')) {
                    $address = explode(",", $forwardAddress);
                    return trim($address[0]);
                } else
                    return $forwardAddress;
            } else
                return common::getServerParam('REMOTE_ADDR');
        } else
            return common::getServerParam('REMOTE_ADDR');
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    function applyDefaults($fieldRow): void
    {
        $this->field = new Field($this->ct, $fieldRow, $this->row_old);

        if (!Fields::isVirtualField($fieldRow) and $this->field->defaultvalue != "" and !isset($this->row_old[$this->field->realfieldname]) and $this->field->type != 'dummy') {

            if ($this->ct->Env->legacySupport) {
                $LayoutProc = new LayoutProcessor($this->ct);
                $LayoutProc->layout = $this->field->defaultvalue;
                $this->field->defaultvalue = $LayoutProc->fillLayout($this->row_old);
            }

            $twig = new TwigProcessor($this->ct, $this->field->defaultvalue);
            $value = $twig->process($this->row_old);

            if ($twig->errorMessage !== null) {
                $this->ct->errors[] = $twig->errorMessage;
                return;
            }

            if ($value == '') {
                $this->setNewValue(null);
            } else {
                $this->setNewValue($value);
            }
        } elseif ($fieldRow['type'] == 'ordering') {
            $this->setNewValue(0);
        } elseif ($fieldRow['type'] == 'virtual') {

            $storage = $this->field->params[1] ?? '';

            if ($storage == "storedintegersigned" or $storage == "storedintegerunsigned" or $storage == "storedstring") {

                try {
                    $code = str_replace('****quote****', '"', ($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] : '');
                    $code = str_replace('****apos****', "'", $code);
                    $twig = new TwigProcessor($this->ct, $code, false, false, true);
                    $value = @$twig->process($this->row_old);

                    if ($twig->errorMessage !== null) {
                        $this->ct->errors[] = $twig->errorMessage;
                        return;
                    }

                } catch (Exception $e) {
                    $this->ct->errors[] = $e->getMessage();
                    return;
                }

                if ($storage == "storedintegersigned" or $storage == "storedintegerunsigned") {
                    $this->setNewValue((int)$value);
                    return;
                }

                $this->setNewValue($value);
            }
        }
    }

    function checkIfFieldAlreadyInTheList(string $realFieldName): bool
    {
        return isset($this->row_new[$realFieldName]);
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    public function Try2CreateUserAccount($field): bool
    {
        $uid = (int)$this->ct->Table->record[$field->realfieldname];

        if ($uid != 0) {

            $email = $this->ct->Env->user->email . '';
            if ($email != '') {
                $this->ct->messages[] = common::translate('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS');
                return false; //all good, user already assigned.
            }
        }

        if (count($field->params) < 3) {
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_USERACCOUNT_PARAMCOUNT_ERROR');
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
                $this->ct->errors[] = $twig->errorMessage;
                return false;
            }

            $new_parts[] = $part;
        }

        $user_groups = $new_parts[0];
        $user_name = $new_parts[1];
        $user_email = $new_parts[2];

        if ($user_groups == '') {
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_USERACCOUNT_GROUPFIELD_NOT_SET');
            return false;
        } elseif ($user_name == '') {
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_USERACCOUNT_NAME_NOT_SET');
            return false;
        } elseif ($user_email == '') {
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_USERACCOUNT_EMAIL_NOT_SET');
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

                $this->ct->messages[] = common::translate('COM_CUSTOMTABLES_RECORD_USER_UPDATED');
            } else {
                $this->ct->errors[] =
                    common::translate('COM_CUSTOMTABLES_ERROR_USER_WITH_EMAIL')
                    . ' "' . $user_email . '" '
                    . common::translate('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS');
            }
        } else {
            CTUser::CreateUser($this->ct->Table->realtablename, $this->ct->Table->realidfieldname, $user_email, $user_name,
                $user_groups, $this->ct->Table->record[$this->ct->Table->realidfieldname], $field->realfieldname);
        }
        return true;
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
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
            $value = CustomPHP::execute($parsed_condition, $error);
        else
            $value = $parsed_condition;

        if ($error != '') {
            $this->ct->errors[] = $error;
            return false;
        }

        if ((int)$value == 1)
            return true;

        return false;
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    function sendEmailIfAddressSet(string $listing_id, array $row, string $email): void
    {
        $status = $this->sendEmailNote($listing_id, $email, $row);

        if ($this->ct->Params->emailSentStatusField != '') {

            foreach ($this->ct->Table->fields as $fieldrow) {
                $fieldname = $fieldrow['fieldname'];
                if ($this->ct->Params->emailSentStatusField == $fieldname) {

                    $data = [
                        $fieldrow['realfieldname'] => $status
                    ];
                    $whereClauseUpdate = new MySQLWhereClause();
                    $whereClauseUpdate->addCondition($this->ct->Table->realidfieldname, $listing_id);
                    database::update($this->ct->Table->realtablename, $data, $whereClauseUpdate);
                    return;
                }
            }
        }
    }

    /**
     * @throws Exception
     * @since 3.2.3
     */
    function sendEmailNote(string $listing_id, string $listOfEmailsString, array $row): int
    {
        $this->ct->Table->loadRecord($listing_id);

        //Prepare Email List
        $emails_raw = CTMiscHelper::csv_explode(',', $listOfEmailsString, '"', true);

        $emails = array();
        foreach ($emails_raw as $SendToEmail) {
            $EmailPair = CTMiscHelper::csv_explode(':', trim($SendToEmail));
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

        if ($this->ct->Params->onRecordAddSendEmailLayout !== null)
            $message_layout_content = $Layouts->getLayout($this->ct->Params->onRecordAddSendEmailLayout);
        else
            $message_layout_content = $Layouts->createDefaultLayout_Email($this->ct->Table->fields);

        $note = $Layouts->parseRawLayoutContent($message_layout_content);
        $status = 0;

        foreach ($emails as $SendToEmail) {
            $EmailTo = $SendToEmail['email'];
            $Subject = $SendToEmail['subject'];

            $attachments = [];

            $options = array();
            $fList = CTMiscHelper::getListToReplace('attachment', $options, $note, '{}');
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
                    $FileFolder = FileUtils::getOrCreateDirectoryPath($field->params[0]);

                    $filename = $FileFolder . $this->ct->Table->record[$fieldrow['realfieldname']];
                    if (file_exists($filename))
                        $attachments[] = $filename;//TODO: Check the functionality
                }
            }

            $sent = Email::sendEmail($EmailTo, $Subject, $note_final, true, $attachments);

            if ($sent !== true) {
                //Something went wrong. Email not sent.
                $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_ERROR_SENDING_EMAIL') . ': ' . $EmailTo . ' (' . $Subject . ')';
                $status = 0;
            } else {
                $this->ct->messages[] = common::translate('COM_CUSTOMTABLES_EMAIL_SENT_TO') . ': ' . $EmailTo . ' (' . $Subject . ')';
                $status = 1;
            }
        }
        return $status;
    }
}
