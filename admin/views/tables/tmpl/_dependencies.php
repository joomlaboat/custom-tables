<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/tables/tmpl/_dependencies.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

function renderDependencies($table_id, $tablename)
{
    $result = '';

    $rows = _getTablesThatDependOnThisTable($tablename);

    if (count($rows) == 0)
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_NO_TABLES_THAT_DEPEND_ON_THIS_TABLE', true) . '</h4>';
    else {
        $result .= '<h3>' . Text::_('COM_CUSTOMTABLES_TABLES_TABLES_THAT_DEPEND_ON_THIS_TABLE', true) . '</h3>';
        $result .= _renderTableList($rows);
    }

    $result .= '<hr/>';
    $rows = _getTablesThisTableDependOn($table_id);
    if (count($rows) == 0)
        $result .= '<h4>' . Text::_('COM_CUSTOMTABLES_TABLES_THIS_TABLE_DOESNT_HAVE_TABLE_JOIN_TYPE_FIELDS', true) . '</h4>';
    else {
        $result .= '<h3>' . Text::_('COM_CUSTOMTABLES_TABLES_TABLES_THIS_TABLE_DEPENDS_ON', true) . '</h3>';
        $result .= _renderTableList($rows);
    }

    $result .= Text::_('COM_CUSTOMTABLES_TABLES_DATABASE_NORMALIZATION_EXPLAINED_IN_SIMPLE_ENGLISH', true);


    return $result;
}

function _renderTableList($rows)
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

function _getTablesThisTableDependOn($table_id)
{
    if ((int)$table_id == 0)
        return array();

    $db = Factory::getDBO();

    if ($db->serverType == 'postgresql') {
        $select_tablename = '(SELECT tabletitle FROM #__customtables_tables AS t WHERE POSITION(CONCAT(t.tablename,\',\') IN typeparams)>0 LIMIT 1) ';
        $query = 'SELECT id, tableid,fieldtitle,typeparams,' . $select_tablename . ' AS tabletitle FROM #__customtables_fields AS f '
            . 'WHERE tableid=' . (int)$table_id . ' AND ' . $db->quoteName('type') . '=\'sqljoin\' ORDER BY tabletitle';
    } else {
        $select_tablename = '(SELECT tabletitle FROM #__customtables_tables AS t WHERE t.tablename LIKE SUBSTRING_INDEX(typeparams,",",1) LIMIT 1) ';
        $query = 'SELECT id, tableid,fieldtitle,typeparams,' . $select_tablename . ' AS tabletitle FROM #__customtables_fields AS f '
            . 'WHERE tableid=' . (int)$table_id . ' AND ' . $db->quoteName('type') . '="sqljoin" ORDER BY tabletitle';
    }

    $db->setQuery($query);
    $rows = $db->loadAssocList();

    return $rows;
}

function _getTablesThatDependOnThisTable($tablename)
{
    if ($tablename === null)
        return [];

    $db = Factory::getDBO();

    $select_tablename = '(SELECT tabletitle FROM #__customtables_tables AS t WHERE t.id=f.tableid LIMIT 1)';

    if ($db->serverType == 'postgresql')
        $where = 'typeparams LIKE \'' . $tablename . ',%\'';
    else
        $where = 'typeparams LIKE "' . $tablename . ',%"';

    $query = 'SELECT id, tableid,fieldtitle,typeparams,' . $select_tablename . ' AS tabletitle FROM #__customtables_fields AS f WHERE ' . $where . ' ORDER BY tabletitle';

    $db->setQuery($query);
    $rows = $db->loadAssocList();

    return $rows;
}
