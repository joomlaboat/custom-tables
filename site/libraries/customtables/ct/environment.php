<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use JoomlaBasicMisc;
use Joomla\CMS\Version;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

class Environment
{
    var float $version;
    var string $current_url;
    var string $current_sef_url;
    var string $encoded_current_url;
    var string $encoded_current_url_no_return;

    var int $userid;
    var ?User $user;
    var bool $isUserAdministrator;
    var bool $print;
    var bool $clean;
    var string $frmt;
    var string $WebsiteRoot;
    var bool $advancedTagProcessor;
    var Input $jinput;
    var bool $isMobile;
    var bool $isModal;

    var string $field_prefix;
    var string $field_input_prefix;

    var bool $loadTwig;
    var string $toolbarIcons;
    var bool $legacySupport;
    var ?string $folderToSaveLayouts;
    var bool $isPlugin; //this can be set by calling the class from the plugin

    var bool $CustomPHPEnabled;

    function __construct(bool $enablePlugin = true)
    {
        $this->CustomPHPEnabled = false;

        if ($enablePlugin) {
            $plugin = PluginHelper::getPlugin('content', 'customtables');

            if (!is_null($plugin) and is_object($plugin) > 0) {
                $pluginParams = new Registry($plugin->params);
                $this->CustomPHPEnabled = (int)$pluginParams->get("phpPlugin") == 1;
            }
        }

        $this->field_prefix = 'es_';
        $this->field_input_prefix = 'com' . $this->field_prefix;

        if (defined('_JEXEC')) {
            $version_object = new Version;
            $this->version = (int)$version_object->getShortVersion();
        } else
            $this->version = 6;

        $this->jinput = Factory::getApplication()->input;

        $this->current_url = JoomlaBasicMisc::curPageURL();

        if (!str_contains($this->current_url, 'option=com_customtables')) {
            $pair = explode('?', $this->current_url);
            $this->current_sef_url = $pair[0] . '/';
            if (isset($pair[1]))
                $this->current_sef_url = '?' . $pair[1];
        } else
            $this->current_sef_url = $this->current_url;

        $tmp_current_url = JoomlaBasicMisc::deleteURLQueryOption($this->current_url, "listing_id");
        $tmp_current_url = JoomlaBasicMisc::deleteURLQueryOption($tmp_current_url, 'number');

        $this->encoded_current_url = base64_encode($tmp_current_url);

        $tmp_current_url = JoomlaBasicMisc::deleteURLQueryOption($tmp_current_url, 'returnto');
        $this->encoded_current_url_no_return = base64_encode($tmp_current_url);

        if ($this->version < 4)
            $this->user = Factory::getUser();
        else
            $this->user = Factory::getApplication()->getIdentity();

        $this->userid = is_null($this->user) ? 0 : $this->user->id;

        if ($this->user !== null)
            $usergroups = $this->user->get('groups');
        else
            $usergroups = [];

        $this->isUserAdministrator = in_array(8, $usergroups);//8 is Super Users
        //$this->isUserAdministrator = $this->user->authorise('core.edit', 'com_content');

        $this->print = (bool)$this->jinput->getInt('print', 0);
        $this->clean = (bool)$this->jinput->getInt('clean', 0);
        $this->isModal = (bool)$this->jinput->getInt('modal', 0);
        $this->frmt = $this->jinput->getCmd('frmt', 'html');
        if ($this->jinput->getCmd('layout', '') == 'json')
            $this->frmt = 'json';

        $mainframe = Factory::getApplication();
        if ($mainframe->getCfg('sef')) {
            $this->WebsiteRoot = Uri::root(true);
            if ($this->WebsiteRoot == '' or $this->WebsiteRoot[strlen($this->WebsiteRoot) - 1] != '/') //Root must have the slash character "/" in the end
                $this->WebsiteRoot .= '/';
        } else
            $this->WebsiteRoot = '';

        $this->advancedTagProcessor = false;

        $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'protagprocessor' . DIRECTORY_SEPARATOR;
        $phpTagProcessor = $path . 'phptags.php';
        if (file_exists($phpTagProcessor)) {
            $this->advancedTagProcessor = true;
            require_once($phpTagProcessor);
        }

        if (file_exists($path . 'customphp.php'))
            require_once($path . 'customphp.php');

        if (file_exists($path . 'servertags.php'))
            require_once($path . 'servertags.php');

        $this->isMobile = self::check_user_agent('mobile');

        $params = ComponentHelper::getParams('com_customtables');

        $this->loadTwig = $params->get('loadTwig') == '1';
        $this->toolbarIcons = strval($params->get('toolbaricons'));
        $this->legacySupport = $params->get('legacysupport') == '';

        $this->folderToSaveLayouts = $params->get('folderToSaveLayouts');
        if ($this->folderToSaveLayouts !== null)
            $this->folderToSaveLayouts = str_replace('/', DIRECTORY_SEPARATOR, $this->folderToSaveLayouts);

        if ($this->folderToSaveLayouts == '')
            $this->folderToSaveLayouts = null;

        if ($this->folderToSaveLayouts !== null) {
            if ($this->folderToSaveLayouts[0] != '/')
                $this->folderToSaveLayouts = JPATH_SITE . DIRECTORY_SEPARATOR . $this->folderToSaveLayouts;
        }

        $this->isPlugin = false;
    }

    /* USER-AGENTS ================================================== */
    //https://stackoverflow.com/questions/6524301/detect-mobile-browser
    public static function check_user_agent($type = NULL): bool
    {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($type == 'bot') {
            // matches popular bots
            if (preg_match("/googlebot|adsbot|yahooseeker|yahoobot|msnbot|watchmouse|pingdom\.com|feedfetcher-google/", $user_agent)) {
                return true;
                // watchmouse|pingdom\.com are "uptime services"
            }
        } else if ($type == 'browser') {
            // matches core browser types
            if (preg_match("/mozilla\/|opera\//", $user_agent)) {
                return true;
            }
        } else if ($type == 'mobile') {
            // matches popular mobile devices that have small screens and/or touch inputs
            // mobile devices have regional trends; some of these will have varying popularity in Europe, Asia, and America
            // detailed demographics are unknown, and South America, the Pacific Islands, and Africa trends might not be represented, here
            if (preg_match("/phone|iphone|itouch|ipod|symbian|android|htc_|htc-|palmos|blackberry|opera mini|iemobile|windows ce|nokia|fennec|hiptop|kindle|mot |mot-|webos\/|samsung|sonyericsson|^sie-|nintendo/", $user_agent)) {
                // these are the most common
                return true;
            } else if (preg_match("/mobile|pda;|avantgo|eudoraweb|minimo|netfront|brew|teleca|lg;|lge |wap;| wap /", $user_agent)) {
                // these are less common, and might not be worth checking
                return true;
            }
        }
        return false;
    }

    public static function check_user_agent_for_ie(): bool
    {
        $u = $_SERVER['HTTP_USER_AGENT'];
        if (str_contains($u, 'MSIE'))
            return true;
        elseif (str_contains($u, 'Trident'))
            return true;

        return false;
    }

    public static function check_user_agent_for_apple(): bool
    {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (preg_match("/iphone|itouch|ipod|ipad/", $user_agent)) {
            // these are the most common
            return true;
        }
        return false;
    }
}
