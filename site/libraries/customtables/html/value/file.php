<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Component\ComponentHelper;

class Value_file extends BaseValue
{
    function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
    {
        parent::__construct($ct, $field, $rowValue, $option_list);
    }

    /**
     * @throws Exception
     * @since 3.3.4
     */
    public static function CheckIfFile2download(&$segments, &$vars): bool
    {
        $path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
        require_once($path . 'loader.php');

        $params = ComponentHelper::getParams('com_customtables');
        $loadTwig = $params->get('loadTwig');

        CustomTablesLoader(false, true, null, 'com_customtables', $loadTwig);

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

                    self::process_file_link(end($segments));
                    $vars["listing_id"] = common::inputGetInt("listing_id", 0);
                    $vars['fieldid'] = common::inputGetInt('fieldid', 0);
                    $vars['security'] = common::inputGetCmd('security', 0);//security level letter (d,e,f,g,h,i)
                    $vars['tableid'] = common::inputGetInt('tableid', 0);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @throws Exception
     * @since 3.3.4
     */
    public static function process_file_link($filename): void
    {
        $parts = explode('.', $filename);

        if (count($parts) == 1)
            self::wrong();

        array_splice($parts, count($parts) - 1);
        $filename_without_ext = implode('.', $parts);

        $parts2 = explode('_', $filename_without_ext);
        $key = $parts2[count($parts2) - 1];

        $key_parts = explode('c', $key);

        if (count($key_parts) == 1)
            self::wrong();

        common::inputSet('key', $key);

        $key_params = $key_parts[count($key_parts) - 1];

//TODO: improve it. Get $security from layout, somehow
//security letters tells what method used
        $security = 'd';//Time Limited (8-24 minutes)

        if (str_contains($key_params, 'b')) $security = 'b';//Blob - Not limited
        elseif (str_contains($key_params, 'e')) $security = 'e';//Time Limited (1.5 - 4 hours)
        elseif (str_contains($key_params, 'f')) $security = 'f';//Time/Host Limited (8-24 minutes)
        elseif (str_contains($key_params, 'g')) $security = 'g';//Time/Host Limited (1.5 - 4 hours)
        elseif (str_contains($key_params, 'h')) $security = 'h';//Time/Host/User Limited (8-24 minutes)
        elseif (str_contains($key_params, 'i')) $security = 'i';//Time/Host/User Limited (1.5 - 4 hours)

        common::inputSet('security', $security);

        $key_params_a = explode($security, $key_params);
        if (count($key_params_a) != 2)
            self::wrong();

        $listing_id = $key_params_a[0];
        common::inputSet("listing_id", $listing_id);

        if (isset($key_params_a[1])) {
            $fieldid = $key_params_a[1];
            common::inputSet('fieldid', $fieldid);
        }

        if (isset($key_params_a[2])) {
            $tableid = $key_params_a[2];
            common::inputSet('tableid', $tableid);
        }
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public static function wrong(): bool
    {
        common::enqueueMessage(common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
        return false;
    }

    /**
     * @throws Exception
     * @since 3.3.1
     */
    function render(): ?string
    {
        if (defined('WPINC'))
            return 'CustomTables for WordPress: "file" field type is not available yet.';

        $listing_id = $this->ct->Table->record[$this->ct->Table->realidfieldname] ?? null;

        return self::process($this->rowValue, $this->field, $this->option_list, $listing_id);
    }

    public static function process($filename, Field $field, $option_list, $record_id, $filename_only = false, int $file_size = 0)
    {
        if ($filename == '')
            return '';

        if ($field->type == 'filelink') {
            $FileFolder = FileUtils::getOrCreateDirectoryPath($field->params[0] ?? '');
            $filepath = $FileFolder . '/' . $filename;

        } elseif ($field->type == 'blob')
            $filepath = $filename;
        else {

            $FileFolder = FileUtils::getOrCreateDirectoryPath($field->params[1] ?? '');
            $filepath = $FileFolder . DIRECTORY_SEPARATOR . $filename;
            if ($filepath[0] == DIRECTORY_SEPARATOR)
                $filepath = substr($filepath, 1, strlen($filepath) - 1);

            $full_filepath = CUSTOMTABLES_ABSPATH . $filepath;
            if (file_exists($full_filepath))
                $file_size = filesize($full_filepath);
        }

        if (!isset($option_list[2]))
            $icon_size = '32';
        else
            $icon_size = $option_list[2];

        if ($icon_size != "16" and $icon_size != "32" and $icon_size != "48")
            $icon_size = '32';

        $parts = explode('.', $filename);
        $fileExtension = end($parts);

        $icon_Name = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables'
            . DIRECTORY_SEPARATOR . 'media'
            . DIRECTORY_SEPARATOR . 'images'
            . DIRECTORY_SEPARATOR . 'fileformats'
            . DIRECTORY_SEPARATOR . $icon_size . 'px'
            . DIRECTORY_SEPARATOR . $fileExtension . '.png';

        if (!file_exists($icon_Name))
            $icon = '';
        else
            $icon = CUSTOMTABLES_MEDIA_WEBPATH . 'images/fileformats/' . $icon_size . 'px/' . $fileExtension . '.png';

        $how_to_process = $option_list[0] ?? '';

        if ($record_id === null) {
            $filepath = null;
        } else {
            if ($how_to_process != '') {
                $filepath = self::get_private_file_path($filename, $how_to_process, $filepath, $record_id, $field->id, $field->ct->Table->tableid, $filename_only);
            } elseif ($field->type == 'blob') {
                $how_to_process = 'blob';//Not secure but BLOB
                $filepath = self::get_private_file_path($filename, $how_to_process, $filepath, $record_id, $field->id, $field->ct->Table->tableid, $filename_only);
            } else {

                //Add host name and path to the link
                if ($filepath !== '' and $filepath[0] == '/')
                    $filepath = substr($filepath, 1);

                $Uri_root = common::UriRoot();
                if ($Uri_root != '' and $Uri_root != '/')
                    $filepath = $Uri_root . '/' . $filepath;
                else
                    $filepath = $Uri_root . $filepath;
            }

            if (isset($option_list[3])) {
                if ($option_list[3] == 'savefile') {
                    if (!str_contains($filepath, '?'))
                        $filepath .= '?';
                    else
                        $filepath .= '&';

                    $filepath .= 'savefile=1'; //Will add HTTP Header: @header("Content-Disposition: attachment; filename=\"".$filename."\"");
                }
            }
        }

        $target = '';
        if (isset($option_list[3]) and $option_list[3] == '_blank')
            $target = ' target="_blank"';

        $output_format = '';
        if (isset($option_list[1]))
            $output_format = $option_list[1];

        switch ($output_format) {

            case '':
            case 'link':
                //Link Only
                return $filepath;

            case 'icon-filename-link':
                //Clickable Icon and File Name
                return '<a href="' . $filepath . '"' . $target . '>'
                    . ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : '')
                    . '<span>' . $filename . '</span></a>';

            case 'icon-link':
                //Clickable Icon
                return '<a href="' . $filepath . '"' . $target . '>' . ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : $filename) . '</a>';//show file name if icon not available

            case 'filename-link':
                //Clickable File Name
                return '<a href="' . $filepath . '"' . $target . '>' . $filename . '</a>';

            case 'link-anchor':
                //Clickable Link
                return '<a href="' . $filepath . '"' . $target . '>' . $filepath . '</a>';

            case 'icon':
                return ($icon != '' ? '<img src="' . $icon . '" alt="' . $filename . '" title="' . $filename . '" />' : '');//show nothing is icon not available

            case 'link-to-icon':
                return $icon;//show nothing if icon not available

            case 'filename':
                return $filename;

            case 'extension':
                return $fileExtension;

            case 'file-size':
                return CTMiscHelper::formatSizeUnits($file_size);

            default:
                return $filepath;
        }
    }

    protected static function get_private_file_path(string $rowValue, string $how_to_process, string $filepath, string $recId, int $fieldid, int $tableid, bool $filename_only = false): string
    {
        $security = self::get_security_letter($how_to_process);

        //make the key
        $key = self::makeTheKey($filepath, $security, $recId, $fieldid, $tableid);

        $currentURL = CUSTOMTABLES_MEDIA_HOME_URL . 'index.php?option=com_customtables';

        //$currentURL = CTMiscHelper::deleteURLQueryOption($currentURL, 'returnto');
        //$currentURL = CTMiscHelper::deleteURLQueryOption($currentURL, 'file');

        //prepare new file name that includes the key
        $fna = explode('.', $rowValue);
        $filetype = $fna[count($fna) - 1];
        array_splice($fna, count($fna) - 1);
        $filename = implode('.', $fna);
        $filepath = $filename . '_' . $key . '.' . $filetype;

        if (!$filename_only) {

            $filepath = CUSTOMTABLES_MEDIA_HOME_URL . '/index.php?option=com_customtables&file=' . $filepath;
            /*
            if (str_contains($currentURL, '?')) {
                $filepath = $currentURL . '&file=' . $filepath;
            } else {
                if ($currentURL[strlen($currentURL) - 1] != '/')
                    $filepath = $currentURL . '/' . $filepath;
                else
                    $filepath = $currentURL . $filepath;
            }
            */
        }

        return $filepath;
    }

    static protected function get_security_letter(string $how_to_process): string
    {
        switch ($how_to_process) {

            case 'blob':
                return 'b';

            case 'timelimited':
                return 'd';

            case 'timelimited_longterm':
                return 'e';

            case 'hostlimited':
                return 'f';

            case 'hostlimited_longterm':
                return 'g';

            case 'private':
                return 'h';

            case 'private_longterm':
                return 'i';

            default:
                return '';
        }
    }

    public static function makeTheKey(string $filepath, string $security, string $recId, string $fieldid, string $tableid): string
    {
        $user = new CTUser();
        $username = $user->username;
        $current_user_id = $user->id;

        $t = time();
        //prepare augmented timer
        $secs = 1000;
        if ($security == 'e' or $security == 'g' or $security == 'i')
            $secs = 10000;

        $timePlus = floor(($t + $secs) / $secs) * $secs;
        $ip = common::getServerParam('REMOTE_ADDR');

        //get secs key char
        $sep = $security;//($secs==1000 ? 'a' : 'b');
        $m2 = 'c' . $recId . $sep . $fieldid . $sep . $tableid;

        $m = '';

        //calculate MD5
        if ($security == 'd' or $security == 'e')
            $m = md5($filepath . $timePlus);
        elseif ($security == 'f' or $security == 'g')
            $m = md5($filepath . $timePlus . $ip);
        elseif ($security == 'h' or $security == 'i')
            $m = md5($filepath . $timePlus . $ip . $username . $current_user_id);

        //replace rear part of the hash
        $m3 = substr($m, 0, strlen($m) - strlen($m2));
        return $m3 . $m2;
    }
}


