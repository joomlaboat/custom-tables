<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\Fields;
use Joomla\CMS\Factory;

// import Joomla view library
jimport('joomla.application.component.view');

$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
    . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'extratasks' . DIRECTORY_SEPARATOR;

require_once($path . 'updateimages.php');
require_once($path . 'updatefiles.php');
require_once($path . 'updateimagegallery.php');
require_once($path . 'updatefilebox.php');

/**
 * Customtables View class for the Listoftables
 */
class CustomtablesViewAPI extends JViewLegacy
{
    /**
     * Listoftables view display method
     * @return void
     */
    function display($tpl = null)
    {
        if (ob_get_contents()) ob_end_clean();
        $jinput = Factory::getApplication()->input;

        $task = $jinput->getCmd('task', '');
        $frmt = $jinput->getCmd('frmt', '');

        $result = array();
        switch ($task) {
            case 'getfields':

                $tableid = $jinput->getInt('tableid', 0);
                if ($tableid == 0) {
                    $result = array('error' => 'tableid not set');
                } else {
                    $result = Fields::getFields($tableid, true);
                }


                break;

            case 'updateimages':

                $result = updateImages::process();

                break;

            case 'updatefiles':

                $result = updateFiles::process();

                break;

            case 'updateimagegallery':

                $result = updateImageGallery::process();

                break;

            case 'updatefilebox':

                $result = updateFileBox::process();

                break;

            default:
                $result = array('error' => 'unknown task');
                break;
        }


        if ($frmt == 'json') {
            header('Content-Type: application/json');
            echo json_encode($result);
        } elseif ($frmt == 'xml') {
            header('Content-Type: text/xml');
            $xml_data = new SimpleXMLElement('<?xml version="1.0"?><data></data>');

            // function call to convert array to xml
            $this->array_to_xml($result, $xml_data);

            //saving generated xml file;
            echo $xml_data->asXML();
        } else
            echo 'error:unknown format';

        die;
    }


    function array_to_xml($data, &$xml_data)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item' . $key; //dealing with <0/>..<n/> issues
            }

            if (is_array($value)) {
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}
