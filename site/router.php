<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;

class CustomTablesRouter extends JComponentRouterView
{
    public function __construct($app = null, $menu = null)
    {
        parent::__construct($app, $menu);
    }

    public function build(&$query): array
    {
        $segments = [];
        if (isset($query['alias'])) {
            $segments[] = $query['alias'];
            unset($query['alias']);
        }
        return $segments;
    }

    public function parse(&$segments): array
    {
        $vars = [];

        //Check if it's a file to download
        if (CustomTablesRouter::CheckIfFile2download($segments, $vars)) {
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

    protected function CheckIfFile2download(&$segments, &$vars): bool
    {
        $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
        require_once($path . 'loader.php');
        CTLoader();

        if (str_contains(end($segments), '.')) {

            //could be a file
            $parts = explode('.', end($segments));
            if (count($parts) >= 2 and strlen($parts[0]) > 0 and strlen($parts[1]) > 0) {

                //probably a file
                $allowedExtensions = explode(' ', 'bin gslides doc docx pdf rtf txt xls xlsx psd ppt pptx mp3 wav ogg jpg bmp ico odg odp ods swf xcf jpeg png gif webp svg ai aac m4a wma flv mpg wmv mov flac txt avi csv accdb zip pages');
                $ext = end($parts);
                if (in_array($ext, $allowedExtensions)) {
                    $vars['view'] = 'files';
                    $vars['key'] = $segments[0];

                    $processor_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
                    require_once($processor_file);

                    CT_FieldTypeTag_file::process_file_link(end($segments));

                    $jinput = Factory::getApplication()->input;
                    $vars["listing_id"] = $jinput->getInt("listing_id", 0);
                    $vars['fieldid'] = $jinput->getInt('fieldid', 0);
                    $vars['security'] = $jinput->getCmd('security', 0);//security level letter (d,e,f,g,h,i)
                    $vars['tableid'] = $jinput->getInt('tableid', 0);

                    return true;
                }
            }
        }
        return false;
    }
}
