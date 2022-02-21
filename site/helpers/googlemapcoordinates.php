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
		$parameters=explode(',',$value);
		// Initialize variables.
		$html = array();
			
		$maptype	= 'marker';//( (string)$this->element['maptype'] ? $this->element['maptype'] : '' );
		
		// One link for latitude, longitude, zoom
		$lat	= $parameters[0];//$this->form->getValue('latitude');
		if(isset($parameters[1]))
			$lng	= $parameters[1];//$this->form->getValue('longitude');
		else
			$lng='';
			
		if(isset($parameters[2]))
			$zoom	= $parameters[2];//$this->form->getValue('longitude');
		else
			$zoom='';
				
		$suffix	= '';
		if ($lat != '') { $suffix .= '&lat='.$lat;}
		if ($lng != '') { $suffix .= '&lng='.$lng;}
		if ($zoom != '' && (int)$zoom > 0) { $suffix .= '&zoom='.$zoom;}
		if ($maptype != '') { $suffix .= '&type='.$maptype;}
		
		//TODO Use some Plugin instead
		$link = 'index.php?option=com_customtables&view=phocamapsgmap&tmpl=component&esobjectname='.$control_name. $suffix;

		$version_object = new Version;
		$version = (int)$version_object->getShortVersion();

		if($version < 4)
		{
			// Load the modal behavior script.
			JHtml::_('behavior.modal', 'a.modal_'.$control_name);
		}

		$html[] = '<div><input type="text" class="form-control  form-control" id="'.$control_name.'" name="'.$control_name.'" value="'. $value.'" />';
		//$html[] = '<button class="btn btn-success form-control-success" style="padding:unset !important;padding-left:5px;padding-right:5px;margin-left:-5px;
		//border-top-left-radius:0px;border-bottom-left-radius:0px;height:30px;maring-top:-1px;">&nbsp;...&nbsp;</button></div>';
		$html[] = '<button class="btn btn-primary">&nbsp;...&nbsp;</button></div>';
		
		$html[] = '<div id="'.$control_name.'_mapitems" style="max-width: 480px; max-height: 540px"></div>';

/*
		$html[] = '<div><table style="border:none;"><tr>';
		$html[] = '<td><input type="text" id="'.$control_name.'" name="'.$control_name.'" value="'. $value.'"' .
					' /></td>';
	
		// Create the user select button.
		$html[] = '<td>';
			
		$html[] = '<div class="button2-left">';
		$html[] = '  <div class="blank">';
		$html[] = '		<a class="modal_'.$control_name.'" title="Get coordinates"' .
								' href="'.$link.'"' .
								' rel="{handler: \'iframe\', size: {x: 780, y: 560}}">';
		$html[] = '			Get coordinates</a>';
		$html[] = '  </div>';
		$html[] = '</div></td>';
		$html[] = '</tr></table></div>';
		*/

		return implode("\n", $html);
	}
}
