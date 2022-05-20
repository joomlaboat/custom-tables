<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage customtables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_customtables')) {
    Factory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
};

$path = JPATH_COMPONENT_SITE . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
require_once($path . 'loader.php');
CTLoader($inclide_utilities = true);

// require helper files
JLoader::register('CustomtablesHelper', dirname(__FILE__) . '/helpers/customtables.php');
JLoader::register('JHtmlBatch_', dirname(__FILE__) . '/helpers/html/batch_.php');

// import joomla controller library
jimport('joomla.application.component.controller');

// Get an instance of the controller prefixed by Customtables
$controller = JControllerLegacy::getInstance('Customtables');

// Perform the Request task
$controller->execute(Factory::getApplication()->input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
