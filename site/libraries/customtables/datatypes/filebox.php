<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\MySQLWhereClause;

class CT_FieldTypeTag_FileBox
{
	public static function process($FileBoxRows, &$field, $listing_id, array $option_list): string
	{
		$fileSRCListArray = array();

		foreach ($FileBoxRows as $fileRow) {
			$filename = $field->ct->Table->tableid . '_' . $field->fieldname . '_' . $fileRow->fileid . '.' . $fileRow->file_ext;
			$fileSRCListArray[] = CT_FieldTypeTag_file::process($filename, $field, $option_list, $listing_id);
		}

		$listFormat = '';
		if (isset($option_list[4]))
			$listFormat = $option_list[4];

		switch ($listFormat) {
			case 'ul':

				$fileTagListArray = array();

				foreach ($fileSRCListArray as $filename)
					$fileTagListArray[] = '<li>' . $filename . '</li>';

				return '<ul>' . implode('', $fileTagListArray) . '</ul>';

			case ',':
				return implode(',', $fileSRCListArray);

			case ';':
				return implode(';', $fileSRCListArray);

			default:
				//INCLUDING OL
				$fileTagListArray = array();

				foreach ($fileSRCListArray as $filename)
					$fileTagListArray[] = '<li>' . $filename . '</li>';

				return '<ol>' . implode('', $fileTagListArray) . '</ol>';
		}
	}

	public static function getFileBoxRows($tablename, $fieldname, $listing_id)
	{
		$fileBoxTableName = '#__customtables_filebox_' . $tablename . '_' . $fieldname;

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', (int)$listing_id);
		return database::loadObjectList($fileBoxTableName, ['fileid', 'file_ext'], $whereClause, 'fileid');
	}

	public static function renderFileBoxIcon(CT $ct, string $listing_id, string $fileBoxName, string $title): string
	{
		$iconPath = CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/';
		$rid = $ct->Table->tableid . 'x' . $listing_id;

		$fileManagerLink = 'index.php?option=com_customtables&amp;view=editfiles'
			. '&amp;establename=' . $ct->Table->tablename
			. '&amp;fileboxname=' . $fileBoxName
			. '&amp;listing_id=' . $listing_id
			. '&amp;returnto=' . $ct->Env->encoded_current_url;

		if (common::inputGetCmd('tmpl'))
			$fileManagerLink .= '&tmpl=' . common::inputGetCmd('tmpl', '');

		if ($ct->Params->ItemId > 0)
			$fileManagerLink .= '&amp;Itemid=' . $ct->Params->ItemId;

		if (!is_null($ct->Params->ModuleId))
			$fileManagerLink .= '&amp;ModuleId=' . $ct->Params->ModuleId;

		if ($ct->Env->toolbarIcons != '')
			$img = '<i class="ba-btn-transition ' . $ct->Env->toolbarIcons . ' fa-folder" data-icon="' . $ct->Env->toolbarIcons . ' fa-folder" title="' . $title . '"></i>';
		else
			$img = '<img src="' . $iconPath . 'filemanager.png" border="0" alt="' . $title . '" title="' . $title . '">';

		return '<div id="esFileBoxIcon' . $rid . '" class="toolbarIcons"><a href="' . $ct->Env->WebsiteRoot . $fileManagerLink . '">' . $img . '</a></div>';
	}
}
