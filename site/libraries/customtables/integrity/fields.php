<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage integrity/fields.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\Integrity;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\IntegrityChecks;
use Joomla\CMS\Factory;
use ESTables;
use JoomlaBasicMisc;

class IntegrityFields extends IntegrityChecks
{
    public static function checkFields(CT &$ct, $link): string
    {
        if (!str_contains($link, '?'))
            $link .= '?';
        else
            $link .= '&';

        require_once('fieldtype_filebox.php');
        require_once('fieldtype_gallery.php');

        $result = '';

        //Do not check third-party tables
        if ($ct->Table->customtablename != '')
            return $result;

        $conf = Factory::getConfig();
        $database = $conf->get('db');
        $dbPrefix = $conf->get('dbprefix');

        if (ESTables::createTableIfNotExists($database, $dbPrefix, $ct->Table->tablename, $ct->Table->tabletitle, $ct->Table->customtablename))
            $result .= '<p>Table "<span style="color:green;">' . $ct->Table->tabletitle . '</span>" <span style="color:green;">added.</span></p>';


        $ExistingFields = Fields::getExistingFields($ct->Table->realtablename, false);
        $jinput = Factory::getApplication()->input;
        $projected_fields = Fields::getFields($ct->Table->tableid, false, false);

        //Delete unnecessary fields:
        $projected_fields[] = ['realfieldname' => 'id', 'type' => '_id', 'typeparams' => '', 'isrequired' => 1];
        $projected_fields[] = ['realfieldname' => 'published', 'type' => '_published', 'typeparams' => '', 'isrequired' => 1];

        $task = $jinput->getCmd('task');
        $taskFieldName = $jinput->getCmd('fieldname');
        $taskTableId = $jinput->getInt('tableid');

        foreach ($ExistingFields as $ExistingField) {
            $existingFieldName = $ExistingField['column_name'];
            $found = false;

            foreach ($projected_fields as $projected_field) {
                $found_field = '';

                if ($projected_field['realfieldname'] == 'id' and $existingFieldName == 'id') {
                    $found = true;
                    $found_field = '_id';
                    $projected_data_type = Fields::getProjectedFieldType('_id', null);

                    break;
                } elseif ($projected_field['realfieldname'] == 'published' and $existingFieldName == 'published') {
                    $found = true;
                    $found_field = '_published';
                    $projected_data_type = Fields::getProjectedFieldType('_published', null);

                    break;
                } elseif ($projected_field['type'] == 'multilangstring' or $projected_field['type'] == 'multilangtext') {
                    $moreThanOneLang = false;
                    foreach ($ct->Languages->LanguageList as $lang) {
                        $fieldname = $projected_field['realfieldname'];
                        if ($moreThanOneLang)
                            $fieldname .= '_' . $lang->sef;

                        if ($existingFieldName == $fieldname) {
                            $projected_data_type = Fields::getProjectedFieldType($projected_field['type'], $projected_field['typeparams']);
                            $found_field = $projected_field['realfieldname'];
                            $found = true;
                            break;
                        }
                        $moreThanOneLang = true;
                    }
                } elseif ($projected_field['type'] == 'imagegallery') {
                    if ($existingFieldName == $projected_field['realfieldname']) {
                        IntegrityFieldType_Gallery::checkGallery($ct, $projected_field['fieldname']);

                        $projected_data_type = Fields::getProjectedFieldType($projected_field['type'], $projected_field['typeparams']);
                        $found_field = $projected_field['realfieldname'];
                        $found = true;
                    }

                } elseif ($projected_field['type'] == 'filebox') {
                    if ($existingFieldName == $projected_field['realfieldname']) {
                        IntegrityFieldType_FileBox::checkFileBox($ct, $projected_field['fieldname']);

                        $projected_data_type = Fields::getProjectedFieldType($projected_field['type'], $projected_field['typeparams']);
                        $found_field = $projected_field['realfieldname'];
                        $found = true;
                        break;
                    }
                } elseif ($projected_field['type'] == 'dummy') {
                    if ($existingFieldName == $projected_field['realfieldname']) {
                        $found = false;
                        break;
                    }
                } else {
                    if ($existingFieldName == $projected_field['realfieldname']) {
                        $projected_data_type = Fields::getProjectedFieldType($projected_field['type'], $projected_field['typeparams']);
                        $found_field = $projected_field['realfieldname'];
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                if ($found_field != '') {
                    //Delete field
                    if ($ct->Table->tableid == $taskTableId and $task == 'deleteurfield' and $taskFieldName == $existingFieldName) {
                        Fields::removeForeignKey($ct->Table->realtablename, $existingFieldName);

                        $msg = '';
                        if (Fields::deleteMYSQLField($ct->Table->realtablename, $existingFieldName, $msg))
                            $result .= '<p>Field <span style="color:green;">' . $existingFieldName . '</span> not registered. <span style="color:green;">Deleted.</span></p>';

                        if ($msg != '')
                            $result .= $msg;
                    } else
                        $result .= '<p>Field <span style="color:red;">' . $existingFieldName . '</span> not registered. <a href="' . $link . 'task=deleteurfield&fieldname=' . $existingFieldName . '">Delete?</a></p>';
                }
            } elseif ($found_field != '') {

                //Check for virtuality
                $isRequiredOrGenerated = (int)$projected_field['isrequired'];
                if ($isRequiredOrGenerated == 2 or $isRequiredOrGenerated == 3) {
                    $expression = Fields::addFieldPrefixToExpression($ct->Table->tableid, $projected_field['defaultvalue']);
                    $projected_data_type['generation_expression'] = $expression;
                    $projected_data_type['extra'] = ($isRequiredOrGenerated == 2 ? 'VIRTUAL' : 'STORED') . ' GENERATED';
                    $projected_data_type['required_or_generated'] = $isRequiredOrGenerated;
                } else {
                    $projected_data_type['required_or_generated'] = null;
                }


                if (isset($ExistingField['extra']) and str_contains($ExistingField['extra'], 'GENERATED')) {

                    $ExistingField['generation_expression'] = str_replace("_utf8mb3", "", $ExistingField['generation_expression']);
                    $ExistingField['generation_expression'] = str_replace('_utf8mb4\\\'', '\'', $ExistingField['generation_expression']);
                    $ExistingField['generation_expression'] = str_replace('\\\'', '\'', $ExistingField['generation_expression']);
                    $ExistingField['generation_expression'] = str_replace('`', '', $ExistingField['generation_expression']);
                }

                if (!IntegrityFields::compareFieldTypes($ExistingField, $projected_data_type)) {

                    $PureFieldType = Fields::makeProjectedFieldType($projected_data_type);

                    if ($found_field == '_id')
                        $nice_field_name = $ct->Table->realtablename . '.id';
                    elseif ($found_field == '_published')
                        $nice_field_name = $ct->Table->realtablename . '.published';
                    else {
                        $nice_field_name = str_replace($ct->Env->field_prefix, '', $found_field)
                            . ($projected_field['typeparams'] != '' ? ' (' . $projected_field['typeparams'] . ')' : '');
                    }

                    if ($ct->Table->tableid == $taskTableId and $task == 'fixfieldtype' and ($taskFieldName == $existingFieldName or $taskFieldName == 'all_fields')) {
                        $msg = '';

                        if ($found_field == '_id')
                            $real_field_name = 'id';
                        elseif ($found_field == '_published')
                            $real_field_name = 'published';
                        else
                            $real_field_name = $found_field;

                        if (Fields::fixMYSQLField($ct->Table->realtablename, $real_field_name, $PureFieldType, $msg)) {
                            $result .= '<p>' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FIELD') . ' <span style="color:green;">'
                                . $nice_field_name . '</span> ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FIELD_FIXED') . '.</p>';
                        } else {
                            Factory::getApplication()->enqueueMessage($msg, 'error');
                        }

                        if ($msg != '')
                            $result .= $msg;
                    } else {

                        $length = self::parse_column_type($ExistingField['column_type']);
                        if ($length != '')
                            $ExistingField['length'] = $length;

                        $existing_field_type_string = Fields::makeProjectedFieldType($ExistingField);

                        $result .= '<p>' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FIELD') . ' <span style="color:orange;">' . $nice_field_name . '</span>'
                            . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FIELD_HAS_WRONG_TYPE') . ' <span style="color:red;">'
                            . $existing_field_type_string . '</span> ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FIELD_INSTEAD_OF') . ' <span style="color:green;">'
                            . $PureFieldType . '</span> <a href="' . $link . 'task=fixfieldtype&fieldname=' . $existingFieldName . '">' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FIELD_TOFIX') . '</a></p>';
                    }
                }
            }
        }

        //Add missing fields
        foreach ($projected_fields as $projected_field) {
            $proj_field = $projected_field['realfieldname'];
            $fieldType = $projected_field['type'];
            if ($fieldType != 'dummy')
                IntegrityFields::addFieldIfNotExists($ct, $ct->Table->realtablename, $ExistingFields, $proj_field, $fieldType, $projected_field['typeparams']);
        }
        return $result;
    }

