<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage customtables.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_customtables'))
{
	JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
};

// Load cms libraries
JLoader::registerPrefix('J', JPATH_PLATFORM . '/cms');
// Load joomla libraries without overwrite
JLoader::registerPrefix('J', JPATH_PLATFORM . '/joomla',false);

// Add CSS file for all pages
$document = JFactory::getDocument();
$document->addStyleSheet(JURI::root(true).'/administrator/components/com_customtables/assets/css/admin.css');
$document->addScript(JURI::root(true).'/administrator/components/com_customtables/assets/js/admin.js');

// require helper files
JLoader::register('CustomtablesHelper', dirname(__FILE__) . '/helpers/customtables.php');
JLoader::register('JHtmlBatch_', dirname(__FILE__) . '/helpers/html/batch_.php');

// import joomla controller library
jimport('joomla.application.component.controller');

// Get an instance of the controller prefixed by Customtables
$controller = JControllerLegacy::getInstance('Customtables');

// Perform the Request task
//echo 'task='.JFactory::getApplication()->input->getCmd('task').'<br/>';
//die;
$controller->execute(JFactory::getApplication()->input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
