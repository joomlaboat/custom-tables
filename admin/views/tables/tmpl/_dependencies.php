<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/tables/tmpl/_dependencies.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\database;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

function renderDependencies($table_id, $tablename): string
{
    $result = '';
    $rows = _getTablesThatDependOnThisTable($tablename);

    if (count($rows) == 0)
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_NO_TABLES_THAT_DEPEND_ON_THIS_TABLE', true) . '</h4>';
    else {
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_TABLES_THAT_DEPEND_ON_THIS_TABLE', true) . '</h4>';
        $result .= _renderTableList($rows);
    }

    $result .= '<hr/>';
    $rows = _getTablesThisTableDependOn($table_id);

    if (count($rows) == 0)
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_THIS_TABLE_DOESNT_HAVE_TABLE_JOIN_TYPE_FIELDS', true) . '</h4>';
    else {
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_TABLES_THIS_TABLE_DEPENDS_ON', true) . '</h4>';
        $result .= _renderTableList($rows);
    }

    $result .= '<hr/>';
    $menus = _getMenuItemsThatUseThisTable($tablename);

    if (count($menus) == 0) {
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_NO_MENU_ITEMS', true) . '</h4>';
    } else {
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_MENUS_DEPENDING', true) . '</h4>';
        $result .= _renderMenuList($menus);
    }

    $result .= '<hr/>';
    $layouts = _getLayoutsThatUseThisTable($table_id, $tablename);
    if (count($layouts) == 0) {
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_NO_LAYOUTS', true) . '</h4>';
    } else {
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_LAYOUTS_DEPENDING', true) . '</h4>';
        $result .= _renderLayoutList($layouts);
    }
    $result .= '<hr/>';
    $result .= Text::_('COM_CUSTOMTABLES_TABLES_DATABASE_NORMALIZATION_EXPLAINED_IN_SIMPLE_ENGLISH', true);
    return $result;
}

function _renderTableList($rows): string
{
    $result = '
        <table class="table table-striped">
			<thead>
                <th>' . Text::_('COM_CUSTOMTABLES_TABLES_TABLETITLE', true) . '</th>
                <th>' . Text::_('COM_CUSTOMTABLES_FIELDS_FIELDNAME', true) . '</th>
                <th>' . Text::_('COM_CUSTOMTABLES_FIELDS_TYPEPARAMS', true) . '</th>
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

function _getLayoutsThatUseThisTable($tableId, $tableName)
{
    $db = Factory::getDBO();

    $wheres = array();
    $wheres[] = 'published=1';
    $layout_params = ['"' . $tableName . '"', "'" . $tableName . "'"];

    $w = [];

    $w[] = 'tableid=' . $db->quote($tableId);

    foreach ($layout_params as $l)
        $w[] = 'INSTR(layoutcode,' . $db->quote($l) . ')';

    foreach ($layout_params as $l)
        $w[] = 'INSTR(layoutmobile,' . $db->quote($l) . ')';

    foreach ($layout_params as $l)
        $w[] = 'INSTR(layoutcss,' . $db->quote($l) . ')';

    foreach ($layout_params as $l)
        $w[] = 'INSTR(layoutjs,' . $db->quote($l) . ')';

    $wheres[] = '(' . implode(' OR ', $w) . ')';
    $query = 'SELECT id,layoutname FROM #__customtables_layouts WHERE ' . implode(' AND ', $wheres);
    $db->setQuery($query);
    return $db->loadAssocList();
}

function _getMenuItemsThatUseThisTable($tablename)
{
    $db = Factory::getDBO();
    $wheres = array();
    $wheres[] = 'published=1';
    $wheres[] = 'INSTR(link,"index.php?option=com_customtables&view=")';
    $toSearch = '"establename":"' . $tablename . '"';
    $wheres[] = 'INSTR(params,' . $db->quote($toSearch) . ')';
    $query = 'SELECT id,title FROM #__menu WHERE ' . implode(' AND ', $wheres);
    $db->setQuery($query);
    return $db->loadAssocList();
}

function _getTablesThisTableDependOn($table_id)
{
    if ((int)$table_id == 0)
        return array();

    $db = Factory::getDBO();

    $select_tableTitle = '(SELECT id FROM #__customtables_tables AS t1 WHERE t1.id=f.tableid LIMIT 1) ';
    $serverType = database::getServerType();

    if ($serverType == 'postgresql') {
        $select_tableNameCheck = '(SELECT id FROM #__customtables_tables AS t2 WHERE POSITION(CONCAT(t2.tablename,\',\') IN f.typeparams)>0 LIMIT 1) ';
        $query = 'SELECT id, tableid,fieldtitle,typeparams,' . $select_tableTitle . ' AS tabletitle FROM #__customtables_fields AS f '
            . 'WHERE'
            . ' tableid=' . (int)$table_id
            . ' AND ' . $select_tableNameCheck . ' IS NOT NULL'
            . ' AND ' . $db->quoteName('type') . '=\'sqljoin\''
            . ' ORDER BY tabletitle';
    } else {
        $select_tableNameCheck = '(SELECT id FROM #__customtables_tables AS t2 WHERE t2.tablename LIKE SUBSTRING_INDEX(f.typeparams,",",1) LIMIT 1) ';
        $query = 'SELECT id, tableid,fieldtitle,typeparams,' . $select_tableTitle . ' AS tabletitle FROM #__customtables_fields AS f'
            . ' WHERE'
            . ' tableid=' . (int)$table_id
            . ' AND ' . $select_tableNameCheck . ' IS NOT NULL'
            . ' AND ' . $db->quoteName('type') . '="sqljoin"'
            . ' ORDER BY tabletitle';
    }
    $db->setQuery($query);
    return $db->loadAssocList();
}

function _getTablesThatDependOnThisTable($tablename)
{
    if ($tablename === null)
        return [];

    $db = Factory::getDBO();
    $select_tablename = '(SELECT tabletitle FROM #__customtables_tables AS t WHERE t.id=f.tableid LIMIT 1)';

    $where = [];
    $where[] = '(published=1 or published=0)';
    $serverType = database::getServerType();
    if ($serverType == 'postgresql')
        $where[] = 'typeparams LIKE \'' . $tablename . ',%\'';
    else {
        $where[] = '(typeparams LIKE "' . $tablename . ',%" OR typeparams LIKE \'"' . $tablename . '",%\')';
    }

    $query = 'SELECT id, tableid,fieldtitle,typeparams,' . $select_tablename . ' AS tabletitle,published FROM #__customtables_fields AS f WHERE '
        . implode(' AND ', $where) . ' ORDER BY tabletitle';

    $db->setQuery($query);
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