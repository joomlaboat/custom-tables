<?php
/*
	@version		1.6.1
	@build			3ed July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

*/

use CustomTables\Fields;

defined('_JEXEC') or die('Restricted access');

$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR
    . 'customtables' . DIRECTORY_SEPARATOR;
require_once($path . 'helpers' . DIRECTORY_SEPARATOR . 'misc.php');
require_once($path . 'tables' . DIRECTORY_SEPARATOR . 'tables.php');
require_once($path . 'fields' . DIRECTORY_SEPARATOR . 'fields.php');

function updateESTables()
{
    getESTables();
}

function getESTables()
{
    $conf = JFactory::getConfig();

    $query = 'SHOW TABLES';
    $db = JFactory::getDBO();
    $db->setQuery($query);

    $tables = $db->loadAssocList();
    $database = $conf->get('db');

    foreach ($tables as $table) {
        $tablename = $table['Tables_in_' . $database];

        if (str_contains($tablename, '_extrasearch_'))//dont change this line _e x t r a  s e a r c h_
        {
            $new_tablename = str_replace('_extrasearch_', '_customtables_', $tablename);//dont change this line. First must be _e x t r a  s e a r c h_

            $query = 'DROP TABLE IF EXISTS ' . $new_tablename;
            $db->setQuery($query);
            $db->execute();

            $query = 'RENAME TABLE ' . $tablename . ' TO ' . $new_tablename;
            $db->setQuery($query);
            $db->execute();

            if (fixFields($new_tablename)) {
                //	die;
            }

        } elseif (str_contains($tablename, '_customtables_')) {
            updateFields($tablename);
        }
    }

    updateMenuItems();

    updateLayouts();

    addCetegoriesTable();

    updateImageFieldTypeParama();

    updatefieldTypes();

    updateContent();

    updateLayoutVerticalBarTags();

    fixTableCategory();
}

function fixTableCategory()
{
    $db = JFactory::getDBO();

    $tablename = '#__customtables_tables';
    $fields = getExistingFields($tablename);

    $catid = findFileByName($fields, 'catid');
    $tablecategory = findFileByName($fields, 'tablecategory');

    if ($tablecategory == null and is_array($catid)) {
        //rename field
        $query = 'ALTER TABLE `' . $tablename . '` CHANGE `catid` `tablecategory` int(11);';
        $db->setQuery($query);
        $db->execute();

    } elseif (is_array($tablecategory) and is_array($catid)) {
        //delete tablecategory
        $query = 'ALTER TABLE `' . $tablename . '` DROP column `tablecategory`';
        $db->setQuery($query);
        $db->execute();

        $query = 'ALTER TABLE `' . $tablename . '` CHANGE `catid` `tablecategory` int(11);';
        $db->setQuery($query);
        $db->execute();
    }
}

function findFileByName($fields, $fieldname)
{
    foreach ($fields as $field) {
        $fn = $field['Field'];
        if ($fn == $fieldname)
            return $field;
    }
    return null;
}

function updateLayoutVerticalBarTags()
{
    $db = JFactory::getDBO();

    $query = 'SELECT id, layoutcode FROM #__customtables_layouts WHERE INSTR(layoutcode,"|toolbar") OR INSTR(layoutcode,"|search")';
    $db->setQuery($query);

    $records = $db->loadAssocList();

    foreach ($records as $record) {
        $c = str_replace('|toolbar', '|batchtoolbar', $record['layoutcode']);

        $c = fixToolBarTags($c, 'toolbar');
        $c = fixToolBarTags($c, 'search');
        $c = fixToolBarTags($c, 'checkbox');
        $c = fixToolBarTags($c, '(id)');
        $c = fixToolBarTags($c, 'batchtoolbar');

        if ($c != $record['layoutcode']) {
            //update
            $query = 'UPDATE `#__customtables_layouts` SET
				layoutcode=' . $db->quote($c) . ' WHERE id=' . $record['id'];


            $db->setQuery($query);
            $db->execute();

            echo '<p>Layout #' . $record['id'] . ' updated.</p>';
        }
    }
}

function fixToolBarTags($htmlresult, $w)
{
    $options = array();
    $fList = JoomlaBasicMisc::getListToReplace($w, $options, $htmlresult, '||');


    //$changed=false;
    $i = 0;
    foreach ($fList as $fItem) {

        $vlu = str_replace('|' . $w, '{' . $w, $fItem);
        $vlu = str_replace('|', '}', $vlu);

        if ($fItem != $vlu) {
            $htmlresult = str_replace($fItem, $vlu, $htmlresult);
        }

        $i++;
    }

    $vlu = str_replace('|(', '{', $htmlresult);
    if ($vlu != $htmlresult) {
        //	$changed=true;
        $htmlresult = $vlu;
    }

    $vlu = str_replace(')|', '}', $htmlresult);
    if ($vlu != $htmlresult) {
        //$changed=true;
        $htmlresult = $vlu;
    }

    //if($changed)
    return $htmlresult;

    //return '';
}


