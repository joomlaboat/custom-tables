<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\Catalog;
use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\Layouts;
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

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function publish()
	{
		$tableid = common::inputGetInt('tableid', 0);

		$ct = new CT([], true);
		$ct->getTable($tableid);

		if ($ct->Table === null) {
			Factory::getApplication()->enqueueMessage('Table "' . $tableid . '" not found', 'error');
			return;
		}

		$layout = new Layouts($ct);
		$result = $layout->renderMixedLayout($ct->Params->editLayout, null, $this->task);

		if ($result['success']) {
			Factory::getApplication()->enqueueMessage($result['message'], 'success');
		} else {
			Factory::getApplication()->enqueueMessage($result['message'], 'error');
		}

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listofrecords&tableid=' . (int)$tableid;

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function delete()
	{
		$tableid = common::inputGet('tableid', 0, 'int');

		$ct = new CT([], true);
		$ct->getTable($tableid);

		if ($ct->Table === null) {
			Factory::getApplication()->enqueueMessage('Table "' . $tableid . '" not found', 'error');
			return;
		}

		$layout = new Layouts($ct);
		$result = $layout->renderMixedLayout($ct->Params->editLayout, null, $this->task);

		if ($result['success']) {
			Factory::getApplication()->enqueueMessage($result['message'], 'success');
		} else {
			Factory::getApplication()->enqueueMessage($result['message'], 'error');
		}

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listofrecords&tableid=' . (int)$tableid;

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function ordering()
	{
		$ct = new CT([], true);

		$tableid = common::inputGetInt('tableid');
		$ct->getTable($tableid);

		if ($ct->Table === null) {
			header("HTTP/1.1 500 Internal Server Error");
			die('Table not selected.');
		}

		$ordering = new CustomTables\Ordering($ct->Table, $ct->Params);

		if (!$ordering->saveorder()) {
			header("HTTP/1.1 500 Internal Server Error");
			die('Something went wrong.');
		}
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function exportcsv(): bool
	{
		$tableid = common::inputGet('tableid', 0, 'int');

		if ($tableid != 0) {
			$table = TableHelper::getTableRowByID($tableid);
			if (!is_object($table) and $table == 0) {
				Factory::getApplication()->enqueueMessage('Table not found', 'error');
				return false;
			}
		}

		$cid = common::inputPostArray('cid', []);
		$ct = new CT(null, false);
		$ct->Params->constructJoomlaParams();
		$ct->Env->frmt = 'csv';

		$ct->getTable($tableid);
		if ($ct->Table === null)
			throw new Exception('Export to CSV: Table not selected.');

		$wheres = [];
		foreach ($cid as $id) {
			if ($id != '')
				$wheres[] = '_id=' . $id;
		}

		$ct->Params->filter = implode('or', $wheres);
		$catalog = new Catalog($ct);

		$pageLayoutContent = $catalog->render();

		if ($ct->Params->allowContentPlugins)
			CTMiscHelper::applyContentPlugins($pageLayoutContent);

		CTMiscHelper::fireFormattedOutput($pageLayoutContent, 'csv', $ct->Table->tablename, 200);

		return true;
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	function importcsv()
	{
		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=importrecords';

		$tableid = common::inputGetInt('tableid');

		$redirect .= '&tableid=' . $tableid;

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}
}
