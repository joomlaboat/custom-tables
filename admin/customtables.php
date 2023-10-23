<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @subpackage customtables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_customtables')) {
    Factory::getApplication()->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
};

$path = JPATH_COMPONENT_SITE . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
require_once($path . 'loader.php');
CTLoader($include_utilities = true);

// require helper files
JLoader::register('CustomtablesHelper', dirname(__FILE__) . '/helpers/customtables.php');
JLoader::register('JHtmlBatch_', dirname(__FILE__) . '/helpers/html/batch_.php');

// import joomla controller library
jimport('joomla.application.component.controller');

// Get an instance of the controller prefixed by Customtables
$controller = JControllerLegacy::getInstance('Customtables');

// Perform the Request task
$controller->execute(common::inputGetCmd('task'));

// Redirect if set by the controller
$controller->redirect();
