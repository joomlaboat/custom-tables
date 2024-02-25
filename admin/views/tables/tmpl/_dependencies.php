<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/tables/tmpl/_dependencies.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\MySQLWhereClause;

/**
 * @throws Exception
 * @since 3.2.2
 */
function renderDependencies(int $table_id, string $tablename): string
{
	$result = '';
	$rows = _getTablesThatDependOnThisTable($tablename);

	if (count($rows) == 0)
		$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_TABLES_NO_TABLES_THAT_DEPEND_ON_THIS_TABLE') . '</h4>';
	else {
		$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_TABLES_TABLES_THAT_DEPEND_ON_THIS_TABLE') . '</h4>';
		$result .= _renderTableList($rows);
	}

	$result .= '<hr/>';
	$rows = _getTablesThisTableDependOn($table_id);

	if (count($rows) == 0)
		$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_TABLES_THIS_TABLE_DOESNT_HAVE_TABLE_JOIN_TYPE_FIELDS') . '</h4>';
	else {
		$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_TABLES_TABLES_THIS_TABLE_DEPENDS_ON') . '</h4>';
		$result .= _renderTableList($rows);
	}

	$result .= '<hr/>';
	$menus = _getMenuItemsThatUseThisTable($tablename);

	if (count($menus) == 0) {
		$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_TABLES_NO_MENU_ITEMS') . '</h4>';
	} else {
		$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_TABLES_MENUS_DEPENDING') . '</h4>';
		$result .= _renderMenuList($menus);
	}

	$result .= '<hr/>';
	$layouts = _getLayoutsThatUseThisTable($table_id, $tablename);
	if (count($layouts) == 0) {
		$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_TABLES_NO_LAYOUTS') . '</h4>';
	} else {
		$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_TABLES_LAYOUTS_DEPENDING') . '</h4>';
		$result .= _renderLayoutList($layouts);
	}
	$result .= '<hr/>';
	$result .= common::translate('COM_CUSTOMTABLES_TABLES_DATABASE_NORMALIZATION_EXPLAINED_IN_SIMPLE_ENGLISH');
	return $result;
}

