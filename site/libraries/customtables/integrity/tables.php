<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @subpackage integrity/tables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\Integrity;

if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\IntegrityChecks;
use CustomTables\MySQLWhereClause;
use Exception;
use Joomla\CMS\Uri\Uri;

class IntegrityTables extends IntegrityChecks
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function checkTables(&$ct)
	{
		$tables = IntegrityTables::getTables();
		IntegrityTables::checkIfTablesExists($tables);
		$result = [];

		foreach ($tables as $table) {

			//Check if table exists
			$rows = database::getTableStatus($table['tablename']);

			$tableExists = !(count($rows) == 0);

			if ($tableExists) {

				$ct->setTable($table, null, false);
				$link = Uri::root() . 'administrator/index.php?option=com_customtables&view=databasecheck&tableid=' . $table['id'];
				$content = IntegrityFields::checkFields($ct, $link);

				$zeroId = IntegrityTables::getZeroRecordID($table['realtablename'], $table['realidfieldname']);

				if ($content != '' or $zeroId > 0) {
					if (!str_contains($link, '?'))
						$link .= '?';
					else
						$link .= '&';

					$result[] = '<p><span style="font-size:1.3em;">' . $table['tabletitle'] . '</span><br/><span style="color:gray;">' . $table['realtablename'] . '</span>'
						. ' <a href="' . $link . 'task=fixfieldtype&fieldname=all_fields">Fix all fields</a>'
						. '</p>'
						. $content
						. ($zeroId > 0 ? '<p style="font-size:1.3em;color:red;">Records with ID = 0 found. Please fix it manually.</p>' : '');
				}
			}
		}

		return $result;
	}

	protected static function getTables(): ?array
	{
		// Create a new query object.
		try {
			return self::getTablesQuery();
		} catch (Exception $e) {
			common::enqueueMessage($e->getMessage());
		}

		try {
			self::getTablesQuery(true);
		} catch (Exception $e) {
			common::enqueueMessage($e->getMessage());
		}
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function getTablesQuery(bool $simple = false): array
	{
		$whereClause = new MySQLWhereClause();

		if ($simple) {
			$selects = [];
			$selects[] = 'id';
			$selects[] = 'tablename';
		} else {
			$selects = TableHelper::getTableRowSelectArray();

			$selects[] = 'CATEGORY_NAME';
			$selects[] = 'FIELD_COUNT';
		}

		// Add the list ordering clause.
		$orderCol = 'tablename';
		$orderDirection = 'asc';

		$whereClause->addCondition('a.published', 1);

		return database::loadAssocList('#__customtables_tables AS a', $selects, $whereClause, $orderCol, $orderDirection);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function checkIfTablesExists($tables_rows)
	{
		$dbPrefix = database::getDBPrefix();

		foreach ($tables_rows as $row) {
			if (!TableHelper::checkIfTableExists($dbPrefix . 'customtables_table_' . $row['tablename'])) {
				$database = database::getDataBaseName();

				if ($row['customtablename'] === null or $row['customtablename'] == '') {
					if (TableHelper::createTableIfNotExists($database, $dbPrefix, $row['tablename'], $row['tabletitle'], $row['customtablename'])) {
						common::enqueueMessage('Table "' . $row['tabletitle'] . '" created.');
					}
				}
			}
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function getZeroRecordID($realtablename, $realidfieldname)
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($realidfieldname, 0);

		$rows = database::loadAssocList($realtablename, ['COUNT_ROWS'], $whereClause, null, null, 1);
		$row = $rows[0];

		return $row['record_count'];
	}
}