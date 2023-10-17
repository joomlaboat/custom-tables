<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;

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

$view = common::inputGetCmd('view');
if ($view == 'xml') {
    $file = common::inputGetCmd('xmlfile');

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