    public static function compareFieldTypes(array $existing_field_data_type, array $projected_field_data_type): bool
    {
        $existing = (object)$existing_field_data_type;
        $projected = (object)$projected_field_data_type;

        if ($existing->data_type != $projected->data_type)
            return false;

        //parse column_type
        if ($existing->data_type == 'varchar' or $existing->data_type == 'char' or $existing->data_type == 'decimal') {

            $length = self::parse_column_type($existing->column_type);

            if ($length != '') {

                if ($projected->length === null)
                    return false;

                $projected_length = (string)$projected->length;

                if ($length != $projected_length)
                    return false;
            }
        }

        if (($existing->is_nullable == 'YES') != $projected->is_nullable) {
            return false;
        }

        if ($projected->is_unsigned !== null) {
            if (($existing->is_unsigned == 'YES') != $projected->is_unsigned) {
                return false;
            }
        }

        if ($projected->default !== null and $existing->column_default != $projected->default) {
            return false;
        }

        if ($existing->extra != $projected->extra) {
            return false;
        } else {
            if (($existing->extra == 'VIRTUAL GENERATED' or $existing->extra == 'STORED GENERATED') and
                $existing->generation_expression != $projected->generation_expression) {
                return false;
            }
        }

        return true;
    }

    protected static function parse_column_type(string $parse_column_type_string)
    {
        $parts = explode('(', $parse_column_type_string);
        if (count($parts) > 1) {
            $length = str_replace(')', '', $parts[1]);
            if ($length != '')
                return $length;
        }
        return '';
    }

