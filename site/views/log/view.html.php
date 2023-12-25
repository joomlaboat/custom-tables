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
use CustomTables\CTUser;
use CustomTables\database;
use CustomTables\Details;
use CustomTables\TwigProcessor;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class CustomTablesViewLog extends HtmlView
{
	var CT $ct;
	var Details $details;
	var int $limit;
	var int $limitStart;
	var int $record_count;
	var int $userid;
	var string $action;
	var int $tableId;
	var bool $isUserAdministrator;
	var ?array $records;
	var string $actionSelector;
	var string $userSelector;
	var string $tableSelector;

	function display($tpl = null)
	{
		$this->ct = new CT;
		$user = new CTUser();

		$this->action = common::inputPostString('action', '');
		if ($this->action == '')
			$this->action = -1;

		$this->userid = common::inputGetInt('user', $user->id);
		$this->tableId = common::inputGetInt('table', 0);

		//Is user super Admin?
		$this->isUserAdministrator = $this->ct->Env->isUserAdministrator;
		$this->records = $this->getRecords($this->action, $this->userid, $this->tableId);
		$this->actionSelector = $this->ActionFilter($this->action);
		$this->userSelector = $this->getUsers($this->userid);
		$this->tableSelector = $this->gettables($this->tableId);

		parent::display($tpl);
	}

	function getRecords($action, $userid, $tableid)
	{
		$mainframe = Factory::getApplication('site');
		$this->limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		if ($this->limit == 0)
			$this->limit = 20;

		$this->limitStart = common::inputGetInt('start', 0);
		// In case limit has been changed, adjust it
		$this->limitStart = ($this->limit != 0 ? (floor($this->limitStart / $this->limit) * $this->limit) : 0);

		$selects = array();
		$selects[] = '*';
		$selects[] = '(SELECT name FROM #__users WHERE id=userid) AS UserName';
		$selects[] = '(SELECT tabletitle FROM #__customtables_tables WHERE id=tableid) AS TableName';
		$selects[] = '(SELECT fieldname FROM #__customtables_fields WHERE #__customtables_fields.published=1 AND #__customtables_fields.tableid=#__customtables_log.tableid '
			. 'ORDER BY ordering LIMIT 1) AS FieldName';

		$where = array();
		if ($action != -1)
			$where[] = 'action=' . $action;

		if ($userid != 0)
			$where[] = 'userid=' . $userid;

		if ($tableid != 0)
			$where[] = 'tableid=' . $tableid;

		$query = 'SELECT ' . implode(',', $selects) . ' FROM #__customtables_log ' . (count($where) > 0 ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY datetime DESC';
		$this->record_count = 1000;

		$the_limit = $this->limit;
		if ($the_limit > 500)
			$the_limit = 500;

		if ($the_limit == 0)
			$the_limit = 500;

		if ($this->record_count < $this->limitStart or $this->record_count < $the_limit)
			$this->limitStart = 0;

		return database::loadAssocList($query, $this->limitStart, $the_limit);
	}

	function ActionFilter($action): string
	{
		$actions = ['New', 'Edit', 'Publish', 'Unpublish', 'Delete', 'Image Uploaded', 'Image Deleted', 'File Uploaded', 'File Deleted', 'Refreshed'];
		$result = '<select onchange="ActionFilterChanged(this)">';
		$result .= '<option value="-1" ' . ($action == -1 ? 'selected="SELECTED"' : '') . '>- ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

		$v = 1;
		foreach ($actions as $a) {
			$result .= '<option value="' . $v . '" ' . ($action == $v ? 'selected="SELECTED"' : '') . '>' . $a . '</option>';
			$v++;
		}
		$result .= '</select>';
		return $result;
	}

	function getUsers($userid): string
	{
		$query = 'SELECT #__users.id AS id, #__users.name AS name FROM #__customtables_log INNER JOIN #__users ON #__users.id=#__customtables_log.userid GROUP BY #__users.id ORDER BY name';
		$rows = database::loadAssocList($query);
		$result = '<select onchange="UserFilterChanged(this)">';
		$result .= '<option value="0" ' . ($userid === null ? 'selected="SELECTED"' : '') . '>- ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

		foreach ($rows as $row)
			$result .= '<option value="' . $row['id'] . '" ' . ($userid == $row['id'] ? 'selected="SELECTED"' : '') . '>' . $row['name'] . '</option>';

		$result .= '</select>';
		return $result;
	}

	function getTables($tableId): string
	{
		$rows = database::loadAssocList('SELECT id,tablename FROM #__customtables_tables ORDER BY tablename');

		$result = '<select onchange="TableFilterChanged(this)">';
		$result .= '<option value="0" ' . ($tableId == 0 ? 'selected="SELECTED"' : '') . '>- ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

		foreach ($rows as $row) {
			$result .= '<option value="' . $row['id'] . '" ' . ($tableId == $row['id'] ? 'selected="SELECTED"' : '') . '>' . $row['tablename'] . '</option>';
		}

		$result .= '</select>';
		return $result;
	}

	function renderLogLine($rec): string
	{
		$actions = ['New', 'Edit', 'Publish', 'Unpublish', 'Delete', 'Image Uploaded', 'Image Deleted', 'File Uploaded', 'File Deleted', 'Refreshed'];
		$action_images = ['new.png', 'edit.png', 'publish.png', 'unpublish.png', 'delete.png', 'photomanager.png', 'photomanager.png', 'filemanager.png', 'filemanager.png', 'refresh.png'];
		$action_image_path = '/components/com_customtables/libraries/customtables/media/images/icons/';

		$a = (int)$rec['action'] - 1;
		$alt = $actions[$a];

		$result = '<tr>'
			. '<td>';

		if ($a == 1 or $a == 2) {
			$link = '/index.php?option=com_customtables&view=edititem&listing_id=' . $rec['listingid'] . '&Itemid=' . $rec['Itemid'];
			$result .= '<a href="' . $link . '" target="_blank"><img src="' . $action_image_path . $action_images[$a] . '" alt=' . $alt . ' title=' . $alt . ' style="width:16px;height:16px;" /></a>';
		} else
			$result .= '<img src="' . $action_image_path . $action_images[$a] . '" alt=' . $alt . ' title=' . $alt . ' style="width:16px;height:16px;" />';

		$result .= '</td>'
			. '<td>' . $rec['UserName'] . '</td>';

		$link = '/index.php?option=com_customtables&view=details&listing_id=' . $rec['listingid'] . '&Itemid=' . $rec['Itemid'];

		$result .= '<td><a href="' . $link . '" target="_blank">' . $rec['datetime'] . '</a></td>'
			. '<td style="vertical-align:top;">' . $rec['TableName'] . '</td>';


		$recordValue = $this->getRecordValue($rec['listingid'], $rec['Itemid'], $rec['FieldName']);
		if ($recordValue != '')
			$recordValue .= '<br/>';

		$result .= '<td style="vertical-align:top;">' . $recordValue . '(id: ' . $rec['listingid'] . ')</td>'
			. '<td>' . $alt . '</td>'
			. '</tr>';

		return $result;
	}

	function getRecordValue($listing_id, $Itemid, $FieldName): string
	{
		if (!isset($FieldName) or $FieldName == '')
			return "Table/Field not found.";

		$app = Factory::getApplication();
		common::inputSet("listing_id", $listing_id);
		common::inputSet('Itemid', $Itemid);

		$menu = $app->getMenu();
		$menuParams = $menu->getParams($Itemid);

		$ct = new CT($menuParams, false);

		$this->details = new Details($ct);
		$this->details->load();

		if ($ct->Table === null or $ct->Table->tablename === null)
			return "Table not found.";

		$layoutContent = '{{ ' . $FieldName . ' }}';
		$twig = new TwigProcessor($ct, $layoutContent);

		$row = $ct->Table->loadRecord($listing_id);
		if ($twig->errorMessage !== null) {
			$ct->errors[] = $twig->errorMessage;
			return '';
		}

		return $twig->process($row);
	}
}