function updateImageFieldTypeParama()
{
    $db = JFactory::getDBO();
    $query = 'UPDATE `#__customtables_fields` SET typeparams=CONCAT(\'"\',REPLACE(typeparams,\'|\',\'",\')) WHERE (`type`="image" OR `type`="imagegallery") AND INSTR(typeparams,\'|\')';

    $db->setQuery($query);
    $db->execute();
}

function updateMenuItems()
{
    $db = JFactory::getDBO();
    $sets = array();

    $sets[] = 'link=replace(link,"com_extrasearch","com_customtables")';
    $sets[] = 'component_id=(SELECT extension_id FROM `#__extensions` where element="com_customtables" LIMIT 1)';

    $query = 'UPDATE #__menu SET ' . implode(',', $sets)
        . ' WHERE instr(link,"com_extrasearch")';

    $db->setQuery($query);
    $db->execute();

    $sets = array();

    $sets[] = 'params=replace(params,\'layout":"layout:\',\'layout":"\')';
    $sets[] = 'params=replace(params,\'"detailslayout":"\',\'"esdetailslayout":"\')';
    $sets[] = 'params=replace(params,\'"editlayout":"\',\'"eseditlayout":"\')';

    $query = 'UPDATE #__menu SET ' . implode(',', $sets)
        . ' WHERE instr(link,"com_customtables")';

    $db->setQuery($query);
    $db->execute();


}

function updatefieldTypes()
{
    $db = JFactory::getDBO();
    $query = 'UPDATE #__customtables_fields SET `type`="customtables" WHERE INSTR(`type`,"extrasearch")';

    $db->setQuery($query);
    $db->execute();
}

function updateLayouts()
{
    $db = JFactory::getDBO();
    $query = 'UPDATE #__customtables_layouts set layoutcode=replace(layoutcode,"extrasearch","customtables") where instr(layoutcode,"extrasearch")';

    $db->setQuery($query);
    $db->execute();
}

function updateContent()
{
    $db = JFactory::getDBO();
    $query = 'UPDATE #__content set introtext=replace(introtext,"{extrasearch","{customtables") where INSTR(introtext,"extrasearch")';

    $db->setQuery($query);
    $db->execute();
}


/*
function fixNewCTTables($mysqltable)
{
    $db = JFactory::getDBO();

    $ads=array();
    $ads[]='`asset_id` INT(10) NOT NULL AFTER `parentid`';
    $ads[]='`params` TEXT NULL AFTER `asset_id`';
    $ads[]='`published` TINYINT(3) NOT NULL DEFAULT 1';
    $ads[]='`created_by` INT(10) unsigned NOT NULL DEFAULT 0';
    $ads[]='`modified_by` INT(10) unsigned NOT NULL DEFAULT 0';
    $ads[]='`created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'';
    $ads[]='`modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'';
    $ads[]='`checked_out` int(11) unsigned NOT NULL DEFAULT 0';
    $ads[]='`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'';
    $ads[]='`version` INT(10) unsigned NOT NULL DEFAULT 1';
    $ads[]='`hits` INT(10) unsigned NOT NULL DEFAULT 0';
    $ads[]='`ordering` INT(11) NOT NULL DEFAULT 0';
    $query='ALTER TABLE `ow94h_customtables_categories` ADD '.implode(', ADD ',$ads);
    $db->setQuery( $query );
    $db->execute();
}
*/

