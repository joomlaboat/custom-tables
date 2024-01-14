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

use CustomTables\common;
use CustomTables\database;
use CustomTables\Fields;
use CustomTables\MySQLWhereClause;

defined('_JEXEC') or die('Restricted access');

$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
	. 'customtables' . DIRECTORY_SEPARATOR;
require_once($path . 'helpers' . DIRECTORY_SEPARATOR . 'misc.php');
require_once($path . 'tables' . DIRECTORY_SEPARATOR . 'tables.php');
require_once($path . 'fields' . DIRECTORY_SEPARATOR . 'fields.php');

/**
 * @throws Exception
 * @since 3.2.2
 */
function updateESTables(): void
{
	getESTables();
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function getESTables(): void
{
	$tables = database::showTables();
	$database = database::getDataBaseName();

	foreach ($tables as $table) {
		$tablename = $table['Tables_in_' . $database];

		if (str_contains($tablename, '_extrasearch_'))//dont change this line _e x t r a  s e a r c h_
		{
			$new_tablename = str_replace('_extrasearch_', '_customtables_', $tablename);//dont change this line. First must be _e x t r a  s e a r c h_
			database::dropTableIfExists($new_tablename);

			database::renameTable($tablename, $new_tablename);
			///$query = 'RENAME TABLE ' . $tablename . ' TO ' . $new_tablename;

			fixFields($new_tablename);

		} elseif (str_contains($tablename, '_customtables_')) {
			updateFields($tablename);
		}
	}

	updateMenuItems();

	updateLayouts();

	addCategoriesTable();

	updateImageFieldTypeParams();

	updatefieldTypes();

	updateContent();

	updateLayoutVerticalBarTags();

	fixTableCategory();
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function fixTableCategory(): void
{
	$tablename = '#__customtables_tables';
	$fields = database::getExistingFields($tablename);

	$catid = findFileByName($fields, 'catid');
	$tableCategory = findFileByName($fields, 'tablecategory');

	if ($tableCategory == null and is_array($catid)) {
		//rename field
		database::changeColumn($tablename, 'catid', 'tablecategory', 'int(11)', true);
		//$query = 'ALTERTABLE `' . $tablename . '` CHANGE `catid` `tablecategory` int(11);';

	} elseif (is_array($tableCategory) and is_array($catid)) {
		//delete tablecategory
		database::dropColumn($tablename, 'tablecategory');
		//$query = 'ALTERTABLE `' . $tablename . '` DROP column `tablecategory`';

		database::changeColumn($tablename, 'catid', 'tablecategory', 'int(11)', true);
		//$query = 'ALTERTABLE `' . $tablename . '` CHANGE `catid` `tablecategory` int(11);';
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

/**
 * @throws Exception
 * @since 3.2.2
 */
function updateLayoutVerticalBarTags(): void
{
	//$query = 'SELECT id, layoutcode FROM #__customtables_layouts WHERE INSTR(layoutcode,"|toolbar") OR INSTR(layoutcode,"|search")';

	$whereClause = new MySQLWhereClause();
	$whereClause->addOrCondition('layoutcode', "%|toolbar%", 'LIKE');
	$whereClause->addOrCondition('layoutcode', "%|search%", 'LIKE');

	$records = database::loadAssocList('#__customtables_layouts', ['id', 'layoutcode'], $whereClause);

	foreach ($records as $record) {
		$c = str_replace('|toolbar', '|batchtoolbar', $record['layoutcode']);

		$c = fixToolBarTags($c, 'toolbar');
		$c = fixToolBarTags($c, 'search');
		$c = fixToolBarTags($c, 'checkbox');
		$c = fixToolBarTags($c, '(id)');
		$c = fixToolBarTags($c, 'batchtoolbar');

		if ($c != $record['layoutcode']) {
			//update

			$data = ['layoutcode' => $c];
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('id', $record['id']);
			database::update('#__customtables_layouts', $data, $whereClauseUpdate);

			//$query = 'UPDATE `#__customtables_layouts` SET
			//	layoutcode=' . database::quote($c) . ' WHERE id=' . $record['id'];
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
	return $htmlresult;
}


/**
 * @throws Exception
 * @since 3.2.2
 */
function updateImageFieldTypeParams(): void
{
	$data = ['typeparams' => ['CONCAT(\'"\',REPLACE(typeparams,\'|\',\'",\'))', 'sanitized']];
	$whereClauseUpdate = new MySQLWhereClause();
	$whereClauseUpdate->addOrCondition('type', 'image');
	$whereClauseUpdate->addOrCondition('type', 'imagegallery');
	$whereClauseUpdate->addCondition('typeparams', '|', 'INSTR');
	database::update('#__customtables_fields', $data, $whereClauseUpdate);

	//$query = 'UPDATE `#__customtables_fields` SET typeparams=CONCAT(\'"\',REPLACE(typeparams,\'|\',\'",\')) WHERE (`type`="image" OR `type`="imagegallery") AND INSTR(typeparams,\'|\')';
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function updateMenuItems(): void
{
	//$sets = array();

	//$sets[] = 'link=replace(link,"com_extrasearch","com_customtables")';
	//$sets[] = 'component_id=(SELECT extension_id FROM `#__extensions` where element="com_customtables" LIMIT 1)';

	$data = [
		'link' => ['replace(link,"com_extrasearch","com_customtables")', 'sanitized'],
		'component_id' => ['(SELECT extension_id FROM `#__extensions` where element="com_customtables" LIMIT 1)', 'sanitized']
	];
	$whereClauseUpdate = new MySQLWhereClause();
	$whereClauseUpdate->addCondition('link', 'com_extrasearch', 'instr');
	database::update('#__menu', $data, $whereClauseUpdate);

	$data = [
		'params' => ['replace(params,\'layout":"layout:\',\'layout":"\')', 'sanitized'],
		'params' => ['replace(params,\'"detailslayout":"\',\'"esdetailslayout":"\')', 'sanitized'],
		'params' => ['replace(params,\'"editlayout":"\',\'"eseditlayout":"\')', 'sanitized']
	];
	$whereClauseUpdate = new MySQLWhereClause();
	$whereClauseUpdate->addCondition('link', 'com_customtables', 'instr');
	database::update('#__menu', $data, $whereClauseUpdate);
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function updatefieldTypes(): void
{
	$data = [
		'type' => 'customtables'
	];
	$whereClauseUpdate = new MySQLWhereClause();
	$whereClauseUpdate->addCondition('type', 'extrasearch', 'INSTR');
	database::update('#__customtables_fields', $data, $whereClauseUpdate);

	//$query = 'UPDATE #__customtables_fields SET `type`="customtables" WHERE INSTR(`type`,"extrasearch")';
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function updateLayouts(): void
{
	//$content = common::getStringFromFile($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename);

	$data = [
		'layoutcode' => ['replace(layoutcode,"extrasearch","customtables")', 'sanitized']
	];
	$whereClauseUpdate = new MySQLWhereClause();
	$whereClauseUpdate->addCondition('layoutcode', 'extrasearch', 'instr');
	database::update('#__customtables_layouts', $data, $whereClauseUpdate);
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function updateContent(): void
{
	//$content = common::getStringFromFile($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename);

	$data = [
		'introtext' => ['replace(introtext,"{extrasearch","{customtables")', 'sanitized']
	];

	$whereClauseUpdate = new MySQLWhereClause();
	$whereClauseUpdate->addCondition('introtext', 'extrasearch', 'INSTR');
	database::update('#__content', $data, $whereClauseUpdate);
}


/*
function fixNewCTTables($mysqltable)
{
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
    $query='ALTERTABLE `ow94h_customtables_categories` ADD '.implode(', ADD ',$ads);
}
*/

/**
 * @throws Exception
 * @since 3.2.2
 */
function addCategoriesTable(): void
{
	$columns = [
		'`published` TINYINT(3) NOT NULL DEFAULT 1',
		'`categoryname` VARCHAR(255) NOT NULL DEFAULT ""',
		'`created_by` INT(10) unsigned NOT NULL DEFAULT 0',
		'`modified_by` INT(10) unsigned NOT NULL DEFAULT 0',
		'`created` DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"',
		'`modified` DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"',
		'`checked_out` int(11) unsigned NOT NULL DEFAULT 0',
		'`checked_out_time` DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"',
	];

	$keys = [
		'KEY `idx_checkout` (`checked_out`)',
		'KEY `idx_createdby` (`created_by`)',
		'KEY `idx_modifiedby` (`modified_by`)',
		'KEY `idx_state` (`published`)',
		'KEY `idx_categoryname` (`categoryname`)',
	];
	database::createTable('#__customtables_categories', 'id', $columns, 'Table Categories', $keys);
}

function updateFields($new_tablename): void
{
	$dbPrefix = database::getDBPrefix();

	if ($new_tablename == $dbPrefix . 'customtables_tables') {
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

	} elseif ($new_tablename == database::getDBPrefix() . 'customtables_layouts') {
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

	} elseif ($new_tablename == database::getDBPrefix() . 'customtables_fields') {
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
	} elseif ($new_tablename == database::getDBPrefix() . 'customtables_categories') {
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

function fixFields($tablename): bool
{
	$found = false;
	$a = array('_10', '_1', '_2', '_3', '_4', '_5', '_6', '_7', '_8', '_9');
	$fields = database::getExistingFields($tablename);

	foreach ($fields as $field) {

		$fn = $field['Field'];
		$type = $field['Type'];
		$null = $field['Null'];

		foreach ($a as $b) {

			if (str_contains($fn, $b)) {
				//Do something

				if ($b == '_1' and !str_contains($fn, '_10')) {
					//rename
					$newColumnName = str_replace('_1', '', $field['Field']);
					database::changeColumn($tablename, $fn, $newColumnName, $type, $null != 'NO');
					//$query = 'ALTERTABLE `' . $tablename . '` CHANGE `' . $fn . '` `' . $newcolumnname . '` ' . $type . ' ' . ($null != 'NO' ? 'NULL' : '') . ';';
				} elseif ($b == '_2') {
					//rename
					$newColumnName = str_replace('_2', '_es', $field['Field']);
					database::changeColumn($tablename, $fn, $newColumnName, $type, $null != 'NO');
					//$query = 'ALTERTABLE `' . $tablename . '` CHANGE `' . $fn . '` `' . $newcolumnname . '` ' . $type . ' ' . ($null != 'NO' ? 'NULL' : '') . ';';
				} else {
					//delete
					database::dropColumn($tablename, $fn);
				}
				$found = true;
				break;
			}
		}
	}
	return $found;
}

