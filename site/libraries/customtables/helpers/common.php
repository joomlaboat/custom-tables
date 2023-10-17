<?php

namespace CustomTables;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use JUri;

class common
{
    public static function translate($text, $value = null)
    {
        if (defined('WPINC')) {
            return $text;
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
            $WebsiteRoot = str_replace(JURI::root(true), '', JURI::root(false));
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

            if ($WebsiteRoot !== '' && substr($WebsiteRoot, -1) === '/') {
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
            if (!isset($_GET[$parameter]))
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
            if (!isset($_GET[$parameter]))
                return $default;

            $source = strip_tags($_GET[$parameter]);
            return (string)sanitize_text_field($source);
        }
    }

    public static function inputGetFloat($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getFloat($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_GET[$parameter]))
                return $default;

            // Only use the first floating point value
            preg_match('/-?[0-9]+(\.[0-9]+)?/', (string)$_GET[$parameter], $matches);
            return @ (float)$matches[0];
        }
    }

    public static function inputGetInt($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getInt($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_GET[$parameter]))
                return $default;

            preg_match('/-?[0-9]+/', (string)$_GET[$parameter], $matches);
            return @ (int)$matches[0];
        }
    }

    public static function inputPostInt($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->post->getInt($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_GET[$parameter]))
                return $default;

            preg_match('/-?[0-9]+/', (string)$_POST[$parameter], $matches);
            return @ (int)$matches[0];
        }
    }

    public static function inputGetUInt($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getInt($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_GET[$parameter]))
                return $default;

            preg_match('/-?[0-9]+/', (string)$_GET[$parameter], $matches);
            return @ abs((int)$matches[0]);
        }
    }

    public static function inputGetCMD($parameter, $default = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->getCmd($parameter, $default);
        } else {
            // Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
            if (!isset($_GET[$parameter]))
                return $default;

            $result = (string)preg_replace('/[^A-Z0-9_\.-]/i', '', $_GET[$parameter]);
            return ltrim($result, '.');
        }
    }

    public static function inputGet($parameter, $default = null, $filter = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->get($parameter, $default, $filter);
        } else {

        }
    }

    public static function inputPost($parameter, $default = null, $filter = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->post->get($parameter, $default, $filter);
        } else {

        }
    }

    public static function inputSet(string $parameter, string $value): void
    {
        if (defined('_JEXEC')) {
            Factory::getApplication()->input->set($parameter, $value);
        } else {

        }
    }

    public static function inputFiles(string $fileId)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->files->get($fileId);
        } else {

        }
    }

    public static function inputCookieSet(string $parameter, $value, $time, $path, $domain): void
    {
        if (defined('_JEXEC')) {
            Factory::getApplication()->input->cookie->set($parameter, $value, $time, $path, $domain);
        } else {

        }
    }

    public static function inputCookieGet($parameter)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->cookie->get($parameter);
        } else {

        }
    }

    public static function inputServer($parameter, $default = null, $filter = null)
    {
        if (defined('_JEXEC')) {
            return Factory::getApplication()->input->server->get($parameter, $default, $filter);
        } else {

        }
    }
}