function addCetegoriesTable()
{
    $db = JFactory::getDBO();
    $query = 'CREATE TABLE IF NOT EXISTS `#__customtables_categories` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`published` TINYINT(3) NOT NULL DEFAULT 1,
	`categoryname` VARCHAR(255) NOT NULL DEFAULT "",
	`created_by` INT(10) unsigned NOT NULL DEFAULT 0,
	`modified_by` INT(10) unsigned NOT NULL DEFAULT 0,
	`created` DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00",
	`modified` DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00",
	`checked_out` int(11) unsigned NOT NULL DEFAULT 0,
	`checked_out_time` DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00",
	PRIMARY KEY  (`id`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_state` (`published`),
	KEY `idx_categoryname` (`categoryname`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;';

    $db->setQuery($query);
    $db->execute();
}

function updateFields($new_tablename)
{
    $conf = JFactory::getConfig();
    $dbprefix = $conf->get('dbprefix');

    if ($new_tablename == $dbprefix . 'customtables_tables') {
        //Fields::AddMySQLFieldNotExist($new_tablename, 'asset_id', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'params', 'text NULL', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'published', 'TINYINT(3) NOT NULL DEFAULT 1', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'created_by', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'modified_by', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'created', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'modified', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'checked_out', 'int(11) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'checked_out_time', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'version', 'INT(10) unsigned NOT NULL DEFAULT 1', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'hits', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'ordering', 'INT(11) NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'tablecategory', 'INT(11) NOT NULL DEFAULT 0', '');

    } elseif ($new_tablename == $dbprefix . 'customtables_layouts') {
        //Fields::AddMySQLFieldNotExist($new_tablename, 'asset_id', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'params', 'text NULL', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'published', 'TINYINT(3) NOT NULL DEFAULT 1', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'created_by', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'modified_by', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'created', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'modified', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'checked_out', 'int(11) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'checked_out_time', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'version', 'INT(10) unsigned NOT NULL DEFAULT 1', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'hits', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'ordering', 'INT(11) NOT NULL DEFAULT 0', '');

    } elseif ($new_tablename == $dbprefix . 'customtables_fields') {
        //Fields::AddMySQLFieldNotExist($new_tablename, 'asset_id', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'params', 'text NULL', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'published', 'TINYINT(3) NOT NULL DEFAULT 1', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'created_by', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'modified_by', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'created', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'modified', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'checked_out', 'int(11) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'checked_out_time', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'version', 'INT(10) unsigned NOT NULL DEFAULT 1', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'hits', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'ordering', 'INT(11) NOT NULL DEFAULT 0', '');

        Fields::AddMySQLFieldNotExist($new_tablename, 'description', 'VARCHAR(1024) NOT NULL DEFAULT ""', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'isdisabled', 'tinyint(1) NOT NULL default "0"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'phponadd', 'text NULL', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'phponchange', 'text NULL', '');
    } elseif ($new_tablename == $dbprefix . 'customtables_categories') {
        //Fields::AddMySQLFieldNotExist($new_tablename, 'asset_id', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'params', 'text NULL', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'published', 'TINYINT(3) NOT NULL DEFAULT 1', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'created_by', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'modified_by', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'created', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'modified', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'checked_out', 'int(11) unsigned NOT NULL DEFAULT 0', '');
        Fields::AddMySQLFieldNotExist($new_tablename, 'checked_out_time', 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'version', 'INT(10) unsigned NOT NULL DEFAULT 1', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'hits', 'INT(10) unsigned NOT NULL DEFAULT 0', '');
        //Fields::AddMySQLFieldNotExist($new_tablename, 'ordering', 'INT(11) NOT NULL DEFAULT 0', '');
    }

}

function fixFields($tablename)
{
    $db = JFactory::getDBO();
    $found = false;

    $a = array('_10', '_1', '_2', '_3', '_4', '_5', '_6', '_7', '_8', '_9');
    $fields = getExistingFields($tablename);

    foreach ($fields as $field) {

        $fn = $field['Field'];
        $type = $field['Type'];
        $null = $field['Null'];

        foreach ($a as $b) {

            if (str_contains($fn, $b)) {
                //Do something

                if ($b == '_1' and !str_contains($fn, '_10')) {
                    //rename
                    $newcolumnname = str_replace('_1', '', $field['Field']);
                    $query = 'ALTER TABLE `' . $tablename . '` CHANGE `' . $fn . '` `' . $newcolumnname . '` ' . $type . ' ' . ($null != 'NO' ? 'NULL' : '') . ';';
                } elseif ($b == '_2') {
                    //rename
                    $newcolumnname = str_replace('_2', '_es', $field['Field']);
                    $query = 'ALTER TABLE `' . $tablename . '` CHANGE `' . $fn . '` `' . $newcolumnname . '` ' . $type . ' ' . ($null != 'NO' ? 'NULL' : '') . ';';
                } else {
                    //delete
                    $query = 'ALTER TABLE `' . $tablename . '` DROP column `' . $fn . '`';
                }

                $db->setQuery($query);
                $db->execute();
                $found = true;
                break;
            }
        }
    }
    return $found;
}

function getExistingFields($tablename)
{
    $db = JFactory::getDBO();
    $query = 'SHOW COLUMNS FROM ' . $tablename;
    $db->setQuery($query);
    return $db->loadAssocList();
}
