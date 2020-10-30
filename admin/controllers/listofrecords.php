<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

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
		
		//$app = JFactory::getApplication();
		//$tableid 	= $app->input->get('tableid', 0, 'int');
		
		//$model->setState('filter.tableid', $tableid);
		
		return $model;
	}

	
}