    public static function addFieldIfNotExists(CT $ct, $realtablename, $ExistingFields, $proj_field, $fieldType, $typeParams): bool
    {
        if ($fieldType == 'multilangstring' or $fieldType == 'multilangtext') {
            $moreThanOneLanguage = false;
            foreach ($ct->Languages->LanguageList as $lang) {
                $fieldname = $proj_field;
                if ($moreThanOneLanguage)
                    $fieldname .= '_' . $lang->sef;

                $found = false;
                foreach ($ExistingFields as $existing_field) {
                    if ($fieldname == $existing_field['column_name']) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    //Add field
                    IntegrityFields::addField($realtablename, $fieldname, $fieldType, $typeParams);
                    return true;
                }

                $moreThanOneLanguage = true;
            }
        } else {
            $found = false;
            foreach ($ExistingFields as $existing_field) {
                if ($proj_field == $existing_field['column_name']) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                IntegrityFields::addField($realtablename, $proj_field, $fieldType, $typeParams);
                return true;
            }
        }
        return false;
    }

    protected static function addField($realtablename, $realfieldname, $fieldType, $typeParams)
    {
        $PureFieldType = Fields::getPureFieldType($fieldType, $typeParams);
        Fields::AddMySQLFieldNotExist($realtablename, $realfieldname, $PureFieldType, '');
        Factory::getApplication()->enqueueMessage('Field "' . $realfieldname . '" added.', 'notice');
    }
}