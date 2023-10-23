<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class ListOfLayouts
{
    var CT $ct;

    function __construct(CT $ct)
    {
        $this->ct = $ct;
    }

    function getItems($published, $search, $layoutType, $tableid, $orderCol, $orderDirection, $limit, $start): array
    {
        $query = $this->getListQuery($published, $search, $layoutType, $tableid, $orderCol, $orderDirection, $limit, $start);

        $items = database::loadObjectList($query);
        return $this->translateLayoutTypes($items);
    }

    function getListQuery($published, $search, $layoutType, $tableid, $orderCol, $orderDirection, $limit = 0, $start = 0): string
    {
        // Select some fields
        $tabletitle = '(SELECT tabletitle FROM #__customtables_tables AS tables WHERE tables.id=a.tableid LIMIT 1)';
        $modifiedby = '(SELECT name FROM #__users AS u WHERE u.id=a.modified_by LIMIT 1)';
        $query = 'SELECT a.*, ' . $tabletitle . ' AS tabletitle, ' . $modifiedby . ' AS modifiedby';

        // From the customtables_item table
        $query .= ' FROM ' . database::quoteName('#__customtables_layouts') . ' AS a';

        $where = [];

        // Filter by published state
        if (is_numeric($published))
            $where [] = 'a.published = ' . (int)$published;
        elseif (is_null($published) or $published === '')
            $where [] = 'a.published = 0 OR a.published = 1';

        // Filter by search.
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $where [] = 'a.id = ' . (int)substr($search, 3);
            } else {
                $search_clean = database::quote('%' . $search . '%');
                $where [] = '('
                    . ' (a.layoutname LIKE ' . $search_clean . ') OR'
                    . ' INSTR(a.layoutcode,' . database::quote($search) . ') OR'
                    . ' INSTR(a.layoutmobile,' . database::quote($search) . ') OR'
                    . ' INSTR(a.layoutcss,' . database::quote($search) . ') OR'
                    . ' INSTR(a.layoutjs,' . database::quote($search) . ')
					)';
            }
        }

        // Filter by Layouttype.
        if ($layoutType) {
            $where [] = '(a.layouttype = ' . database::quote($layoutType) . ')';
        }
        // Filter by Tableid.
        if ($tableid) {
            $where [] = '(a.tableid = ' . database::quote($tableid) . ')';
        }

        $query .= ' WHERE ' . implode(' AND ', $where);

        // Add the list ordering clause.
        if ($orderCol != '')
            $query .= ' ORDER BY ' . database::quoteName($orderCol) . ' ' . $orderDirection;

        if ($limit != 0)
            $query .= ' LIMIT ' . $limit;

        if ($start != 0)
            $query .= ' OFFSET ' . $start;

        return $query;
    }

    function translateLayoutTypes(array $items): array
    {
        $Layouts = new Layouts($this->ct);
        $translations = $Layouts->layoutTypeTranslation();

        foreach ($items as $item) {
            // convert layoutType
            if (isset($translations[$item->layouttype])) {
                $item->layouttype = $translations[$item->layouttype];
            } else {
                $item->layouttype = '<span style="color:red;">NOT SELECTED</span>';
            }
        }
        return $items;
    }
}