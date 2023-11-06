<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/layouts/tmpl/dependencies.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\common;
use CustomTables\database;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

function renderDependencies($layout_row): string
{
    $count = 0;
    $layoutname = $layout_row->layoutname;

    $result = '<div id="layouteditor_modal_content_box">';

    $serverType = database::getServerType();

    if ($serverType == 'postgresql') {
        $w1 = '(' . database::quoteName('type') . '=\'sqljoin\' OR ' . database::quoteName('type') . '=\'records\')';
        $w2a = 'POSITOIN(\'layout:' . $layoutname . '\' IN SUBSTRING_INDEX(typeparams,",",2))>0';
        $w2b = 'POSITOIN(\'tablelesslayout:' . $layoutname . '\' IN SUBSTRING_INDEX(typeparams,",",2))>0';
    } else {
        $w1 = '(' . database::quoteName('type') . '="sqljoin" OR ' . database::quoteName('type') . '="records")';
        $w2a = 'INSTR(SUBSTRING_INDEX(typeparams,",",2),"layout:' . $layoutname . '")';
        $w2b = 'INSTR(SUBSTRING_INDEX(typeparams,",",2),"tablelesslayout:' . $layoutname . '")';
    }
    $w2 = '(' . $w2a . ' OR ' . $w2b . ')';
    $wF = 'f.published=1 AND f.tableid=t.id AND ' . $w1 . ' AND ' . $w2;

    $rows = _getTablesThatUseThisLayout($wF);

    if (count($rows) > 0) {
        $count += count($rows);
        $result .= '<h3>' . common::translate('COM_CUSTOMTABLES_LAYOUTS_TABLES_WITH_TABLEJOIN_FIELDS') . '</h3>';
        $result .= _renderTableList($rows);
    }

    $serverType = database::getServerType();

    if ($serverType == 'postgresql') {
        $w2a = 'POSITION(\'layout:' . $layoutname . '\' IN SUBSTRING_INDEX(defaultvalue,",",2))>0';
        $w2b = 'POSITION(\'tablelesslayout:' . $layoutname . '\' IN SUBSTRING_INDEX(defaultvalue,",",2))>0';
    } else {
        $w2a = 'INSTR(SUBSTRING_INDEX(defaultvalue,",",2),"layout:' . $layoutname . '")';
        $w2b = 'INSTR(SUBSTRING_INDEX(defaultvalue,",",2),"tablelesslayout:' . $layoutname . '")';
    }

    $wF = '(' . $w2a . ' OR ' . $w2b . ')';
    $rows = _getTablesThatUseThisLayout($wF);

    if (count($rows) > 0) {
        $count += count($rows);
        $result .= '<h3>' . common::translate('COM_CUSTOMTABLES_LAYOUTS_TABLES_DEFAULTVALUE') . '</h3>';
        $result .= _renderTableList($rows);
    }

    $menus = _getMenuItemsThatUseThisLayout($layout_row->layoutname);

    if (count($menus) > 0) {
        $count += count($menus);
        $result .= '<h3>' . common::translate('COM_CUSTOMTABLES_LAYOUTS_MENUS') . '</h3>';
        $result .= _renderMenuList($menus);

    }

    $modules = _getModulesThatUseThisLayout($layout_row->layoutname);
    if (count($modules) > 0) {
        $count += count($modules);
        $result .= '<h3>' . common::translate('COM_CUSTOMTABLES_LAYOUTS_MODULES') . '</h3>';
        $result .= _renderModuleList($modules);

    }

    $layouts = _getLayoutsThatUseThisLayout($layout_row->layoutname);
    if (count($layouts) > 0) {
        $count += count($layouts);
        $result .= '<h3>' . common::translate('COM_CUSTOMTABLES_DASHBOARD_LISTOFLAYOUTS') . '</h3>';
        $result .= _renderLayoutList($layouts);
    }

    if ($count == 0)
        $result .= '<p>' . common::translate('COM_CUSTOMTABLES_LAYOUTS_THIS_LAYOUT_IS_NOT_IN_USE') . '</p>';

    $result .= '</div>';
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

function _renderModuleList($modules): string
{
    $result = '<ul style="list-style-type:none;margin:0;">';

    foreach ($modules as $module) {
        $link = '/administrator/index.php?option=com_modules&task=module.edit&id=' . $module['id'];
        $result .= '<li><a href="' . $link . '" target="_blank">' . $module['title'] . '</a></li>';
    }

    $result .= '</ul>';
    return $result;
}

function _renderTableList($rows): string
{
    $result = '
        <table class="table table-striped">
			<thead>
                <th>' . common::translate('COM_CUSTOMTABLES_TABLES_TABLENAME') . '</th>
                <th>' . common::translate('COM_CUSTOMTABLES_TABLES_TABLETITLE') . '</th>
                <th>' . common::translate('COM_CUSTOMTABLES_LISTOFFIELDS') . '</th>
            </thead>
			<tbody>
            ';

    foreach ($rows as $row) {

        $result .= '<tr>
        <td><a href="/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $row['tableid'] . '" target="_blank">' . $row['tablename'] . '</a></td>
		<td><a href="/administrator/index.php?option=com_customtables&view=listoffields&tableid=' . $row['tableid'] . '" target="_blank">' . $row['tabletitle'] . '</a></td>
        <td><ul style="list-style-type:none;margin:0;">';

        $fields = explode(';', $row['fields']);

        foreach ($fields as $field) {
            if ($field != "") {
                $pair = explode(',', $field);
                $result .= '<li><a href="/administrator/index.php?option=com_customtables&view=listoffields&task=fields.edit&tableid=' . $row['tableid'] . '&id=' . $pair[0] . '" target="_blank">' . $pair[1] . '</a></li>';
            }
        }
        $result .= '</ul></td></tr>';
    }

    $result .= '</tbody>
            <tfoot></tfoot>
		</table>
    ';
    return $result;
}

function _getTablesThatUseThisLayout($wF)
{
    $fields = '(SELECT GROUP_CONCAT(CONCAT(f.id,",",fieldname),";") FROM #__customtables_fields AS f WHERE ' . $wF . ' ORDER BY fieldname) AS fields';
    $w = '(SELECT tableid FROM #__customtables_fields AS f WHERE ' . $wF . ' LIMIT 1) IS NOT NULL';
    $query = 'SELECT id AS tableid, tabletitle,tablename, ' . $fields . ' FROM #__customtables_tables AS t WHERE ' . $w . ' ORDER BY tablename';
    return database::loadAssocList($query);
}

function _getMenuItemsThatUseThisLayout($layoutname)
{
    $wheres = array();
    $wheres[] = 'published=1';
    $wheres[] = 'INSTR(link,"index.php?option=com_customtables&view=")';

    $layout_params = ['escataloglayout', 'esitemlayout', 'esdetailslayout', 'eseditlayout', 'onrecordaddsendemaillayout', 'cataloglayout'];
    $w = array();
    foreach ($layout_params as $l) {
        $toSearch = '"' . $l . '":"' . $layoutname . '"';
        $w[] = 'INSTR(params,' . database::quote($toSearch) . ')';
    }
    $wheres[] = '(' . implode(' OR ', $w) . ')';
    $query = 'SELECT id,title FROM #__menu WHERE ' . implode(' AND ', $wheres);
    return database::loadAssocList($query);
}

function _getModulesThatUseThisLayout($layoutname)
{
    $wheres = array();
    $wheres[] = 'published=1';
    $wheres[] = 'module=' . database::quote('mod_ctcatalog');

    $layout_params = ['ct_pagelayout', 'ct_itemlayout'];
    $w = array();
    foreach ($layout_params as $l) {
        $toSearch = '"' . $l . '":"' . $layoutname . '"';
        $w[] = 'INSTR(params,' . database::quote($toSearch) . ')';
    }
    $wheres[] = '(' . implode(' OR ', $w) . ')';
    $query = 'SELECT id,title FROM #__modules WHERE ' . implode(' AND ', $wheres);
    return database::loadAssocList($query);
}

function _getLayoutsThatUseThisLayout(string $layoutName)
{
    $wheres = array();
    $wheres[] = 'published=1';

    $layout_params = ['{layout:' . $layoutName . '}',    //example: {layout:layoutname}
        ':layout:' . $layoutName . ',',    //example: [field:layout,someparameter]
        ':layout:' . $layoutName . ']',    //example: [field:layout]
        ':tablelesslayout:' . $layoutName . ',',    //example: [tablelesslayout:layout,someparameter]
        ':tablelesslayout:' . $layoutName . ']',    //example: [tablelesslayout:layout]
        '"' . $layoutName . '"',    //example: "layout"
        "'" . $layoutName . "'",    //example: 'layout'
        ',' . $layoutName . ','];        //For plugins

    $w = [];
    foreach ($layout_params as $l)
        $w[] = 'INSTR(layoutcode,' . database::quote($l) . ')';

    foreach ($layout_params as $l)
        $w[] = 'INSTR(layoutmobile,' . database::quote($l) . ')';

    foreach ($layout_params as $l)
        $w[] = 'INSTR(layoutcss,' . database::quote($l) . ')';

    foreach ($layout_params as $l)
        $w[] = 'INSTR(layoutjs,' . database::quote($l) . ')';

    foreach ($layout_params as $l) {
        $w[] = 'INSTR(layoutcode,' . database::quote($l) . ')';
    }
    $wheres[] = '(' . implode(' OR ', $w) . ')';
    $query = 'SELECT id,layoutname FROM #__customtables_layouts WHERE ' . implode(' AND ', $wheres);
    return database::loadAssocList($query);
}