function _renderTableList($rows): string
{
	$result = '
        <table class="table table-striped">
			<thead>
                <th>' . common::translate('COM_CUSTOMTABLES_TABLES_TABLETITLE') . '</th>
                <th>' . common::translate('COM_CUSTOMTABLES_FIELDS_FIELDNAME') . '</th>
                <th>' . common::translate('COM_CUSTOMTABLES_FIELDS_TYPEPARAMS') . '</th>
            </thead>
			<tbody>
            ';

	foreach ($rows as $row) {
		$result .= '<tr>
        <td><a href="/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $row['tableid'] . '" target="_blank">' . $row['tabletitle'] . '</a></td>
        <td><a href="/administrator/index.php?option=com_customtables&view=listoffields&task=fields.edit&tableid=' . $row['tableid'] . '&id=' . $row['id'] . '" target="_blank">' . $row['fieldtitle'] . '</a></td>
        <td>' . $row['typeparams'] . '</td>
        </tr>';

	}

	$result .= '</tbody>
            <tfoot></tfoot>
		</table>
    ';

	return $result;
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function _getLayoutsThatUseThisTable($tableId, $tableName)
{
	$whereClause = new MySQLWhereClause();
	$whereClause->addCondition('published', 1);
	$layout_params = ['"' . $tableName . '"', "'" . $tableName . "'"];
	$whereClause->addCondition('tableid', $tableId);

	foreach ($layout_params as $l)
		$whereClause->addCondition('layoutcode', $l, 'INSTR');

	foreach ($layout_params as $l)
		$whereClause->addCondition('layoutmobile', $l, 'INSTR');

	foreach ($layout_params as $l)
		$whereClause->addCondition('layoutcss', $l, 'INSTR');

	foreach ($layout_params as $l)
		$whereClause->addCondition('layoutjs', $l, 'INSTR');

	$db = database::getDB();

	$query = 'SELECT id, layoutname FROM #__customtables_layouts WHERE ' . $whereClause;

	try {
		$db->setQuery($query);
	} catch (Exception $e) {
		echo 'Query error: ' . $query . ', Message: ' . $e->getMessage();
	}

	return $db->loadAssocList();
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function _getMenuItemsThatUseThisTable($tablename)
{
	$whereClause = new MySQLWhereClause();
	$whereClause->addCondition('published', 1);
	$whereClause->addCondition('link', 'index.php?option=com_customtables&view=', 'INSTR');
	$whereClause->addCondition('params', '"establename":"' . $tablename . '"', 'INSTR');

	$db = database::getDB();

	$query = 'SELECT id, title FROM #__menu WHERE ' . $whereClause;

	try {
		$db->setQuery($query);
	} catch (Exception $e) {
		echo 'Query error: ' . $query . ', Message: ' . $e->getMessage();
	}

	return $db->loadAssocList();
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function _getTablesThisTableDependOn($table_id)
{
	if ((int)$table_id == 0)
		return array();

	$serverType = database::getServerType();

	$whereClause = new MySQLWhereClause();
	$whereClause->addCondition('tableid', (int)$table_id);
	$whereClause->addCondition('type', 'sqljoin');

	if ($serverType == 'postgresql') {
		$select_tableNameCheck = '(SELECT id FROM #__customtables_tables AS t2 WHERE POSITION(CONCAT(t2.tablename,\',\') IN a.typeparams)>0 LIMIT 1) ';
	} else {
		$select_tableNameCheck = '(SELECT id FROM #__customtables_tables AS t2 WHERE t2.tablename LIKE SUBSTRING_INDEX(a.typeparams,",",1) LIMIT 1) ';
	}

	$whereClause->addCondition($select_tableNameCheck, null, 'NOT NULL');

	$db = database::getDB();
	$selects = database::sanitizeSelects(['id', 'tableid', 'fieldtitle', 'typeparams', 'TABLE_TITLE'], '#__customtables_fields');
	$query = 'SELECT ' . $selects . ' FROM #__customtables_fields AS a WHERE ' . $whereClause . ' ORDER BY tabletitle';

	try {
		$db->setQuery($query);
	} catch (Exception $e) {
		echo 'Query error: ' . $query . ', Message: ' . $e->getMessage();
	}

	return $db->loadAssocList();
}

/**
 * @throws Exception
 * @since 3.2.2
 */
function _getTablesThatDependOnThisTable($tablename)
{
	if ($tablename === null)
		return [];

	$whereClause = new MySQLWhereClause();
	$whereClauseTemp = new MySQLWhereClause();
	$whereClauseTemp->addOrCondition('published', 1);
	$whereClauseTemp->addOrCondition('published', 0);
	$whereClause->addNestedCondition($whereClauseTemp);

	$serverType = database::getServerType();
	if ($serverType == 'postgresql')
		$whereClause->addCondition('typeparams', $tablename . ',%', 'LIKE');
	//$where[] = 'typeparams LIKE \'' . $tablename . ',%\'';
	else {
		$whereClauseTemp = new MySQLWhereClause();
		$whereClauseTemp->addOrCondition('typeparams', $tablename . ',%', 'LIKE');
		$whereClauseTemp->addOrCondition('typeparams', '"' . $tablename . '",%', 'LIKE');

		//$where[] = '(typeparams LIKE "' . $tablename . ',%" OR typeparams LIKE \'"' . $tablename . '",%\')';
		$whereClause->addNestedCondition($whereClauseTemp);
	}

	$db = database::getDB();
	$selects = database::sanitizeSelects(['id', 'tableid', 'fieldtitle', 'typeparams', 'TABLE_TITLE', 'published'], '#__customtables_fields');
	$query = 'SELECT ' . $selects . ' FROM #__customtables_fields AS a WHERE ' . $whereClause . ' ORDER BY tabletitle';

	try {
		$db->setQuery($query);
	} catch (Exception $e) {
		echo 'Query error: ' . $query . ', Message: ' . $e->getMessage();
	}
	return $db->loadAssocList();
}

function _renderMenuList($menus): string
{
	$result = '<ul style="list-style-type:none;margin:0;">';

	foreach ($menus as $menu) {
		$link = '/administrator/index.php?option=com_menus&view=item&client_id=0&layout=edit&id=' . $menu['id'];
		$result .= '<li><a href="' . $link . '" target="_blank">' . $menu['title'] . '</a></li>';
	}

	$result .= '</ul>';
	return $result;
}

function _renderLayoutList($layouts): string
{
	$result = '<ul style="list-style-type:none;margin:0;">';

	foreach ($layouts as $layout) {
		$link = '/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $layout['id'];
		$result .= '<li><a href="' . $link . '" target="_blank">' . $layout['layoutname'] . '</a></li>';
	}

	$result .= '</ul>';
	return $result;
}