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

class InputBox_filebox extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(): string
	{
		if (!$this->ct->isRecordNull($this->row)) {
			$manageButton = '';
			$listing_id = $this->row[$this->ct->Table->realidfieldname] ?? null;
			$FileBoxRows = self::getFileBoxRows($this->ct->Table->tablename, $this->field->fieldname, $listing_id);

			foreach ($this->ct->Table->fileboxes as $fileBox) {
				if ($fileBox[0] == $this->field->fieldname) {
					$manageButton = self::renderFileBoxIcon($this->ct, $listing_id, $fileBox[0], $fileBox[1]);
					break;
				}
			}

			if (count($FileBoxRows) > 0) {
				$vlu = self::process($FileBoxRows, $this->field, $listing_id, ['', 'icon-filename-link', '32', '_blank', 'ol']);
				$backGround = "background-image: url('" . CUSTOMTABLES_MEDIA_WEBPATH . "images/icons/bg.png');";
				$result = '<div style="padding:5px;width:100%;overflow:scroll;border:1px dotted grey;' . $backGround . '">'
					. $manageButton . '<br/>' . $vlu . '</div>';
			} else
				$result = common::translate('COM_CUSTOMTABLES_FILE_NO_FILES') . ' ' . $manageButton;

			return $result;
		}
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getFileBoxRows(string $tablename, string $fieldname, ?string $listing_id)
	{
		$fileBoxTableName = '#__customtables_filebox_' . $tablename . '_' . $fieldname;

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', (int)$listing_id);
		return database::loadObjectList($fileBoxTableName, ['fileid', 'file_ext'], $whereClause, 'fileid');
	}

	/**
	 * @since 3.2.2
	 */
	public static function renderFileBoxIcon(CT $ct, string $listing_id, string $fileBoxName, string $title): string
	{
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

		$icon = Icons::iconFileManager($ct->Env->toolbarIcons, $title);
		return '<div id="esFileBoxIcon' . $rid . '" class="toolbarIcons"><a href="' . $ct->Env->WebsiteRoot . $fileManagerLink . '">' . $icon . '</a></div>';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function process(array $FileBoxRows, $field, ?string $listing_id, array $option_list): string
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php');

		$fileSRCListArray = array();

		foreach ($FileBoxRows as $fileRow) {
			$filename = $field->ct->Table->tableid . '_' . $field->fieldname . '_' . $fileRow->fileid . '.' . $fileRow->file_ext;
			$fileSRCListArray[] = Value_file::process($filename, $field, $option_list, $listing_id);
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
}