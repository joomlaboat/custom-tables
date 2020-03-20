<?php
/**
 * @version		$Id: icon.php 14401.6.10-01-26 14:10:00Z louis $
 * @package		Joomla
 * @subpackage	Content
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
/**
 * Content Component HTML Helper
 *
 * @static
 * @package		Joomla
 * @subpackage	Content
 * @since 1.5
 */
class JHTMLIcon
{
	function pdf($link, $params, $access, $attribs = array())
	{
		$url  = $link.'&format=pdf';//index.php?view=article';
		

		$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

		// checks template image directory for image, if non found default are loaded
		$text = JHTML::_('image.site', 'pdf_button.png', '/images/M_images/', NULL, NULL,'PDF');

		$attribs['title']	= 'PDF';
		$attribs['onclick'] = "window.open(this.href,'win2','".$status."'); return false;";
		$attribs['rel']     = 'nofollow';

		return JHTML::_('link', JRoute::_($url), $text, $attribs);
	}

	function print_popup($link, $attribs = array())
	{
		$url  = $link.'&print=1';//index.php?view=article';
	
		$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

		// checks template image directory for image, if non found default are loaded
		$text = JHTML::_('image.site',  'printButton.png', '/images/M_images/', NULL, NULL, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT') );

		$attribs['title']	= JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT');
		$atribs['onclick'] = "window.open(this.href,'win2','".$status."'); return false;";
		$attribs['rel']     = 'nofollow';

		return JHTML::_('link', JRoute::_($url), $text, $attribs);
	}

}

