<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CT_FieldTypeTag_FileBox;

class InputBox_filebox extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(): string
	{
		if (!$this->ct->isRecordNull($this->row)) {
			require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
				. 'customtables' . DIRECTORY_SEPARATOR . 'datatypes' . DIRECTORY_SEPARATOR . 'filebox.php');

			$manageButton = '';
			$listing_id = $this->row[$this->ct->Table->realidfieldname] ?? null;
			$FileBoxRows = CT_FieldTypeTag_FileBox::getFileBoxRows($this->ct->Table->tablename, $this->field->fieldname, $listing_id);

			foreach ($this->ct->Table->fileboxes as $fileBox) {
				if ($fileBox[0] == $this->field->fieldname) {
					$manageButton = CT_FieldTypeTag_FileBox::renderFileBoxIcon($this->ct, $listing_id, $fileBox[0], $fileBox[1]);
					break;
				}
			}

			if (count($FileBoxRows) > 0) {
				$vlu = CT_FieldTypeTag_FileBox::process($FileBoxRows, $this->field, $listing_id, ['', 'icon-filename-link', '32', '_blank', 'ol']);
				$result = '<div style="width:100%;overflow:scroll;background-image: url(\'components/com_customtables/libraries/customtables/media/images/icons/bg.png\');">'
					. $manageButton . '<br/>' . $vlu . '</div>';
			} else
				$result = common::translate('COM_CUSTOMTABLES_FILE_NO_FILES') . ' inputbox_filebox.php' . $manageButton;

			return $result;
		}
		return '';
	}
}