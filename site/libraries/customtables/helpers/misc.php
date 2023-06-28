<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;
use CustomTables\Fields;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

class JoomlaBasicMisc
{
    static public function array_insert(array &$array, $insert, $position = -1)
    {
        $tmp = [];
        $position = ($position == -1) ? (count($array)) : $position;
        if ($position != (count($array))) {
            $ta = $array;
            for ($i = $position; $i < (count($array)); $i++) {
                if (!isset($array[$i]))
                    die("Invalid array: All keys must be numerical and in sequence.");

                $tmp[$i + 1] = $array[$i];
                unset($ta[$i]);
            }

            $ta[$position] = $insert;
            $array = $ta + $tmp;
        } else
            $array[$position] = $insert;

        ksort($array);
        return true;
    }

    //https://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size
    public static function file_upload_max_size(int $custom_max_size = 20000000): int
    {
        $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = JoomlaBasicMisc::parse_size(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = JoomlaBasicMisc::parse_size(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }

        if ($custom_max_size != 0 and $custom_max_size < $max_size)
            $max_size = $custom_max_size;

        return $max_size;
    }

    protected static function parse_size(string $size): int
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^\d\.]/', '', $size); // Remove the non-numeric characters from the size.
        //$size = preg_replace('/[^\d.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else
            return round($size);
    }

    // Snippet from PHP Share: https://www.phpshare.org
    public static function formatSizeUnits(int $bytes): string
    {
        if ($bytes >= 1073741824)
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        elseif ($bytes >= 1048576)
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        elseif ($bytes >= 1024)
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        elseif ($bytes > 1)
            $bytes = $bytes . ' bytes';
        elseif ($bytes == 1)
            $bytes = $bytes . ' byte';
        else
            $bytes = '0 bytes';

        return $bytes;
    }

    public static function deleteURLQueryOption(string $urlString, string $opt_): string
    {
        $urlString = str_replace('&amp;', '&', $urlString);
        $link = '';
        $newQuery = array();
        $opt = $opt_ . '=';

        $parts = explode('?', $urlString);

        if (isset($parts[1])) {
            $link = $parts[0];
            $query = explode('&', $parts[1]);
        } else
            $query = explode('&', $parts[0]);

        foreach ($query as $q) {
            if (!str_contains($q, $opt))
                $newQuery[] = $q;
        }

        if (count($newQuery) == 0)
            return $link;

        if ($link == '')
            return implode('&', $newQuery);

        return $link . '?' . implode('&', $newQuery);
    }

    public static function curPageURL(): string
    {
        $WebsiteRoot = str_replace(JURI::root(true), '', JURI::root(false));
        $RequestURL = $_SERVER["REQUEST_URI"];

        if ($WebsiteRoot != '' and $WebsiteRoot[strlen($WebsiteRoot) - 1] == '/') {
            if ($RequestURL != '' and $RequestURL[0] == '/') {
                //Delete $WebsiteRoot end /
                $WebsiteRoot = substr($WebsiteRoot, 0, strlen($WebsiteRoot) - 1);
            }
        }

        return $WebsiteRoot . $RequestURL;
    }

    public static function getFirstImage(?string $content): string
    {
        if ($content === null)
            return "";

        preg_match_all('/<img[^>]+>/i', $content, $result);
        if (count($result[0]) == 0)
            return '';

        $img_tag = $result[0][0];

        $img = array();
        preg_match_all('/(src|alt)=("[^"]*")/i', $img_tag, $img, PREG_SET_ORDER);

        $image = JoomlaBasicMisc::getSrcParam($img);

        if ($image == '') {
            $img = array();
            preg_match_all("/(src|alt)=('[^']*')/i", $img_tag, $img, PREG_SET_ORDER);
            $image = JoomlaBasicMisc::getSrcParam($img);

            if ($image == '')
                return '';

            $image = str_replace("'", '', $image);
        } else
            $image = str_replace('"', '', $image);

        return $image;
    }

    public static function getSrcParam($img)
    {
        foreach ($img as $i) {
            if ($i[1] == 'src' or $i[1] == 'SRC')
                return $i[2];
        }
        return null;
    }

    public static function charsTrimText($text, $count, $cleanBraces = false, $cleanQuotes = false): string
    {
        if ($count == 0)
            return "";

        $desc = strip_tags($text);
        $desc = trim($desc);
        $desc = str_replace("/n", "", $desc);
        $desc = str_replace("/r", "", $desc);

        if (strlen($desc) > $count and $count != 1)
            $desc = substr($desc, 0, $count);

        if ($cleanBraces)
            $desc = preg_replace('!{.*?}!s', '', $desc);

        if ($cleanQuotes) {
            $desc = str_replace('"', '', $desc);
            $desc = str_replace('\'', '', $desc);
        }
        return trim($desc);
    }

    public static function wordsTrimText(?string $text, int $count, bool $cleanBraces = false, bool $cleanQuotes = false): string
    {
        if ($text === null or $count == 0)
            return "";

        $desc = strip_tags($text);
        $matches = [];

        if ($count != 1)
            preg_match('/(\S*(?>\\s+|$)){0,' . $count . '}/', $desc, $matches);

        $desc = trim($matches[0]);
        $desc = str_replace("/n", "", $desc);
        $desc = str_replace("/r", "", $desc);
        //$desc = str_replace("+", "&plus;", $desc);

        if ($cleanBraces)
            $desc = preg_replace('!{.*?}!s', '', $desc);

        if ($cleanQuotes) {
            $desc = str_replace('"', '', $desc);
            $desc = str_replace('\'', '', $desc);
        }
        return trim(preg_replace('/\s\s+/', ' ', $desc));
    }

    public static function colorStringValueToCSSRGB($value): string
    {
        $colors = [];

        if (strlen($value) >= 6) {
            $colors[] = hexdec(substr($value, 0, 2));
            $colors[] = hexdec(substr($value, 2, 2));
            $colors[] = hexdec(substr($value, 4, 2));
        }

        if (strlen($value) == 8) {
            $a = hexdec(substr($value, 6, 2));
            $colors[] = round($a / 255, 2);
        }

        if (strlen($value) == 8)
            return 'rgba(' . implode(',', $colors) . ')';
        else
            return 'rgb(' . implode(',', $colors) . ')';
    }

    public static function getListToReplace(string $par, array &$options, string $text, string $qtype, string $separator = ':', string $quote_char = '"'): array
    {
        $fList = array();
        $l = strlen($par) + 2;

        $offset = 0;
        while (1) {
            if ($offset >= strlen($text))
                break;

            $ps = strpos($text, $qtype[0] . $par . $separator, $offset);
            if ($ps === false)
                break;


            if ($ps + $l >= strlen($text))
                break;

            $quote_open = false;

            $ps1 = $ps + $l;
            $count = 0;
            while (1) {

                $count++;
                if ($count > 1000) {
                    Factory::getApplication()->enqueueMessage('Quote count > 1000', 'error');
                    return [];
                }

                if ($quote_char == '')
                    $peq = false;
                else {
                    while (1) {
                        $peq = strpos($text, $quote_char, $ps1);

                        if ($peq > 0 and $text[$peq - 1] == '\\') {
                            // ignore quote in this case
                            $ps1++;

                        } else
                            break;
                    }
                }

                $pe = strpos($text, $qtype[1], $ps1);

                if ($pe === false)
                    break;

                if ($peq !== false and $peq < $pe) {
                    //quote before the end character

                    if (!$quote_open)
                        $quote_open = true;
                    else
                        $quote_open = false;

                    $ps1 = $peq + 1;
                } else {
                    if (!$quote_open)
                        break;

                    $ps1 = $pe + 1;

                }
            }

            if ($pe === false)
                break;

            $notestr = substr($text, $ps, $pe - $ps + 1);

            $options[] = trim(substr($text, $ps + $l, $pe - $ps - $l));
            $fList[] = $notestr;

            $offset = $ps + $l;
        }

        //for these with no parameters
        $ps = strpos($text, $qtype[0] . $par . $qtype[1]);
        if (!($ps === false)) {
            $options[] = '';
            $fList[] = $qtype[0] . $par . $qtype[1];
        }

        return $fList;
    }

    public static function getListToReplaceAdvanced($beginning_tag, $ending_tag, &$options, $text, $sub_beginning_tag = ''): array
    {
        $fList = array();
        $l = strlen($beginning_tag);//+1;

        $skip_count = 0;

        $offset = 0;
        do {
            if ($offset >= strlen($text))
                break;

            $ps = strpos($text, $beginning_tag, $offset);
            if ($ps === false)
                break;

            if ($ps + $l >= strlen($text))
                break;

            $quote_open = false;

            $ps1 = $ps + $l;
            $count = 0;
            while (1) {

                $count++;
                if ($count > 1000) {
                    Factory::getApplication()->enqueueMessage('Too many quotes.', 'error');
                    return [];
                }

                $peq = strpos($text, '"', $ps1);
                $pe = strpos($text, $ending_tag, $ps1);

                if (!$quote_open and $sub_beginning_tag != '')// this part to all sub-entries, example:  {if:[a]=1} Hello {if:[b]=1} Sir {endif}. How do you do?{endif}
                {
                    $sub_bt = strpos($text, $sub_beginning_tag, $ps1);
                    if ($sub_bt !== false and $sub_bt < $pe and ($peq === false or $peq > $sub_bt))
                        $skip_count++;//sub entry found. Increase skip count
                }

                if ($pe === false)
                    break;

                if ($peq !== false and $peq < $pe) {
                    //quote before the end character

                    if (!$quote_open)
                        $quote_open = true;
                    else
                        $quote_open = false;

                    $ps1 = $peq + 1;
                } else {
                    if (!$quote_open) {
                        if ($skip_count == 0)//this is to skip sub entries
                            break;

                        $skip_count -= 1;
                    }
                    $ps1 = $pe + 1;
                }
            }

            if ($pe === false)
                break;

            $notestr = substr($text, $ps, $pe - $ps + strlen($ending_tag));
            $options[] = substr($text, $ps + $l, $pe - $ps - $l);
            $fList[] = $notestr;

            $offset = $ps + $l;

        } while (!($pe === false));

        //for these with no parameters
        $ps = strpos($text, $beginning_tag . $ending_tag);
        if (!($ps === false)) {
            $options[] = '';
            $fList[] = $beginning_tag . $ending_tag;
        }
        return $fList;
    }

    public static function getMenuParams($Itemid, $rawParams = '')
    {
        if ($rawParams == '') {
            $db = Factory::getDBO();
            $query = 'SELECT params FROM #__menu WHERE id=' . (int)$Itemid . ' LIMIT 1';
            $db->setQuery($query);
            $rows = $db->loadObjectList();

            if (count($rows) == 0)
                return '';

            $row = $rows[0];
            $rawParams = $row->params;
        }
        return json_decode($rawParams);
    }

    public static function processValue($field, &$ct, $row)
    {
        $p = strpos($field, '->');
        if (!($p === false)) {
            $field = substr($field, 0, $p);
        }

        //get options
        $options = '';
        $p = strpos($field, '(');

        if ($p !== false) {
            $e = strpos($field, '(', $p);
            if ($e === false)
                return 'syntax error';

            $options = substr($field, $p + 1, $e - $p - 1);
            $field = substr($field, 0, $p);
        }

        //getting filed row (we need field typeparams, to render formatted value)
        if ($field == '_id' or $field == '_published') {
            $htmlresult = $row[str_replace('_', '', $field)];
        } else {
            $fieldrow = Fields::FieldRowByName($field, $ct->Table->fields);
            if (!is_null($fieldrow)) {

                $options_list = explode(',', $options);

                $v = tagProcessor_Value::getValueByType($ct,
                    $fieldrow,
                    $row,
                    $options_list,
                );

                $htmlresult = $v;
            } else {
                $htmlresult = 'Field "' . $field . '" not found.';
            }
        }
        return $htmlresult;
    }

    public static function getGroupIdByTitle($grouptitle): string
    {
        $db = Factory::getDbo();

        // Build the database query to get the rules for the asset.
        $query = 'SELECT id FROM #__usergroups WHERE title=' . $db->quote(trim($grouptitle)) . ' LIMIT 1';

        // Execute the query and load the rules from the result.

        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) < 1)
            return '';

        return $rows[0]->id;
    }

    //-- only for "records" field type;
    public static function makeNewFileName(string $filename, string $format): string
    {
        //Use translation if needed
        $parts = explode('.', $filename);
        $filename_array = array();

        $filename_array[] = JoomlaBasicMisc::JTextExtended($parts[0]);
        if (count($parts) > 1) {
            for ($i = 1; $i < count($parts); $i++)
                $filename_array[] = $parts[$i];
        }

        $filename = implode('.', $filename_array);

        // Remove anything which isn't a word, whitespace, number
        // or any of the following characters -_~,;[]().
        // If you don't need to handle multibyte characters
        // you can use preg_replace rather than mb_ereg_replace
        // Thanks @Łukasz Rysiak!
        if (function_exists('mb_ereg_replace')) {
            $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
            // Remove any runs of periods (thanks falstro!)
            $filename = mb_ereg_replace("([\.]{2,})", '', $filename);
        } else {
            //$filename = preg_replace("([^\w\s\d\-_~,;\[\]\().])", '', $filename);

            $filename = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
            // Remove any runs of periods (thanks falstro!)
            $filename = preg_replace("(\.{2,})", '', $filename);
            //$filename = preg_replace("([.]{2,})", '', $filename);
        }

        if ($format != '')
            $filename .= '.' . $format;

        return $filename;
    }

    public static function JTextExtended(string $text, int $value = null): string
    {
        return common::translate($text, $value);
    }

    public static function strip_tags_content($text, $tags = '', $invert = FALSE)
    {
        //$tags - list of tags. Example: <b><span>

        preg_match_all('/<(.+?)\s*\/?\s*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);

        if (count($tags) > 0) {
            //if (!$invert) {
            if (!$invert) {
                return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
            } else {
                return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
            }

        } elseif (!$invert) {
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        }
        return $text;
        /*
         *Usage example:
    $text = '<b>example</b> text with <div>tags</div>';

    Result for strip_tags($text):
    example text with tags

    Result for strip_tags_content($text):
    text with

    Result for strip_tags_content($text, '<b>'):
    <b>example</b> text with

    Result for strip_tags_content($text, '<b>', TRUE);
    text with <div>tags</div>
         */
    }

    public static function slugify($text): string
    {
        //or use
        //JFilterOutput::stringURLSafe($this->alias);

        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // transliterate
        //$text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        if (function_exists('iconv'))
            $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        //$text = iconv('utf-8', 'us-ascii//IGNORE//TRANSLIT', $text);
        //$text = iconv('utf-8', 'ISO-8859-1//TRANSLIT', $text);

        $text = trim($text, '-');

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text))
            return '';

        return $text;
    }

    public static function FindItemidbyAlias($alias)
    {
        $db = Factory::getDBO();
        $query = 'SELECT id FROM #__menu WHERE published=1 AND alias=' . $db->Quote($alias);

        $db->setQuery($query);
        $recs = $db->loadAssocList();
        if (!$recs) return 0;
        if (count($recs) < 1) return 0;

        $r = $recs[0];
        return $r['id'];
    }

    public static function FindMenuItemRowByAlias($alias)
    {
        $db = Factory::getDBO();
        $query = 'SELECT * FROM #__menu WHERE published=1 AND alias=' . $db->Quote($alias);

        $db->setQuery($query);

        $recs = $db->loadAssocList();
        if (!$recs) return 0;
        if (count($recs) < 1) return 0;

        return $recs[0];
    }

    public static function checkUserGroupAccess($thegroup = 0): bool
    {
        if ($thegroup == 0)
            return false;

        $user = Factory::getUser();
        $isAdmin = $user->get('isRoot');
        if ($isAdmin)
            return true;

        $usergroups = JAccess::getGroupsByUser($user->id);

        if (in_array($thegroup, $usergroups))
            return true;

        return false;
    }

    public static function applyContentPlugins(string &$htmlresult): string
    {
        $version_object = new Version;
        $version = (int)$version_object->getShortVersion();

        $mainframe = Factory::getApplication();

        if (method_exists($mainframe, 'getParams')) {
            $myDoc = Factory::getDocument();
            $pageTitle = $myDoc->getTitle(); //because content plugins may overwrite the title

            $content_params = $mainframe->getParams('com_content');

            $o = new stdClass();
            $o->text = $htmlresult;
            $o->created_by_alias = 0;

            JPluginHelper::importPlugin('content');

            if ($version < 4) {
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onContentPrepare', array('com_content.article', &$o, &$content_params, 0));
            } else
                Factory::getApplication()->triggerEvent('onContentPrepare', array('com_content.article', &$o, &$content_params, 0));

            $htmlresult = $o->text;
            $myDoc->setTitle(JoomlaBasicMisc::JTextExtended($pageTitle)); //because content plugins may overwrite the title
        }
        return $htmlresult;
    }

    public static function suggest_TempFileName(&$webFileLink, ?string $fileExtension = null): ?string
    {
        $tempDir = 'tmp';
        
        if ($tempDir[0] != '/' and $tempDir[0] != '\\') {
            $output_dir = JPATH_SITE . DIRECTORY_SEPARATOR . $tempDir . DIRECTORY_SEPARATOR;
            $webDir = DIRECTORY_SEPARATOR . $tempDir . DIRECTORY_SEPARATOR;
        } else {
            $output_dir = JPATH_SITE . $tempDir . DIRECTORY_SEPARATOR;
            $webDir = DIRECTORY_SEPARATOR . substr($tempDir, 1) . DIRECTORY_SEPARATOR;
        }

        $random_name = JoomlaBasicMisc::generateRandomString();

        while (1) {

            $fileName = $random_name . ($fileExtension !== null ? '.' . $fileExtension : '');
            $file = $output_dir . $fileName;
            if (!file_exists($file)) {
                $webFileLink = $webDir . $fileName;
                return $file;
            }
        }
        return null;
    }

    public static function generateRandomString(int $length = 32): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++)
            $randomString .= $characters[rand(0, $charactersLength - 1)];

        return $randomString;
    }

    public static function getHTMLTagParameters($tag): array
    {
        $params = JoomlaBasicMisc::csv_explode(' ', $tag, '"', true);
        $result = [];
        foreach ($params as $param) {
            $param = JoomlaBasicMisc::csv_explode('=', $param, '"', false);
            if (count($param) == 2) {
                $result[strtolower($param[0])] = $param[1];
            }
        }
        return $result;
    }

    public static function csv_explode(string $separator, string $string, string $enclosure = '"', bool $preserve = false): array
    {
        if (!$preserve and strlen($separator) == 1)
            return str_getcsv($string, $separator, $enclosure, "\\");

        $resArr = array();
        $n = 0;
        $expEncArr = explode($enclosure, $string);
        foreach ($expEncArr as $EncItem) {
            if ($n++ % 2) {
                $resArr[] = array_pop($resArr) . ($preserve ? $enclosure : '') . $EncItem . ($preserve ? $enclosure : '');
            } else {
                $expDelArr = explode($separator, $EncItem);
                $resArr[] = array_pop($resArr) . array_shift($expDelArr);
                $resArr = array_merge($resArr, $expDelArr);
            }
        }
        return $resArr;
    }

    static public function getXMLData(string $file, string $path = '')
    {
        if ($path == '')
            $path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
                . 'media' . DIRECTORY_SEPARATOR . 'xml';

        $xml_content = file_get_contents($path . DIRECTORY_SEPARATOR . $file);

        if ($xml_content != '') {
            $xml = simplexml_load_string($xml_content) or die('Cannot load or parse "' . $file . '" file.');
            return $xml;
        }
        return '';
    }

    static public function mime2ext($mime)
    {
        $mime_map = [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'application/x-compressed' => '7zip',
            'audio/x-acc' => 'aac',
            'audio/ac3' => 'ac3',
            'application/postscript' => 'ai',
            'audio/x-aiff' => 'aif',
            'audio/aiff' => 'aif',
            'audio/x-au' => 'au',
            'video/x-msvideo' => 'avi',
            'video/msvideo' => 'avi',
            'video/avi' => 'avi',
            'application/x-troff-msvideo' => 'avi',
            'application/macbinary' => 'bin',
            'application/mac-binary' => 'bin',
            'application/x-binary' => 'bin',
            'application/x-macbinary' => 'bin',
            'image/bmp' => 'bmp',
            'image/x-bmp' => 'bmp',
            'image/x-bitmap' => 'bmp',
            'image/x-xbitmap' => 'bmp',
            'image/x-win-bitmap' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'application/bmp' => 'bmp',
            'application/x-bmp' => 'bmp',
            'application/x-win-bitmap' => 'bmp',
            'application/cdr' => 'cdr',
            'application/coreldraw' => 'cdr',
            'application/x-cdr' => 'cdr',
            'application/x-coreldraw' => 'cdr',
            'image/cdr' => 'cdr',
            'image/x-cdr' => 'cdr',
            'zz-application/zz-winassoc-cdr' => 'cdr',
            'application/mac-compactpro' => 'cpt',
            'application/pkix-crl' => 'crl',
            'application/pkcs-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'application/pkix-cert' => 'crt',
            'text/css' => 'css',
            'text/x-comma-separated-values' => 'csv',
            'text/comma-separated-values' => 'csv',
            'application/vnd.msexcel' => 'csv',
            'application/x-director' => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'message/rfc822' => 'eml',
            'application/x-msdownload' => 'exe',
            'video/x-f4v' => 'f4v',
            'audio/x-flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gpg-keys' => 'gpg',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gzip',
            'application/mac-binhex40' => 'hqx',
            'application/mac-binhex' => 'hqx',
            'application/x-binhex40' => 'hqx',
            'application/x-mac-binhex40' => 'hqx',
            'text/html' => 'html',
            'image/x-icon' => 'ico',
            'image/x-ico' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'application/x-java-application' => 'jar',
            'application/x-jar' => 'jar',
            'image/jp2' => 'jp2',
            'video/mj2' => 'jp2',
            'image/jpx' => 'jp2',
            'image/jpm' => 'jp2',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/vnd.google-earth.kml+xml' => 'kml',
            'application/vnd.google-earth.kmz' => 'kmz',
            'text/x-log' => 'log',
            'audio/x-m4a' => 'm4a',
            'audio/mp4' => 'm4a',
            'application/vnd.mpegurl' => 'm4u',
            'audio/midi' => 'mid',
            'application/vnd.mif' => 'mif',
            'video/quicktime' => 'mov',
            'video/x-sgi-movie' => 'movie',
            'audio/mpeg' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'application/oda' => 'oda',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'font/otf' => 'otf',
            'application/x-pkcs10' => 'p10',
            'application/pkcs10' => 'p10',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs7-signature' => 'p7a',
            'application/pkcs7-mime' => 'p7c',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/pkcs7-signature' => 'p7s',
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'pdf',
            'application/x-x509-user-cert' => 'pem',
            'application/x-pem-file' => 'pem',
            'application/pgp' => 'pgp',
            'application/x-httpd-php' => 'php',
            'application/php' => 'php',
            'application/x-php' => 'php',
            'text/php' => 'php',
            'text/x-php' => 'php',
            'application/x-httpd-php-source' => 'php',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'application/powerpoint' => 'ppt',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-office' => 'ppt',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop' => 'psd',
            'image/vnd.adobe.photoshop' => 'psd',
            'audio/x-realaudio' => 'ra',
            'audio/x-pn-realaudio' => 'ram',
            'application/x-rar' => 'rar',
            'application/rar' => 'rar',
            'application/x-rar-compressed' => 'rar',
            'audio/x-pn-realaudio-plugin' => 'rpm',
            'application/x-pkcs7' => 'rsa',
            'text/rtf' => 'rtf',
            'text/richtext' => 'rtx',
            'video/vnd.rn-realvideo' => 'rv',
            'application/x-stuffit' => 'sit',
            'application/smil' => 'smil',
            'text/srt' => 'srt',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/x-gzip-compressed' => 'tgz',
            'image/tiff' => 'tiff',
            'font/ttf' => 'ttf',
            'text/plain' => 'txt',
            'text/x-vcard' => 'vcf',
            'application/videolan' => 'vlc',
            'text/vtt' => 'vtt',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'application/wbxml' => 'wbxml',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'audio/x-ms-wma' => 'wma',
            'application/wmlc' => 'wmlc',
            'video/x-ms-wmv' => 'wmv',
            'video/x-ms-asf' => 'wmv',
            'font/woff' => 'woff',
            'font/woff2' => 'woff2',
            'application/xhtml+xml' => 'xhtml',
            'application/excel' => 'xl',
            'application/msexcel' => 'xls',
            'application/x-msexcel' => 'xls',
            'application/x-ms-excel' => 'xls',
            'application/x-excel' => 'xls',
            'application/x-dos_ms_excel' => 'xls',
            'application/xls' => 'xls',
            'application/x-xls' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xlsx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/xsl' => 'xsl',
            'application/xspf+xml' => 'xspf',
            'application/x-compress' => 'z',
            'application/x-zip' => 'zip',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/s-compressed' => 'zip',
            'multipart/x-zip' => 'zip',
            'text/x-scriptzsh' => 'zsh',
        ];
        return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
    }

    public static function deleteFolderIfEmpty($folder)
    {
        //Check if the folder is already empty, if it is empty then delete the folder
        $files = scandir(JPATH_SITE . DIRECTORY_SEPARATOR . $folder);
        $count = 0;
        foreach ($files as $file) {
            if ($file != '.' and $file != '..')
                $count += 1;
        }

        if ($count == 0)
            rmdir(JPATH_SITE . DIRECTORY_SEPARATOR . $folder);
    }

    public static function parse_query($var): array
    {
        $arr = array();

        $var = parse_url($var);
        $varQuery = $var['query'] ?? '';

        if ($varQuery == '')
            return $arr;

        $var = html_entity_decode($varQuery);
        $var = explode('&', $var);

        foreach ($var as $val) {
            $x = explode('=', $val);
            $arr[$x[0]] = $x[1];
        }
        unset($val, $x, $var);
        return $arr;
    }
}