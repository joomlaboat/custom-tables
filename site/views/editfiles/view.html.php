<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTMiscHelper;
use CustomTables\CTUser;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

jimport('joomla.application.component.view'); //Important to get menu parameters
class CustomTablesViewEditFiles extends HtmlView
{
	var int $max_file_size;
	var $FileBoxTitle;
	var $listing_id;
	var $fileboxname;
	var $allowedExtensions;

	function display($tpl = null)
	{
		$user = new CTUser();

		if ($user->id === null) {
			Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
			return;
		}

		$this->Model = $this->getModel();
		$this->files = $this->Model->getFileList();

		$this->idList = array();

		foreach ($this->files as $file)
			$this->idList[] = $file->fileid;

		$this->max_file_size = CTMiscHelper::file_upload_max_size();
		$this->FileBoxTitle = $this->Model->FileBoxTitle;
		$this->listing_id = $this->Model->ct->Params->listing_id;
		$this->fileboxname = $this->Model->fileboxname;
		$this->allowedExtensions = $this->Model->allowedExtensions;
		parent::display($tpl);
	}

	function drawFiles(): string
	{
		$HTMLOut = '
		
		<h2>' . common::translate('COM_CUSTOMTABLES_FILE_LIST_OF_FILES') . '</h2>
		<table style="width:100%;border:none;">
			<thead>
				<tr>
					<th style="vertical-align: top; text-align: center; width:40px;">
					    <input type="checkbox" name="SelectAllBox" id="SelectAllBox" onClick=SelectAll(this.checked) style="text-align: left; vertical-align:top"> ' . common::translate('COM_CUSTOMTABLES_SELECT_ALL') . '</th>
					<th style="vertical-align: top; text-align: center; "></th>
				</tr>
			</thead>
			<tbody>
		';

		$c = 0;
		foreach ($this->files as $file) {
			$HTMLOut .= '
				<tr>';

			$filename = $this->Model->ct->Table->tableid . '_' . $this->fileboxname . '_' . $file->fileid . '.' . $file->file_ext;
			$filepath = $this->Model->fileboxfolderweb . '/' . $filename;

			$HTMLOut .= '
					<td  style="vertical-align: top; text-align: center; ">
						<input type="checkbox" name="esfile' . $file->fileid . '" id="esfile' . $file->fileid . '" style="text-align: left;" style="vertical-align:top">
					</td>
					<td style="text-align: left;"><a href="' . $filepath . '" target="_blank">' . $filename . '</a></td>
			';

			$c++;
			$HTMLOut .= '
				</tr>';
		}

		$HTMLOut .= '
			</tbody>
		</table>
		';

		return $HTMLOut;
	}
}
