<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com 
/-------------------------------------------------------------------------------------------------------/

	@version		1.8.1
	@build			1st July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		listofrecords.php
	@author			Ivan Komlev <https://joomlaboat.com>	
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

/**
 * Listoffields Controller
 */
class CustomtablesControllerListofRecords extends JControllerAdmin
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFRECORDS';
	/**
	 * Proxy for getModel.
	 * @since	2.5
	 */
	public function getModel($name = 'Records', $prefix = 'CustomtablesModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		
		return $model;
	}  
}
