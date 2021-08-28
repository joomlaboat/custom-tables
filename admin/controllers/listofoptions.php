<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * @package		Joomla
 * @subpackage	List
 */
class CustomTablesControllerListOfOptions extends JControllerAdmin
{


	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFOPTIONS';
	/**
	 * Proxy for getModel.
	 * @since	2.5
	 */
	public function getModel($name = 'Options', $prefix = 'CustomtablesModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}
}

