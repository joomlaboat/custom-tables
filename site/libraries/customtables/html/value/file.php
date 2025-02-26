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

use CustomTablesImageMethods;
use Exception;
use finfo;
use Joomla\CMS\Component\ComponentHelper;

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
	. DIRECTORY_SEPARATOR . 'value.php');

class Value_file extends BaseValue
{
	var CT $ct;

	var ?array $row;
	var string $key;
	var string $security;
	var string $listing_id;
	var int $tableid;
	var int $fieldid;
	var Field $field;

	function __construct(?CT &$ct = null, ?Field $field = null, $rowValue = null, array $option_list = [])
	{
		if ($ct !== null and $field !== null)
			parent::__construct($ct, $field, $rowValue, $option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	public function CheckIfFile2download(&$segments, &$vars): bool
	{
		$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
		require_once($path . 'loader.php');

		$params = ComponentHelper::getParams('com_customtables');
		$loadTwig = $params->get('loadTwig');

		CustomTablesLoader(false, true, null, 'com_customtables', $loadTwig);

		if (str_contains(end($segments), '.')) {

			//could be a file
			$parts = explode('.', end($segments));
			if (count($parts) >= 2 and strlen($parts[0]) > 0 and strlen($parts[1]) > 0) {

				//probably a file
				$allowedExtensions = explode(' ', 'bin gslides doc docx pdf rtf txt xls xlsx psd ppt pptx mp3 wav ogg jpg bmp ico odg odp ods swf xcf jpeg png gif webp svg ai aac m4a wma flv mpg wmv mov flac txt avi csv accdb zip pages');
				$ext = end($parts);
				if (in_array($ext, $allowedExtensions)) {
					$vars['view'] = 'files';
					$vars['key'] = $segments[0];

					$this->process_file_link(end($segments));
					$vars["listing_id"] = common::inputGetInt("listing_id", 0);
					$vars['fieldid'] = common::inputGetInt('fieldid', 0);
					$vars['security'] = common::inputGetCmd('security', 0);//security level letter (d,e,f,g,h,i)
					$vars['tableid'] = common::inputGetInt('tableid', 0);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	public function process_file_link(string $filename): void
	{
		$parts = explode('.', $filename);

		if (count($parts) < 2)
			throw new Exception('File name does not contain the extension type.');

		array_splice($parts, count($parts) - 1);
		$filename_without_ext = implode('.', $parts);

		$parts2 = explode('_', $filename_without_ext);
		$this->key = $parts2[count($parts2) - 1];

		$key_parts = explode('c', $this->key);

		if (count($key_parts) == 1)
			throw new Exception('The key format is invalid.');

		$key_params = $key_parts[count($key_parts) - 1];

//TODO: improve it. Get $security from layout, somehow
//security letters tells what method used
		$security = 'd';//Time Limited (8-24 minutes)

		if (str_contains($key_params, 'b')) $security = 'b';//Blob - Not limited
		elseif (str_contains($key_params, 'e')) $security = 'e';//Time Limited (1.5 - 4 hours)
		elseif (str_contains($key_params, 'f')) $security = 'f';//Time/Host Limited (8-24 minutes)
		elseif (str_contains($key_params, 'g')) $security = 'g';//Time/Host Limited (1.5 - 4 hours)
		elseif (str_contains($key_params, 'h')) $security = 'h';//Time/Host/User Limited (8-24 minutes)
		elseif (str_contains($key_params, 'i')) $security = 'i';//Time/Host/User Limited (1.5 - 4 hours)

		$this->security = $security;

		$key_params_a = explode($security, $key_params);
		if (count($key_params_a) != 3)
			throw new Exception('The key security format is invalid.');

		$this->listing_id = $key_params_a[0];

		if (isset($key_params_a[1]))
			$this->fieldid = $key_params_a[1];

		if (isset($key_params_a[2]))
			$this->tableid = $key_params_a[2];
	}

	/**
	 * @throws Exception
	 * @since 3.3.1
	 */
	function render(): ?string
	{
		$listing_id = $this->ct->Table->record[$this->ct->Table->realidfieldname] ?? null;

		if (empty($this->rowValue))
			return null;

		return self::process($this->rowValue, $this->field, $this->option_list, $listing_id);
	}

	/**
	 * @throws Exception
	 * @since 3.4.5
	 */
	public static function process(string $filename, Field $field, array $option_list, string $record_id, bool $filename_only = false, int $file_size = 0)
	{
		$fileWeb = null;
		$filePath = null;

		if ($field->type == 'filelink') {
			$FileFolderArray = CustomTablesImageMethods::getImageFolder($field->params, $field->type);
			$fileWeb = $FileFolderArray['web'] . '/' . $filename;
			$filePath = $FileFolderArray['path'] . DIRECTORY_SEPARATOR . $filename;

		} elseif ($field->type == 'blob') {
			$fileWeb = $filename;
		} else {
			$FileFolderArray = CustomTablesImageMethods::getImageFolder($field->params, $field->type);
			$filePath = $FileFolderArray['path'] . DIRECTORY_SEPARATOR . $filename;

			if (file_exists($filePath)) {
				$file_size = filesize($filePath);
				$fileWeb = $FileFolderArray['web'] . '/' . $filename;
			}
		}

		if (!isset($option_list[2]))
			$icon_size = '32';
		else
			$icon_size = $option_list[2];

		if ($icon_size != "16" and $icon_size != "32" and $icon_size != "48")
			$icon_size = '32';

		$parts = explode('.', $filename);
		$fileExtension = end($parts);

		//Get the Icon
		$icon_Name = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables'
			. DIRECTORY_SEPARATOR . 'media'
			. DIRECTORY_SEPARATOR . 'images'
			. DIRECTORY_SEPARATOR . 'fileformats'
			. DIRECTORY_SEPARATOR . $icon_size . 'px'
			. DIRECTORY_SEPARATOR . $fileExtension . '.png';

		if (!file_exists($icon_Name))
			$icon = '';
		else
			$icon = CUSTOMTABLES_MEDIA_WEBPATH . 'images/fileformats/' . $icon_size . 'px/' . $fileExtension . '.png';

		$how_to_process = $option_list[0] ?? '';

		if ($how_to_process != '') {
			$fileWeb = self::get_private_file_path($filename, $how_to_process, $filePath, $record_id, $field->id, $field->ct->Table->tableid, $filename_only);
		} elseif ($field->type == 'blob') {
			$how_to_process = 'blob';//Not secure but BLOB
			$fileWeb = self::get_private_file_path($filename, $how_to_process, $fileWeb, $record_id, $field->id, $field->ct->Table->tableid, $filename_only);
		}

		if (isset($option_list[3])) {
			if ($option_list[3] == 'savefile') {
				if (!str_contains($fileWeb, '?'))
					$fileWeb .= '?';
				else
					$fileWeb .= '&';

				$fileWeb .= 'savefile=1'; //Will add HTTP Header: @header("Content-Disposition: attachment; filename=\"".$filename."\"");
			}
		}

		$target = '';
		if (isset($option_list[3]) and $option_list[3] == '_blank')
			$target = ' target="_blank"';

		$output_format = '';
		if (isset($option_list[1]))
			$output_format = $option_list[1];

		switch ($output_format) {

			case '':
			case 'link':
				//Link Only
				return $fileWeb;

			case 'icon-filename-link':
				//Clickable Icon and File Name
				return '<a href="' . $fileWeb . '"' . $target . '>'
					. ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : '')
					. '<span>' . $filename . '</span></a>';

			case 'icon-link':
				//Clickable Icon
				return '<a href="' . $fileWeb . '"' . $target . '>' . ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : $filename) . '</a>';//show file name if icon not available

			case 'filename-link':
				//Clickable File Name
				return '<a href="' . $fileWeb . '"' . $target . '>' . $filename . '</a>';

			case 'link-anchor':
				//Clickable Link
				return '<a href="' . $fileWeb . '"' . $target . '>' . $fileWeb . '</a>';

			case 'icon':
				return ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : '');//show nothing is icon not available

			case 'link-to-icon':
				return $icon;//show nothing if icon not available

			case 'filename':
				return $filename;

			case 'extension':
				return $fileExtension;

			case 'file-size':
				return CTMiscHelper::formatSizeUnits($file_size);

			default:
				return $fileWeb;
		}
	}

	protected static function get_private_file_path(string $rowValue, string $how_to_process, string $fileWebPath, string $listing_id, int $fieldid, int $tableid, bool $filename_only = false): ?string
	{
		$security = self::get_security_letter($how_to_process);

		//make the key
		$key = self::makeTheKey($fileWebPath, $security, $listing_id, $fieldid, $tableid);

		//prepare new file name that includes the key
		$fna = explode('.', $rowValue);
		$filetype = $fna[count($fna) - 1];
		array_splice($fna, count($fna) - 1);
		$fileName = implode('.', $fna);
		$filePath = $fileName . '_' . $key . '.' . $filetype;

		if (!$filename_only) {
			if (defined('_JEXEC'))
				return CUSTOMTABLES_MEDIA_HOME_URL . '/index.php?option=com_customtables&file=' . $filePath;
			elseif (defined('WPINC'))
				return CUSTOMTABLES_MEDIA_HOME_URL . '/index.php?customtables=1&file=' . $filePath;
		}
		return null;
	}

	static protected function get_security_letter(string $how_to_process): string
	{
		switch ($how_to_process) {

			case 'blob':
				return 'b';

			case 'timelimited':
				return 'd';

			case 'timelimited_longterm':
				return 'e';

			case 'hostlimited':
				return 'f';

			case 'hostlimited_longterm':
				return 'g';

			case 'private':
				return 'h';

			case 'private_longterm':
				return 'i';

			default:
				return '';
		}
	}

	protected static function makeTheKey(string $filepath, string $security, string $recId, string $fieldid, string $tableid): string
	{
		$user = new CTUser();
		$username = $user->username;
		$current_user_id = $user->id;

		$t = time();
		//prepare augmented timer
		$secs = 1000;
		if ($security == 'e' or $security == 'g' or $security == 'i')
			$secs = 10000;

		$timePlus = floor(($t + $secs) / $secs) * $secs;
		$ip = common::getServerParam('REMOTE_ADDR');

		//get secs key char
		$sep = $security;//($secs==1000 ? 'a' : 'b');
		$m2 = 'c' . $recId . $sep . $fieldid . $sep . $tableid;

		$m = '';

		//calculate MD5
		if ($security == 'd' or $security == 'e')
			$m = md5($filepath . $timePlus);
		elseif ($security == 'f' or $security == 'g')
			$m = md5($filepath . $timePlus . $ip);
		elseif ($security == 'h' or $security == 'i')
			$m = md5($filepath . $timePlus . $ip . $username . $current_user_id);

		//replace rear part of the hash
		$m3 = substr($m, 0, strlen($m) - strlen($m2));
		return $m3 . $m2;
	}

	//Display the file

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	public function display()
	{
		$this->ct = new CT([], true);
		$this->ct->getTable($this->tableid);

		if ($this->ct->Table === null)
			throw new Exception('Table not selected (79).');

		$fieldRow = null;
		foreach ($this->ct->Table->fields as $f) {
			if ($f['id'] == $this->fieldid) {
				$fieldRow = $f;
				break;
			}
		}

		if (is_null($fieldRow))
			throw new Exception('File View: Field not found.');

		if (!empty($this->listing_id)) {
			$this->ct->Params->listing_id = $this->listing_id;
			$this->ct->getRecord();
		}

		$this->row = $this->ct->Table->record;
		$this->field = new Field($this->ct, $fieldRow, $this->row);

		if ($this->field->type == 'blob') {

			if (isset($this->field->params[2])) {
				$fileNameField_String = $this->field->params[2];
				$fileNameField_Row = $this->ct->Table->getFieldByName($fileNameField_String);
				$fileNameField = $fileNameField_Row['realfieldname'];
				$filepath = $this->row[$fileNameField];
			} else {
				$filepath = 'blob-' . strtolower(str_replace(' ', '', CTMiscHelper::formatSizeUnits((int)$this->row[$this->field->realfieldname]))) . '.bin';
			}

		} else {
			$filepath = $this->getFilePath();

			if ($filepath == '')
				throw new Exception('File path not set.');
		}

		$test_key = self::makeTheKey($filepath, $this->security, $this->listing_id, $this->fieldid, $this->tableid);

		if ($this->key == $test_key) {
			if ($this->field->type == 'blob') {
				if (isset($this->field->params[2]))
					$this->render_blob_output($filepath);
				else
					$this->render_blob_output('');
			} else
				$this->render_file_output($filepath);
		} else {
			throw new Exception(common::translate('COM_CUSTOMTABLES_DOWNLOAD_LINK_IS_EXPIRED'));
		}

	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function getFilePath(): string
	{
		if (!isset($this->row[$this->field->realfieldname]))
			throw new Exception('Real field name not set');

		$rowValue = $this->row[$this->field->realfieldname];

		$FileFolderArray = CustomTablesImageMethods::getImageFolder($this->field->params, $this->field->type);
		return $FileFolderArray['path'] . DIRECTORY_SEPARATOR . $rowValue;

		/*
				$FileFolder = FileUtils::getOrCreateDirectoryPath($field->params[1] ?? '');
				$filepath = $FileFolder . DIRECTORY_SEPARATOR . $filename;
				if ($filepath[0] == DIRECTORY_SEPARATOR)
					$filepath = substr($filepath, 1, strlen($filepath) - 1);

				$full_filepath = CUSTOMTABLES_ABSPATH . $filepath;
				if (file_exists($full_filepath))
					$file_size = filesize($full_filepath);
				*/
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render_blob_output($filename)
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($this->ct->Table->realidfieldname, $this->listing_id);

		$rows = database::loadAssocList($this->ct->Table->realtablename, [$this->field->realfieldname], $whereClause, null, null, 1);

		if (count($rows) < 1)
			throw new Exception(common::translate('COM_CUSTOMTABLES_FILE_NOT_FOUND'));

		$content = stripslashes($rows[0][$this->field->realfieldname]);
		$content = $this->ProcessContentWithCustomPHP($content, $this->row);

		if (ob_get_contents()) ob_end_clean();

		$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);

		if ($filename == '') {
			$file_extension = CTMiscHelper::mime2ext($mime);
			$filename = 'blob.' . $file_extension;
		}

		@header('Content-Type: ' . $mime);
		@header("Pragma: public");
		@header("Expires: 0");
		@header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		@header("Cache-Control: public");
		@header("Content-Description: File Transfer");
		@header("Content-Transfer-Encoding: binary");
		@header("Content-Disposition: attachment; filename=\"" . $filename . "\"");

		echo $content;

		die;//clean exit
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function ProcessContentWithCustomPHP($content, $row)
	{
		if (!empty($this->field->params[4] != '')) {

			$customPHPFile = $this->field->params[4];

			if (defined('_JEXEC')) {

				$serverTagProcessorFile = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR
					. 'customtables' . DIRECTORY_SEPARATOR . 'protagprocessor' . DIRECTORY_SEPARATOR . 'servertags.php';

				if (!file_exists($serverTagProcessorFile))
					return $content;

				$parts = explode('/', $customPHPFile); //just a security check
				if (count($parts) > 1)
					return $content;

				$file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'customphp' . DIRECTORY_SEPARATOR . $customPHPFile;
				if (file_exists($file)) {
					require_once($file);
					$function_name = 'CTProcessFile_' . str_replace('.php', '', $customPHPFile);

					if (function_exists($function_name))
						return call_user_func($function_name, $content, $row, $this->ct->Table->tableid, $this->fieldid);
					else
						throw new Exception('Function "' . $function_name . '" not found.');

				} else {
					throw new Exception('Custom PHP file "' . $file . '" not found.');
				}
			} elseif (defined('WPINC')) {
				throw new Exception('Custom PHP file "' . $customPHPFile . '" Execution of a custom PHP in WordPress version of the CustomTables is not implemented.');
			}
		}
		return $content;
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function render_file_output(string $filePath): bool
	{
		if (!file_exists($filePath))
			throw new Exception(common::translate('COM_CUSTOMTABLES_FILE_NOT_FOUND') . ': \'' . $filePath . '\'');

		$content = common::getStringFromFile($filePath);

		//Make a file name
		$parts = explode(DIRECTORY_SEPARATOR, $filePath);
		$fileName = end($parts);

		try {
			$content = $this->ProcessContentWithCustomPHP($content, $this->row);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if (ob_get_contents()) ob_end_clean();

		$mt = mime_content_type($filePath);

		@header('Content-Type: ' . $mt);
		@header("Pragma: public");
		@header("Expires: 0");
		@header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		@header("Cache-Control: public");
		@header("Content-Description: File Transfer");
		@header("Content-Transfer-Encoding: binary");
		@header("Content-Disposition: attachment; filename=\"" . $fileName . "\"");

		echo $content;

		die;//clean exit
	}
}
