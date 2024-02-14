<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\Fields;
use Joomla\CMS\MVC\View\HtmlView;

// import Joomla view library
jimport('joomla.application.component.view');

$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'extratasks' . DIRECTORY_SEPARATOR;

require_once($path . 'updateimages.php');
require_once($path . 'updatefiles.php');
require_once($path . 'updateimagegallery.php');
require_once($path . 'updatefilebox.php');

/**
 * Customtables View class for the Listoftables
 */
class CustomtablesViewAPI extends HtmlView
{
	/**
	 * Listoftables view display method
	 * @return void
	 */
	function display($tpl = null)
	{
		if (ob_get_contents()) ob_end_clean();
		$task = common::inputGetCmd('task', '');
		$frmt = common::inputGetCmd('frmt', '');

		$result = array();
		switch ($task) {
			case 'getfields':

				$tableid = common::inputGetInt('tableid', 0);
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
			echo common::ctJsonEncode($result);
		} elseif ($frmt == 'xml') {
			header('Content-Type: text/xml');
			$xml_data = new SimpleXMLElement('<?xml version="1.0"?><data value=""></data>');

			// function call to convert array to xml
			$this->array_to_xml($result, $xml_data);

			//saving generated xml file;
			echo $xml_data->asXML();
		} else
			echo 'error:unknown format';

		die;//Admin API clean output
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
				if ($value === null)
					$value = '';

				$xml_data->addChild("$key", htmlspecialchars("$value"));
			}
		}
	}
}
