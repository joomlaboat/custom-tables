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

use Joomla\CMS\MVC\Controller\FormController;

class CustomTablesControllerImportRecords extends FormController
{
	function __construct()
	{
		parent::__construct();
	}

	function display($cachable = false, $urlparams = array())
	{
		$task = common::inputGetCmd('task', '');

		if ($task == 'importrecords')
			$this->importrecords();
		else {
			common::inputSet('view', 'importrecords');
			parent::display();
		}
	}

	function importrecords()
	{
		$model = $this->getModel('importrecords');
		$tableId = common::inputGetInt('tableid', 0);

		$msg = '';
		if ($model->importRecords($msg)) {
			$link = 'index.php?option=com_customtables&view=listofrecords&tableid=' . $tableId;
			$this->setRedirect($link, common::translate('Records Imported Successfully'));
		} else {
			$link = 'index.php?option=com_customtables&view=importrecords&tableid=' . $tableId;
			$this->setRedirect($link, common::translate('Records was Unable to Import: ' . $msg), 'error');
		}
	}
}
