<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\Fields;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Controller\AdminController;

class CustomtablesControllerListOfFields extends AdminController
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFFIELDS';

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	public function checkin($model = null)
	{
		$tableid = common::inputGet('tableid', 0, 'int');
		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listoffields&tableid=' . (int)$tableid;

		$cid = common::inputPostArray('cid', []);
		$cid = ArrayHelper::toInteger($cid);
		$count = count($cid);

		foreach ($cid as $id) {
			$data = [
				'checked_out' => 0,
				'checked_out_time' => null
			];
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('id', $id);
			database::update('#__customtables_fields', $data, $whereClauseUpdate);
		}

		if ($count == 1)
			$message = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN';
		elseif ($count == 0)
			$message = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN_0';
		else
			$message = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN_MORE';

		Factory::getApplication()->enqueueMessage(common::translate($message, $count), 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}

	public function getModel($name = 'Fields', $prefix = 'CustomtablesModel', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	public function publish()
	{
		if ($this->task == 'publish')
			$status = 1;
		elseif ($this->task == 'unpublish')
			$status = 0;
		elseif ($this->task == 'trash')
			$status = -2;
		else
			return;

		$tableid = common::inputGet('tableid', 0, 'int');

		if ($tableid != 0) {
			$table = TableHelper::getTableRowByID($tableid);
			if (!is_object($table) and $table == 0) {
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			} else {
				$tablename = $table->tablename;
			}
		}

		$cid = common::inputPostArray('cid', []);
		$cid = ArrayHelper::toInteger($cid);

		foreach ($cid as $id) {
			if ((int)$id != 0) {
				$id = (int)$id;
				$isok = $this->setPublishStatusSingleRecord($id, $status);
				if (!$isok) {
					break;
				}
			}
		}

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listoffields&tableid=' . (int)$tableid;

		if ($this->task == 'trash')
			$message = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_TRASHED';
		elseif ($this->task == 'publish')
			$message = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_PUBLISHED';
		else
			$message = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_UNPUBLISHED';

		if (count($cid) == 1)
			$message .= '_1';

		Factory::getApplication()->enqueueMessage(common::translate($message, count($cid)), 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	protected function setPublishStatusSingleRecord(int $id, int $status): bool
	{
		$data = [
			'published' => $status
		];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addCondition('id', $id);
		database::update('#__customtables_fields', $data, $whereClauseUpdate);
		return true;
	}

	public function delete()
	{
		$tableId = common::inputGetInt('tableid');
		$ct = new CT([], true);

		if ($tableId !== null) {
			$ct->getTable($tableId);

			if ($ct->Table === null) {
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			}
		} else {
			Factory::getApplication()->enqueueMessage('Table not set', 'error');
			return;
		}

		$cid = common::inputPostArray('cid', []);
		$cid = ArrayHelper::toInteger($cid);

		foreach ($cid as $id) {
			if ((int)$id != 0) {
				$id = (int)$id;
				$ok = Fields::deleteField_byID($ct, $id);

				if (!$ok)
					break;
			}
		}

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listoffields&tableid=' . (int)$tableId;

		$message = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_DELETED';
		if (count($cid) == 1)
			$message .= '_1';

		Factory::getApplication()->enqueueMessage(common::translate($message, count($cid)), 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}
}
