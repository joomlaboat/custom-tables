<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;

use \Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

/**
 * Customtables component helper.
 */
abstract class CustomtablesHelper
{
    /**
     *    Load the Component xml manifest.
     **/

    public static function manifest()
    {
        $manifestUrl = JPATH_ADMINISTRATOR . "/components/com_customtables/customtables.xml";
        return simplexml_load_file($manifestUrl);
    }

    public static function getContributors()
    {
        // get params
        $params = ComponentHelper::getParams('com_customtables');
        // start contributors array
        $contributors = array();
        // get all Contributors (max 20)
        $searchArray = range('0', '20');
        foreach ($searchArray as $nr) {
            if ((NULL !== $params->get("showContributor" . $nr)) && ($params->get("showContributor" . $nr) == 1 || $params->get("showContributor" . $nr) == 3)) {
                // set link based of selected option
                if ($params->get("useContributor" . $nr) == 1) {
                    $link_front = '<a href="mailto:' . $params->get("emailContributor" . $nr) . '" target="_blank">';
                    $link_back = '</a>';
                } elseif ($params->get("useContributor" . $nr) == 2) {
                    $link_front = '<a href="' . $params->get("linkContributor" . $nr) . '" target="_blank">';
                    $link_back = '</a>';
                } else {
                    $link_front = '';
                    $link_back = '';
                }
                $contributors[$nr]['title'] = self::htmlEscape($params->get("titleContributor" . $nr));
                $contributors[$nr]['name'] = $link_front . self::htmlEscape($params->get("nameContributor" . $nr)) . $link_back;
            }
        }
        return $contributors;
    }

    public static function htmlEscape($var, $charset = 'UTF-8', $shorten = false, $length = 40)
    {
        if (self::checkString($var)) {
            $filter = new JFilterInput();
            $string = $filter->clean(html_entity_decode(htmlentities($var, ENT_COMPAT, $charset)), 'HTML');
            if ($shorten) {
                return self::shorten($string, $length);
            }
            return $string;
        } else {
            return '';
        }
    }

    public static function checkString($string)
    {
        if (isset($string) && is_string($string) && strlen($string) > 0) {
            return true;
        }
        return false;
    }

    public static function shorten($string, $length = 40, $addTip = true)
    {
        if (self::checkString($string)) {
            $initial = strlen($string);
            $words = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
            $words_count = count((array)$words);

            $word_length = 0;
            $last_word = 0;
            for (; $last_word < $words_count; ++$last_word) {
                $word_length += strlen($words[$last_word]);
                if ($word_length > $length) {
                    break;
                }
            }

            $newString = implode(array_slice($words, 0, $last_word));
            $final = strlen($newString);
            if ($initial != $final && $addTip) {
                $title = self::shorten($string, 400, false);
                return '<span class="hasTip" title="' . $title . '" style="cursor:help">' . trim($newString) . '...</span>';
            } elseif ($initial != $final && !$addTip) {
                return trim($newString) . '...';
            }
        }
        return $string;
    }

    public static function addSubmenu($submenu)
    {
        $ct = new CT;

        // load the submenus to sidebar
        JHtmlSidebar::addEntry(Text::_('COM_CUSTOMTABLES_SUBMENU_DASHBOARD'), 'index.php?option=com_customtables&view=customtables', $submenu === 'customtables');

        if ($ct->Env->advancedtagprocessor)
            JHtmlSidebar::addEntry(Text::_('COM_CUSTOMTABLES_SUBMENU_LISTOFCATEGORIES'), 'index.php?option=com_customtables&view=listofcategories', $submenu === 'listofcategories');

        JHtmlSidebar::addEntry(Text::_('COM_CUSTOMTABLES_SUBMENU_LISTOFTABLES'), 'index.php?option=com_customtables&view=listoftables', $submenu === 'listoftables');
        JHtmlSidebar::addEntry(Text::_('COM_CUSTOMTABLES_SUBMENU_LISTOFLAYOUTS'), 'index.php?option=com_customtables&view=listoflayouts', $submenu === 'listoflayouts');

        JHtmlSidebar::addEntry(Text::_('COM_CUSTOMTABLES_SUBMENU_DATABASECHECK'), 'index.php?option=com_customtables&view=databasecheck', $submenu === 'databasecheck');

        JHtmlSidebar::addEntry(Text::_('COM_CUSTOMTABLES_SUBMENU_DOCUMENTATION'), 'index.php?option=com_customtables&view=documentation', $submenu === 'documentation');

    }

