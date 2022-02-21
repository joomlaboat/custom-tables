<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use \Joomla\CMS\Version;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');

class JHTMLGoogleMapCoordinates
{
	function render($control_name, $value)
	{
		$html = [];
		$html[] = '<div class="controls"><div class="field-calendar"><div class="input-group has-success">';
		$html[] = '<input type="text" class="form-control valid form-control-success" id="'.$control_name.'" name="'.$control_name.'" value="'. $value.'" />';
		$html[] = '<button type="button" class="btn btn-primary" onclick="ctInputbox_googlemapcoordinates(\''.$control_name.'\')" data-inputfield="comes_'.$control_name.'" data-button="comes_'.$control_name.'_btn">&nbsp;...&nbsp;</button>';
		$html[] = '</div></div></div>';
		$html[] = '<div id="'.$control_name.'_map" style="width: 480px; height: 540px;display:none;"></div>';

		return implode("\n", $html);
	}
}
