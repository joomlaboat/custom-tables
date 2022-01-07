<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controllerform library
jimport('joomla.application.component.controllerform');

class CustomTablesControllerDatabaseCheck extends JControllerForm
{
	protected $task;

	public function __construct($config = array())
	{
		$this->view_list = 'databasecheck'; // safeguard for setting the return view listing to the main view.
		parent::__construct($config);
	}
}
