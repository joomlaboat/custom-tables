<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access

defined('_JEXEC') or die('Restricted access');

$path = JPATH_COMPONENT_SITE . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
require_once($path.'loader.php');
CTLoader($inclide_utilities = false, $include_html = true);

// Require the base controller
require_once JPATH_COMPONENT.DIRECTORY_SEPARATOR.'controller.php';

// Initialize the controller
$controller = new CustomTablesController();
$controller->execute( null );

// Redirect if set by the controller
$controller->redirect();
