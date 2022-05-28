<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

class CustomTablesController extends JControllerLegacy
{
    function display($cachable = false, $urlparams = array())
    {
        $jinput = Factory::getApplication()->input;
        if ($jinput->getString('file') != '') {
            //Load file instead

            $processor_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
            require_once($processor_file);

            CT_FieldTypeTag_file::process_file_link($jinput->getString('file'));

            $jinput->set('view', 'files');
            parent::display();
            return;
        }

        // Make sure we have the default view
        if ($jinput->getCmd('view') == '') {
            $jinput->set('view', 'catalog');
            parent::display();
        } else {
            $view = $jinput->getCmd('view');

            switch ($view) {
                case 'log' :
                    require_once('controllers/log.php');
                    break;

                case 'list' :
                    require_once('controllers/list.php');
                    break;

                case 'edititem' :
                    require_once('controllers/save.php');
                    break;

                case ($view == 'home' || $view == 'catalog') :
                    require_once('controllers/catalog.php');
                    break;

                case 'editphotos' :
                    require_once('controllers/editphotos.php');
                    break;

                case 'editfiles' :
                    require_once('controllers/editfiles.php');
                    break;

                case 'createuser':
                case 'resetuserpassword':
                case 'paypal':
                case 'a2checkout':
                case 'files':
                case 'fileuploader':
                case 'structure' :
                    parent::display();
                    break;

                case 'details' :
                    require_once('controllers/details.php');
                    break;
            }
        }
    }
}
