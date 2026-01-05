<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class Twig_Table_Tags
{
	var CT $ct;

	function __construct(&$ct)
	{
		$this->ct = &$ct;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function recordstotal(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;//Table not selected

		$whereClause = new MySQLWhereClause();
		$count = $this->ct->getNumberOfRecords($whereClause);

		return $count === null ? -1 : $count;
	}

	function records(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return $this->ct->Table->recordcount;
	}

	function fields(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return count($this->ct->Table->fields);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function description()
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			throw new Exception('Table not selected');

		if (isset($this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix])
			and $this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix] !== '') {
			return $this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix];
		} else
			return $this->ct->Table->tablerow['description'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function title(): string
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			throw new Exception('Table not selected');

		return $this->ct->Table->tabletitle;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function name(): ?string
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			throw new Exception('Table not selected');

		return $this->ct->Table->tablename;
	}

	function id(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return $this->ct->Table->tableid;
	}

	function recordsperpage(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return $this->ct->Limit;
	}

	function recordpagestart(): int
	{
		if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
			return -1;

		return $this->ct->LimitStart;
	}
}

