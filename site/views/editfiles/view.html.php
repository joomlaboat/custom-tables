<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;

jimport('joomla.application.component.view'); //Important to get menu parameters
class CustomTablesViewEditFiles extends JViewLegacy
{
    var int $max_file_size;
    var $jinput;
    var $FileBoxTitle;
    var $listing_id;
    var $fileboxname;
    var $allowedExtensions;

    function display($tpl = null)
    {
        $user = Factory::getUser();
        $userid = $user->get('id');
        if ((int)$userid == 0) {
            Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
            return;
        }

        $this->Model = $this->getModel();
        $this->files = $this->Model->getFileList();

        $this->idList = array();

        foreach ($this->files as $file)
            $this->idList[] = $file->fileid;

        $this->max_file_size = JoomlaBasicMisc::file_upload_max_size();
        $this->jinput = Factory::getApplication()->input;
        $this->FileBoxTitle = $this->Model->FileBoxTitle;
        $this->listing_id = $this->Model->ct->Params->listing_id;
        $this->fileboxname = $this->Model->fileboxname;
        $this->allowedExtensions = $this->Model->allowedExtensions;

        parent::display($tpl);
    }

    function drawFiles()
    {
        $htmlout = '
		
		<h2>' . JoomlaBasicMisc::JTextExtended("List of Files") . '</h2>
		<table style="width:100%;border:none;">
			<thead>
				<tr>
					<th style="vertical-align: top; text-align: center; width:40px;"><input type="checkbox" name="SelectAllBox" id="SelectAllBox" onClick=SelectAll(this.checked) style="text-align: left; vertical-align:top"> Select All</th>
					<th style="vertical-align: top; text-align: center; "></th>
				</tr>
			</thead>
			<tbody>
		';

        $c = 0;
        foreach ($this->files as $file) {
            $htmlout .= '
				<tr>';

            $filename = $this->Model->ct->Table->tableid . '_' . $this->fileboxname . '_' . $file->fileid . '.' . $file->file_ext;
            $filepath = $this->Model->fileboxfolderweb . '/' . $filename;

            $htmlout .= '
					<td  style="vertical-align: top; text-align: center; ">
						<input type="checkbox" name="esfile' . $file->fileid . '" id="esfile' . $file->fileid . '" style="text-align: left;" style="vertical-align:top">
					</td>
					<td style="text-align: left;"><a href="' . $filepath . '" target="_blank">' . $filename . '</a></td>
			';

            $c++;
            $htmlout .= '
				</tr>';
        }

        $htmlout .= '
			</tbody>
		</table>
		';

        return $htmlout;
    }
}
