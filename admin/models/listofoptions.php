<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\DataTypes\Tree;
use CustomTables\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;

class CustomTablesModelListOfOptions extends ListModel
{
	var CT $ct;
	var $_table = null;
	var $_pagination = null;

	function &getPagination()
	{
		if ($this->_pagination === null) {
			$this->getItems();
		}
		return $this->_pagination;
	}

	function &getItems()
	{
		$this->ct = new CT;

		$mainframe = Factory::getApplication();

		static $items;

		if (isset($items)) {
			return $items;
		}
		$context = 'com_customtables.list.';

		$filter_order = $mainframe->getUserStateFromRequest($context . 'filter_order', 'filter_order', 'm.ordering', 'cmd');
		$filter_order_Dir = $mainframe->getUserStateFromRequest($context . 'filter_order_Dir', 'filter_order_Dir', 'ASC', 'word');

		$filter_rootparent = $mainframe->getUserStateFromRequest($context . 'filter_rootparent', 'filter_rootparent', '', 'int');

		$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		$limitstart = $mainframe->getUserStateFromRequest($context . 'limitstart', 'limitstart', 0, 'int');
		$levellimit = $mainframe->getUserStateFromRequest($context . 'levellimit', 'levellimit', 10, 'int');
		$search = $mainframe->getUserStateFromRequest($context . 'search', 'search', '', 'string');

		$search = StringHelper::strtolower($search);

		if ($filter_order != 'm.ordering' and $filter_order != 'optionname')
			$filter_order = 'm.ordering';

		$where = array();

		// just in case filter_order gets messed up
		if ($filter_order) {
			$orderby = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir . ', m.parentid, m.ordering';
		} else {
			$orderby = ' ORDER BY m.parentid, m.ordering';
		}

		// select the records
		// note, since this is a tree we have to do the limits code-side
		$search_rows = array();
		if ($search != '') {
			$query = 'SELECT m.id AS id' .
				' FROM #__customtables_options AS m' .
				' WHERE ' .

				' LOWER( m.title ) LIKE ' . database::quote('%' . $search . '%', false);

			$search_rows = database::loadObjectList($query);
		}

		if ($filter_rootparent)
			$where[] = ' ( id=' . $filter_rootparent . ' OR parentid!=0 )';

		$WhereStr = '';
		if (count($where) > 0) {
			$WhereStr = ' WHERE ' . implode(' AND ', $where);//$WhereStr;
		}

		$titlelist = 'title' . $this->ct->Languages->Postfix;

		$query = 'SELECT m.*, parentid AS parent_id, title AS title ' .
			' FROM #__customtables_options AS m' .
			$WhereStr .
			$orderby;

		$rows = database::loadObjectList($query);

		$children = array();
		// first pass - collect children
		foreach ($rows as $v) {
			$pt = $v->parentid;
			$list = @$children[$pt] ? $children[$pt] : array();
			array_push($list, $v);
			$children[$pt] = $list;
		}

		// second pass - get an indent list of the items
		$list = HTMLHelper::_('menu.treerecurse', 0, '', array(), $children, max(0, $levellimit - 1));
		$list = $this->treerecurse(0, '', array(), $children, max(0, $levellimit - 1));

		// eventually only pick out the searched items.
		if ($search) {
			$list1 = array();

			foreach ($search_rows as $sid) {
				foreach ($list as $item) {
					if ($item->id == $sid->id) {
						$list1[] = $item;
					}
				}
			}
			// replace full list with found items

			$list = $list1;
		}

		$total = count($list);

		jimport('joomla.html.pagination');
		$this->_pagination = new JPagination($total, $limitstart, $limit);

		// slice out elements based on limits
		$list = array_slice($list, $this->_pagination->limitstart, $this->_pagination->limit);

		$items = $list;

		return $items;
	}

	function treerecurse($id, $indent, $list, &$children, $maxlevel = 9999, $level = 0, $type = 1)
	{
		if (@$children[$id] && $level <= $maxlevel) {
			foreach ($children[$id] as $v) {
				$id = $v->id;

				if ($type) {
					$pre = '<sup>|_</sup>&nbsp;';
					$spacer = '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				} else {
					$pre = '- ';
					$spacer = '&nbsp;&nbsp;';
				}

				if ($v->parentid == 0) {
					$txt = $v->optionname;
				} else {
					$txt = $pre . $v->optionname;
				}
				$pt = $v->parentid;
				$list[$id] = $v;
				$list[$id]->treename = "$indent$txt";

				if (isset($children[$id])) {
					$list[$id]->children = count(@$children[$id]);
					$list = $this->treerecurse($id, $indent . $spacer, $list, $children, $maxlevel, $level + 1, $type);
				}
			}
		}
		return $list;
	}

