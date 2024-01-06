<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\common;
use CustomTables\database;
use CustomTables\ImportTables;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

//------------- CURRENTLY UNUSED

class ImportExportUserGroups
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function processFile($filename, &$msg): bool
	{
		if (file_exists($filename)) {
			$data = common::getStringFromFile($filename);

			if (!str_contains($data, '<usergroupsexport>')) {
				$msg = 'Uploaded file does not contain User Groups JSON data.';
				return false;
			}

			$jsondata = json_decode(str_replace('<usergroupsexport>', '', $data), true);

			return ImportExportUserGroups::processData($jsondata, $msg);
		} else {
			$msg = 'Uploaded file not found. Code EX02';
			return false;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processData($jsondata): bool
	{
		$usergroups = $jsondata['usergroups'];

		foreach ($usergroups as $usergroup) {
			$old = ImportTables::getRecordByField('#__usergroups', 'id', $usergroup['id'], false);
			if (is_array($old) and count($old) > 0)
				ImportTables::updateRecords('#__usergroups', $usergroup, $old, false, array(), true);
			else
				$usergroupid = ImportTables::insertRecords('#__usergroups', $usergroup, false, array(), true);
		}

		$viewlevels = $jsondata['viewlevels'];

		foreach ($viewlevels as $viewlevel) {
			$old = ImportTables::getRecordByField('#__viewlevels', 'id', $viewlevel['id'], false);
			if (is_array($old) and count($old) > 0)
				ImportTables::updateRecords('#__viewlevels', $viewlevel, $old, false, array(), true);
			else
				$usergroupidid = ImportTables::insertRecords('#__viewlevels', $viewlevel, false, array(), true);
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function exportUserGroups(): ?string
	{
		$whereClause = new MySQLWhereClause();

		//This function will export user groups
		//$query = 'SELECT * FROM #__usergroups';
		$usergroups = database::loadAssocList('#__usergroups', ['*'], $whereClause);
		if (count($usergroups) == 0)
			return null;

		//$query = 'SELECT * FROM #__viewlevels';
		$viewLevels = database::loadAssocList('#__viewlevels', ['*'], $whereClause);
		if (count($viewLevels) == 0)
			return null;

		$output = ['usergroups' => $usergroups, 'viewlevels' => $viewLevels];

		if (count($output) > 0) {
			$output_str = '<usergroupsexport>' . common::ctJsonEncode($output);

			$tmp_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
			$filename = 'usergroups';
			$filename_available = $filename;
			$a = '';
			$i = 0;
			do {
				if (!file_exists($tmp_path . $filename . $a . '.txt')) {
					$filename_available = $filename . $a . '.txt';
					break;
				}

				$i++;
				$a = $i . '';

			} while (1 == 1);

			$link = '/tmp/' . $filename_available;

			$msg = common::saveString2File($tmp_path . $filename_available, $output_str);
			if ($msg !== null) {
				Factory::getApplication()->enqueueMessage($tmp_path . $filename_available . '<br/>' . $msg, 'error');
				return null;
			}
			return $link;
		}
		return null;
	}

}
