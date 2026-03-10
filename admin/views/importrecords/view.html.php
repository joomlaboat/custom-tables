<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

// Import Joomla! libraries
jimport('joomla.application.component.view');

use CustomTables\common;
use CustomTables\CT;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use CustomTables\FileUploader;
use CustomTables\ImportCSV;

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;

class CustomTablesViewImportRecords extends HtmlView
{
	var string $fieldInputPrefix;
	var array $previewData;

	var CT $ct;

	function display($tpl = null)
	{
		//ToolbarHelper::title(common::translate('Custom Tables - Import Records'), 'joomla');
		$tableId = common::inputGetInt('tableid', 0);
		$this->ct = new CT;
		$this->ct->getTable($tableId);
		$this->fieldInputPrefix = $this->ct->Table->fieldInputPrefix;

		ToolbarHelper::back('Back to Records', 'index.php?option=com_customtables&view=listofrecords&tableid=' . $tableId);
		// First button (Create from Schema)

		// Get the toolbar object instance

//		$toolbar->standardButton('plus')
//			->text('Create from Schema')
//			->task('listoftables.createFromSchema');

		$task = common::inputGetCmd('task', '');
		if ($task == 'preview_import') {
			$this->previewData = $this->buildPreview();
			$toolbar = Toolbar::getInstance('toolbar');
			$toolbar->appendButton('Standard', 'upload', 'Import Records', 'listofrecords.importcsv', false, null);
			$toolbar->appendButton('Standard', 'refresh', 'Refresh', 'listofrecords.importcsv', false, null);

			$application = Factory::getApplication();
			$document = $application->getDocument();
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/csvimport.js"></script>');

			ToolbarHelper::title('Custom Tables - Table "' . $this->ct->Table->tabletitle . '" - Import Records - Preview', 'joomla');
			parent::display('preview');
		} else {
			ToolbarHelper::title('Custom Tables - Table "' . $this->ct->Table->tabletitle . '" - Import Records', 'joomla');
			parent::display($tpl);
		}
	}

	function buildPreview(): array
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
			. 'helpers' . DIRECTORY_SEPARATOR . 'FileUploader.php');
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
			. 'helpers' . DIRECTORY_SEPARATOR . 'ImportCSV.php');

		try {
			$tableId = common::inputGetInt('tableid', 0);
			$fileId = common::inputGetCmd('fileid', '');
			$filename = FileUploader::getFileNameByID($fileId);
		} catch (Exception $e) {
			echo $e->getMessage();
			die;
		}

		//try {
		return ImportCSV::importCSVFile($filename, $tableId, true);


		//} catch (Throwable $e) {
		//	unlink($filename);
		//	$link = 'index.php?option=com_customtables&view=importrecords&tableid=' . $tableId;
		//	$this->setRedirect($link, common::translate('Records was Unable to Import: ' . $e->getMessage()), 'error');
		//}
	}
}
