<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTablesFileMethods;
use CustomTablesImageMethods;
use Exception;
use JoomlaBasicMisc;
use ESTables;

use Joomla\CMS\Factory;

class Field
{
    var CT $ct;

    var int $id;
    var array $params;
    var string $type;
    var bool $isrequired;
    var ?string $defaultvalue;

    var string $title;
    var ?string $description;
    var string $fieldname;
    var string $realfieldname;
    var ?string $comesfieldname;
    var ?string $valuerule;
    var ?string $valuerulecaption;

    var array $fieldrow;
    var string $prefix; //part of the table class

    function __construct(CT &$ct, $fieldrow, $row = null)
    {
        $this->ct = &$ct;
        $this->id = $fieldrow['id'];
        $this->type = $fieldrow['type'];
        $this->fieldrow = $fieldrow;

        if (!array_key_exists('fieldtitle' . $ct->Languages->Postfix, $fieldrow)) {
            $this->title = 'fieldtitle' . $ct->Languages->Postfix . ' - not found';
        } else {
            $vlu = $fieldrow['fieldtitle' . $ct->Languages->Postfix];
            if ($vlu == '')
                $this->title = $fieldrow['fieldtitle'];
            else
                $this->title = $vlu;
        }

        if (!array_key_exists('description' . $ct->Languages->Postfix, $fieldrow)) {
            $this->description = 'description' . $ct->Languages->Postfix . ' - not found';
        } else {
            $vlu = $fieldrow['description' . $ct->Languages->Postfix];
            if ($vlu == '')
                $this->description = $fieldrow['description'];
            else
                $this->description = $vlu;
        }

        $this->fieldname = $fieldrow['fieldname'];
        $this->realfieldname = $fieldrow['realfieldname'];
        $this->isrequired = (bool)intval($fieldrow['isrequired']);
        $this->defaultvalue = $fieldrow['defaultvalue'];

        $this->valuerule = $fieldrow['valuerule'];
        $this->valuerulecaption = $fieldrow['valuerulecaption'];

        $this->prefix = $this->ct->Env->field_input_prefix;
        $this->comesfieldname = $this->prefix . $this->fieldname;

        $this->params = JoomlaBasicMisc::csv_explode(',', $fieldrow['typeparams'], '"', false);

        $this->parseParams($row);
    }

    function parseParams($row): void
    {
        $new_params = [];

        foreach ($this->params as $type_param) {
            $type_param = str_replace('****quote****', '"', $type_param);
            $type_param = str_replace('****apos****', '"', $type_param);

            if (is_numeric($type_param))
                $new_params[] = $type_param;
            elseif (!str_contains($type_param, '{{'))
                $new_params[] = $type_param;
            else {
                $twig = new TwigProcessor($this->ct, $type_param);
                $new_params[] = $twig->process($row);
            }
        }
        $this->params = $new_params;
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
}

class Fields
{
    public static function isFieldNullable(string $realtablename, string $relaFieldName): bool
    {
        $db = Factory::getDBO();

        $realtablename = str_replace('#__', $db->getPrefix(), $realtablename);
        if ($db->serverType == 'postgresql') {
            $query = 'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ' . $db->quote($realtablename)
                . ' AND column_name=' . $db->quote($relaFieldName);
        } else {

            $conf = Factory::getConfig();
            $database = $conf->get('db');

            $query = 'SELECT COLUMN_NAME AS column_name,'
                . 'DATA_TYPE AS data_type,'
                . 'COLUMN_TYPE AS column_type,'
                . 'IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') AS is_unsigned,'
                . 'IS_NULLABLE AS is_nullable,'
                . 'COLUMN_DEFAULT AS column_default,'
                . 'EXTRA AS extra'
                . ' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=' . $db->quote($database)
                . ' AND TABLE_NAME=' . $db->quote($realtablename)
                . ' AND column_name=' . $db->quote($relaFieldName)
                . ' LIMIT 1';
        }

        $db->setQuery($query);
        $recs = $db->loadAssocList();

        $rec = $recs[0];

        return $rec['is_nullable'] == 'YES';
    }

    public static function deleteField_byID(CT &$ct, $fieldid): bool
    {
        $db = Factory::getDBO();

        $ImageFolder = JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'esimages';

        $fieldrow = Fields::getFieldRow($fieldid);

        if (is_null($fieldrow))
            return false;

        $field = new Field($ct, $fieldrow);

        $tablerow = ESTables::getTableRowByID($field->fieldrow['tableid']);

        //for Image Gallery
        if ($field->type == 'imagegallery') {
            //Delete all photos belongs to the gallery

            $imageMethods = new CustomTablesImageMethods;
            $gallery_table_name = '#__customtables_gallery_' . $tablerow->tablename . '_' . $field->fieldname;
            $imageMethods->DeleteGalleryImages($gallery_table_name, $field->fieldrow['tableid'], $field->fieldname, $field->params, true);

            //Delete gallery table
            $query = 'DROP TABLE IF EXISTS ' . $gallery_table_name;
            $db->setQuery($query);
            $db->execute();
        } elseif ($field->type == 'filebox') {
            //Delete all files belongs to the filebox

            $fileBoxTableName = '#__customtables_filebox_' . $tablerow->tablename . '_' . $field->fieldname;
            CustomTablesFileMethods::DeleteFileBoxFiles($fileBoxTableName, $field->fieldrow['tableid'], $field->fieldname, $field->params);

            //Delete gallery table
            $query = 'DROP TABLE IF EXISTS ' . $fileBoxTableName;
            $db->setQuery($query);
            $db->execute();
        } elseif ($field->type == 'image') {
            if (Fields::checkIfFieldExists($tablerow->realtablename, $field->realfieldname)) {
                $imageMethods = new CustomTablesImageMethods;
                //$imageparams=str_replace('|compare','|delete:',$field->params); //disable image comparision if set
                $imageMethods->DeleteCustomImages($tablerow->realtablename, $field->realfieldname, $ImageFolder, $field->params[0], $tablerow->realidfieldname, true);
            }
        } elseif ($field->type == 'user' or $field->type == 'userid' or $field->type == 'sqljoin') {
            Fields::removeForeignKey($tablerow->realtablename, $field->realfieldname);
        } elseif ($field->type == 'file') {
            // delete all files
            //if(file_exists($filename))
            //unlink($filename);
        }

        $realFieldNames = array();

        if (!str_contains($field->type, 'multilang')) {
            $realFieldNames[] = $field->realfieldname;
        } else {
            $index = 0;
            foreach ($ct->Languages->LanguageList as $lang) {
                if ($index == 0)
                    $postfix = '';
                else
                    $postfix = '_' . $lang->sef;

                $realFieldNames[] = $field->realfieldname . $postfix;
                $index += 1;
            }
        }

        foreach ($realFieldNames as $realfieldname) {
            if ($field->type != 'dummy') {
                $msg = '';
                Fields::deleteMYSQLField($tablerow->realtablename, $realfieldname, $msg);
            }
        }

        //Delete field from the list
        $query = 'DELETE FROM #__customtables_fields WHERE published=1 AND id=' . $fieldid;
        $db->setQuery($query);
        $db->execute();
        return true;
    }

