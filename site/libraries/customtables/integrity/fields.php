<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @subpackage integrity/fields.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\Integrity;

defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\Fields;
use CustomTables\IntegrityChecks;
use Exception;
use Joomla\CMS\Language\Text;

class IntegrityFields extends IntegrityChecks
{
    /**
     * @throws Exception
     * @since 3.2.2
     */
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

        $dbPrefix = database::getDBPrefix();

        if (TableHelper::createTableIfNotExists($dbPrefix, $ct->Table->tablename, $ct->Table->tabletitle, $ct->Table->customtablename ?? ''))
            $result .= '<p>Table "<span style="color:green;">' . $ct->Table->tabletitle . '</span>" <span style="color:green;">added.</span></p>';

        $ExistingFields = database::getExistingFields($ct->Table->realtablename, false);
        $projected_fields = Fields::getFields($ct->Table->tableid, false, false);

        //Delete unnecessary fields:
        $projected_fields[] = ['realfieldname' => 'id', 'type' => '_id', 'typeparams' => '', 'isrequired' => 1];
        $projected_fields[] = ['realfieldname' => 'published', 'type' => '_published', 'typeparams' => '', 'isrequired' => 1];

        $task = common::inputGetCmd('task');
        $taskFieldName = common::inputGetCmd('fieldname');

        if (defined('_JEXEC'))
            $taskTableId = common::inputGetInt('tableid');
        elseif (defined('WPINC'))
            $taskTableId = common::inputGetInt('table');
        else
            return 'Integrity Check not supported';

        $projected_data_type = null;

        foreach ($ExistingFields as $ExistingField) {
            $existingFieldName = $ExistingField['column_name'];
            $found = false;

            foreach ($projected_fields as $projected_field) {

                $found_field = '';

                if ($projected_field['realfieldname'] == 'id' and $existingFieldName == 'id') {
                    $found = true;
                    $found_field = '_id';
                    $projected_field['fieldtitle'] = 'Primary Key';
                    $projected_data_type = Fields::getProjectedFieldType('_id', null);

                    break;
                } elseif ($projected_field['realfieldname'] == 'published' and $existingFieldName == 'published') {
                    $found = true;
                    $found_field = '_published';
                    $projected_field['fieldtitle'] = 'Publish Status';
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
                } elseif ($projected_field['type'] == 'virtual') {

                    if ($existingFieldName == $projected_field['realfieldname']) {
                        if (fields::isVirtualField($projected_field)) {
                            $found = false;
                        } else {
                            $projected_data_type = Fields::getProjectedFieldType($projected_field['type'], $projected_field['typeparams']);
                            $found_field = $projected_field['realfieldname'];
                            $found = true;
                        }
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

                $ExistingFieldConvertedType = Fields::convertRawFieldType($ExistingField);

                if (!IntegrityFields::compareFieldTypes($ExistingFieldConvertedType, $projected_data_type)) {

                    if ($found_field == '_id')
                        $nice_field_name = $ct->Table->realtablename . '.' . $ct->Table->realidfieldname;
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

                        $PureFieldType = Fields::makeProjectedFieldType($projected_data_type);

                        if (Fields::fixMYSQLField($ct->Table->realtablename, $real_field_name, $PureFieldType, $msg, $projected_field['fieldtitle'])) {
                            $result .= '<p>' . common::translate('COM_CUSTOMTABLES_FIELD') . ' <span style="color:green;">'
                                . $nice_field_name . '</span> ' . common::translate('COM_CUSTOMTABLES_FIELD_FIXED') . '.</p>';
                        } else {
                            common::enqueueMessage($msg);
                        }

                        if ($msg != '')
                            $result .= $msg;
                    } else {

                        $length = self::parse_column_type($ExistingField['column_type']);
                        if ($length != '')
                            $ExistingField['length'] = $length;

                        $existing_field_type_string = Fields::projectedFieldTypeToString($ExistingFieldConvertedType);

                        $result .= '<p>' . common::translate('COM_CUSTOMTABLES_FIELD') . ' <span style="color:orange;">' . $nice_field_name . '</span>'
                            . ' ' . common::translate('COM_CUSTOMTABLES_FIELD_HAS_WRONG_TYPE') . ' <span style="color:red;">'
                            . $existing_field_type_string . '</span> ' . common::translate('COM_CUSTOMTABLES_FIELD_INSTEAD_OF') . ' <span style="color:green;">'
                            . Fields::projectedFieldTypeToString($projected_data_type) . '</span> <a href="' . $link . 'task=fixfieldtype&fieldname=' . $existingFieldName . '">' . common::translate('COM_CUSTOMTABLES_FIELD_TOFIX') . '</a></p>';
                    }
                }
            }
        }

        //Add missing fields
        foreach ($projected_fields as $projected_field) {
            $proj_field = $projected_field['realfieldname'];
            $fieldType = $projected_field['type'];
            if ($fieldType !== null and $projected_field['typeparams'] !== null and $fieldType != 'dummy' and !Fields::isVirtualField($projected_field)) {
                IntegrityFields::addFieldIfNotExists($ct, $ct->Table->realtablename, $ExistingFields, $proj_field, $fieldType, $projected_field['typeparams']);
            }
        }
        return $result;
    }

    public static function compareFieldTypes(array $existing, array $projected): bool
    {
        // Check if the values for each key are the same
        foreach ($existing as $key => $value) {
            if ($value === null)
                $value = '';

            if (isset($projected[$key])) {
                if ($projected[$key] != ($value ?? '')) {

                    if ($key == 'is_nullable') {
                        if ($projected[$key] === false and $value == 'NO')
                            return true;
                        elseif ($projected[$key] === true and $value == 'YES')
                            return true;

                    }
                    return false;
                }
            }
        }
        return true;
    }

    protected static function parse_column_type(string $parse_column_type_string): string
    {
        $parts = explode('(', $parse_column_type_string);
        if (count($parts) > 1) {
            $length = str_replace(')', '', $parts[1]);
            if ($length != '')
                return $length;
        }
        return '';
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public static function addFieldIfNotExists(CT $ct, $realtablename, $ExistingFields, $proj_field, string $fieldType, string $typeParams): bool
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

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected static function addField($realtablename, $realfieldname, string $fieldType, string $typeParams)
    {
        $PureFieldType = Fields::getPureFieldType($fieldType, $typeParams);
        $fieldTypeString = fields::projectedFieldTypeToString($PureFieldType);
        Fields::AddMySQLFieldNotExist($realtablename, $realfieldname, $fieldTypeString, '');

        if (defined('_JEXEC')) {
            common::enqueueMessage(sprintf("Field `%s` has been added.", $realfieldname), 'notice');
        } elseif (defined('WPINC')) {
            printf(
                esc_html__('Field `%s` has been added.', 'customtables'),
                esc_html($realfieldname)
            );
        }
    }
}