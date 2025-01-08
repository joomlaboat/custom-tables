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

class Value_log extends BaseValue
{
	function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
	{
		parent::__construct($ct, $field, $rowValue, $option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(): ?string
	{
		if ($this->rowValue === null)
			return null;

		$current_json_data_size = $this->getVersionDataSize($this->ct->Table->record);

		$url = common::curPageURL();
		$new_url = CTMiscHelper::deleteURLQueryOption($url, 'version');

		$result = '';
		$versions = explode(';', $this->rowValue);
		$version = common::inputGetInt('version', 0);

		$version_date_string = null;
		$version_author = '';
		$version_size = 0;

		//get creation date
		foreach ($this->ct->Table->fields as $fieldRow) {
			if ($fieldRow['type'] == 'creationtime') {
				$version_date_string = $this->ct->Table->record[$this->ct->Table->fieldPrefix . $fieldRow['fieldname']];
				break;
			}
		}

		//get original author
		foreach ($this->ct->Table->fields as $fieldRow) {
			if ($fieldRow['type'] == 'userid') {

				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
					. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'user.php');

				$version_author = Value_user::renderUserValue($this->ct->Table->record[$this->ct->Table->fieldPrefix . $fieldRow['fieldname']]);
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

				if ($version_date_string !== null) {
					$str = common::formatDate($version_date_string) . ' - ' . $version_author;

					if (isset($data[3])) {
						$decoded_data_rows = json_decode(base64_decode($data[3]), true);

						if ($decoded_data_rows === null) {
							//Log data is too long (longer than 65,535 bytes)
							//JSON record is corrupted
							//Update to 5.4.5
							$current_version_size = 0;
						} else {
							$decoded_data_row = $decoded_data_rows[0];
							$current_version_size = $this->getVersionDataSize($decoded_data_row);
						}
					} else
						$current_version_size = $current_json_data_size;

					if ($current_version_size > $version_size)
						$str .= ' <span style="color:#00aa00;">+' . ($current_version_size - $version_size) . '</span>';
					elseif ($current_version_size < $version_size)
						$str .= ' <span style="color:#aa0000;">-' . ($version_size - $current_version_size) . '</span>';
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
						$result .= $str . '(no data)';
				}

				$result .= '</li>';
				$version_date_string = $data[0];

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
							$version_size = $this->getVersionDataSize($decoded_data_row);
						}
					} else
						$version_size = 0;

				}
			}
			$result .= '</ol>';
		}

		return $result;
	}

	protected function getVersionDataSize($decoded_data_row): int
	{
		$version_size = 0;

		foreach ($this->ct->Table->fields as $fieldRow) {
			if ($fieldRow['type'] != 'log' and $fieldRow['type'] != 'dummy' and !Fields::isVirtualField($fieldRow)) {
				$field_name = $this->ct->Table->fieldPrefix . $fieldRow['fieldname'];
				if (isset($decoded_data_row[$field_name]))
					$version_size += strlen($decoded_data_row[$field_name]);
			}
		}
		return $version_size;
	}
}
