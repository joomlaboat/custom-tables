<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

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
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        //$size = preg_replace('/[^\d.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else
            return round($size);
    }

    // Snippet from PHP Share: http://www.phpshare.org
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

    public static function deleteURLQueryOption(string $urlstr, string $opt_): string
    {
        $urlstr = str_replace('&amp;', '&', $urlstr);
        $link = '';
        $newquery = array();
        $opt = $opt_ . '=';

        $parts = explode('?', $urlstr);

        if (isset($parts[1])) {
            $link = $parts[0];
            $query = explode('&', $parts[1]);
        } else
            $query = explode('&', $parts[0]);

        foreach ($query as $q) {
            if (!str_contains($q, $opt))
                $newquery[] = $q;
        }

        if (count($newquery) == 0)
            return $link;

        if ($link == '')
            return implode('&', $newquery);

        return $link . '?' . implode('&', $newquery);
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

    public static function chars_trimtext($text, $count, $cleanbraces = false, $cleanquotes = false): string
    {
        if ($count == 0)
            return "";

        $desc = strip_tags($text);
        $desc = trim($desc);
        $desc = str_replace("/n", "", $desc);
        $desc = str_replace("/r", "", $desc);

        if (strlen($desc) > $count and $count != 1)
            $desc = substr($desc, 0, $count);

        if ($cleanbraces)
            $desc = preg_replace('!{.*?}!s', '', $desc);

        if ($cleanquotes) {
            $desc = str_replace('"', '', $desc);
            $desc = str_replace('\'', '', $desc);
        }

        return trim($desc);
    }

    public static function words_trimtext(?string $text, int $count, bool $cleanbraces = false, bool $cleanquotes = false): string
    {
        if ($text === null or $count == 0)
            return "";

        $desc = strip_tags($text);

        $matches = [];

        if ($count != 1)
            preg_match('/([^\\s]*(?>\\s+|$)){0,' . $count . '}/', $desc, $matches);

        $desc = trim($matches[0]);
        $desc = str_replace("/n", "", $desc);
        $desc = str_replace("/r", "", $desc);
        $desc = str_replace("+", "_", $desc);

        if ($cleanbraces)
            $desc = preg_replace('!{.*?}!s', '', $desc);

        if ($cleanquotes) {
            $desc = str_replace('"', '', $desc);
            $desc = str_replace('\'', '', $desc);
        }

        return trim(preg_replace('/\s\s+/', ' ', $desc));
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

    public static function getListToReplaceAdvanced($begining_tag, $ending_tag, &$options, &$text, $sub_begining_tag = ''): array
    {
        $fList = array();
        $l = strlen($begining_tag);//+1;

        $skip_count = 0;

        $offset = 0;
        do {
            if ($offset >= strlen($text))
                break;

            $ps = strpos($text, $begining_tag, $offset);
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

                if (!$quote_open and $sub_begining_tag != '')// this part to all sub-entries, example:  {if:[a]=1} Hello {if:[b]=1} Sir {endif}. How do you do?{endif}
                {
                    $sub_bt = strpos($text, $sub_begining_tag, $ps1);
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
        $ps = strpos($text, $begining_tag . $ending_tag);
        if (!($ps === false)) {
            $options[] = '';
            $fList[] = $begining_tag . $ending_tag;
        }

        return $fList;
    }

    public static function getMenuParams($Itemid, $rawparams = '')
    {
        if ($rawparams == '') {
            $db = Factory::getDBO();
            $query = 'SELECT params FROM #__menu WHERE id=' . (int)$Itemid . ' LIMIT 1';
            $db->setQuery($query);
            $rows = $db->loadObjectList();

            if (count($rows) == 0)
                return '';

            $row = $rows[0];
            $rawparams = $row->params;
        }

        return json_decode($rawparams);
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

        //getting filed row (we need field typeparams, to render formated value)
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
        // or any of the following caracters -_~,;[]().
        // If you don't need to handle multi-byte characters
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
            $filename = preg_replace("([\.]{2,})", '', $filename);
            //$filename = preg_replace("([.]{2,})", '', $filename);
        }

        if ($format != '')
            $filename .= '.' . $format;

        return $filename;
    }//processValue()

    public static function JTextExtended($text, $value = null)
    {
        if (is_null($value))
            $new_text = Text::_($text);
        else
            $new_text = Text::sprintf($text, $value);

        if ($new_text == $text) {
            $parts = explode('_', $text);
            if (count($parts) > 1) {
                $type = $parts[0];
                if ($type == 'PLG' and count($parts) > 2) {
                    $extension = strtolower($parts[0] . '_' . $parts[1] . '_' . $parts[2]);
                } else
                    $extension = strtolower($parts[0] . '_' . $parts[1]);

                $lang = Factory::getLanguage();
                $lang->load($extension, JPATH_BASE);

                if (is_null($value))
                    return Text::_($text);
                else
                    return Text::sprintf($text, $value);
            } else
                return $text;
        } else
            return $new_text;

    }

    public static function strip_tags_content($text, $tags = '', $invert = FALSE)
    {
        //$tags - list of tags. Example: <b><span>

        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);

        if (is_array($tags) and count($tags) > 0) {
            //if (!$invert) {
            if ($invert == FALSE) {
                return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
            } else {
                return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
            }

        } elseif ($invert == FALSE) {
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

    public static function applyContentPlugins(string &$htmlresult)
    {
        $version_object = new Version;
        $version = (int)$version_object->getShortVersion();

        $mainframe = Factory::getApplication();

        if (method_exists($mainframe, 'getParams')) {
            $mydoc = Factory::getDocument();
            $pagetitle = $mydoc->getTitle(); //because content plugins may overwrite the title

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

            $mydoc->setTitle(JoomlaBasicMisc::JTextExtended($pagetitle)); //because content plugins may overwrite the title
        }

        return $htmlresult;
    }

    public static function suggest_TempFileName(): string
    {
        $output_dir = DIRECTORY_SEPARATOR . trim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $random_name = JoomlaBasicMisc::generateRandomString();

        while (1) {
            $file = $output_dir . $random_name;
            if (!file_exists($file))
                return $file;
        }
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

    public static function getHTMLTagParameters($tag)
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

    public static function csv_explode(string $delim, string $str, string $enclose = '"', bool $preserve = false): array
    {
        //$delim=','

        $resArr = array();
        $n = 0;
        $expEncArr = explode($enclose, $str);
        foreach ($expEncArr as $EncItem) {
            if ($n++ % 2) {
                array_push($resArr, array_pop($resArr) . ($preserve ? $enclose : '') . $EncItem . ($preserve ? $enclose : ''));
            } else {
                $expDelArr = explode($delim, $EncItem);
                array_push($resArr, array_pop($resArr) . array_shift($expDelArr));
                $resArr = array_merge($resArr, $expDelArr);
            }
        }
        return $resArr;
    }

    function getXMLData($file)
    {
        $xml_content = file_get_contents(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR
            . 'media' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $file);

        if ($xml_content != '') {
            $xml = simplexml_load_string($xml_content) or die('Cannot load or parse "' . $file . '" file.');
            return $xml;
        }
        return '';
    }
}
