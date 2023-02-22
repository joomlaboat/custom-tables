<?php

// If this file is called directly, abort.
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use \Joomla\CMS\Factory;

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle): bool
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

function CTLoader($inclide_utilities = false, $include_html = false, $PLUGIN_NAME_DIR = null, $componentName = 'com_customtables')
{
    if (defined('_JEXEC')) {
        $params = JComponentHelper::getParams($componentName);
        $loadTwig = $params->get('loadTwig');
    }

    if (defined('_JEXEC'))
        $libraryPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $componentName . DIRECTORY_SEPARATOR . 'libraries';
    else
        $libraryPath = $PLUGIN_NAME_DIR . 'libraries';

    if (!defined('CUSTOMTABLES_LIBRARIES_PATH'))
        define('CUSTOMTABLES_LIBRARIES_PATH', $libraryPath);

    if (!defined('_JEXEC') or ($loadTwig === null or $loadTwig or Factory::getApplication()->getName() == 'administrator') and !class_exists('Twig')) {

        if ($componentName == 'com_customtables') {
            $twig_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'twig' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            require_once($twig_file);
        }
    }


    $path = dirname(__FILE__) . DIRECTORY_SEPARATOR;

    $pathIntegrity = $path . 'integrity' . DIRECTORY_SEPARATOR;

    require_once($pathIntegrity . 'integrity.php');
    require_once($pathIntegrity . 'fields.php');
    require_once($pathIntegrity . 'options.php');
    require_once($pathIntegrity . 'coretables.php');
    require_once($pathIntegrity . 'tables.php');

    $path_helpers = $path . 'helpers' . DIRECTORY_SEPARATOR;

    //require_once($path_helpers.'customtablesmisc.php');
    //require_once($path_helpers.'fields.php');


    require_once($path_helpers . 'imagemethods.php');
    require_once($path_helpers . 'email.php');
    require_once($path_helpers . 'user.php');
    require_once($path_helpers . 'misc.php');
    require_once($path_helpers . 'common.php');
    require_once($path_helpers . 'tables.php');
    require_once($path_helpers . 'compareimages.php');
    require_once($path_helpers . 'findsimilarimage.php');
    //require_once($path_helpers.'layouts.php');
    require_once($path_helpers . 'types.php');

    if ($inclide_utilities) {
        $path_utilities = $path . 'utilities' . DIRECTORY_SEPARATOR;
        require_once($path_utilities . 'importtables.php');
        require_once($path_utilities . 'exporttables.php');
    }


    $pathDataTypes = $path . 'ct' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'ct.php');
    require_once($pathDataTypes . 'environment.php');
    require_once($pathDataTypes . 'params.php');

    $pathDataTypes = $path . 'datatypes' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'datatypes.php');
    require_once($pathDataTypes . 'filebox.php');
    require_once($pathDataTypes . 'filemethods.php');
    require_once($pathDataTypes . 'tree.php');

    $pathDataTypes = $path . 'layouts' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'layouts.php');


    require_once($pathDataTypes . 'twig.php');
    require_once($pathDataTypes . 'general_tags.php');
    require_once($pathDataTypes . 'record_tags.php');
    require_once($pathDataTypes . 'html_tags.php');


    $pathDataTypes = $path . 'logs' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'logs.php');

    $pathDataTypes = $path . 'ordering' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'ordering.php');

    if ($include_html) {
        $pathDataTypes = $path . 'ordering' . DIRECTORY_SEPARATOR;
        require_once($pathDataTypes . 'html.php');
    }

    $pathDataTypes = $path . 'records' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'savefieldqueryset.php');

    //$path_datatypes = $path . 'customphp' . DIRECTORY_SEPARATOR;
    //require_once($path_datatypes.'customphp.php');

    $pathDataTypes = $path . 'table' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'table.php');

    $pathDataTypes = $path . 'html' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'toolbar.php');
    require_once($pathDataTypes . 'forms.php');
    require_once($pathDataTypes . 'inputbox.php');
    require_once($pathDataTypes . 'value.php');

    if (defined('_JEXEC'))
        require_once($pathDataTypes . 'pagination.php');

    $pathDataTypes = $path . 'tables' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'tables.php');

    $pathDataTypes = $path . 'fields' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'fields.php');

    $pathDataTypes = $path . 'languages' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'languages.php');

    $pathDataTypes = $path . 'filter' . DIRECTORY_SEPARATOR;
    require_once($pathDataTypes . 'filtering.php');

    //$path_datatypes = $path . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
    //require_once($path_datatypes.'Logs.php');

    $pathViews = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

    require_once($pathViews . 'edit.php');
    require_once($pathViews . 'catalog.php');
    require_once($pathViews . 'details.php');
}
