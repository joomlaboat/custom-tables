<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class Environment
{
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
	var string $WebsiteRoot;//With trailing front slash /
	var bool $advancedTagProcessor;
	var bool $isMobile;
	var bool $isModal;
	var string $field_prefix;
	var bool $loadTwig;
	var string $toolbarIcons;
	var ?string $folderToSaveLayouts;
	var bool $isPlugin; //this can be set by calling the class from the plugin
	var bool $CustomPHPEnabled;
	var bool $debug;

	function __construct()
	{
		$this->CustomPHPEnabled = false;

		if (defined('_JEXEC')) {
			$plugin = PluginHelper::getPlugin('content', 'customtables');

			if (!is_null($plugin) and is_object($plugin) > 0) {
				$pluginParamsArray = json_decode($plugin->params);
				$this->CustomPHPEnabled = (int)($pluginParamsArray->phpPlugin ?? 0) == 1;
			}
		}

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
		$this->isUserAdministrator = $this->user->isUserAdministrator;

		$this->print = (bool)common::inputGetInt('print', 0);
		$this->clean = (bool)common::inputGetInt('clean', 0);
		$this->isModal = (bool)common::inputGetInt('modal', 0);
		$this->frmt = common::inputGetCmd('frmt', 'html');

		if (defined('_JEXEC')) {
			if (common::inputGetCmd('layout', '') == 'json')
				$this->frmt = 'json';
		}

		if (defined('_JEXEC')) {

			if (CUSTOMTABLES_JOOMLA_MIN_4) {
				try {
					$sef = Factory::getApplication()->get('sef');
				} catch (Exception $e) {
					// Handle error if needed
					$sef = false;
				}
			} else {
				$mainframe = Factory::getApplication();
				$sef = $mainframe->getCfg('sef');
			}

			$this->WebsiteRoot = CUSTOMTABLES_MEDIA_HOME_URL;
			if ($this->WebsiteRoot == '' or $this->WebsiteRoot[strlen($this->WebsiteRoot) - 1] != '/') //Root must have the slash character "/" in the end
				$this->WebsiteRoot .= '/';
		} else {
			$this->WebsiteRoot = home_url();
			if (substr($this->WebsiteRoot, -1) !== '/')
				$this->WebsiteRoot .= '/';
		}

		$this->advancedTagProcessor = false;

		if (defined('_JEXEC')) {
			$path = CUSTOMTABLES_PRO_PATH . 'protagprocessor' . DIRECTORY_SEPARATOR;

			if (file_exists($path . 'customphp.php')) {
				$this->advancedTagProcessor = true;
				require_once($path . 'customphp.php');
			}

			if (file_exists($path . 'helpers.php'))
				require_once($path . 'helpers.php');

			if (file_exists($path . 'servertags.php'))
				require_once($path . 'servertags.php');
		} elseif (defined('WPINC') and defined('CustomTablesWPPro\CTWPPRO')) {
			$path = CUSTOMTABLES_PRO_PATH;
			$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
			$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);

			if (file_exists($path . 'helpers.php')) {
				$this->advancedTagProcessor = true;
				require_once($path . 'helpers.php');
				if (file_exists($path . 'CustomPHP.php')) {
					require_once($path . 'CustomPHP.php');
				}
			}
		}

		$this->isMobile = self::check_user_agent('mobile');

		if (defined('_JEXEC')) {
			$params = ComponentHelper::getParams('com_customtables');
			$this->field_prefix = $params->get('fieldPrefix') ?? 'ct_';
			$this->loadTwig = $params->get('loadTwig') == '1';
			$this->toolbarIcons = strval($params->get('toolbaricons'));// Default is empty means Legacy Image Icons
			if ($this->toolbarIcons === 'fas')
				$this->toolbarIcons = 'font-awesome-6';

			$this->folderToSaveLayouts = $params->get('folderToSaveLayouts');
			if ($this->folderToSaveLayouts !== null)
				$this->folderToSaveLayouts = str_replace('/', DIRECTORY_SEPARATOR, $this->folderToSaveLayouts);

			if ($this->folderToSaveLayouts == '')
				$this->folderToSaveLayouts = null;

			if ($this->folderToSaveLayouts !== null) {
				if ($this->folderToSaveLayouts[0] != '/')
					$this->folderToSaveLayouts = CUSTOMTABLES_ABSPATH . $this->folderToSaveLayouts;
			}

			$this->debug = Factory::getApplication()->get('debug');
		} else {

			$this->field_prefix = get_option('customtables-fieldprefix') ?? 'ct_';
			if (empty($this->field_prefix))
				$this->field_prefix = 'ct_';

			$this->loadTwig = true;
			$this->toolbarIcons = get_option('customtables-toolbaricons', ''); // Default is empty means Legacy Image Icons
			$this->folderToSaveLayouts = null;

			$this->debug = defined('WP_DEBUG') && WP_DEBUG;
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
