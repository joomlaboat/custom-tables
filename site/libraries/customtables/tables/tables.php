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
		$query = 'SELECT id,tablename,tabletitle FROM #__customtables_tables WHERE published=1 ORDER BY tablename';
		$records = database::loadObjectList($query);

		$allTables = [];
		foreach ($records as $rec)
			$allTables[] = [$rec->id, $rec->tablename, $rec->tabletitle];

		return $allTables;
	}

	function loadRecords($tablename_or_id, string $filter = '', ?string $orderby = null, int $limit = 0)
	{
		if (is_numeric($tablename_or_id) and (int)$tablename_or_id == 0)
			return null;

		if ($tablename_or_id == '')
			return null;

		$this->ct->getTable($tablename_or_id);

		if ($this->ct->Table->tablename === null) {
			$this->ct->errors[] = 'Table not found.';
			return false;
		}

		$this->ct->Table->recordcount = 0;

		$this->ct->setFilter($filter, 2);

		$this->ct->Ordering->ordering_processed_string = $orderby ?? '';
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

		if ($this->ct->Table->tablename === null) {
			$this->ct->errors[] = 'Table not found.';
			return null;
		}
		$this->ct->Table->recordcount = 0;

		$this->ct->setFilter('', 2);
		$this->ct->Filter->where[] = $this->ct->Table->realidfieldname . '=' . database::quote($recordId);
		$this->ct->Limit = 1;
		$this->ct->LimitStart = 0;
		$this->ct->getRecords();

		if (count($this->ct->Records) == 0)
			return null;

		return $this->ct->Records[0];
	}
}
