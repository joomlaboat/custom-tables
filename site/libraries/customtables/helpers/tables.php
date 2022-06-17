<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Fields;
use Joomla\CMS\Factory;

class ESTables
{
    //This function works with MySQL not PostgreeSQL
    public static function checkTableName($tablename)
    {
        $new_tablename = $tablename;
        $i = 1;
        while (1) {

            $already_exists = ESTables::getTableID($new_tablename);
            if ($already_exists != 0) {
                $pair = explode('_', $new_tablename);

                $cleantablename = $pair[0];
                $new_tablename = $cleantablename . '_' . $i;
                $i++;
            } else
                break;
        }

        return $new_tablename;
    }

    public static function getTableID(string $tablename): int
    {
        if (str_contains($tablename, '"'))
            return 0;

        $db = Factory::getDBO();

        if ($tablename == '')
            return 0;

        $query = 'SELECT id FROM #__customtables_tables AS s WHERE tablename=' . $db->quote($tablename) . ' LIMIT 1';

        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) != 1)
            return 0;

        return $rows[0]->id;
    }

    public static function checkIfTableExists(string $realtablename): bool
    {
        $conf = Factory::getConfig();
        $database = $conf->get('db');

        $db = Factory::getDBO();

        $realtablename = str_replace('#__', $db->getPrefix(), $realtablename);

        if ($db->serverType == 'postgresql')
            $query = 'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_name = ' . $db->quote($realtablename) . ' LIMIT 1';
        else
            $query = 'SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = ' . $db->quote($database) . ' AND table_name = ' . $db->quote($realtablename) . ' LIMIT 1';

        $db->setQuery($query);
        $rows = $db->loadObjectList();

        $c = (int)$rows[0]->c;
        if ($c > 0)
            return true;

        return false;
    }

    public static function getTableRowByID(int $tableid)
    {
        if ($tableid == 0)
            return null;

        $row = ESTables::getTableRowByIDAssoc($tableid);
        if (!is_array($row))
            return null;

        return (object)$row;
    }

    public static function getTableRowByIDAssoc(int $tableid)
    {
        if ($tableid == 0)
            return null;

        return ESTables::getTableRowByWhere('id=' . $tableid);
    }

    public static function getTableRowByWhere($where)
    {
        $db = Factory::getDBO();

        $query = 'SELECT ' . ESTables::getTableRowSelects() . ' FROM #__customtables_tables AS s WHERE ' . $where . ' LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadAssocList();
        if (count($rows) != 1)
            return null;

        $row = $rows[0];

        $published_field_found = true;
        if ($row['customtablename'] != '') {
            $realfields = Fields::getListOfExistingFields($row['realtablename'], false);
            if (!in_array('published', $realfields))
                $published_field_found = false;
        }
        $row['published_field_found'] = $published_field_found;

        if ($published_field_found)
            $query_selects = '*, ' . $row['realtablename'] . '.' . $row['realidfieldname'] . ' AS listing_id, ' . $row['realtablename'] . '.published AS listing_published';
        else
            $query_selects = '*, ' . $row['realtablename'] . '.' . $row['realidfieldname'] . ' AS listing_id, 1 AS listing_published';

        $row['query_selects'] = $query_selects;

        return $row;
    }

    public static function getTableRowSelects(): string
    {
        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql') {
            $realtablename_query = 'CASE WHEN customtablename!=\'\' THEN customtablename ELSE CONCAT(\'#__customtables_table_\', tablename) END AS realtablename';
            $realidfieldname_query = 'CASE WHEN customidfield!=\'\' THEN customidfield ELSE \'id\' END AS realidfieldname';
        } else {
            $realtablename_query = 'IF((customtablename IS NOT NULL AND customtablename!=\'\'	), customtablename, CONCAT(\'#__customtables_table_\', tablename)) AS realtablename';
            $realidfieldname_query = 'IF(customidfield!=\'\', customidfield, \'id\') AS realidfieldname';
        }

        return '*, ' . $realtablename_query . ',' . $realidfieldname_query . ', 1 AS published_field_found';
    }

    public static function createTableIfNotExists($database, $dbprefix, $tablename, $tabletitle, $complete_table_name = ''): bool
    {
        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql') {
            //PostgreSQL
            //Check if table exists
            if ($complete_table_name == '')
                $table_name = $dbprefix . 'customtables_table_' . $tablename;
            elseif ($complete_table_name == '-new-')
                $table_name = $tablename;
            else
                $table_name = $complete_table_name;// used for custom table names - to connect to third-part tables for example

            $fields = Fields::getListOfExistingFields($table_name, false);

            if (count($fields) == 0) {
                //create new table
                $db->setQuery('CREATE SEQUENCE IF NOT EXISTS ' . $table_name . '_seq');
                $db->execute();


                $query = '
				CREATE TABLE IF NOT EXISTS ' . $table_name . '
				(
					id int NOT NULL DEFAULT nextval (\'' . $table_name . '_seq\'),
					published smallint NOT NULL DEFAULT 1,
					PRIMARY KEY (id)
				)';

                $db->setQuery($query);
                $db->execute();

                $db->setQuery('ALTER SEQUENCE ' . $table_name . '_seq RESTART WITH 1');
                $db->execute();

                return true;
            }
        } else {
            //Mysql;
            $rows2 = ESTables::getTableStatus($database, $dbprefix, $tablename);

            if (count($rows2) > 0) {
                if ($complete_table_name == '') {
                    //do not modify third-party tables
                    $row2 = $rows2[0];

                    $table_name = $dbprefix . 'customtables_table_' . $tablename;

                    if ($row2->Engine != 'InnoDB') {
                        $query = 'ALTER TABLE ' . $table_name . ' ENGINE = InnoDB';
                        $db->setQuery($query);
                        $db->execute();
                    }

                    $query = 'ALTER TABLE ' . $table_name . ' COMMENT = "' . $tabletitle . '";';
                    $db->setQuery($query);
                    $db->execute();

                    return false;
                }
            } else {

                if ($complete_table_name == '')
                    $table_name = $dbprefix . 'customtables_table_' . $tablename;
                elseif ($complete_table_name == '-new-')
                    $table_name = $tablename;
                else
                    $table_name = $complete_table_name;// used for custom table names - to connect to third-part tables for example

                $query = '
					CREATE TABLE IF NOT EXISTS ' . $table_name . '
					(
						id int(10) UNSIGNED NOT NULL auto_increment,
						published tinyint(1) NOT NULL DEFAULT 1,
						PRIMARY KEY (id)
					) ENGINE=InnoDB COMMENT="' . $tabletitle . '" DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;
';
                $db->setQuery($query);
                $db->execute();

                return true;
            }
        }

        return false;
    }

    public static function getTableStatus($database, $dbprefix, $tablename)
    {
        $db = Factory::getDBO();
        $query = 'SHOW TABLE STATUS FROM ' . $db->quoteName($database) . ' LIKE ' . $db->quote($dbprefix . 'customtables_table_' . $tablename);
        $db->setQuery($query);

        return $db->loadObjectList();
    }

    public static function insertRecords($realtablename, $sets)
    {
        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql') {
            $set_fieldnames = array();
            $set_values = array();
            foreach ($sets as $set) {
                $break_sets = explode('=', $set);
                $set_fieldnames[] = $break_sets[0];
                $set_values[] = $break_sets[1];
            }

            $query = 'INSERT INTO ' . $realtablename . ' (' . implode(',', $set_fieldnames) . ') VALUES (' . implode(',', $set_values) . ')';
        } else {
            $query = 'INSERT ' . $realtablename . ' SET ' . implode(', ', $sets);

        }
        $db->setQuery($query);
        $db->execute();
        return $db->insertid();
    }

    public static function renameTableIfNeeded($tableid, $database, $dbprefix, $tablename): void
    {
        $db = Factory::getDBO();
        $old_tablename = ESTables::getTableName($tableid);

        if ($old_tablename != $tablename) {
            //rename table
            $tablestatus = ESTables::getTableStatus($database, $dbprefix, $old_tablename);

            if (count($tablestatus) > 0) {
                $query = 'RENAME TABLE ' . $db->quoteName($database . '.' . $dbprefix . 'customtables_table_' . $old_tablename) . ' TO '
                    . $db->quoteName($database . '.' . $dbprefix . 'customtables_table_' . $tablename) . ';';

                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    public static function getTableName($tableid = 0): ?string
    {
        $db = Factory::getDBO();

        if ($tableid == 0)
            $tableid = Factory::getApplication()->input->get('tableid', 0, 'INT');

        $query = 'SELECT tablename FROM #__customtables_tables AS s WHERE id=' . (int)$tableid . ' LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) != 1)
            return null;

        return $rows[0]->tablename;
    }

    public static function addThirdPartyTableFieldsIfNeeded($database, $tablename, $realtablename): bool
    {
        $fields = Fields::getFields($tablename, false, true);
        if (count($fields) > 0)
            return false;

        //Add third-party fields

        $tablerow = ESTables::getTableRowByName($tablename);

        $db = Factory::getDBO();

        if ($db->serverType == 'postgresql')
            $query = 'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ' . $db->quote($realtablename);
        else
            $query = 'SELECT '
                . 'COLUMN_NAME AS column_name,'
                . 'DATA_TYPE AS data_type,'
                . 'COLUMN_TYPE AS column_type,'
                . 'IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') AS is_unsigned,'
                . 'IS_NULLABLE AS is_nullable,'
                . 'COLUMN_DEFAULT AS column_default,'
                . 'COLUMN_COMMENT AS column_comment,'
                . 'COLUMN_KEY AS column_key,'
                . 'EXTRA AS extra FROM information_schema.columns WHERE table_schema = ' . $db->quote($database) . ' AND table_name = ' . $db->quote($realtablename);

        $db->setQuery($query);
        $fields = $db->loadObjectList();

        $set_fieldnames = ['tableid', 'fieldname', 'fieldtitle', 'allowordering', 'type', 'typeparams', 'ordering', 'defaultvalue', 'description', 'customfieldname', 'isrequired'];

        $primary_key_column = '';
        $ordering = 1;
        foreach ($fields as $field) {
            if ($primary_key_column == '' and strtolower($field->column_key) == 'pri') {
                $primary_key_column = $field->column_name;
            } else {
                $set_values = [];

                $ct_field_type = Fields::convertMySQLFieldTypeToCT($field->data_type, $field->column_type);
                if ($ct_field_type['type'] == '') {
                    Factory::getApplication()->enqueueMessage('third-party table field type "' . $field->data_type . '" is unknown.', 'error');
                    return false;
                }

                $set_values['tableid'] = (int)$tablerow->id;
                $set_values['fieldname'] = $db->quote(strtolower($field->column_name));
                $set_values['fieldtitle'] = $db->quote(ucwords(strtolower($field->column_name)));
                $set_values['allowordering'] = 'true';
                $set_values['type'] = $db->quote($ct_field_type['type']);
                $set_values['typeparams'] = $db->quote($ct_field_type['typeparams']);
                $set_values['ordering'] = $ordering;
                $set_values['defaultvalue'] = $field->column_default != '' ? $db->quote($field->column_default) : 'NULL';
                $set_values['description'] = $field->column_comment != '' ? $db->quote($field->column_comment) : 'NULL';
                $set_values['customfieldname'] = $db->quote($field->column_name);
                $set_values['isrequired'] = 0;

                $query = 'INSERT INTO #__customtables_fields (' . implode(',', $set_fieldnames) . ') VALUES (' . implode(',', $set_values) . ')';

                $db->setQuery($query);
                $db->execute();

                $ordering += 1;
            }
        }

        if ($primary_key_column != '') {
            //Update primary key column
            $query = 'UPDATE #__customtables_tables SET customidfield = ' . $db->quote($primary_key_column) . ' WHERE id = ' . (int)$tablerow->id;
            $db->setQuery($query);
            $db->execute();
        }
        return true;
    }

    public static function getTableRowByName($tablename = '')
    {
        if ($tablename == '')
            return null;

        $row = ESTables::getTableRowByNameAssoc($tablename);
        if (!is_array($row))
            return null;

        return (object)$row;
    }

    public static function getTableRowByNameAssoc($tablename = '')
    {
        if ($tablename == '')
            return null;

        $db = Factory::getDBO();

        return ESTables::getTableRowByWhere('tablename=' . $db->quote($tablename));
    }

    public static function copyTable(CT $ct, $originaltableid, $new_table, $old_table, $customtablename = '')
    {
        //Copy Table
        $db = Factory::getDBO();

        //get ID of new table
        $new_table_id = ESTables::getTableID($new_table);

        if ($customtablename == '') {
            //Do not copy real third-party tables

            if ($db->serverType == 'postgresql')
                $query = 'CREATE TABLE #__customtables_table_' . $new_table . ' AS TABLE #__customtables_table_' . $old_table;
            else
                $query = 'CREATE TABLE #__customtables_table_' . $new_table . ' AS SELECT * FROM #__customtables_table_' . $old_table;

            $db->setQuery($query);
            $db->execute();

            $query = 'ALTER TABLE #__customtables_table_' . $new_table . ' ADD PRIMARY KEY (id)';
            $db->setQuery($query);
            $db->execute();

            $query = 'ALTER TABLE #__customtables_table_' . $new_table . ' CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT';
            $db->setQuery($query);
            $db->execute();
        }

        //Copy Fields
        $fields = array('fieldname', 'type', 'typeparams', 'ordering', 'defaultvalue', 'allowordering', 'parentid', 'isrequired', 'valuerulecaption', 'valuerule',
            'customfieldname', 'isdisabled', 'savevalue', 'alwaysupdatevalue', 'created_by', 'modified_by', 'created', 'modified');

        $morethanonelang = false;

        foreach ($ct->Languages->LanguageList as $lang) {
            if ($morethanonelang) {
                $fields[] = 'fieldtitle' . '_' . $lang->sef;
                $fields[] = 'description' . '_' . $lang->sef;
            } else {
                $fields[] = 'fieldtitle';
                $fields[] = 'description';

                $morethanonelang = true;
            }
        }

        $query = 'SELECT * FROM #__customtables_fields WHERE published=1 AND tableid=' . $originaltableid;
        $db->setQuery($query);

        $rows = $db->loadAssocList();

        if (count($rows) == 0)
            die('Original table has no fields.');

        foreach ($rows as $row) {

            $inserts = array('tableid=' . $new_table_id);
            foreach ($fields as $fld) {
                $value = $row[$fld];
                $value = str_replace('"', '\"', $value);

                $inserts[] = $fld . '="' . $value . '"';
            }

            $iq = 'INSERT INTO #__customtables_fields SET ' . implode(', ', $inserts);

            $db->setQuery($iq);
            $db->execute();
        }
        return true;
    }
}
