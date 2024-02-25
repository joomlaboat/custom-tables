<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\Fields;
use CustomTables\Value_user;

class CT_FieldTypeTag_log
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getLogVersionLinks(CT &$ct, $rowValue, &$row): string
	{
		$current_json_data_size = CT_FieldTypeTag_log::getVersionDataSize($ct, $row);

		$url = CTMiscHelper::curPageURL();
		$new_url = CTMiscHelper::deleteURLQueryOption($url, 'version');

		$result = '';
		$versions = explode(';', $rowValue);
		$version = common::inputGetInt('version', 0);

		$version_date = '';
		$version_author = '';
		$version_size = 0;

		//get creation date
		foreach ($ct->Table->fields as $fieldRow) {
			if ($fieldRow['type'] == 'creationtime') {
				$version_date = strtotime($row[$ct->Env->field_prefix . $fieldRow['fieldname']]);
				break;
			}
		}

		//get original author
		foreach ($ct->Table->fields as $fieldRow) {
			if ($fieldRow['type'] == 'userid') {

				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
					. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'user.php');

				$version_author = Value_user::renderUserValue($row[$ct->Env->field_prefix . $fieldRow['fieldname']]);
				break;
			}
		}

		if (count($versions) > 1) {
			$result .= '<ol>';
			$i = 0;
			foreach ($versions as $v) {
				$i++;
				$data = explode(',', $v);


				$result .= '<li>';

				if ($version_date != '') {
					$str = gmdate('Y-m-d H:m:s', $version_date) . ' - ' . $version_author;


					if (isset($data[3])) {
						$decoded_data_rows = json_decode(base64_decode($data[3]), true);

						if ($decoded_data_rows === null) {
							//Log data is too long (longer than 65,535 bytes)
							//JSON record is corrupted
							//Update to 5.4.5
							$current_version_size = 0;
						} else {
							$decoded_data_row = $decoded_data_rows[0];
							$current_version_size = CT_FieldTypeTag_log::getVersionDataSize($ct, $decoded_data_row);
						}
					} else
						$current_version_size = $current_json_data_size;

					if ($current_version_size > $version_size)
						$str .= ' <span style="color:#00aa00">+' . ($current_version_size - $version_size) . '</span>';
					elseif ($current_version_size < $version_size)
						$str .= ' <span style="color:#aa0000">-' . ($version_size - $current_version_size) . '</span>';
				} else
					$str = $version_author;

				if ($str == '')
					$str = 'Original Version';

				if ($i == count($versions)) {
					if ($version == 0)
						$result .= '<b>' . $str . '</b>';
					else
						$result .= '<a href="' . $new_url . '" target="_blank">' . $str . '</a>';
				} else {
					if ($data[3] != '') {
						if (!str_contains($new_url, '?'))
							$link = $new_url . '?version=' . $i;
						else
							$link = $new_url . '&version=' . $i;

						if ($version == $i)
							$result .= '<b>' . $str . '</b>';
						else
							$result .= '<a href="' . $link . '" target="_blank">' . $str . '</a>';
					} else
						$result .= $str;


				}

				$result .= '</li>';

				$version_date = $data[0];

				if (isset($data[1])) //last comma is empty so no element number 1
				{
					require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
						. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'user.php');

					$version_author = Value_user::renderUserValue($data[1]);

					if (isset($data[3])) //last comma is empty so no element number 1
					{

						$decoded_data_rows = json_decode(base64_decode($data[3]), true);
						if ($decoded_data_rows === null) {
							//Log data is too long (longer than 65,535 bytes)
							//JSON record is corrupted
							//Update to 5.4.5
							$version_size = 0;
						} else {
							$decoded_data_row = $decoded_data_rows[0];
							$version_size = CT_FieldTypeTag_log::getVersionDataSize($ct, $decoded_data_row);
						}
					} else
						$version_size = 0;

				}
			}
			$result .= '</ol>';
		}

		return $result;

	}


	public static function getVersionDataSize(CT &$ct, $decoded_data_row)
	{
		$version_size = 0;

		foreach ($ct->Table->fields as $fieldRow) {
			if ($fieldRow['type'] != 'log' and $fieldRow['type'] != 'dummy' and !Fields::isVirtualField($fieldRow)) {
				$field_name = $ct->Env->field_prefix . $fieldRow['fieldname'];
				if (isset($decoded_data_row[$field_name]))
					$version_size += strlen($decoded_data_row[$field_name]);
			}
		}
		return $version_size;
	}
}
