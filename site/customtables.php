<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;

$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
require_once($path . 'loader.php');
CTLoader(false, $include_html = true);

// Require the base controller
require_once JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'controller.php';

// Initialize the controller
$controller = new CustomTablesController();
try {
    $controller->execute(null);
} catch (Exception $e) {
}

// Redirect if set by the controller

$jinput = Factory::getApplication()->input;
$view = $jinput->getCmd('view');
if ($view == 'xml') {
    $file = $jinput->getCmd('xmlfile');

    $xml = 'unknown file';
    if ($file == 'tags')

        $xml = file_get_contents(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
            . 'media' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $file . '.xml');

    elseif ($file == 'fieldtypes')
        $xml = file_get_contents(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
            . 'media' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $file . '.xml');

    echo $xml;
    die;
}

$controller->redirect();
