<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Import Joomla! libraries
jimport( 'joomla.application.component.view');

class CustomTablesViewFileUploader extends JViewLegacy
{
    function display($tpl = null)
    {

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'uploader.php');

		if (ob_get_contents()) ob_end_clean();

        $jinput=JFactory::getApplication()->input;
		$fileid = $jinput->getCmd( 'fileid', '' );

        echo ESFileUploader::uploadFile($fileid,'txt html');

		die; //to stop rendering template and staff
	}
}
