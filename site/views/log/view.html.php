<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Details;
use CustomTables\MySQLWhereClause;
use CustomTables\Params;
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
		$this->action = common::inputGetInt('action', 0);
		$this->userid = common::inputGetInt('user', 0);
		$this->tableId = common::inputGetInt('table', 0);

		//Is user super Admin?
		$this->isUserAdministrator = $this->ct->Env->isUserAdministrator;
		$this->records = $this->getRecords($this->action, $this->userid, $this->tableId);
		$this->actionSelector = $this->ActionFilter($this->action);
		$this->userSelector = $this->getUsers($this->userid);
		$this->tableSelector = $this->gettables($this->tableId);

		parent::display($tpl);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getRecords($action, $userid, $tableid)
	{
		$mainframe = Factory::getApplication();
		$this->limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		if ($this->limit == 0)
			$this->limit = 20;

		$this->limitStart = common::inputGetInt('start', 0);
		// In case limit has been changed, adjust it
		$this->limitStart = ($this->limit != 0 ? (floor($this->limitStart / $this->limit) * $this->limit) : 0);

		$selects = array();
		$selects[] = '*';
		$selects[] = 'USER_NAME';
		$selects[] = 'TABLE_TITLE';
		$selects[] = 'FIELD_NAME';

		$whereClause = new MySQLWhereClause();

		if ($action != -1)
			$whereClause->addCondition('action', $action);

		if ($userid != 0)
			$whereClause->addCondition('userid', $userid);

		if ($tableid != 0)
			$whereClause->addCondition('tableid', $tableid);

		$this->record_count = 1000;

		$the_limit = $this->limit;
		if ($the_limit > 500)
			$the_limit = 500;

		if ($the_limit == 0)
			$the_limit = 500;

		if ($this->record_count < $this->limitStart or $this->record_count < $the_limit)
			$this->limitStart = 0;

		return database::loadAssocList('#__customtables_log AS a', $selects, $whereClause, 'datetime', 'DESC', $the_limit, $this->limitStart);
	}

	function ActionFilter($action): string
	{
		$actions = ['New', 'Edit', 'Publish', 'Unpublish', 'Delete', 'Image Uploaded', 'Image Deleted', 'File Uploaded', 'File Deleted', 'Refreshed'];
		$result = '<select class="' . common::convertClassString('form-select') . '" onchange="ActionFilterChanged(this)">';
		$result .= '<option value="0" ' . ($action == -1 ? 'selected="SELECTED"' : '') . '>' . common::translate('COM_CUSTOMTABLES_SELECT_ACTION') . '</option>';

		$v = 1;
		foreach ($actions as $a) {
			$result .= '<option value="' . $v . '" ' . ($action == $v ? 'selected="SELECTED"' : '') . '>' . $a . '</option>';
			$v++;
		}
		$result .= '</select>';
		return $result;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getUsers($userid): string
	{
		$from = '#__customtables_log';
		$from .= ' INNER JOIN #__users ON #__users.id=#__customtables_log.userid';
		$whereClause = new MySQLWhereClause();
		$rows = database::loadAssocList($from, ['#__users.id AS id', '#__users.name AS name'], $whereClause, 'name', null, null, null, '#__users.id');

		$result = '<select class="' . common::convertClassString('form-select') . '" onchange="UserFilterChanged(this)">';
		$result .= '<option value="0" ' . ($userid === null ? 'selected="SELECTED"' : '') . '>' . common::translate('COM_CUSTOMTABLES_SELECT_USER') . '</option>';

		foreach ($rows as $row)
			$result .= '<option value="' . $row['id'] . '" ' . ($userid == $row['id'] ? 'selected="SELECTED"' : '') . '>' . $row['name'] . '</option>';

		$result .= '</select>';
		return $result;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getTables($tableId): string
	{
		$whereClause = new MySQLWhereClause();
		$rows = database::loadAssocList('#__customtables_tables', ['id', 'tablename'], $whereClause, 'tablename');

		$result = '<select class="' . common::convertClassString('form-select') . '" onchange="TableFilterChanged(this)">';
		$result .= '<option value="0" ' . ($tableId == 0 ? 'selected="SELECTED"' : '') . '>' . common::translate('COM_CUSTOMTABLES_SELECT_TABLE') . '</option>';

		foreach ($rows as $row) {
			$result .= '<option value="' . $row['id'] . '" ' . ($tableId == $row['id'] ? 'selected="SELECTED"' : '') . '>' . $row['tablename'] . '</option>';
		}

		$result .= '</select>';
		return $result;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
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
			$result .= '<img src="' . $action_image_path . $action_images[$a] . '" alt=' . $alt . ' title=' . $alt . ' style="width:16px;height:16px;" />';
		} else
			$result .= '<img src="' . $action_image_path . $action_images[$a] . '" alt=' . $alt . ' title=' . $alt . ' style="width:16px;height:16px;" />';

		$result .= '</td>'
			. '<td>' . $rec['USER_NAME'] . '</td>';

		$result .= '<td>' . $rec['datetime'] . '</td>'
			. '<td style="vertical-align:top;">' . $rec['tabletitle'] . '</td>';

		$recordValue = $this->getRecordValue($rec['tableid'], $rec['listingid'], $rec['Itemid'], $rec['FIELD_NAME']);
		if ($recordValue != '')
			$recordValue .= '<br/>';

		$result .= '<td style="vertical-align:top;">' . $recordValue . '(id: ' . $rec['listingid'] . ')</td>'
			. '<td>' . $alt . '</td>'
			. '</tr>';

		return $result;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function getRecordValue($tableId, $listing_id, $Itemid, $FieldName): string
	{
		if (!isset($FieldName) or $FieldName == '')
			return "Table/Field not found.";

		$app = Factory::getApplication();
		common::inputSet("listing_id", $listing_id);
		common::inputSet('Itemid', $Itemid);

		$menu = $app->getMenu();
		$menuParams = $menu->getParams($Itemid);
		$menuParamsArray = Params::menuParamsRegistry2Array($menuParams);
		$ct = new CT($menuParamsArray, true);
		$ct->getTable($tableId);

		if ($ct->Table === null) {
			Factory::getApplication()->enqueueMessage("Table '" . $tableId . "' not found.", 'error');
			return "Table '" . $tableId . "' not found.";
		}

		$layoutContent = '{{ ' . $FieldName . ' }}';
		$twig = new TwigProcessor($ct, $layoutContent);

		if ($ct->getRecord($listing_id)) {
			$row = $ct->Table->record;
		} else
			$row = null;

		if ($twig->errorMessage !== null) {
			$ct->errors[] = $twig->errorMessage;
			return '';
		}

		return $twig->process($row);
	}
}