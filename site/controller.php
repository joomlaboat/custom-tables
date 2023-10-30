<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;

class CustomTablesController extends JControllerLegacy
{
    function display($cachable = false, $urlparams = array())
    {
        $file = common::inputGetString('file');
        if ($file != '') {
            //Load file instead

            $processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
            require_once($processor_file);
            CT_FieldTypeTag_file::process_file_link($file);

            common::inputSet('view', 'files');
            parent::display();
            return;
        }

        // Make sure we have the default view
        if (common::inputGetCmd('view') == '') {
            common::inputSet('view', 'catalog');
            parent::display();
        } else {
            $view = common::inputGetCmd('view');

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