	function orderItem($item, $movement)
	{
		$row = Table::getInstance('List', 'Table');
		$row->load($item);

		if (!$row->move($movement, ' parentid = ' . (int)$row->parentid)) {
			$this->setError($row->getError());
			return false;
		}
		return true;
	}

	function setOrder($items)
	{
		$total = count($items);
		$row = Table::getInstance('List', 'Table');
		$groupings = array();
		$order = common::inputPost('order', array(), 'ARRAY');

		ArrayHelper::toInteger($order);
		// update ordering values
		for ($i = 0; $i < $total; $i++) {
			$row->load($items[$i]);

			//return true;
			// track parents
			$groupings[] = $row->parentid;

			if ($row->ordering != $order[$i]) {
				$row->ordering = $order[$i];
				if (!$row->store()) {
					$this->setError($row->getError());
					return false;
				}
			} // if
		} // for

		// execute updateOrder for each parentid group
		$groupings = array_unique($groupings);
		foreach ($groupings as $group) {
			$row->reorder(' parentid = ' . (int)$group . ' ');
		}

		// clean cache
		//MenusHelper::cleanCache();

		return true;
	}

	/**
	 * Delete one or more menu items
	 * @param mixed int or array of id values
	 */
	function delete($ids)
	{
		ArrayHelper::toInteger($ids);

		if (!empty($ids)) {

			// Add all children to the list
			foreach ($ids as $id)
				$this->_addChildren($id, $ids);

			// Delete the menu items
			$query = 'DELETE FROM #__customtables_options WHERE id = ' . implode(' OR id = ', $ids);
			database::setQuery($query);
		}
		return true;
	}

	function _addChildren($id, &$list)
	{
		// Initialize variables
		$return = true;

		// Get all rows with parentid of $id
		$query = 'SELECT id' .
			' FROM #__customtables_options' .
			' WHERE parentid = ' . (int)$id;
		$rows = database::loadObjectList($query);

		// Make sure there aren't any errors
		/*
		if ($db->getErrorNum()) {
			$this->setError($db->getErrorMsg());
			return false;
		}
		*/

		// Recursively iterate through all children... kinda messy
		// TODO: Cleanup this method
		foreach ($rows as $row) {
			$found = false;
			foreach ($list as $idx) {
				if ($idx == $row->id) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$list[] = $row->id;
			}
			$return = $this->_addChildren($row->id, $list);
		}
		return $return;
	}

	/*
	 * Rebuild the sublevel field for items in the menu (if called with 2nd param = 0 or no params, it will rebuild entire menu tree's sublevel
	 * @param array of menu item ids to change level to
	 * @param int level to set the menu items to (based on parentid
	 */
	function _rebuildSubLevel($cid = array(0), $level = 0)
	{
		ArrayHelper::toInteger($cid, array(0));
		$ids = implode(',', $cid);

		if ($level == 0) {
			$query = 'UPDATE #__customtables_options SET sublevel = 0 WHERE parentid = 0';
			database::setQuery($query);

			$query = 'SELECT id FROM #__customtables_options WHERE parentid = 0';
			$cids = database::loadColumn($query);
		} else {
			$query = 'UPDATE #__customtables_options SET sublevel = ' . (int)$level
				. ' WHERE parentid IN (' . $ids . ')';
			database::setQuery($query);

			$query = 'SELECT id FROM #__customtables_options WHERE parentid IN (' . $ids . ')';
			$cids = database::loadColumn($query);
		}
		if (!empty($cids)) {
			$this->_rebuildSubLevel($cids, $level + 1);
		}
	}

	function GetNewParentID($parentid, &$AssociatedTable)
	{
		foreach ($AssociatedTable as $Ass) {
			if ($Ass[0] == $parentid)
				return $Ass[1];
		}
		return -1;
	}

	function copyItem($cid)
	{
		$item = $this->getTable();

		foreach ($cid as $id) {
			$item->load($id);
			$item->id = NULL;
			$item->optionname = 'Copy of ' . $item->optionname;

			if (!$item->check()) {
				Factory::getApplication()->enqueueMessage($item->getError(), 'error');
			}

			if (!$item->store()) {
				Factory::getApplication()->enqueueMessage($item->getError(), 'error');
			}
			$item->checkin();
		}
		return true;
	}

	function RefreshFamily()
	{
		$query = "SELECT id, optionname FROM #__customtables_options";// WHERE parentid!=0";
		$rows = database::loadObjectList($query);
		foreach ($rows as $row) {
			$familytreestr = Tree::getFamilyTreeString($row->id, 0);
			if ($familytreestr != '')
				$familytreestr = ',' . $familytreestr . '.' . $row->optionname . '.';
			else
				$familytreestr = ',' . $row->optionname . '.';

			database::setQuery('UPDATE #__customtables_options SET familytreestr="' . $familytreestr . '" WHERE id=' . $row->id);
		}
		return true;
	}
}
