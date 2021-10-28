<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

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
		if ($lat != '') { $suffix .= '&amp;lat='.$lat;}
		if ($lng != '') { $suffix .= '&amp;lng='.$lng;}
		if ($zoom != '' && (int)$zoom > 0) { $suffix .= '&amp;zoom='.$zoom;}
		if ($maptype != '') { $suffix .= '&amp;type='.$maptype;}
		
		//TODO Use some Plugin instead
		$link = 'index.php?option=com_customtables&amp;view=phocamapsgmap&amp;tmpl=component&amp;esobjectname='.$control_name. $suffix;
		
		// Load the modal behavior script.
		JHtml::_('behavior.modal', 'a.modal_'.$control_name);

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

		return implode("\n", $html);
	}
}