    public static function getFieldRow($fieldid = 0)
    {
        $db = Factory::getDBO();

        if ($fieldid == 0)
            $fieldid = Factory::getApplication()->input->get('fieldid', 0, 'INT');

        $query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS s WHERE id=' . $fieldid . ' LIMIT 1';//published=1 AND

        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if (count($rows) != 1)
            return array();

        return $rows[0];
    }

    protected static function getFieldRowSelects(): string
    {
        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql')
            $realfieldname_query = 'CASE WHEN customfieldname!=\'\' THEN customfieldname ELSE CONCAT(\'es_\',fieldname) END AS realfieldname';
        else
            $realfieldname_query = 'IF(customfieldname!=\'\', customfieldname, CONCAT(\'es_\',fieldname)) AS realfieldname';

        return '*, ' . $realfieldname_query;
    }

    public static function checkIfFieldExists($realtablename, $realfieldname): bool//,$add_table_prefix=true)
    {
        $realFieldNames = Fields::getListOfExistingFields($realtablename, false);

        return in_array($realfieldname, $realFieldNames);
    }

    public static function getListOfExistingFields($tablename, $add_table_prefix = true): array
    {
        $realFieldNames = Fields::getExistingFields($tablename, $add_table_prefix);
        $list = [];

        foreach ($realFieldNames as $rec)
            $list[] = $rec['column_name'];

        return $list;
    }

    public static function getExistingFields($tablename, $add_table_prefix = true)
    {
        $db = Factory::getDBO();

        if ($add_table_prefix)
            $realtablename = '#__customtables_table_' . $tablename;
        else
            $realtablename = $tablename;

        $realtablename = str_replace('#__', $db->getPrefix(), $realtablename);
        if ($db->serverType == 'postgresql') {
            $query = 'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ' . $db->quote($realtablename);
        } else {

            $conf = Factory::getConfig();
            $database = $conf->get('db');

            $query = 'SELECT COLUMN_NAME AS column_name,'
                . 'DATA_TYPE AS data_type,'
                . 'COLUMN_TYPE AS column_type,'
                . 'IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') AS is_unsigned,'
                . 'IS_NULLABLE AS is_nullable,'
                . 'COLUMN_DEFAULT AS column_default,'
                . 'EXTRA AS extra'
                . ' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=' . $db->quote($database) . ' AND TABLE_NAME=' . $db->quote($realtablename);
        }

