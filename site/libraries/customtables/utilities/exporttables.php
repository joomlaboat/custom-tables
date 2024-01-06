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

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class ExportTables
{
	//this function creates json(.txt) file that will include instruction to create selected tables and depended on menu items and layouts.
	//Records can be exported too, if it set in table parameters
	//file is created in /tmp folder or as set in $path parameter

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function export($table_ids, $path = 'tmp'): ?string
	{
		$link = '';
		$tables = array();
		$output = array();

		foreach ($table_ids as $table_id) {
			//get table
			$s1 = '(SELECT categoryname FROM #__customtables_categories WHERE #__customtables_categories.id=#__customtables_tables.tablecategory) AS categoryname';
			//$query = 'SELECT *,' . $s1 . ' FROM #__customtables_tables WHERE published=1 AND id=' . (int)$table_id . ' LIMIT 1';

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('published', 1);
			$whereClause->addCondition('id', (int)$table_id);

			$table_rows = database::loadAssocList('#__customtables_tables', ['*', $s1], $whereClause, null, null, 1);

			//Add the table with dependencies to export array
			if (count($table_rows) == 1) {
				$tables[] = $table_rows[0]['tablename'];
				$output[] = ExportTables::processTable($table_rows[0]);
			}
		}

		//Save the array to file
		if (count($output) > 0) {
			//Prepare output string with data
			$output_str = '<customtablestableexport>' . common::ctJsonEncode($output);

			$tmp_path = JPATH_SITE . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR;
			$filename = substr(implode('_', $tables), 0, 128);

			$a = '';
			$i = 0;
			while (1) {
				if (!file_exists($tmp_path . $filename . $a . '.txt')) {
					$filename_available = $filename . $a . '.txt';
					break;
				}
				$i++;
				$a = $i . '';
			}

			//Save file
			$link = str_replace(DIRECTORY_SEPARATOR, '/', Uri::root());

			if ($link[strlen($link) - 1] != '/' and $path[0] != '/')
				$link .= '/';

			$link .= $path . '/' . $filename_available;

			$msg = common::saveString2File($tmp_path . $filename_available, $output_str);
			if ($msg !== null) {
				Factory::getApplication()->enqueueMessage($tmp_path . $filename_available . '<br/>' . $msg, 'error');
				return null;
			}
		}
		return $link;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processTable($table): array
	{
		//get fields
		//$query = 'SELECT * FROM #__customtables_fields WHERE published=1 AND tableid=' . (int)$table['id'];

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addCondition('tableid', (int)$table['id']);

		$fields = database::loadAssocList('#__customtables_fields', ['*'], $whereClause, null, null);

		//get layouts
		//$query = 'SELECT * FROM #__customtables_layouts WHERE published=1 AND tableid=' . (int)$table['id'];

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addCondition('tableid', (int)$table['id']);

		$layouts = database::loadAssocList('#__customtables_layouts', ['*'], $whereClause, null, null);

		//Get depended menu items
		//$wheres = array();
		//$wheres[] = 'published=1';
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);

		$serverType = database::getServerType();
		if ($serverType == 'postgresql') {
			$whereClause->addCondition('POSITION("index.php?option=com_customtables&view=" IN link)', 0, '>');
			$whereClause->addCondition('POSITION("establename":"' . $table['tablename'] . '" IN params)', 0, '>');
			//$wheres[] = 'POSITION(' . database::quote("index.php?option=com_customtables&view=") . ' IN link)>0';
			//$wheres[] = 'POSITION(' . database::quote('"establename":"' . $table['tablename'] . '"') . ' IN params)>0';
		} else {
			$whereClause->addCondition('link', 'index.php?option=com_customtables&view=', 'INSTR');
			$whereClause->addCondition('params', '"establename":"' . $table['tablename'] . '"', 'INSTR');
			//$wheres[] = 'INSTR(link,' . database::quote("index.php?option=com_customtables&view=") . ')';
			//$wheres[] = 'INSTR(params,' . database::quote('"establename":"' . $table['tablename'] . '"') . ')';
		}

		//$query = 'SELECT * FROM #__menu WHERE ' . implode(' AND ', $wheres);

		$menu = database::loadAssocList('#__menu', ['*'], $whereClause, null, null);

		//Get depended records
		if (intval($table['allowimportcontent']) == 1) {
			//$query = 'SELECT * FROM #__customtables_table_' . $table['tablename'] . ' WHERE published=1';

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('published', 1);

			$records = database::loadAssocList('#__customtables_table_' . $table['tablename'], ['*'], $whereClause, null, null);
		} else
			$records = null;

		return ['table' => $table, 'fields' => $fields, 'layouts' => $layouts, 'records' => $records, 'menu' => $menu];
	}
}