    /**
     *    Check if have an object with a length
     *
     * @input    object   The object to check
     *
     * @returns bool true on success
     **/

    public static function checkObject($object)
    {
        if (isset($object) && is_object($object)) {
            return count((array)$object) > 0;
        }
        return false;
    }

    public static function safeString($string, $type = 'L', $spacer = '_', $replaceNumbers = true)
    {
        if ($replaceNumbers === true) {
            // remove all numbers and replace with english text version (works well only up to millions)
            $string = self::replaceNumbers($string);
        }
        // 0nly continue if we have a string
        if (self::checkString($string)) {
            // create file name without the extention that is safe
            if ($type === 'filename') {
                // make sure VDM is not in the string
                $string = str_replace('VDM', 'vDm', $string);
                // Remove anything which isn't a word, whitespace, number
                // or any of the following caracters -_()
                // If you don't need to handle multibyte characters
                // you can use preg_replace rather than mb_ereg_replace
                // Thanks @Łukasz Rysiak!
                // $string = mb_ereg_replace("([^\w\s\d\-_\(\)])", '', $string);
                $string = preg_replace("([^\w\s\d\-_\(\)])", '', $string);
                // https://stackoverflow.com/a/2021729/1.8.177
                return preg_replace('/\s+/', ' ', $string);
            }
            // remove all others characters
            $string = trim($string);
            $string = preg_replace('/' . $spacer . '+/', ' ', $string);
            $string = preg_replace('/\s+/', ' ', $string);
            $string = preg_replace("/[^A-Za-z ]/", '', $string);
            // select final adaptations
            if ($type === 'L' || $type === 'strtolower') {
                // replace white space with underscore
                $string = preg_replace('/\s+/', $spacer, $string);
                // default is to return lower
                return strtolower($string);
            } elseif ($type === 'W') {
                // return a string with all first letter of each word uppercase(no underscore)
                return ucwords(strtolower($string));
            } elseif ($type === 'w' || $type === 'word') {
                // return a string with all lowercase(no underscore)
                return strtolower($string);
            } elseif ($type === 'Ww' || $type === 'Word') {
                // return a string with first letter of the first word uppercase and all the rest lowercase(no underscore)
                return ucfirst(strtolower($string));
            } elseif ($type === 'WW' || $type === 'WORD') {
                // return a string with all the uppercase(no underscore)
                return strtoupper($string);
            } elseif ($type === 'U' || $type === 'strtoupper') {
                // replace white space with underscore
                $string = preg_replace('/\s+/', $spacer, $string);
                // return all upper
                return strtoupper($string);
            } elseif ($type === 'F' || $type === 'ucfirst') {
                // replace white space with underscore
                $string = preg_replace('/\s+/', $spacer, $string);
                // return with first character to upper
                return ucfirst(strtolower($string));
            } elseif ($type === 'cA' || $type === 'cAmel' || $type === 'camelcase') {
                // convert all words to first letter uppercase
                $string = ucwords(strtolower($string));
                // remove white space
                $string = preg_replace('/\s+/', '', $string);
                // now return first letter lowercase
                return lcfirst($string);
            }
            // return string
            return $string;
        }
        // not a string
        return '';
    }

    public static function replaceNumbers($string)
    {
        // set numbers array
        $numbers = array();
        // first get all numbers
        preg_match_all('!\d+!', $string, $numbers);
        // check if we have any numbers
        if (isset($numbers[0]) && self::checkArray($numbers[0])) {
            foreach ($numbers[0] as $number) {
                $searchReplace[$number] = self::numberToString((int)$number);
            }
            // now replace numbers in string
            $string = str_replace(array_keys($searchReplace), array_values($searchReplace), $string);
            // check if we missed any, strange if we did.
            return self::replaceNumbers($string);
        }
        // return the string with no numbers remaining.
        return $string;
    }

    public static function checkArray($array, $removeEmptyString = false)
    {
        if (isset($array) && is_array($array) && count((array)$array) > 0) {
            // also make sure the empty strings are removed
            if ($removeEmptyString) {
                foreach ($array as $key => $string) {
                    if (empty($string)) {
                        unset($array[$key]);
                    }
                }
                return self::checkArray($array, false);
            }
            return true;
        }
        return false;
    }
}