        $db->setQuery($query);
        return $db->loadAssocList();
    }

    public static function removeForeignKey($realtablename, $realfieldname): bool
    {
        $constrances = Fields::getTableConstrances($realtablename, $realfieldname);

        if (!is_null($constrances)) {
            foreach ($constrances as $constrance) {
                Fields::removeForeignKey($realtablename, $constrance);
            }
            return true;
        }
        return false;
    }

    protected static function getTableConstrances($realtablename, $realfieldname): ?array
    {
        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql')
            return null;

        //get constrant name
        $query = 'show create table ' . $realtablename;

        $db->setQuery($query);
        $db->execute();
        $tablecreatequery = $db->loadAssocList();

        if (count($tablecreatequery) == 0)
            return null;

        $rec = $tablecreatequery[0];


        $constrances = array();

        $q = $rec['Create Table'];
        $lines = explode(',', $q);


        foreach ($lines as $line_) {
            $line = trim(str_replace('`', '', $line_));
            if (str_contains($line, 'CONSTRAINT')) {
                $pair = explode(' ', $line);

                if ($realfieldname == '')
                    $constrances[] = $pair;
                elseif ($pair[4] == '(' . $realfieldname . ')')
                    $constrances[] = $pair[1];
            }
        }

        return $constrances;
    }

    public static function deleteMYSQLField($realtablename, $realfieldname, &$msg): bool
    {
        if (Fields::checkIfFieldExists($realtablename, $realfieldname)) {
            try {
                $db = Factory::getDBO();

                $query = 'SET foreign_key_checks = 0;';
                $db->setQuery($query);
                $db->execute();

                $query = 'ALTER TABLE ' . $realtablename . ' DROP ' . $realfieldname;

                $db->setQuery($query);
                $db->execute();

                $query = 'SET foreign_key_checks = 1;';
                $db->setQuery($query);
                $db->execute();

                return true;
            } catch (Exception $e) {
                $msg = '<p style="color:#ff0000;">Caught exception: ' . $e->getMessage() . '</p>';
                return false;
            }
        }
        return false;
    }

    public static function convertMySQLFieldTypeToCT($data_type, $column_type): array
    {
        $type = '';
        $typeParams = '';

        switch (strtolower(trim($data_type))) {
            case 'bit':
            case 'tinyint':
            case 'int':
            case 'integer':
            case 'smallint':
            case 'mediumint':
            case 'bigint':
                $type = 'int';
                break;

            case 'dec':
            case 'decimal':
            case 'float':
            case 'double':

                $parts = explode('(', $column_type);
                if (count($parts) > 1) {
                    $length = str_replace(')', '', $parts[1]);
                    if ($length != '')
                        $typeParams = $length;
                }
                $type = 'float';
                break;

            case 'char':
            case 'varchar':

                $parts = explode('(', $column_type);
                if (count($parts) > 1) {
                    $length = str_replace(')', '', $parts[1]);
                    if ($length != '')
                        $typeParams = $length;
                }
                $type = 'string';
                break;

            case 'tynyblob':
            case 'blob':
            case 'mediumblob':
            case 'longblob':
                $type = 'blob';
                break;

            case 'text':
            case 'mediumtext':
            case 'longtext':
                $type = 'text';
                break;

            case 'datetime':
                $type = 'creationtime';
                break;

            case 'date':
                $type = 'date';
                break;
        }

        return ['type' => $type, 'typeparams' => $typeParams];
    }

    public static function isLanguageFieldName($fieldname): bool
    {
        $parts = explode('_', $fieldname);
        if ($parts[0] == 'es') {
            //custom field
            if (count($parts) == 3)
                return true;
            else
                return false;
        }

        if (count($parts) == 2)
            return true;
        else
            return false;

    }

    public static function getLanguagelessFieldName($fieldname): string
    {
        $parts = explode('_', $fieldname);
        if ($parts[0] == 'es') {
            //custom field
            if (count($parts) == 3)
                return $parts[0] . '_' . $parts[1];
            else
                return '';
        }

        if (count($parts) == 2)
            return $parts[0];
        else
            return '';
    }

    public static function getFieldType(string $realtablename, $realfieldname)
    {
        $db = Factory::getDBO();

        $realtablename = str_replace('#__', $db->getPrefix(), $realtablename);

        if ($db->serverType == 'postgresql')
            $query = 'SELECT data_type FROM information_schema.columns WHERE table_name = ' . $db->quote($realtablename) . ' AND column_name=' . $db->quote($realfieldname);
        else
            $query = 'SHOW COLUMNS FROM ' . $realtablename . ' WHERE ' . $db->quoteName('field') . '=' . $db->quote($realfieldname);

        $db->setQuery($query);

        $recs = $db->loadAssocList();

        if (count($recs) == 0)
            return '';

        $rec = $recs[0];

        if ($db->serverType == 'postgresql')
            return $rec['data_type'];
        else
            return $rec['Type'];
    }

    public static function fixMYSQLField($realtablename, $fieldname, $PureFieldType, &$msg): bool
    {
        $db = Factory::getDBO();

        if ($fieldname == 'id') {
            $constrances = Fields::getTableConstrances($realtablename, '');

            //Delete same table child-parent constrances
            if (!is_null($constrances)) {
                foreach ($constrances as $constrance) {
                    if ($constrance[7] == '(id)')
                        Fields::removeForeignKeyConstrance($realtablename, $constrance[1]);
                }
            }

            $query = 'ALTER TABLE ' . $realtablename . ' CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT';

            $db->setQuery($query);
            $db->execute();

            $msg = '';
            return true;
        } elseif ($fieldname == 'published')
            $query = 'ALTER TABLE ' . $realtablename . ' CHANGE published published TINYINT NOT NULL DEFAULT 1';
        else
            $query = 'ALTER TABLE ' . $realtablename . ' CHANGE ' . $fieldname . ' ' . $fieldname . ' ' . $PureFieldType;

        try {
            $db->setQuery($query);
            $db->execute();

            $msg = '';
            return true;
        } catch (Exception $e) {
            $msg = '<p style="color:red;">Caught exception: ' . $e->getMessage() . '</p>';
            return false;
        }
    }

    protected static function removeForeignKeyConstrance($realtablename, $constrance): void
    {
        $db = Factory::getDBO();

        $query = 'SET foreign_key_checks = 0;';
        $db->setQuery($query);
        $db->execute();

        $query = 'ALTER TABLE ' . $realtablename . ' DROP FOREIGN KEY ' . $constrance;

        try {
            $db->setQuery($query);
            $db->execute();
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        $query = 'SET foreign_key_checks = 1;';
        $db->setQuery($query);
        $db->execute();
    }

    public static function getFieldName($fieldid): string
    {
        $db = Factory::getDBO();

        if ($fieldid == 0)
            $fieldid = Factory::getApplication()->input->get('fieldid', 0, 'INT');

        $query = 'SELECT fieldname FROM #__customtables_fields AS s WHERE s.published=1 AND s.id=' . $fieldid . ' LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) != 1)
            return '';

        return $rows[0]->fieldname;
    }

    public static function getFields($tableid_or_name, $as_object = false, $order_fields = true)
    {
        $db = Factory::getDBO();

        if ($order_fields)
            $order = ' ORDER BY f.ordering, f.fieldname';
        else
            $order = '';

        if ((int)$tableid_or_name > 0)
            $where = 'f.published=1 AND f.tableid=' . (int)$tableid_or_name;
        else {
            $w1 = '(SELECT t.id FROM #__customtables_tables AS t WHERE t.tablename=' . $db->quote($tableid_or_name) . ' LIMIT 1)';
            $where = 'f.published=1 AND f.tableid=' . $w1;
        }

        $query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS f WHERE ' . $where . $order;

        $db->setQuery($query);

        if ($as_object)
            return $db->loadObjectList();
        else
            return $db->loadAssocList();
    }

    public static function getFieldRowByName($fieldname, $tableid = 0, $sj_tablename = '')
    {
        $db = Factory::getDBO();

        if ($fieldname == '')
            return array();

        if ($sj_tablename == '')
            $query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS s WHERE s.published=1 AND tableid=' . (int)$tableid . ' AND fieldname=' . $db->quote(trim($fieldname)) . ' LIMIT 1';
        else {
            $query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS s

			INNER JOIN #__customtables_tables AS t ON t.tablename=' . $db->quote($sj_tablename) . '
			WHERE s.published=1 AND s.tableid=t.id AND s.fieldname=' . $db->quote(trim($fieldname)) . ' LIMIT 1';
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList();

        if (count($rows) != 1)
            return null;

        return $rows[0];
    }

    public static function getFieldAssocByName($fieldname, $tableid): ?array
    {
        $db = Factory::getDBO();

        if ($fieldname == '')
            $fieldname = Factory::getApplication()->input->get('fieldname', '', 'CMD');

        if ($fieldname == '')
            return null;

        $query = 'SELECT ' . Fields::getFieldRowSelects() . ' FROM #__customtables_fields AS s WHERE s.published=1 AND tableid=' . (int)$tableid . ' AND fieldname="' . trim($fieldname) . '" LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadAssocList();
        if (count($rows) != 1) {
            return null;
        }
        return $rows[0];
    }

    public static function FieldRowByName($fieldname, $ctFields)
    {
        if (is_null($ctFields))
            return null;

        foreach ($ctFields as $field) {
            if ($field['fieldname'] == $fieldname)
                return $field;
        }
        return null;
    }

    public static function getRealFieldName($fieldname, $ctfields, $all_fields = false)
    {
        foreach ($ctfields as $row) {
            if (($all_fields or $row['allowordering'] == 1) and $row['fieldname'] == $fieldname)
                return $row['realfieldname'];
        }
        return '';
    }

    public static function shortFieldObjects($fields): array
    {
        $field_objects = [];

        foreach ($fields as $fieldRow)
            $field_objects[] = Fields::shortFieldObject($fieldRow, null, []);

        return $field_objects;
    }

    public static function shortFieldObject($fieldRow, $value, $options): array
    {
        $field = [];
        $field['fieldname'] = $fieldRow['fieldname'];
        $field['title'] = $fieldRow['fieldtitle'];
        $field['defaultvalue'] = $fieldRow['defaultvalue'];
        $field['description'] = $fieldRow['description'];
        $field['isrequired'] = $fieldRow['isrequired'];
        $field['isdisabled'] = $fieldRow['isdisabled'];
        $field['type'] = $fieldRow['type'];

        $typeParams = JoomlaBasicMisc::csv_explode(',', $fieldRow['typeparams'], '"', false);
        $field['typeparams'] = $typeParams;
        $field['valuerule'] = $fieldRow['valuerule'];
        $field['valuerulecaption'] = $fieldRow['valuerulecaption'];

        $field['value'] = $value;

        if (count($options) == 1 and $options[0] == '')
            $field['options'] = null;
        else
            $field['options'] = $options;

        return $field;
    }

    //MySQL only

    public static function deleteTablelessFields(): void
    {
        $db = Factory::getDBO();

        $query = 'DELETE FROM #__customtables_fields AS f WHERE (SELECT id FROM #__customtables_tables AS t WHERE t.id = f.tableid) IS NULL';

        $db->setQuery($query);
        $db->execute();
    }

    public static function getSelfParentField($ct)
    {
        //Check if this table has self-parent field - the TableJoing field linked with the same table.

        foreach ($ct->Table->fields as $fld) {
            if ($fld['type'] == 'sqljoin') {
                $type_params = JoomlaBasicMisc::csv_explode(',', $fld['typeparams'], '"', false);
                $join_tablename = $type_params[0];

                if ($join_tablename == $ct->Table->tablename) {
                    return $fld;//['fieldname'];
                }
            }
        }
        return null;
    }

    public static function saveField()
    {
        $ct = new CT;
        $input = Factory::getApplication()->input;
        $data = $input->get('jform', array(), 'ARRAY');

        //clean field name
        if (function_exists("transliterator_transliterate"))
            $fieldName = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", $data['fieldname']);
        else
            $fieldName = $data['fieldname'];

        $fieldName = strtolower(trim(preg_replace("/[^a-zA-Z\d]/", "", $fieldName)));
        if (strlen($fieldName) > 40)
            $fieldName = substr($fieldName, 0, 40);

        $tableid = $data['tableid'];
        $fieldid = $input->getInt('id');
        $data['id'] = $fieldid;

        if ($fieldid == 0)
            $fieldName = self::checkFieldName($tableid, $fieldName);

        $data['fieldname'] = $fieldName;

        //Add language fields to the fields' table if necessary

        $moreThanOneLang = false;
        $fields = Fields::getListOfExistingFields('#__customtables_fields', false);
        foreach ($ct->Languages->LanguageList as $lang) {
            $id_title = 'fieldtitle';
            $id_description = 'description';

            if ($moreThanOneLang) {
                $id_title .= '_' . $lang->sef;
                $id_description .= '_' . $lang->sef;

                if (!in_array($id_title, $fields))
                    Fields::addLanguageField('#__customtables_fields', 'fieldtitle', $id_title);

                if (!in_array($id_description, $fields))
                    Fields::addLanguageField('#__customtables_fields', 'description', $id_description);

            }
            $moreThanOneLang = true; //More than one language installed
        }

        $table_row = ESTables::getTableRowByID($tableid);

        if (!is_object($table_row)) {
            Factory::getApplication()->enqueueMessage('Table not found', 'error');
            return null;
        }

        if ($table_row->customtablename === null) //do not create fields to third-party tables
        {
            if (!self::update_physical_field($ct, $table_row, $fieldid, $data)) {
                //Cannot create
                return null;
            }
        } elseif ($table_row->customtablename == $table_row->tablename) {

            $data['customfieldname'] = $data['fieldname'];

            //Third-party table but managed by the Custom Tables
            if (!self::update_physical_field($ct, $table_row, $fieldid, $data)) {
                //Cannot create
                return null;
            }
        }

        if ($fieldid != 0) {

            $data_old = ['id' => $fieldid];
            ImportTables::updateRecords('#__customtables_fields', $data, $data_old, false, array(), true);
        } else
            $fieldid = ImportTables::insertRecords('#__customtables_fields', $data, false, array(), true);

        return $fieldid;
    }

    protected static function checkFieldName($tableid, $fieldname): string
    {
        $new_fieldname = $fieldname;

        while (1) {
            $already_exists = Fields::getFieldID($tableid, $new_fieldname);

            if ($already_exists != 0) {
                $new_fieldname .= 'copy';
            } else
                break;
        }

        return $new_fieldname;
    }

    public static function getFieldID($tableid, $fieldname): int
    {
        $db = Factory::getDBO();
        $query = 'SELECT id FROM #__customtables_fields WHERE published=1 AND tableid=' . (int)$tableid . ' AND fieldname=' . $db->quote($fieldname);

        $db->setQuery($query);

        $rows2 = $db->loadObjectList();
        if (count($rows2) == 0)
            return 0;

        $row = $rows2[0];

        return $row->id;
    }

    public static function addLanguageField($tablename, $original_fieldname, $new_fieldname): bool
    {
        $fields = Fields::getExistingFields($tablename, false);

        foreach ($fields as $field) {
            if ($field['column_name'] == $original_fieldname) {
                $AdditionOptions = '';
                if ($field['is_nullable'] != 'NO')
                    $AdditionOptions = 'null';

                Fields::AddMySQLFieldNotExist($tablename, $new_fieldname, $field['column_type'], $AdditionOptions);
                return true;
            }
        }
        return false;
    }

    public static function AddMySQLFieldNotExist($realtablename, $realfieldname, $fieldType, $options): void
    {
        $db = Factory::getDBO();

        if (!Fields::checkIfFieldExists($realtablename, $realfieldname)) {
            $query = 'ALTER TABLE ' . $realtablename . ' ADD COLUMN ' . $realfieldname . ' ' . $fieldType . ' ' . $options;

            $db->setQuery($query);
            $db->execute();
        }
    }

    protected static function update_physical_field(CT $ct, $table_row, $fieldid, $data)
    {
        $db = Factory::getDBO();

        $realtablename = $table_row->realtablename;
        $realtablename = str_replace('#__', $db->getPrefix(), $realtablename);

        if ($fieldid != 0) {
            $fieldrow = Fields::getFieldRow($fieldid);

            $ex_type = $fieldrow->type;
            $ex_typeparams = $fieldrow->typeparams;
            $realfieldname = $fieldrow->realfieldname;
        } else {
            $ex_type = '';
            $ex_typeparams = '';
            $realfieldname = '';

            if ($table_row->customtablename === null)
                $realfieldname = 'es_' . $data['fieldname'];
            elseif ($table_row->customtablename == $table_row->tablename)
                $realfieldname = $data['fieldname'];
        }

        $new_typeparams = $data['typeparams'];
        $fieldtitle = $data['fieldtitle'];

        //---------------------------------- Convert Field

        $new_type = $data['type'];
        $PureFieldType = Fields::getPureFieldType($new_type, $new_typeparams);

        if ($realfieldname != '')
            $fieldFound = Fields::checkIfFieldExists($realtablename, $realfieldname, false);
        else
            $fieldFound = false;

        if ($fieldid != 0 and $fieldFound) {

            if ($PureFieldType == '') {
                //do nothing. field can be deleted
                $convert_ok = true;
            } else
                $convert_ok = Fields::ConvertFieldType($realtablename, $realfieldname, $ex_type, $new_type, $ex_typeparams, $new_typeparams, $PureFieldType, $fieldtitle);

            if (!$convert_ok) {
                Factory::getApplication()->enqueueMessage('Cannot convert the type.', 'error');
                return false;
            }

            $input = Factory::getApplication()->input;
            $extraTask = '';

            if ($ex_type == $new_type and $new_type == 'image' and ($ex_typeparams != $new_typeparams or str_contains($new_typeparams, '|delete'))) {

                $ex_typeparams_array = JoomlaBasicMisc::csv_explode(',', $ex_typeparams);
                $new_typeparams_array = JoomlaBasicMisc::csv_explode(',', $new_typeparams);

                if ($ex_typeparams_array[0] != $new_typeparams_array[0])
                    $extraTask = 'updateimages'; //Resize all images if needed
                elseif (($ex_typeparams_array[2] ?? null) != ($new_typeparams_array[2] ?? null)) {
                    $input->set('stepsize', 1000);
                    $extraTask = 'updateimages'; //Move all images if needed
                }
            }
            if ($ex_type == $new_type and $new_type == 'file' and $ex_typeparams != $new_typeparams)
                $extraTask = 'updatefiles';

            if ($ex_type == $new_type and $new_type == 'imagegallery' and $ex_typeparams != $new_typeparams)
                $extraTask = 'updateimagegallery'; //Resize or move all images in the gallery if needed

            if ($ex_type == $new_type and $new_type == 'filebox' and $ex_typeparams != $new_typeparams)
                $extraTask = 'updatefilebox'; //Resize or move all images in the gallery if needed

            if ($extraTask != '') {
                $input->set('extratask', $extraTask);
                $input->set('old_typeparams', base64_encode($ex_typeparams));
                $input->set('new_typeparams', base64_encode($new_typeparams));
                $input->set('fieldid', $fieldid);
            }
        }
        //---------------------------------- end convert field

        if ($fieldid == 0 or !$fieldFound) {
            //Add Field

            Fields::addField($ct, $realtablename, $realfieldname, $new_type, $PureFieldType, $fieldtitle);
        }

        if ($new_type == 'sqljoin') {
            //Create Index if needed
            Fields::addIndexIfNotExist($realtablename, $realfieldname);

            //Add Foreign Key
            $msg = '';
            Fields::addForeignKey($realtablename, $realfieldname, $new_typeparams, '', 'id', $msg);
        }

        if ($new_type == 'user' or $new_type == 'userid') {
            //Create Index if needed
            Fields::addIndexIfNotExist($realtablename, $realfieldname);

            //Add Foreign Key
            $msg = '';
            Fields::addForeignKey($realtablename, $realfieldname, '', '#__users', 'id', $msg);
        }
        return true;
    }

    public static function getPureFieldType($ct_fieldType, $typeParams): string
    {
        $ct_fieldTypeArray = Fields::getProjectedFieldType($ct_fieldType, $typeParams);
        return Fields::makeProjectedFieldType($ct_fieldTypeArray);
    }

    public static function getProjectedFieldType($ct_fieldType, $typeParams): array
    {
        //Returns an array of mysql column parameters
        switch (trim($ct_fieldType)) {
            case '_id':
                return ['data_type' => 'int', 'is_nullable' => false, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => 'auto_increment'];

            case '_published':
                return ['data_type' => 'tinyint', 'is_nullable' => false, 'is_unsigned' => false, 'length' => null, 'default' => 1, 'extra' => null];

            case 'filelink':
            case 'file':
            case 'url':
                return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 1024, 'default' => null, 'extra' => null];
            case 'color':
                return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 8, 'default' => null, 'extra' => null];
            case 'string':
            case 'multilangstring':
                $l = (int)$typeParams;
                return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => ($l < 1 ? 255 : (min($l, 1024))), 'default' => null, 'extra' => null];
            case 'signature':

                $typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);
                $format = $typeParamsArray[3] ?? 'svg';

                if ($format == 'svg-db')
                    return ['data_type' => 'text', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];
                else
                    return ['data_type' => 'bigint', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];

            case 'blob':
                $typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);

                if ($typeParamsArray[0] == 'tiny')
                    $type = 'tinyblob';
                elseif ($typeParamsArray[0] == 'medium')
                    $type = 'mediumblob';
                elseif ($typeParamsArray[0] == 'long')
                    $type = 'longblob';
                else
                    $type = 'blob';

                return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

            case 'text':
            case 'multilangtext':

                $typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);
                $type = 'text';
                if (isset($typeParamsArray[2])) {
                    if ($typeParamsArray[2] == 'tiny')
                        $type = 'tinytext';
                    elseif ($typeParamsArray[2] == 'medium')
                        $type = 'mediumtext';
                    elseif ($typeParamsArray[2] == 'long')
                        $type = 'longtext';
                }

                return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

            case 'log':
                //mediumtext
                return ['data_type' => 'text', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];
            case 'ordering':
                return ['data_type' => 'int', 'is_nullable' => false, 'is_unsigned' => true, 'length' => null, 'default' => 0, 'extra' => null];
            case 'time':
            case 'int':
                return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];
            case 'float':

                $typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);

                if (count($typeParamsArray) == 1)
                    $l = '20,' . (int)$typeParamsArray[0];
                elseif (count($typeParamsArray) == 2)
                    $l = (int)$typeParamsArray[1] . ',' . (int)$typeParamsArray[0];
                else
                    $l = '20,2';
                return ['data_type' => 'decimal', 'is_nullable' => true, 'is_unsigned' => false, 'length' => $l, 'default' => null, 'extra' => null];

            case 'customtables':
                $typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);

                if (count($typeParamsArray) < 255)
                    $l = 255;
                else
                    $l = (int)$typeParamsArray[2];

                if ($l > 65535)
                    $l = 65535;

                return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => $l, 'default' => null, 'extra' => null];

            case 'userid':
            case 'user':
            case 'usergroup':
            case 'sqljoin':
            case 'article':
            case 'multilangarticle':
                return ['data_type' => 'int', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => null];

            case 'image':

                $typeParamsArray = JoomlaBasicMisc::csv_explode(',', $typeParams);

                $fileNameType = $typeParamsArray[3] ?? '';
                $length = null;

                if ($fileNameType == '') {
                    $type = 'bigint';
                } else {
                    $type = 'varchar';
                    $length = 1024;
                }

                return ['data_type' => $type, 'is_nullable' => true, 'is_unsigned' => false, 'length' => $length, 'default' => null, 'extra' => null];

            case 'checkbox':
                return ['data_type' => 'tinyint', 'is_nullable' => false, 'is_unsigned' => false, 'length' => null, 'default' => 0, 'extra' => null];

            case 'date':
                return ['data_type' => 'date', 'is_nullable' => true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

            case 'creationtime':
            case 'changetime':
            case 'lastviewtime':
                return ['data_type' => 'datetime', 'is_nullable' => true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];

            case 'viewcount':
            case 'imagegallery':
            case 'id':
            case 'filebox':
                return ['data_type' => 'bigint', 'is_nullable' => true, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => null];

            case 'language':
                return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 5, 'default' => null, 'extra' => null];

            case 'dummy':
                return ['data_type' => null, 'is_nullable' => null, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];

            case 'md5':
                return ['data_type' => 'char', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 32, 'default' => null, 'extra' => null];

            case 'phponadd':
            case 'phponchange':
            case 'phponview':
                $typeParamsArray = explode(',', $typeParams);

                if (isset($typeParamsArray[1]) and $typeParamsArray[1] == 'dynamic')
                    return ['data_type' => null, 'is_nullable' => null, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null]; //do not store field values
                else
                    return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 255, 'default' => null, 'extra' => null];

            default:

                return ['data_type' => 'varchar', 'is_nullable' => true, 'is_unsigned' => null, 'length' => 255, 'default' => null, 'extra' => null];
        }
    }

    public static function makeProjectedFieldType($ct_fieldtype_array): string
    {
        $type = (object)$ct_fieldtype_array;

        $db = Factory::getDBO();

        $elements = [];

        switch ($type->data_type) {
            case 'varchar':
                $elements[] = 'varchar(' . $type->length . ')';
                break;

            case 'tinytext':
                $elements[] = 'tinytext';
                break;

            case 'text':
                $elements[] = 'text';
                break;

            case 'mediumtext':
                $elements[] = 'mediumtext';
                break;

            case 'longtext':
                $elements[] = 'longtext';
                break;

            case 'tinyblob':
                $elements[] = 'tinyblob';
                break;

            case 'blob':
                $elements[] = 'blob';
                break;

            case 'mediumblob':
                $elements[] = 'mediumblob';
                break;

            case 'longblob':
                $elements[] = 'longblob';
                break;

            case 'char':
                $elements[] = 'char(' . $type->length . ')';
                break;

            case 'int':
                $elements[] = 'int';

                if ($db->serverType != 'postgresql') {
                    if ($type->is_nullable !== null and $type->is_unsigned)
                        $elements[] = 'unsigned';
                }
                break;

            case 'bigint':
                $elements[] = 'bigint';

                if ($db->serverType != 'postgresql') {
                    if ($type->is_nullable !== null and $type->is_unsigned)
                        $elements[] = 'unsigned';
                }
                break;

            case 'decimal':
                if ($db->serverType == 'postgresql')
                    $elements[] = 'numeric(' . $type->length . ')';
                else
                    $elements[] = 'decimal(' . $type->length . ')';

                break;

            case 'tinyint':
                if ($db->serverType == 'postgresql')
                    $elements[] = 'smallint';
                else
                    $elements[] = 'tinyint';

                break;

            case 'date':
                $elements[] = 'date';
                break;

            case 'datetime':
                if ($db->serverType == 'postgresql')
                    $elements[] = 'TIMESTAMP';
                else
                    $elements[] = 'datetime';

                break;

            default:
                return '';
        }

        if ($type->is_nullable)
            $elements[] = 'null';
        else
            $elements[] = 'not null';

        if ($type->default !== null)
            $elements[] = 'default ' . (is_numeric($type->default) ? $type->default : $db->quote($type->default));

        if ($type->extra !== null)
            $elements[] = $type->extra;

        return implode(' ', $elements);
    }

    public static function ConvertFieldType($realtablename, $realfieldname, $ex_type, $new_type, $ex_typeparams, $new_typeparams, $PureFieldType, $fieldtitle): bool
    {
        if ($new_type == 'blob' or $new_type == 'text' or $new_type == 'multilangtext' or $new_type == 'image') {
            if ($new_typeparams == $ex_typeparams)
                return true; //no need to convert
        } else {
            if ($new_type == $ex_type)
                return true; //no need to convert
        }

        $unconvertable_types = array('dummy', 'imagegallery', 'file', 'filebox', 'signature', 'records', 'customtables', 'log');

        if (in_array($new_type, $unconvertable_types) or in_array($ex_type, $unconvertable_types))
            return false;

        $PureFieldType_ = $PureFieldType;

        //Check and fix record
        if ($new_type == 'customtables') {
            //get number of string like "varchar(255)"
            $maxlength = (int)preg_replace("/\D/", "", $PureFieldType);
            $typeParamsArray = explode(',', $new_typeparams);
            $optionName = $typeParamsArray[0];

            Fields::FixCustomTablesRecords($realtablename, $realfieldname, $optionName, $maxlength);
        }

        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql') {
            $parts = explode(' ', $PureFieldType_);
            $query = 'ALTER TABLE ' . $realtablename
                . ' ALTER COLUMN ' . $realfieldname . ' TYPE ' . $parts[0];

        } else {
            $query = 'ALTER TABLE ' . $realtablename . ' CHANGE ' . $realfieldname . ' ' . $realfieldname . ' ' . $PureFieldType_;
            $query .= ' COMMENT ' . $db->quote($fieldtitle);

        }
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (Exception $e) {
            $app = Factory::getApplication();
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
        return true;
    }

    public static function FixCustomTablesRecords($realtablename, $realfieldname, $optionname, $maxlenght): void
    {
        $db = Factory::getDBO();

        //CustomTables field type
        if ($db->serverType == 'postgresql')
            return;

        $fixCount = 0;

        $fixQuery = 'SELECT id, ' . $realfieldname . ' AS fldvalue FROM ' . $realtablename;


        $db->setQuery($fixQuery);

        $fixRows = $db->loadObjectList();
        foreach ($fixRows as $fixRow) {

            $newrow = Fields::FixCustomTablesRecord($fixRow->fldvalue, $optionname, $maxlenght);

            if ($fixRow->fldvalue != $newrow) {
                $fixCount++;

                $fixitquery = 'UPDATE ' . $realtablename . ' SET ' . $realfieldname . '="' . $newrow . '" WHERE id=' . $fixRow->id;
                $db->setQuery($fixitquery);
                $db->execute();

            }
        }
    }

    /*
    public static function checkField($ExistingFields,$realtablename,$proj_field,$type)
    {
        $db = Factory::getDBO();

        $found=false;

        foreach($ExistingFields as $existing_field)
        {
            if($proj_field==$existing_field['column_name'])
            {
                $found=true;
                break;
            }
        }

        if(!$found)
        {
            $query='ALTER TABLE '.$realtablename.' ADD COLUMN '.$proj_field.' '.$type;

            $db->setQuery($query);
            $db->execute();
        }
    }
    */

    public static function FixCustomTablesRecord($record, $optionname, $maxlen): string
    {
        $l = 2;

        $e = explode(',', $record);
        $r = array();

        foreach ($e as $a) {
            $p = explode('.', $a);
            $b = array();

            foreach ($p as $t) {
                if ($t != '')
                    $b[] = $t;
            }
            if (count($b) > 0) {
                $d = implode('.', $b);
                if ($d != $optionname)
                    $e = implode('.', $b) . '.';

                $l += strlen($e) + 1;
                if ($l >= $maxlen)
                    break;

                $r[] = $e;
            }
        }

        if (count($r) > 0)
            $newrow = ',' . implode(',', $r) . ',';
        else
            $newrow = '';

        return $newrow;
    }

    public static function addField(CT $ct, $realtablename, $realfieldname, $fieldType, $PureFieldType, $fieldtitle): void
    {
        if ($PureFieldType == '')
            return;

        $db = Factory::getDBO();

        if (!str_contains($fieldType, 'multilang')) {
            $AdditionOptions = '';
            if ($db->serverType != 'postgresql')
                $AdditionOptions = ' COMMENT ' . $db->Quote($fieldtitle);

            if ($fieldType != 'dummy')
                Fields::AddMySQLFieldNotExist($realtablename, $realfieldname, $PureFieldType, $AdditionOptions);
        } else {
            $index = 0;
            foreach ($ct->Languages->LanguageList as $lang) {
                if ($index == 0)
                    $postfix = '';
                else
                    $postfix = '_' . $lang->sef;

                $AdditionOptions = '';
                if ($db->serverType != 'postgresql')
                    $AdditionOptions = ' COMMENT ' . $db->Quote($fieldtitle);

                Fields::AddMySQLFieldNotExist($realtablename, $realfieldname . $postfix, $PureFieldType, $AdditionOptions);

                $index++;
            }
        }

        if ($fieldType == 'imagegallery') {
            //Create table
            //get CT table name if possible

            $tableName = str_replace($db->getPrefix() . 'customtables_table', '', $realtablename);
            $fieldName = str_replace($ct->Env->field_prefix, '', $realfieldname);
            Fields::CreateImageGalleryTable($tableName, $fieldName);
        } elseif ($fieldType == 'filebox') {
            //Create table
            //get CT table name if possible
            $tableName = str_replace($db->getPrefix() . 'customtables_table', '', $realtablename);
            $fieldName = str_replace($ct->Env->field_prefix, '', $realfieldname);
            Fields::CreateFileBoxTable($tableName, $fieldName);
        }
    }

    public static function CreateImageGalleryTable($tablename, $fieldname): bool
    {
        $image_gallery_table = '#__customtables_gallery_' . $tablename . '_' . $fieldname;
        $db = Factory::getDBO();

        $query = 'CREATE TABLE IF not EXISTS ' . $image_gallery_table . ' (
  photoid bigint not null auto_increment,
  listingid bigint not null,
  ordering int not null,
  photo_ext varchar(10) not null,
  title varchar(100) null,
   PRIMARY KEY  (photoid)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1 ;
';
        $db->setQuery($query);
        $db->execute();

        return true;
    }

    public static function CreateFileBoxTable($tablename, $fieldname): bool
    {
        $filebox_gallery_table = '#__customtables_filebox_' . $tablename . '_' . $fieldname;
        $db = Factory::getDBO();

        $query = 'CREATE TABLE IF not EXISTS ' . $filebox_gallery_table . ' (
  fileid bigint not null auto_increment,
  listingid bigint not null,
  ordering int not null,
  file_ext varchar(10) not null,
  title varchar(100) not null,
   PRIMARY KEY  (fileid)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1 ;
';
        $db->setQuery($query);
        $db->execute();

        return true;
    }

    public static function addIndexIfNotExist($realtablename, $realfieldname): void
    {
        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql') {
            //Indexes not yet supported
        } else {
            $db = Factory::getDBO();
            $query = 'SHOW INDEX FROM ' . $realtablename . ' WHERE Key_name = "' . $realfieldname . '"';
            $db->setQuery($query);
            $db->execute();

            $rows2 = $db->loadObjectList();


            if (count($rows2) == 0) {
                $query = 'ALTER TABLE ' . $realtablename . ' ADD INDEX(' . $realfieldname . ');';

                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    public static function addForeignKey($realtablename_, $realfieldname, string $new_typeparams, string $join_with_table_name, string $join_with_table_field, &$msg): bool
    {
        $db = Factory::getDBO();

        $conf = Factory::getConfig();
        $dbPrefix = $conf->get('dbprefix');

        $realtablename = str_replace('#__', $dbPrefix, $realtablename_);

        if ($db->serverType == 'postgresql')
            return false;

        //Create Key only if possible
        $typeParams = explode(',', $new_typeparams);

        if ($join_with_table_name == '') {
            if ($new_typeparams == '') {
                $msg = 'Parameters not set.';
                return false; //Exit if parameters not set
            }

            if (count($typeParams) < 2) {
                $msg = 'Parameters not complete.';
                return false;    // Exit if field not set (just in case)
            }

            $tableRow = ESTables::getTableRowByName($typeParams[0]); //[0] - is tablename
            if (!is_object($tableRow)) {
                $msg = 'Join with table "' . $join_with_table_name . '" not found.';
                return false;    // Exit if table to connect with not found
            }

            $join_with_table_name = $tableRow->realtablename;
            $join_with_table_field = $tableRow->realidfieldname;
        }

        $join_with_table_name = str_replace('#__', $dbPrefix, $join_with_table_name);

        $conf = Factory::getConfig();
        $database = $conf->get('db');

        Fields::removeForeignKey($realtablename, $realfieldname);

        if (isset($typeParams[7]) and $typeParams[7] == 'addforignkey') {
            Fields::cleanTableBeforeNormalization($realtablename, $realfieldname, $join_with_table_name, $join_with_table_field);

            $query = 'ALTER TABLE ' . $db->quoteName($realtablename) . ' ADD FOREIGN KEY (' . $realfieldname . ') REFERENCES '
                . $db->quoteName($database . '.' . $join_with_table_name) . ' (' . $join_with_table_field . ') ON DELETE RESTRICT ON UPDATE RESTRICT;';

            try {
                $db->setQuery($query);
                $db->execute();
                return true;
            } catch (Exception $e) {
                $msg = $e->getMessage();
            }
        }
        return false;
    }

    public static function cleanTableBeforeNormalization($realtablename, $realfieldname, $join_with_table_name, $join_with_table_field): void
    {
        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql')
            return;

        //Find broken records
        $query = 'SELECT DISTINCT a.' . $realfieldname . ' AS customtables_distinct_temp_id FROM
			' . $realtablename . ' a LEFT JOIN ' . $join_with_table_name . ' b ON a.' . $realfieldname . '=b.' . $join_with_table_field
            . ' WHERE b.' . $join_with_table_field . ' IS NULL;';

        $db->setQuery($query);
        $db->execute();

        $rows = $db->loadAssocList();

        $where_ids = array();
        $where_ids[] = $realfieldname . '=0';

        foreach ($rows as $row) {
            if ($row['customtables_distinct_temp_id'] != '')
                $where_ids[] = $realfieldname . '=' . $row['customtables_distinct_temp_id'];
        }

        $query = 'UPDATE ' . $realtablename . ' SET ' . $realfieldname . '=NULL WHERE ' . implode(' OR ', $where_ids) . ';';

        $db->setQuery($query);
        $db->execute();
    }
}


