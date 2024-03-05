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

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class Environment
{
	var float $version;
	var string $current_url;
	var string $current_sef_url;
	var string $encoded_current_url;
	var string $encoded_current_url_no_return;

	//var int $userid;
	var ?CTUser $user;
	var bool $isUserAdministrator;
	var bool $print;
	var bool $clean;
	var string $frmt;
	var string $WebsiteRoot;
	var bool $advancedTagProcessor;
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

		if (defined('_JEXEC')) {
			if ($enablePlugin) {
				$plugin = PluginHelper::getPlugin('content', 'customtables');

				if (!is_null($plugin) and is_object($plugin) > 0) {
					$pluginParamsArray = json_decode($plugin->params);
					$this->CustomPHPEnabled = (int)$pluginParamsArray->phpPlugin == 1;
				}
			}
			$this->field_prefix = 'es_';
		} else
			$this->field_prefix = 'es_'; //$this->field_prefix = 'ct_'; ct is in future

		$this->field_input_prefix = 'com' . $this->field_prefix;

		if (defined('_JEXEC')) {
			$version_object = new Version;
			$this->version = (int)$version_object->getShortVersion();
		} else
			$this->version = 6;

		$this->current_url = common::curPageURL();

		if (!str_contains($this->current_url, 'option=com_customtables')) {
			$pair = explode('?', $this->current_url);
			$this->current_sef_url = $pair[0] . '/';
			if (isset($pair[1]))
				$this->current_sef_url = '?' . $pair[1];
		} else
			$this->current_sef_url = $this->current_url;

		$tmp_current_url = CTMiscHelper::deleteURLQueryOption($this->current_url, "listing_id");
		$tmp_current_url = CTMiscHelper::deleteURLQueryOption($tmp_current_url, 'number');

		$this->encoded_current_url = common::makeReturnToURL($tmp_current_url);

		$tmp_current_url = CTMiscHelper::deleteURLQueryOption($tmp_current_url, 'returnto');
		$this->encoded_current_url_no_return = common::makeReturnToURL($tmp_current_url);

		$this->user = new CTUser();
		$this->isUserAdministrator = $this->user->isUserAdministrator;//in_array(8, $this->user->groups);//8 is Super Users

		$this->print = (bool)common::inputGetInt('print', 0);
		$this->clean = (bool)common::inputGetInt('clean', 0);
		$this->isModal = (bool)common::inputGetInt('modal', 0);
		$this->frmt = common::inputGetCmd('frmt', 'html');

		if (defined('_JEXEC')) {
			if (common::inputGetCmd('layout', '') == 'json')
				$this->frmt = 'json';
		}

		if (defined('_JEXEC')) {
			$mainframe = Factory::getApplication();
			if ($mainframe->getCfg('sef')) {
				$this->WebsiteRoot = CUSTOMTABLES_MEDIA_HOME_URL;
				if ($this->WebsiteRoot == '' or $this->WebsiteRoot[strlen($this->WebsiteRoot) - 1] != '/') //Root must have the slash character "/" in the end
					$this->WebsiteRoot .= '/';
			} else
				$this->WebsiteRoot = '';
		} else {
			if (get_option('permalink_structure')) {
				$this->WebsiteRoot = home_url();
				if (substr($this->WebsiteRoot, -1) !== '/') {
					$this->WebsiteRoot .= '/';
				}
			} else {
				$this->WebsiteRoot = '';
			}
		}

		$this->advancedTagProcessor = false;

		if (defined('_JEXEC')) {
			$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'protagprocessor' . DIRECTORY_SEPARATOR;

			if (file_exists($path . 'phptags.php')) {
				$this->advancedTagProcessor = true;
				require_once($path . 'phptags.php');
			}

			if (file_exists($path . 'customphp.php'))
				require_once($path . 'customphp.php');

			if (file_exists($path . 'helpers.php'))
				require_once($path . 'helpers.php');

			if (file_exists($path . 'servertags.php'))
				require_once($path . 'servertags.php');
		} elseif (defined('WPINC') and defined('CustomTablesWPPro\CTWPPRO')) {
			$path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'customtablespro' . DIRECTORY_SEPARATOR . 'helpers.php';
			$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
			$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);

			if (file_exists($path)) {
				$this->advancedTagProcessor = true;
				require_once($path);
			}
		}

		$this->isMobile = self::check_user_agent('mobile');

		if (defined('_JEXEC')) {
			$params = ComponentHelper::getParams('com_customtables');

			$this->loadTwig = $params->get('loadTwig') == '1';
			$this->toolbarIcons = strval($params->get('toolbaricons'));
			$this->legacySupport = $params->get('legacysupport') == 'legacy';

			$this->folderToSaveLayouts = $params->get('folderToSaveLayouts');
			if ($this->folderToSaveLayouts !== null)
				$this->folderToSaveLayouts = str_replace('/', DIRECTORY_SEPARATOR, $this->folderToSaveLayouts);

			if ($this->folderToSaveLayouts == '')
				$this->folderToSaveLayouts = null;

			if ($this->folderToSaveLayouts !== null) {
				if ($this->folderToSaveLayouts[0] != '/')
					$this->folderToSaveLayouts = JPATH_SITE . DIRECTORY_SEPARATOR . $this->folderToSaveLayouts;
			}

		} else {

			$this->loadTwig = true;
			$this->toolbarIcons = '';
			$this->legacySupport = false;
			$this->folderToSaveLayouts = null;

			/*
			 *
			 * TODO:
			 *
			In WordPress, plugin settings are typically stored in the WordPress database. Plugin developers can use the WordPress Options API or Custom Post Types to store and retrieve settings.

			Options API: This is the most common way for plugins to store settings. Plugins can use functions like add_option(), update_option(), and get_option() to save and retrieve settings. These settings are stored in the wp_options table in the database.

			Custom Post Types: Some plugins might create custom post types to store settings. This is less common for simple settings, but it can be used for more complex configurations.

			Settings API: WordPress provides a Settings API that allows developers to create a settings page in the WordPress admin area. This API handles the form creation, validation, and saving of settings.

			Configuration Files: Some plugins may use configuration files (typically PHP files) to store settings. These files might be located within the plugin's directory.

			The exact location and structure of these settings can vary depending on how the plugin developer chose to implement them. It's important to note that well-developed plugins should handle settings securely and follow WordPress coding standards.
			*/
		}
		$this->isPlugin = false;
	}

	/* USER-AGENTS ================================================== */
	//https://stackoverflow.com/questions/6524301/detect-mobile-browser
	public static function check_user_agent($type = NULL): bool
	{
		$user_agent = strtolower(common::getServerParam('HTTP_USER_AGENT') ?? '');
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
		$u = common::getServerParam('HTTP_USER_AGENT');
		if (str_contains($u, 'MSIE'))
			return true;
		elseif (str_contains($u, 'Trident'))
			return true;

		return false;
	}

	public static function check_user_agent_for_apple(): bool
	{
		$user_agent = strtolower(common::getServerParam('HTTP_USER_AGENT'));
		if (preg_match("/iphone|itouch|ipod|ipad/", $user_agent)) {
			// these are the most common
			return true;
		}
		return false;
	}
}
