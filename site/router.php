<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\Value_file;
use Joomla\CMS\Component\Router\RouterInterface;

class CustomTablesRouter implements RouterInterface
{
    public function build(&$query): array
    {
        $segments = [];
        if (isset($query['alias'])) {
            $segments[] = $query['alias'];
            unset($query['alias']);
        }
        return $segments;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public function parse(&$segments): array
    {
        $vars = [];

        //Check if it's a file to download

        $libraryPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries';
        if (!defined('CUSTOMTABLES_LIBRARIES_PATH'))
            define('CUSTOMTABLES_LIBRARIES_PATH', $libraryPath);

        $processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
            . DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php';
        require_once($processor_file);

        $fileOutput = new Value_file();
        
        if ($fileOutput->CheckIfFile2download($segments, $vars)) {
            //rerouted
            $vars['option'] = 'com_customtables';
            $segments[0] = null;
            return $vars;
        }

        if (isset($segments[0])) {

            $vars['option'] = 'com_customtables';
            $vars['view'] = 'details';
            $vars['alias'] = $segments[0];
            $segments[0] = null;
        }
        return $vars;
    }

    public function preprocess($query): array
    {
        return $query;
    }
}
