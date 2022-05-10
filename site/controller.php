<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CustomTablesController extends JControllerLegacy
{
	function display($cachable = false, $urlparams = array())
	{
		$jinput=JFactory::getApplication()->input;
		if($jinput->getString( 'file' )!='')
		{
			//Load file instead

			$processor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
			require_once($processor_file);

			CT_FieldTypeTag_file::process_file_link($jinput->getString( 'file' ));

			$jinput->set('view', 'files' );
			parent::display();
			return;
		}

		$user = JFactory::getUser();

		// Make sure we have a default view
		if($jinput->getCmd( 'view' )=='')
		{
			$jinput->set('view', 'catalog' );
			parent::display();
		}
		else
		{
			$theview=$jinput->getCmd( 'view' );

			switch($theview)
			{
				case 'log' :
					require_once('controllers/log.php');
					break;

				case 'list' :
					require_once('controllers/list.php');
					break;

				case 'edititem' :
					require_once('controllers/save.php');
					break;

				case ($theview=='home' || $theview=='catalog') :
					require_once('controllers/catalog.php');
					break;

				case 'editphotos' :
					require_once('controllers/editphotos.php');
					break;

				case 'editfiles' :
					require_once('controllers/editfiles.php');
					break;

				case 'structure' :
					parent::display();
					break;

				case 'details' :
					require_once('controllers/details.php');
					break;

				case 'createuser' :
					parent::display();
					break;

				case 'resetuserpassword' :
					parent::display();
					break;

				case 'paypal' :
					parent::display();
					break;

				case 'a2checkout' :
					parent::display();
					break;

				case 'files' :
					parent::display();
					break;

				case 'fileuploader' :
					parent::display();
					break;
			}
		}
	}
}
