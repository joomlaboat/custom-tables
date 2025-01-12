<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
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
 *
 * @since 3.0.0
 */
class CustomtablesViewAPI extends HtmlView
{
	/**
	 * Listoftables view display method
	 * @return void
	 *
	 * @throws Exception
	 * @since 3.0.0
	 */
	function display($tpl = null)
	{
		if (ob_get_contents()) ob_end_clean();
		$task = common::inputGetCmd('task', '');
		$frmt = common::inputGetCmd('frmt', '');

		$tableId = common::inputGetInt('tableid', 0);
		if ($tableId == 0) {
			$result = array('error' => 'tableid not set');
		} else {
			switch ($task) {
				case 'getfields':
					$ct = new CT([], true);
					$ct->getTable($tableId);
					if ($ct->Table === null) {
						$result = array('error' => 'tableid \'' . $tableId . '\' set but not loaded.');
						break;
					}

					$result = $ct->Table->fields;// Fields::getFields($ct->Table, true);//TODO:  Check the output
					break;
				case 'updateimages':
					$result = updateImages::process($tableId);
					break;
				case 'updatefiles':
					$result = updateFiles::process($tableId);
					break;
				case 'updateimagegallery':
					$result = updateImageGallery::process($tableId);
					break;
				case 'updatefilebox':
					$result = updateFileBox::process($tableId);
					break;
				default:
					$result = array('error' => 'unknown task');
					break;
			}
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

	function array_to_xml($data, $xml_data)
	{
		foreach ($data as $key => $value) {
			if (is_numeric($key)) {
				$key = 'item' . $key; //dealing with <0/>..<n/> issues
			}

			if (is_array($value)) {
				$subNode = $xml_data->addChild($key);
				$this->array_to_xml($value, $subNode);
			} else {
				if ($value === null)
					$value = '';

				$xml_data->addChild("$key", htmlspecialchars("$value"));
			}
		}
	}
}
