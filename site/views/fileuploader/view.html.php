<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		view.html.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/


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

        $fieldname=$jinput->getCmd('fieldname','');
		$fileid = $jinput->getCmd($fieldname.'_fileid', '' );

        $task = $jinput->getCmd('op', '' );

        if($task=='delete')
        {
            $file = str_replace('/','',$jinput->getString('name', '' ));
         //   echo $file;
            $file = str_replace('..','',$file);
            $file = str_replace('index.','',$file);

            $output_dir=JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR;

            if(file_exists($output_dir.$file))
            {
                unlink($output_dir.$file);
                echo json_encode(['status'=>'Deleted']);
            }
            else
                echo json_encode(['error'=>'File not found.']);
        }
        else
            echo ESFileUploader::uploadFile($fileid);

		die; //to stop rendering template and staff
	}
}

?>
