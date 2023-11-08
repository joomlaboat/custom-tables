<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use JoomlaBasicMisc;
use JUri;

class common
{
    public static function enqueueMessage($text, $type): void
    {
        if (defined('_JEXEC')) {
            Factory::getApplication()->enqueueMessage($text, $type);
        } elseif (defined('WPINC')) {
            echo '<div class="success-message">' . $text . '</div>';
        }
    }

    public static function translate(string $text, int|float $value = null)
    {
        if (defined('WPINC')) {
            return __($text, 'customtables');
        }

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
                $lang->load($extension, JPATH_SITE);//JPATH_BASE);

                if (is_null($value))
                    return Text::_($text);
                else
                    return Text::sprintf($text, $value);
            } else
                return $text;
        } else
            return $new_text;
    }

    public static function curPageURL(): string
    {
        if (defined('_JEXEC')) {
            $WebsiteRoot = str_replace(JURI::root(true), '', JURI::root());
            $RequestURL = $_SERVER["REQUEST_URI"];

            if ($WebsiteRoot != '' and $WebsiteRoot[strlen($WebsiteRoot) - 1] == '/') {
                if ($RequestURL != '' and $RequestURL[0] == '/') {
                    //Delete $WebsiteRoot end /
                    $WebsiteRoot = substr($WebsiteRoot, 0, strlen($WebsiteRoot) - 1);
                }
            }
        } else {
            $WebsiteRoot = str_replace(site_url(), '', home_url());
            $RequestURL = $_SERVER["REQUEST_URI"];

            if ($WebsiteRoot !== '' && str_ends_with($WebsiteRoot, '/')) {
                if ($RequestURL !== '' && $RequestURL[0] === '/') {
                    $WebsiteRoot = rtrim($WebsiteRoot, '/');
                }
            }
        }
        return $WebsiteRoot . $RequestURL;
    }

    public static function inputPostString($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->post->getString($parameter, $default);
        } else {
            if (!isset($_POST[$parameter]))
                return $default;

            $source = strip_tags($_POST[$parameter]);
            return (string)sanitize_text_field($source);
        }
    }

    public static function inputGetString($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getString($parameter, $default);
        } else {
            if (!isset($_REQUEST[$parameter]))
                return $default;

            $source = strip_tags(wp_unslash($_REQUEST[$parameter]));
            return (string)sanitize_text_field($source);
        }
    }

    public static function inputGetFloat($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getFloat($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_REQUEST[$parameter]))
                return $default;

            // Only use the first floating point value
            preg_match('/-?\d+(\.\d+)?/', (string)$_REQUEST[$parameter], $matches);
            return @ (float)$matches[0];
        }
    }

    public static function inputGetInt(string $parameter, ?int $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getInt($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_REQUEST[$parameter]))
                return $default;

            preg_match('/-?\d+/', (string)$_REQUEST[$parameter], $matches);
            return @ (int)$matches[0];
        }
    }

    public static function inputPostInt($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->post->getInt($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_POST[$parameter]))
                return $default;

            preg_match('/-?\d+/', (string)$_POST[$parameter], $matches);
            return @ (int)$matches[0];
        }
    }

    public static function inputGetUInt($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getInt($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_REQUEST[$parameter]))
                return $default;

            preg_match('/-?\d+/', (string)$_REQUEST[$parameter], $matches);
            return @ abs((int)$matches[0]);
        }
    }

    public static function inputGetCmd(string $parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getCmd($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_REQUEST[$parameter]))
                return $default;

            $result = (string)preg_replace('/[^A-Z\d_\.-]/i', '', $_REQUEST[$parameter]);
            return ltrim($result, '.');
        }
    }

    public static function inputGetRow(string $parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->get($parameter, $default, "RAW");
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_REQUEST[$parameter]))
                return $default;

            return stripslashes($_REQUEST[$parameter]);
        }
    }

    public static function inputGetBase64(string $parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->get($parameter, $default, 'BASE64');
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_REQUEST[$parameter]))
                return $default;

            // Allow a-z, 0-9, slash, plus, equals.
            return (string)preg_replace('/[^A-Z\d\/+=]/i', '', $_REQUEST[$parameter]);
        }
    }

    public static function inputGetWord(string $parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->get($parameter, $default, 'BASE64');
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_REQUEST[$parameter]))
                return $default;

            // Only allow characters a-z, and underscores
            return (string)preg_replace('/[^A-Z_]/i', '', $_REQUEST[$parameter]);
        }
    }

    public static function inputGetAlnum(string $parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->get($parameter, $default, 'ALNUM');
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_REQUEST[$parameter]))
                return $default;

            // Allow a-z and 0-9 only
            return (string)preg_replace('/[^A-Z\d]/i', '', $_REQUEST[$parameter]);
        }
    }

    public static function inputGet(string $parameter, $default, string $filter)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->get($parameter, $default, $filter);
        } else {
            echo 'common::inputGet not supported in WordPress';
        }
        return null;
    }

    public static function inputPost($parameter, $default = null, $filter = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->post->get($parameter, $default, $filter);
        } else {
            echo 'common::inputPost not supported in WordPress';
        }
        return null;
    }

    public static function inputSet(string $parameter, string $value): void
    {
        if (defined('_JEXEC')) {
            Factory::getApplication()->input->set($parameter, $value);
        } else {
            echo 'common::inputSet not supported in WordPress';
        }
    }

    public static function inputFiles(string $fileId)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->files->get($fileId);
        } else {
            echo 'common::inputFiles not supported in WordPress';
        }
        return null;
    }

    public static function inputCookieSet(string $parameter, $value, $time, $path, $domain): void
    {
        if (defined('_JEXEC')) {
            Factory::getApplication()->input->cookie->set($parameter, $value, $time, $path, $domain);
        } else {
            die('common::inputCookieSet not supported in WordPress');
        }
    }

    public static function inputCookieGet($parameter)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->cookie->get($parameter);
        } else {
            die('common::inputCookieGet not supported in WordPress');
        }
    }

    public static function inputServer($parameter, $default = null, $filter = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->server->get($parameter, $default, $filter);
        } else {
            die('common::inputServer not supported in WordPress');
        }
    }

    public static function ExplodeSmartParams(string $param): array
    {
        $items = array();

        if ($param === null)
            return $items;

        $a = JoomlaBasicMisc::csv_explode(' and ', $param, '"', true);
        foreach ($a as $b) {
            $c = JoomlaBasicMisc::csv_explode(' or ', $b, '"', true);

            if (count($c) == 1)
                $items[] = array('and', $b);
            else {
                foreach ($c as $d)
                    $items[] = array('or', $d);
            }
        }
        return $items;
    }
}