<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use finfo;

class Value_blob extends BaseValue
{
	function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
	{
		parent::__construct($ct, $field, $rowValue, $option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.3.1
	 */
	function render(): ?string
	{
		if (defined('WPINC'))
			return 'CustomTables for WordPress: "blob" field type is not available yet.';

		return $this->blobProcess($this->rowValue, $this->option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function blobProcess(?string $value, array $option_list): ?string
	{
		if ((int)$value == 0)
			return null;

		if ($this->field->type != 'blob' and $this->field->type != 'tinyblob' and $this->field->type != 'mediumblob' and $this->field->type != 'longblob')
			return self::TextFunctions($value, $option_list);

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php');

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'blob.php');

		$filename = self::getBlobFileName($this->field, $value, $this->ct->Table);

		$listing_id = $this->ct->Table->record[$this->ct->Table->realidfieldname] ?? null;

		return Value_file::process($filename, $this->field, $option_list, $listing_id, false, intval($value));
	}

	public static function getBlobFileName(Field $field, int $valueSize, Table $table)
	{
		$row = $table->record;
		$filename = '';

		//params[2] is the Field to get the file name from / to
		if (isset($field->params[2]) and $field->params[2] != '') {
			$fileNameField_String = $field->params[2];
			$fileNameField_Row = $table->getFieldByName($fileNameField_String);
			$fileNameField = $fileNameField_Row['realfieldname'];
			$filename = $row[$fileNameField];
		}

		if ($filename == '') {

			$file_extension = 'bin';
			$content = stripslashes($row[$field->realfieldname . '_sample']);
			$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);
			$mime_file_extension = CTMiscHelper::mime2ext($mime);
			if ($mime_file_extension !== null)
				$file_extension = $mime_file_extension;

			if ($valueSize == 0)
				$filename = '';
			else
				$filename = 'blob-' . strtolower(str_replace(' ', '', CTMiscHelper::formatSizeUnits($valueSize))) . '.' . $file_extension;
		}
		return $filename;
	}
}