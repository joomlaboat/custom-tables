<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.8.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access

defined('_JEXEC') or die('Restricted access');

// Require the base controller
require_once JPATH_COMPONENT.DIRECTORY_SEPARATOR.'controller.php';

// Initialize the controller
$controller = new CustomTablesController();
$controller->execute( null );

// Redirect if set by the controller
$controller->redirect();
