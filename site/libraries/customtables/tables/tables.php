<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access

use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class Tables
{
    var CT $ct;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
    }

    public static function getAllTables(): array
    {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id,tablename,tabletitle');
        $query->from('#__customtables_tables');
        $query->where('published=1');
        $query->order('tablename');

        $db->setQuery((string)$query);

        $records = $db->loadObjectList();

        $allTables = [];
        foreach ($records as $rec)
            $allTables[] = [$rec->id, $rec->tablename, $rec->tabletitle];

        return $allTables;
    }

    function loadRecords($tablename_or_id, string $filter = '', string $orderby = '', int $limit = 0)
    {
        if (is_numeric($tablename_or_id) and (int)$tablename_or_id == 0)
            return null;

        if ($tablename_or_id == '')
            return null;

        $this->ct->getTable($tablename_or_id);

        if ($this->ct->Table->tablename == '') {
            $this->ct->app->enqueueMessage('Table not found.', 'error');
            return false;
        }

        $this->ct->Table->recordcount = 0;

        $this->ct->setFilter($filter, 2);

        $this->ct->Ordering->ordering_processed_string = $orderby;
        $this->ct->Ordering->parseOrderByString();

        $this->ct->Limit = $limit;
        $this->ct->LimitStart = 0;

        $this->ct->getRecords(false, $limit);

        return true;
    }

    function loadRecord($tablename_or_id, string $recordId)
    {
        if (is_numeric($tablename_or_id) and (int)$tablename_or_id == 0)
            return null;

        if ($tablename_or_id == '')
            return null;

        $this->ct->getTable($tablename_or_id);

        if ($this->ct->Table->tablename == '') {
            $this->ct->app->enqueueMessage('Table not found.', 'error');
            return null;
        }

        $this->ct->Table->recordcount = 0;

        $this->ct->setFilter('', 2);
        $this->ct->Filter->where[] = $this->ct->Table->realidfieldname . '=' . $this->ct->db->quote($recordId);


        $this->ct->Limit = 1;
        $this->ct->LimitStart = 0;

        $this->ct->getRecords();


        if (count($this->ct->Records) == 0)
            return null;

        return $this->ct->Records[0];
    }
}
