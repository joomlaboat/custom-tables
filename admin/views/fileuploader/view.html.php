<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');

// Import Joomla! libraries
jimport('joomla.application.component.view');

class CustomTablesViewFileUploader extends JViewLegacy
{
    function display($tpl = null)
    {

        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'uploader.php');

        if (ob_get_contents()) ob_end_clean();

        $jinput = Factory::getApplication()->input;
        $fileid = $jinput->getCmd('fileid', '');

        echo ESFileUploader::uploadFile($fileid, 'txt html');

        die; //to stop rendering template and staff
    }
}
