<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\DataTypes\Tree;

use \Joomla\CMS\Factory;

jimport('joomla.application.component.model');

class CustomTablesModelStructure extends JModel
{
	var CT $ct;

	var $record_count = 0;

	var $optionname;
	var $parentid;

	var $linkable;
	var $image_prefix;

	var $row_break;

	var $esTable;
	var $tableName;
	var $estableid;
	var $fieldname;
	var $fieldType;

	var $ListingJoin;

	function __construct()
	{
		$this->ct = new CT;

		$this->esTable = new ESTables;

		parent::__construct();

		$app = Factory::getApplication();
		$params = $app->getParams();

		if (common::inputGet('establename', '', 'CMD'))
			$this->tableName = common::inputGet('establename', '', 'CMD');
		else
			$this->tableName = $params->get('establename');

		if (common::inputGet('esfieldname', '', 'CMD')) {
			$esfn = common::inputGet('esfieldname', '', 'CMD');
			$this->fieldname = strtolower(trim(preg_replace("/[^a-zA-Z]/", "", $esfn)));
		} else {
			$esfn = $params->get('esfieldname');
			$this->fieldname = strtolower(trim(preg_replace("/[^a-zA-Z]/", "", $esfn)));
		}

		$tablerow = $this->esTable->getTableRowByName($this->tableName);
		$this->estableid = $tablerow->id;

		// Get pagination request variables
		$mainframe = Factory::getApplication('site');
		$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		$limitstart = common::inputGet('limitstart', 0, 'INT');

		// In case limit has been changed, adjust it
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);

		//get field
		$row = $this->esTable->getFieldRowByName($this->esfieldname, $this->estableid);
		$this->fieldType = $row->type;

		if ($params->get('optionname') != '')
			$this->optionname = $params->get('optionname');
		else {
			//get OptionName by FieldName
			$typeParams = explode(',', $row->typeparams);
			$this->optionname = $typeParams[0];
		}

		if (common::inputGetString('image_prefix'))
			$this->image_prefix = common::inputGetString('image_prefix');
		else
			$this->image_prefix = $params->get('image_prefix');

		if (common::inputGetInt('row_break', 0))
			$this->row_break = common::inputGetInt('row_break', 0);
		else
			$this->row_break = $params->get('row_break');

		if (common::inputGetInt('linkable', 0))
			$this->linkable = common::inputGetInt('linkable', 0);
		else
			$this->linkable = (int)$params->get('linkable');

		if (common::inputGetInt('listingjoin', 0))
			$this->ListingJoin = common::inputGetInt('listingjoin', 0);
		else
			$this->ListingJoin = (int)$params->get('listingjoin');
	}

	function getPagination()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_pagination)) {
			jimport('joomla.html.pagination');
			$a = new JPagination($this->record_count, $this->getState('limitstart'), $this->getState('limit'));
			return $a;
		}
		return $this->_pagination;
	}

	function getStructure()
	{
		if (!$this->fieldType == 'customtables')
			return array();

		$wherearr = array();

		if (common::inputGetString('alpha')) {
			$parentid = Tree::getOptionIdFull($this->optionname);
			$wherearr[] = 'INSTR(familytree,"-' . $parentid . '-") AND SUBSTRING(title' . $this->ct->Languages->Postfix . ',1,1)="'
				. common::inputGetString('alpha') . '"';
		} else {
			$this->parentid = Tree::getOptionIdFull($this->optionname);
			$wherearr[] = 'parentid=' . (int)$this->parentid;
		}

		$where = '';
		if (count($wherearr) > 0)
			$where = ' WHERE ' . implode(" AND ", $wherearr);

		if ($this->ListingJoin) {
			$query = 'SELECT optionname, '
				. 'CONCAT("",familytreestr,".",optionname) as theoptionname, '
				. 'CONCAT( title' . $this->ct->Languages->Postfix . '," (",COUNT(#__customtables_table_' . $this->tableName . '.id),")") AS optiontitle, '
				. 'image, '
				. 'imageparams '

				. 'FROM #__customtables_options '
				. ' INNER JOIN #__customtables_table_' . $this->tableName
				. ' ON INSTR(es_' . $this->esfieldname . ', CONCAT(familytreestr,".",optionname))'
				. ' ' . $where
				. ' GROUP BY #__customtables_options.id'
				. ' ORDER BY title' . $this->ct->Languages->Postfix;
		} else {
			$query = 'SELECT optionname, '
				. 'CONCAT("",familytreestr,".",optionname) as theoptionname, '
				. 'title' . $this->ct->Languages->Postfix . ' AS optiontitle, '
				. 'image, '
				. 'imageparams '

				. 'FROM #__customtables_options '
				. ' ' . $where
				. ' ORDER BY title' . $this->ct->Languages->Postfix;
		}

		$this->record_count = database::getNumRowsOnly($query);
		$rows = database::loadAssocList($query, $this->getState('limitstart'), $this->getState('limit'));
		$newRows = [];
		foreach ($rows as $row)
			$newRows[] = $row;

		return $newRows;
	}
}
