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

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Uri\Uri;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class common
{
	public static function enqueueMessage($text, $type = 'error'): void
	{
		//Types: error, success-message, notice

		if (defined('_JEXEC'))
			Factory::getApplication()->enqueueMessage($text, $type);
		elseif (defined('WPINC')) {
			if ($type == 'notice')
				$type = 'success-message';
			echo '<div class="' . esc_attr($type) . '"><p>' . esc_html($text) . '</p></div>';
		}
	}

	public static function translate(string $text, int|float $value = null): string
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

	public static function inputPostString($parameter, $default = null): ?string
	{
		return Factory::getApplication()->input->post->getString($parameter, $default);
	}

	public static function inputPostFloat($parameter, $default = null): ?float
	{
		return Factory::getApplication()->input->getFloat($parameter, $default);
	}

	public static function inputGetFloat($parameter, $default = null): ?float
	{
		return Factory::getApplication()->input->getFloat($parameter, $default);
	}

	public static function inputPostInt(string $parameter, ?int $default = null): ?int
	{
		return Factory::getApplication()->input->getInt($parameter, $default);
	}

	public static function inputGetInt(string $parameter, ?int $default = null): ?int
	{
		return Factory::getApplication()->input->getInt($parameter, $default);
	}

	public static function inputPostUInt($parameter, $default = null)
	{
		return Factory::getApplication()->input->getInt($parameter, $default);
	}

	public static function inputGetUInt($parameter, $default = null)
	{
		return Factory::getApplication()->input->getInt($parameter, $default);
	}

	public static function inputPostCmd(string $parameter, $default = null): ?string
	{
		return Factory::getApplication()->input->getCmd($parameter, $default);
	}

	public static function inputGetCmd(string $parameter, $default = null): ?string
	{
		return Factory::getApplication()->input->getCmd($parameter, $default);
	}

	public static function inputPostRow(string $parameter, $default = null)
	{
		return Factory::getApplication()->input->get($parameter, $default, "RAW");
	}

	public static function inputGetRow(string $parameter, $default = null)
	{
		return Factory::getApplication()->input->get($parameter, $default, "RAW");
	}

	public static function inputPostBase64(string $parameter, $default = null)
	{
		return Factory::getApplication()->input->get($parameter, $default, 'BASE64');
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

	public static function inputPostAlnum(string $parameter, $default = null)
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

		$a = CTMiscHelper::csv_explode(' and ', $param, '"', true);
		foreach ($a as $b) {
			$c = CTMiscHelper::csv_explode(' or ', $b, '"', true);

			if (count($c) == 1)
				$items[] = array('and', $b);
			else {
				foreach ($c as $d)
					$items[] = array('or', $d);
			}
		}
		return $items;
	}

	public static function folderList(string $directory): ?array
	{
		$folders = [];
		$directoryLength = strlen($directory);

		if ($directory > 0 and $directory[$directoryLength - 1] !== DIRECTORY_SEPARATOR)
			$directoryLength += 1;

		if (is_dir($directory)) {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

			foreach ($iterator as $item) {
				if ($item->isDir())
					$folders[] = substr($item->getPathname(), $directoryLength);
			}
		} else {
			// Handle the case when $directory is not a valid directory
			// You can throw an exception, return an error message, etc.
			return null;
		}
		return $folders;
	}

	public static function escape($var)
	{
		if ($var === null)
			$var = '';

		if (strlen($var) > 50) {
			// use the helper htmlEscape method instead and shorten the string
			return self::htmlEscape($var, 'UTF-8', true);
		}
		// use the helper htmlEscape method instead.
		return self::htmlEscape($var);
	}

	public static function htmlEscape($var, $charset = 'UTF-8', $shorten = false, $length = 40)
	{
		if (self::checkString($var)) {
			// Encode special characters to HTML entities
			$encoded = htmlentities($var, ENT_COMPAT, $charset);

			// Decode HTML entities to their corresponding characters
			$decoded = html_entity_decode($encoded, ENT_COMPAT, $charset);

			// Remove any potential scripting or dangerous content
			$string = common::ctStripTags($decoded);

			if ($shorten) {
				return self::shorten($string, $length);
			}
			return $string;
		} else {
			return '';
		}
	}

	public static function checkString($string): bool
	{
		if (isset($string) && is_string($string) && strlen($string) > 0) {
			return true;
		}
		return false;
	}

	public static function ctStripTags(string $argument): string
	{
		return strip_tags($argument);
	}

	public static function shorten($string, $length = 40, $addTip = true)
	{
		if (self::checkString($string)) {
			$initial = strlen($string);
			$words = preg_split('/([\s\n\r]+)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
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

	public static function ctJsonEncode($argument): string
	{
		return json_encode($argument);
	}

	public static function getReturnToURL(bool $decode = true): ?string
	{
		$returnto = common::inputGet('returnto', null, 'BASE64');

		if ($returnto === null)
			return null;

		if ($decode) {
			return base64_decode($returnto);

			/* TODO: future optional method
			// Construct the session variable key from the received returnto ID
			$returnto_key = 'returnto_' . $returnto_id;

			// Retrieve the value associated with the returnto key from the session
			$session = JFactory::getSession();
			return $session->get($returnto_key, '');
			*/
		} else
			return $returnto;
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

	//Returns base64 encoded/decoded url in Joomla and Sessions ReturnTo variable reference in WP or reference converted to URL

	public static function makeReturnToURL(string $currentURL = null): ?string
	{
		if ($currentURL === null)
			$currentURL = CTMiscHelper::curPageURL();

		return base64_encode($currentURL);
	}

	public static function curPageURL(): string
	{
		$WebsiteRoot = str_replace(Uri::root(true), '', Uri::root());
		$RequestURL = common::getServerParam("REQUEST_URI");

		if ($WebsiteRoot != '' and $WebsiteRoot[strlen($WebsiteRoot) - 1] == '/') {
			if ($RequestURL != '' and $RequestURL[0] == '/') {
				//Delete $WebsiteRoot end /
				$WebsiteRoot = substr($WebsiteRoot, 0, strlen($WebsiteRoot) - 1);
			}
		}

		return $WebsiteRoot . $RequestURL;
	}

	public static function getServerParam(string $param)
	{
		return $_SERVER[$param];
	}

	public static function ctParseUrl($argument)
	{
		return parse_url($argument);
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

	public static function saveString2File(string $filePath, string $content): ?string
	{
		try {
			@file_put_contents($filePath, $content);
		} catch (Exception $e) {
			return $e->getMessage();
		}
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getStringFromFile(string $filePath): ?string
	{
		try {
			if (file_exists($filePath))
				return @file_get_contents($filePath);
			else
				throw new Exception($filePath . ' not found.');

		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		return null;
	}

	static public function base64file_decode($inputFile, $outputFile)
	{
		/* read data (binary) */
		$ifp = fopen($inputFile, "rb");
		$srcData = fread($ifp, filesize($inputFile));
		fclose($ifp);
		/* encode & write data (binary) */
		$ifp = fopen($outputFile, "wb");
		fwrite($ifp, base64_decode($srcData));
		fclose($ifp);
		/* return output filename */
		return ($outputFile);
	}

	public static function default_timezone_set(): void
	{
		date_default_timezone_set('UTC');
	}

	public static function getWhereParameter($field): string
	{
		$list = self::getWhereParameters();

		if ($list === null)
			return '';

		foreach ($list as $l) {
			$p = explode('=', $l);
			$fld_name = str_replace('_t_', '', $p[0]);
			$fld_name = str_replace('_r_', '', $fld_name); //range

			if ($fld_name == $field and isset($p[1]))
				return $p[1];
		}
		return '';
	}

	protected static function getWhereParameters(): ?array
	{
		$value = common::inputGetString('where');
		if ($value !== null) {
			$b = urldecode($value);//base64_decode
			$b = str_replace(' or ', ' and ', $b);
			$b = str_replace(' OR ', ' and ', $b);
			$b = str_replace(' AND ', ' and ', $b);
			return explode(' and ', $b);
		}
		return null;
	}

	public static function inputGetString($parameter, $default = null): ?string
	{
		return Factory::getApplication()->input->get->getString($parameter, $default);
	}

	public static function inputGetBase64(string $parameter, $default = null)
	{
		return Factory::getApplication()->input->get($parameter, $default, 'BASE64');
	}

	public static function loadJQueryUI(): void
	{
		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('script', 'https://code.jquery.com/ui/1.13.2/jquery-ui.js');
		HTMLHelper::_('stylesheet', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
	}

	public static function loadJSAndCSS(Params $params, Environment $env): void
	{
		$app = Factory::getApplication();
		$document = $app->getDocument();

		if ($params->ModuleId === null or (int)$params->ModuleId == 0) {
			//JQuery and Bootstrap
			if ($env->version < 4) {
				$document->addCustomTag('<script src="' . URI::root(true) . '/media/jui/js/jquery.min.js"></script>');
				$document->addCustomTag('<script src="' . URI::root(true) . '/media/jui/js/bootstrap.min.js"></script>');
			} else {
				if (defined('_JEXEC')) {
					HTMLHelper::_('jquery.framework');
					$document->addCustomTag('<link rel="stylesheet" href="' . URI::root(true) . '/media/system/css/fields/switcher.css">');
				}
			}
		}

		if (defined('_JEXEC')) {
			$document->addCustomTag('<script src="' . CUSTOMTABLES_PLUGIN_WEBPATH . 'js/jquery.uploadfile.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_PLUGIN_WEBPATH . 'js/jquery.form.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/uploader.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/ajax.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/base64.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/catalog.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/edit.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/esmulti.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/modal.js"></script>');

			$document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/virtualselect/virtual-select.min.js"></script>');
			$document->addCustomTag('<link rel="stylesheet" href="' . URI::root(true) . '/components/com_customtables/libraries/virtualselect/virtual-select.min.css" />');
		}

		if (defined('_JEXEC')) {
			$joomla_params = ComponentHelper::getParams('com_customtables');
			$googleMapAPIKey = $joomla_params->get('googlemapapikey');

			if ($googleMapAPIKey !== null and $googleMapAPIKey != '')
				$document->addCustomTag('<script src="https://maps.google.com/maps/api/js?key=' . $googleMapAPIKey . '&sensor=false"></script>');

			$document->addCustomTag('<script>let ctWebsiteRoot = "' . $env->WebsiteRoot . '";</script>');

			if ($params->ModuleId == null)
				$document->addCustomTag('<script>ctItemId = "' . $params->ItemId . '";</script>');

			//Styles
			$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/style.css" type="text/css" rel="stylesheet" >');
			$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/modal.css" type="text/css" rel="stylesheet" >');
			$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/uploadfile.css" rel="stylesheet">');

			$document->addCustomTag('<link href="' . URI::root(true) . '/media/system/css/fields/calendar.min.css" rel="stylesheet" />');
			$document->addCustomTag('<script src="' . URI::root(true) . '/media/system/js/fields/calendar-locales/date/gregorian/date-helper.min.js" defer></script>');
			$document->addCustomTag('<script src="' . URI::root(true) . '/media/system/js/fields/calendar.min.js" defer></script>');

			Text::script('COM_CUSTOMTABLES_JS_SELECT_RECORDS');
			Text::script('COM_CUSTOMTABLES_JS_SELECT_DO_U_WANT_TO_DELETE1');
			Text::script('COM_CUSTOMTABLES_JS_SELECT_DO_U_WANT_TO_DELETE');
			Text::script('COM_CUSTOMTABLES_JS_NOTHING_TO_SAVE');
			Text::script('COM_CUSTOMTABLES_JS_SESSION_EXPIRED');
			Text::script('COM_CUSTOMTABLES_SELECT');
			Text::script('COM_CUSTOMTABLES_SELECT_NOTHING');
			Text::script('COM_CUSTOMTABLES_ADD');
			Text::script('COM_CUSTOMTABLES_REQUIRED');
			Text::script('COM_CUSTOMTABLES_NOT_SELECTED');
			Text::script('COM_CUSTOMTABLES_JS_EMAIL_INVALID');
			Text::script('COM_CUSTOMTABLES_JS_URL_INVALID');
			Text::script('COM_CUSTOMTABLES_JS_SECURE_URL_INVALID');
			Text::script('COM_CUSTOMTABLES_JS_SIGNATURE_REQUIRED');
			Text::script('COM_CUSTOMTABLES_JS_HOSTNAME_INVALID');
			Text::script('COM_CUSTOMTABLES_JS_SIGNATURE_REQUIRED');
		}
	}
}