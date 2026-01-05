<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class Save_blob
{
	var CT $ct;
	public Field $field;
	var ?array $row_new;

	function __construct(CT &$ct, Field $field, ?array &$row_new)
	{
		$this->ct = &$ct;
		$this->field = $field;
		$this->row_new = &$row_new;//It's important to pass a reference because file name maybe saved to another field
	}

	/**
	 * @throws Exception
	 * @since 3.3.3
	 */
	function saveFieldSet(): ?array
	{
		$newValue = null;

		$processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'blob.php';
		require_once($processor_file);

		$to_delete = common::inputPostCmd($this->field->comesfieldname . '_delete', null, 'create-edit-record');


		//Get File Data
		//Get new file
		$CompletePathToFile = null;
		$fileName = null;

		if (defined('_JEXEC')) {
			$temporaryFile = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');
			if (empty($temporaryFile))
				return null;

			$CompletePathToFile = CUSTOMTABLES_TEMP_PATH . $temporaryFile;
			$fileName = common::inputPostString('com' . $this->field->realfieldname . '_filename', '', 'create-edit-record');
		} elseif (defined('WPINC')) {
			//Get new image
			if (isset($_FILES[$this->field->comesfieldname])) {
				$CompletePathToFile = $_FILES[$this->field->comesfieldname]['tmp_name'];
				$fileName = $_FILES[$this->field->comesfieldname]['name'];
			}
		} else
			return null;

		if (!file_exists($CompletePathToFile))
			return null;

		$mime = mime_content_type($CompletePathToFile);

		$parts = explode('.', $fileName);
		$fileExtension = end($parts);

		if ($mime == 'application/zip' and $fileExtension != 'zip') {
			//could be docx, xlsx, pptx
			FileUploader::checkZIP_File_X($CompletePathToFile, $fileExtension);
		}

		$fileData = addslashes(common::getStringFromFile($CompletePathToFile));

		unlink($CompletePathToFile);

		$fileNameField = '';
		if (isset($this->field->params[2])) {
			$fileNameField_String = $this->field->params[2];
			$fileNameField_Row = $this->ct->Table->getFieldByName($fileNameField_String);
			$fileNameField = $fileNameField_Row['realfieldname'];
		}

		if ($fileData !== '') {
			$newValue = ['value' => $fileData];

			if ($fileNameField != '')
				$this->row_new[$fileNameField] = $fileName;
		} elseif ($to_delete == 'true') {
			$newValue = ['value' => null];

			if ($fileNameField != '' and !isset($this->row_new[$fileNameField]))
				$this->row_new[$fileNameField] = null;
		}

		return $newValue;
	}

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	protected function get_blob_value(): ?string
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'FileUploader.php');

		//Get new file
		$CompletePathToFile = null;
		$fileName = null;

		if (defined('_JEXEC')) {
			$temporaryFile = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');
			$CompletePathToFile = CUSTOMTABLES_TEMP_PATH . $temporaryFile;
			$fileName = common::inputPostString('com' . $this->field->realfieldname . '_filename', '', 'create-edit-record');
		} elseif (defined('WPINC')) {
			//Get new image
			if (isset($_FILES[$this->field->comesfieldname])) {
				$CompletePathToFile = $_FILES[$this->field->comesfieldname]['tmp_name'];
				$fileName = $_FILES[$this->field->comesfieldname]['name'];
			}
		} else
			return null;

		if (!file_exists($CompletePathToFile))
			return null;

		$mime = mime_content_type($CompletePathToFile);

		$parts = explode('.', $fileName);
		$fileExtension = end($parts);

		if ($mime == 'application/zip' and $fileExtension != 'zip') {
			//could be docx, xlsx, pptx
			FileUploader::checkZIP_File_X($CompletePathToFile, $fileExtension);
		}

		$fileData = addslashes(common::getStringFromFile($CompletePathToFile));

		unlink($CompletePathToFile);
		return $fileData;
	}
}
