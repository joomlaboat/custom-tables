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
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

// Import Joomla! libraries
jimport('joomla.application.component.view');

class CustomTablesViewFileUploader extends JViewLegacy
{
    function display($tpl = null)
    {
        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'uploader.php');

        if (ob_get_contents()) ob_end_clean();

        $jinput = Factory::getApplication()->input;

        $fieldname = $jinput->getCmd('fieldname', '');
        $fileid = $jinput->getCmd($fieldname . '_fileid', '');

        $task = $jinput->getCmd('op', '');

        if ($task == 'delete') {
            $file = str_replace('/', '', $jinput->getString('name', ''));
            $file = str_replace('..', '', $file);
            $file = str_replace('index.', '', $file);

            $output_dir = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

            if ($file != '' and file_exists($output_dir . $file)) {
                unlink($output_dir . $file);
                echo json_encode(['status' => 'Deleted']);
            } else
                echo json_encode(['error' => 'File not found. Code: FU-1']);
        } else
            echo ESFileUploader::uploadFile($fileid);

        die; //to stop rendering template and staff
    }
}
