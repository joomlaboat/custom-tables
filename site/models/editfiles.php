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
use CustomTables\CTMiscHelper;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CustomTablesModelEditFiles extends BaseDatabaseModel
{
	var CT $ct;
	var ?array $row;
	var CustomTablesFileMethods $filemethods;
	var string $fileBoxName;
	var string $FileBoxTitle;
	var array $fileBoxFolderArray;
	var int $maxfilesize;
	var string $fileboxtablename;
	var string $allowedExtensions;
	var Field $field;

	function __construct()
	{
		$this->ct = new CT(null, false);
		$this->ct->Params->constructJoomlaParams();

		parent::__construct();

		$this->allowedExtensions = 'doc docx pdf rtf txt xls xlsx psd ppt pptx webp png mp3 jpg jpeg csv accdb pages';

		$this->maxfilesize = CTMiscHelper::file_upload_max_size();
		$this->filemethods = new CustomTablesFileMethods;

		$this->ct->getTable($this->ct->Params->tableName, null);

		if ($this->ct->Table === null) {
			Factory::getApplication()->enqueueMessage('Table not selected (63).', 'error');
			return false;
		}

		if (!common::inputGetCmd('fileboxname'))
			return false;

		$this->fileBoxName = common::inputGetCmd('fileboxname');

		if (!empty($this->ct->Params->listing_id))
			$this->ct->getRecord();

		$this->row = $this->ct->Table->record;

		if (!$this->getFileBox())
			return false;

		$this->fileboxtablename = '#__customtables_filebox_' . $this->ct->Table->tablename . '_' . $this->fileBoxName;

		parent::__construct();
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getFileBox(): bool
	{
		$fieldRow = $this->ct->Table->getFieldByName($this->fileBoxName);
		$this->field = new Field($this->ct, $fieldRow, $this->row);
		$this->fileBoxFolderArray = CustomTablesImageMethods::getImageFolder($this->field->params, $this->field->type);
		$this->FileBoxTitle = $this->field->title;
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getFileList()
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', $this->ct->Params->listing_id);
		return database::loadObjectList($this->fileboxtablename, ['fileid', 'file_ext'], $whereClause, 'fileid');
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function delete(): bool
	{
		$fileIds = common::inputPostString('fileids', '', 'create-edit-record');
		$file_arr = explode('*', $fileIds);

		foreach ($file_arr as $fileid) {
			if ($fileid != '') {
				$file_ext = CustomTablesFileMethods::getFileExtByID($this->ct->Table->tablename, $this->fileBoxName, $fileid);
				CustomTablesFileMethods::DeleteExistingFileBoxFile($this->fileBoxFolderArray['path'], $this->ct->Table->tableid, $this->fileBoxName, $fileid, $file_ext);
				database::deleteRecord($this->fileboxtablename, 'fileid', $fileid);
			}
		}

		$this->ct->Table->saveLog($this->ct->Params->listing_id, 9);
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function add(): bool
	{
		$file = common::inputFiles('uploadedfile'); //not zip -  regular Joomla input method will be used

		$uploadedFile = "tmp/" . basename($file['name']);

		if (!move_uploaded_file($file['tmp_name'], $uploadedFile)) {
			common::enqueueMessage('Cannot move uploaded file.');
			return false;
		}

		if (common::inputGetCmd('base64ecnoded', '') == "true") {
			$src = $uploadedFile;
			$dst = "tmp/decoded_" . basename($file['name']);
			common::base64file_decode($src, $dst);
			$uploadedFile = $dst;
		}

		//Save to DB
		$file_ext = CustomTablesFileMethods::FileExtension($uploadedFile, $this->allowedExtensions);
		if ($file_ext == '') {
			//unknown file extension (type)
			unlink($uploadedFile);
			common::enqueueMessage('Unknown file extensions.');
			return false;
		}

		$filenameParts = explode('/', $uploadedFile);
		$filename = end($filenameParts);
		$title = str_replace('.' . $file_ext, '', $filename);

		try {
			$fileId = $this->addFileRecord($file_ext, $title);
		} catch (Exception $e) {
			common::enqueueMessage('Cannot add new file record: ' . $e->getMessage());
			return false;
		}

		$newFileName = $this->fileBoxFolderArray['path'] . DIRECTORY_SEPARATOR . $this->ct->Table->tableid . '_' . $this->fileBoxName . '_' . $fileId . "." . $file_ext;

		if (!copy($uploadedFile, $newFileName)) {
			unlink($uploadedFile);
			common::enqueueMessage('Cannot copy file');
			return false;
		}

		unlink($uploadedFile);
		$this->ct->Table->saveLog($this->ct->Params->listing_id, 8);
		return true;
	}


	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function addFileRecord(string $file_ext, string $title): int
	{
		$data = [
			'file_ext' => $file_ext,
			'ordering' => 0,
			'listingid' => $this->ct->Params->listing_id,
			'title' => $title
		];

		try {
			database::insert($this->fileboxtablename, $data);
		} catch (Exception $e) {
			common::enqueueMessage('Caught exception: ' . $e->getMessage());
			return -1;
		}

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', $this->ct->Params->listing_id);

		$rows = database::loadObjectList($this->fileboxtablename, ['fileid'], $whereClause, 'fileid', 'DESC', 1);

		if (count($rows) == 1) {
			return $rows[0]->fileid;
		}
		return -1;
	}
}
