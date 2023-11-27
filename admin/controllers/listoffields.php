<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Fields;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Controller\AdminController;

class CustomtablesControllerListOfFields extends AdminController
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFFIELDS';

	public function checkin($model = null)
	{
		$tableid = common::inputGet('tableid', 0, 'int');
		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listoffields&tableid=' . (int)$tableid;

		$cid = common::inputPost('cid', array(), 'array');
		$cid = ArrayHelper::toInteger($cid);
		$count = count($cid);

		foreach ($cid as $id) {
			$query = 'UPDATE #__customtables_fields SET checked_out=0, checked_out_time=NULL WHERE id=' . $id;
			database::setQuery($query);
		}

		if ($count == 1)
			$msg = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN';
		elseif ($count == 0)
			$msg = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN_0';
		else
			$msg = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN_MORE';

		Factory::getApplication()->enqueueMessage(common::translate($msg, $count), 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			JRoute::_(
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
			$table = ESTables::getTableRowByID($tableid);
			if (!is_object($table) and $table == 0) {
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			} else {
				$tablename = $table->tablename;
			}
		}

		$cid = common::inputPost('cid', array(), 'array');
		$cid = ArrayHelper::toInteger($cid);

		$ok = true;

		foreach ($cid as $id) {
			if ((int)$id != 0) {
				$id = (int)$id;
				$isok = $this->setPublishStatusSingleRecord($id, $status);
				if (!$isok) {
					$ok = false;
					break;
				}
			}
		}

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listoffields&tableid=' . (int)$tableid;

		if ($this->task == 'trash')
			$msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_TRASHED';
		elseif ($this->task == 'publish')
			$msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_PUBLISHED';
		else
			$msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_UNPUBLISHED';

		if (count($cid) == 1)
			$msg .= '_1';

		Factory::getApplication()->enqueueMessage(common::translate($msg, count($cid)), 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			JRoute::_(
				$redirect, false
			)
		);
	}

	protected function setPublishStatusSingleRecord($id, $status)
	{
		$query = 'UPDATE #__customtables_fields SET published=' . (int)$status . ' WHERE id=' . (int)$id;
		database::setQuery($query);
		return true;
	}

	public function delete()
	{
		$tableId = common::inputGetInt('tableid');

		if ($tableId !== null) {
			$ct = new CT();
			$ct->getTable($tableId);

			if ($ct->Table->tablename === null) {
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			}
		} else {
			Factory::getApplication()->enqueueMessage('Table not set', 'error');
			return;
		}

		$cid = common::inputPost('cid', array(), 'array');
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

		$msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_DELETED';
		if (count($cid) == 1)
			$msg .= '_1';

		Factory::getApplication()->enqueueMessage(common::translate($msg, count($cid)), 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			JRoute::_(
				$redirect, false
			)
		);
	}
}
