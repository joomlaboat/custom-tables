<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\Catalog;
use CustomTables\CatalogExportCSV;
use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\TableHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\AdminController;

class CustomtablesControllerListOfRecords extends AdminController
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFRECORDS';

	public function getModel($name = 'Records', $prefix = 'CustomtablesModel', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	public function publish()
	{
		$status = (int)($this->task == 'publish');
		$tableid = common::inputGet('tableid', 0, 'int');

		if ($tableid != 0) {
			$tableRow = TableHelper::getTableRowByIDAssoc($tableid);
			if (!is_array($tableRow) and $tableRow == 0) {
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			} else {
				$tablename = $tableRow['tablename'];
			}
		} else {
			Factory::getApplication()->enqueueMessage('Table not set', 'error');
			return;
		}

		$cid = common::inputPost('cid', array(), 'array');
		$paramsArray = $this->getRecordParams($tableid, $tablename, 0);

		$ct = new CT($paramsArray, false);
		$ct->setTable($tableRow);

		foreach ($cid as $id) {
			if ($id != '') {
				if ($ct->setPublishStatusSingleRecord($id, $status) == -1)
					break;
			}
		}

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listofrecords&tableid=' . (int)$tableid;

		$msg = $this->task == 'publish' ? 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED' : 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED';

		if (count($cid) == 1)
			$msg .= '_1';

		Factory::getApplication()->enqueueMessage(common::translate($msg, count($cid)), 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}

	protected function getRecordParams(int $tableid, string $tablename, $recordid): array
	{
		$paramsArray = array();

		$paramsArray['listingid'] = $recordid;
		$paramsArray['estableid'] = $tableid;
		$paramsArray['establename'] = $tablename;

		return $paramsArray;
	}

	public function delete()
	{
		$tableid = common::inputGet('tableid', 0, 'int');

		if ($tableid != 0) {
			$tableRow = TableHelper::getTableRowByIDAssoc($tableid);
			if (!is_array($tableRow) and $tableRow == 0) {
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			} else {
				$tablename = $tableRow['tablename'];
			}
		} else {
			Factory::getApplication()->enqueueMessage('Table not set', 'error');
			return;
		}

		$cid = common::inputPost('cid', array(), 'array');
		$paramsArray = $this->getRecordParams($tableid, $tablename, 0);

		$ct = new CT($paramsArray, false);
		$ct->setTable($tableRow);

		foreach ($cid as $id) {
			if ($id != '') {
				$ok = $ct->deleteSingleRecord($id);
				if (!$ok)
					break;
			}
		}

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listofrecords&tableid=' . (int)$tableid;

		$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_DELETED';

		if (count($cid) == 1)
			$msg .= '_1';

		Factory::getApplication()->enqueueMessage(common::translate($msg, count($cid)), 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}

	public function ordering()
	{
		$ct = new CT;

		$tableid = common::inputGetInt('tableid');
		$ct->getTable($tableid);

		if ($ct->Table->tablename === null) {
			header("HTTP/1.1 500 Internal Server Error");
			die('Table not selected.');
		}

		$ordering = new CustomTables\Ordering($ct->Table, $ct->Params);

		if (!$ordering->saveorder()) {
			header("HTTP/1.1 500 Internal Server Error");
			die('Something went wrong.');
		}
	}

	public function exportcsv()
	{
		$tableid = common::inputGet('tableid', 0, 'int');

		if ($tableid != 0) {
			$table = TableHelper::getTableRowByID($tableid);
			if (!is_object($table) and $table == 0) {
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
				return false;
			} else {
				$tablename = $table->tablename;
			}
		}

		$cid = common::inputPost('cid', array(), 'array');

		$ct = new CT(null, false);
		$ct->Env->frmt = 'csv';

		$ct->getTable($tableid);
		if ($ct->Table->tablename === null) {
			$ct->errors[] = 'Export to CSV: Table not selected.';
			return false;
		}

		$wheres = [];
		foreach ($cid as $id) {
			if ($id != '') {
				$wheres[] = '_id=' . $id;
			}
		}

		$ct->Params->filter = implode('or', $wheres);

		$catalog = new Catalog($ct);

		$pathViews = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
		require_once($pathViews . 'catalog-csv.php');
		$catalogCSV = new CatalogExportCSV($ct, $catalog);

		if (!$catalogCSV->error) {

			if (ob_get_contents())
				ob_end_clean();

			$filename = CTMiscHelper::makeNewFileName($ct->Table->tablename, 'csv');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Type: text/csv; charset=utf-16');
			header("Pragma: no-cache");
			header("Expires: 0");
			echo $catalogCSV->render(null);
			die;//CSV output
		} else {
			$ct->errors[] = $catalogCSV->error;
		}
		return false;
	}
}
