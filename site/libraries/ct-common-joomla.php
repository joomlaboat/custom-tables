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

use DateInvalidTimeZoneException;
use DateTimeZone;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class common
{
	public static function convertClassString(string $class_string): string
	{
		return $class_string;
	}

	public static function translate(string $text, $value = null): string
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
				$lang->load($extension, JPATH_SITE);

				if (is_null($value))
					return Text::_($text);
				else
					return Text::sprintf($text, $value);
			} else
				return $text;
		} else
			return $new_text;
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostString($parameter, $default = null): ?string
	{
		$input = self::inputPostVariable();
		return $input->getString($parameter, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.
	 */
	protected static function inputPostVariable()
	{
		$app = Factory::getApplication();
		$input = $app->input;

		// Check content type
		$contentType = $app->input->server->get('CONTENT_TYPE');

		if (
			$contentType === 'applicationjson' ||         // Joomla's modified version
			$contentType === 'application/json' ||        // Standard version
			strpos(($contentType ?? ''), 'application/json') !== false  // Partial match for safety

		) {
			// Handle JSON data
			return $input->json;
		} else {
			// Handle regular form data
			return $input->post;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostFloat($parameter, $default = null): ?float
	{
		$input = self::inputPostVariable();
		return $input->getFloat($parameter, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputGetFloat($parameter, $default = null): ?float
	{
		return Factory::getApplication()->input->getFloat($parameter, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostInt(string $parameter, ?int $default = null): ?int
	{
		$input = self::inputPostVariable();
		return $input->getInt($parameter, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputGetInt(string $parameter, ?int $default = null): ?int
	{
		return Factory::getApplication()->input->getInt($parameter, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostUInt($parameter, $default = null): ?int
	{
		$input = self::inputPostVariable();
		return $input->getInt($parameter, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputGetUInt($parameter, $default = null): ?int
	{
		return Factory::getApplication()->input->getInt($parameter, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostCmd(string $parameter, $default = null): ?string
	{
		$input = self::inputPostVariable();
		return $input->getCmd($parameter, $default);
	}

	/**
	 * @since 3.2.9
	 */
	public static function inputGetCmd(string $parameter, $default = null): ?string
	{
		try {
			return Factory::getApplication()->input->getCmd($parameter, $default);
		} catch (Throwable $e) {
			return $default;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostRaw(string $parameter, $default = null)
	{
		return Factory::getApplication()->input->get($parameter, $default, "RAW");
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputGetRow(string $parameter, $default = null)
	{
		return Factory::getApplication()->input->get($parameter, $default, "RAW");
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostBase64(string $parameter, $default = null)
	{
		$input = self::inputPostVariable();
		return $input->get($parameter, $default, 'BASE64');
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputGetWord(string $parameter, $default = null)
	{
		return Factory::getApplication()->input->get($parameter, $default, 'BASE64');
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostAlnum(string $parameter, $default = null)
	{
		$input = self::inputPostVariable();
		return $input->get($parameter, $default, 'ALNUM');
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputGetAlnum(string $parameter, $default = null)
	{
		return Factory::getApplication()->input->get($parameter, $default, 'ALNUM');
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputPostArray($parameter, ?array $default = null)
	{
		$input = self::inputPostVariable();
		return $input->get($parameter, $default, 'array');
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputSet(string $parameter, string $value): void
	{
		Factory::getApplication()->input->set($parameter, $value);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputFiles(string $fileId)
	{
		return Factory::getApplication()->input->files->get($fileId);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputCookieSet(string $parameter, $value, $time, $path, $domain): void
	{
		Factory::getApplication()->input->cookie->set($parameter, $value, $time, $path, $domain);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputCookieGet($parameter)
	{
		return Factory::getApplication()->cookie->get($parameter);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputServer($parameter, $default = null, $filter = null)
	{
		return Factory::getApplication()->input->server->get($parameter, $default, $filter);
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
			$string = self::ctStripTags($decoded);

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
				return '<span class="hasTip" title="' . $title . '" style="cursor:help;">' . trim($newString) . '...</span>';
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

	/**
	 * @since 3.2.9
	 */
	public static function getReturnToURL(bool $decode = true): ?string
	{
		try {
			$returnto = self::inputGet('returnto', null, 'BASE64');
		} catch (Exception $e) {
			return null;
		}

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

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function inputGet(string $parameter, $default, string $filter)
	{
		return Factory::getApplication()->input->get($parameter, $default, $filter);
	}

	public static function makeReturnToURL(string $currentURL = null): ?string
	{
		if ($currentURL === null)
			$currentURL = self::curPageURL();

		return base64_encode($currentURL);
	}

	//Returns base64 encoded/decoded url in Joomla and Sessions ReturnTo variable reference in WP or reference converted to URL

	public static function curPageURL(): string
	{
		//Uri::root() returns the string http://www.mydomain.org/mysite/ (or https if you're using SSL, etc.).
		//self::UriRoot(true) returns the string /mysite.
		$WebsiteRoot = str_replace(Uri::root(true), '', Uri::root());
		//Uri$WebsiteRoot = http://www.mydomain.org/
		$RequestURL = self::getServerParam("REQUEST_URI");

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
		return $_SERVER[$param] ?? null;
	}

	public static function UriRoot(bool $pathOnly = false, bool $addTrailingSlash = false): string
	{
		//Uri::root() returns the string http://www.mydomain.org/mysite (or https if you're using SSL, etc.).
		//self::UriRoot(true) returns the string /mysite

		$url = Uri::root($pathOnly);
		if (strlen($url) > 0 and $url[strlen($url) - 1] == '/')
			$url = substr($url, 0, strlen($url) - 1);

		if ($addTrailingSlash and ($url == "" or $url[strlen($url) - 1] != '/'))
			$url .= '/';

		return $url;
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

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function saveString2File(string $filePath, string $content)
	{
		try {
			@file_put_contents($filePath, $content);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
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
		//date_default_timezone_set('UTC');
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function getWhereParameter($field): string
	{
		$list = self::getWhereParameters();

		if ($list === null)
			return '';

		foreach ($list as $l) {

			$p = explode('==', $l);//This is for exact text search
			if (count($p) == 2) {
				$fld_name = str_replace('_t_', '', $p[0]);
				$fld_name = str_replace('_r_', '', $fld_name); //range
				if ($fld_name == $field and isset($p[1]))
					return $p[1];

			} else {
				$p = explode('=', $l);//Contain text search
				if (count($p) == 2) {
					$fld_name = str_replace('_t_', '', $p[0]);
					$fld_name = str_replace('_r_', '', $fld_name); //range
					if ($fld_name == $field and isset($p[1]))
						return $p[1];
				}
			}
		}
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	protected static function getWhereParameters(): ?array
	{
		$value = self::inputGetString('where');
		if ($value !== null) {
			$b = urldecode($value);//base64_decode
			$b = str_replace(' or ', ' and ', $b);
			$b = str_replace(' OR ', ' and ', $b);
			$b = str_replace(' AND ', ' and ', $b);
			return explode(' and ', $b);
		}
		return null;
	}

	/**
	 * @since 3.2.9
	 */
	public static function inputGetString($parameter, $default = null): ?string
	{
		try {
			return Factory::getApplication()->input->get->getString($parameter, $default);
		} catch (Exception $e) {
			return $default;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
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

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	public static function loadJSAndCSS(Params $params, Environment $env, string $fieldInputPrefix): void
	{
		$app = Factory::getApplication();
		$document = $app->getDocument();

		if (empty($params->ModuleId)) {
			//JQuery and Bootstrap
			if (CUSTOMTABLES_JOOMLA_MIN_4) {
				HTMLHelper::_('jquery.framework');
				$document->addCustomTag('<link rel="stylesheet" href="' . URI::root(true) . '/media/system/css/fields/switcher.css">');
			} else {
				$document->addCustomTag('<script src="' . URI::root(true) . '/media/jui/js/jquery.min.js"></script>');
				$document->addCustomTag('<script src="' . URI::root(true) . '/media/jui/js/bootstrap.min.js"></script>');
			}
		}

		$document->addCustomTag('<script src="' . CUSTOMTABLES_LIBRARIES_WEBPATH . 'js/jquery.uploadfile.min.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_LIBRARIES_WEBPATH . 'js/jquery.form.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/uploader.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/ajax.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/base64.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/catalog.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/edit.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/esmulti.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/modal.js"></script>');
		$document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/virtualselect/virtual-select.min.js"></script>');
		$document->addCustomTag('<link rel="stylesheet" href="' . URI::root(true) . '/components/com_customtables/libraries/virtualselect/virtual-select.min.css" />');

		$joomla_params = ComponentHelper::getParams('com_customtables');
		$googleMapAPIKey = $joomla_params->get('googlemapapikey');

		if ($googleMapAPIKey !== null and $googleMapAPIKey != '') {
			$document->addCustomTag('<script async defer src="https://maps.google.com/maps/api/js?key='
				. $googleMapAPIKey . '&loading=async"></script>');//&sensor=false.&callback=initMap
		}

		$js = [];
		$js[] = 'let ctWebsiteRoot = "' . $env->WebsiteRoot . '";';
		$js[] = 'let ctFieldInputPrefix = "' . $fieldInputPrefix . '";';
		$js[] = 'let gmapdata = [];';
		$js[] = 'let gmapmarker = [];';
		$js[] = '
if (typeof window.CTEditHelper === "undefined") {
	window.CTEditHelper = new CustomTablesEdit("Joomla",' . (explode('.', CUSTOMTABLES_JOOMLA_VERSION)[0]) . ',' . ($params->ItemId ?? 0) . ');
}
';

		$document->addCustomTag('
<script>
    ' . implode('
', $js) . '
</script>
');

		$document->addCustomTag('
<style>
	:root {--ctToolBarIconSize: 16px;--ctToolBarIconFontSize: 16px;}
	
	.toolbarIcons{
		text-decoration: none;
	}
	
	.toolbarIcons a{
		text-decoration: none;
	}
	
	.ctToolBarIcon{
		width: var(--ctToolBarIconSize);
		height: var(--ctToolBarIconSize);
	}
	
	.ctToolBarIcon + span {
		margin-left:10px;
	}
	
	.ctToolBarIcon2x{
		width: calc(var(--ctToolBarIconSize) * 2);
		height: calc(var(--ctToolBarIconSize) * 2);
		font-size: 1.5em;
	}
	
	.ctToolBarIcon2x + span {
		margin-left:15px;
	}
</style>
');

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
		Text::script('COM_CUSTOMTABLES_SEARCH_ALERT_MINLENGTH');

		if ($env->toolbarIcons == 'font-awesome-4' or $env->toolbarIcons == 'font-awesome-5' or $env->toolbarIcons == 'font-awesome-6') {
			$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

			// Check if Font Awesome is loaded
			$isFontAwesomeLoaded = $wa->assetExists('style', 'fontawesome');

			if (!$isFontAwesomeLoaded) {
				common::enqueueMessage('Font Awesome icons have been selected for the toolbar, but the Font Awesome library is not loaded in your template.'
					. ' Please ensure the library is included for proper icon display.');
			}
		}

		if ($env->toolbarIcons == 'bootstrap') {
			$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

			// Check if Bootstrap Icons are loaded
			$isBootstrapLoaded = $wa->assetExists('style', 'bootstrap-icons');

			if (!$isBootstrapLoaded) {
				common::enqueueMessage('Bootstrap icons have been selected for the toolbar, but the Bootstrap Icons library is not loaded in your template. Please ensure the library is included for proper icon display.');
			}
		}
	}

	/**
	 * @since 3.2.9
	 */
	public static function enqueueMessage($text, string $type = 'error'): void
	{
		try {
			Factory::getApplication()->enqueueMessage($text, $type);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public static function filterText(?string $text): string
	{
		if ($text === null)
			return '';

		return ComponentHelper::filterText($text);
	}

	/**
	 * Redirect user to a specified URL with optional notification message
	 *
	 * @param string $link The URL to redirect to
	 * @param string|null $message Optional message to display after redirect
	 * @param bool $success Whether the message is a success (true) or error (false) notification
	 * @return void
	 *
	 * @throws Exception
	 * @since 3.5.1
	 */
	public static function redirect(string $link, ?string $message = null, bool $success = true): void
	{
		if ($message !== null) {
			$app = Factory::getApplication();
			$messageType = $success ? 'message' : 'error';
			$app->enqueueMessage($message, $messageType);
		}

		$app = Factory::getApplication();
		$app->redirect(Route::_($link, false));
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 *
	 * @since 3.0.0
	 */
	public static function formatDateFromTimeStamp($timeStamp = null, ?string $format = 'Y-m-d H:i:s'): ?string
	{
		// Get Joomla version
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$config = Factory::getContainer()->get('config');
		else
			$config = Factory::getConfig();

		$timezone = new DateTimeZone($config->get('offset'));
		$date = Factory::getDate($timeStamp, $timezone);
		$date->setTimezone($timezone);

		return $date->format($format, true);
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 *
	 * @since 3.0.0
	 */
	public static function formatDate(?string $dateString = null, ?string $format = 'Y-m-d H:i:s', ?string $emptyValue = 'Never'): ?string
	{
		if ($format === null)
			$format = 'Y-m-d H:i:s';

		if ($dateString === null or $dateString == '0000-00-00 00:00:00')
			return $emptyValue;

		$config = Factory::getContainer()->get('config');
		$timezone = new DateTimeZone($config->get('offset'));
		$date = Factory::getDate($dateString, $timezone);

		if ($format === 'timestamp')
			return (string)$date->getTimestamp();

		$date->setTimezone($timezone);
		return $date->format($format, true);
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 *
	 * @since 3.0.0
	 */
	public static function currentDate(string $format = 'Y-m-d H:i:s'): string
	{
		$date = Factory::getDate();
		$config = Factory::getContainer()->get('config');
		$timezone = new DateTimeZone($config->get('offset'));
		$date->setTimezone($timezone);

		// Format the date and time as a string in the desired format
		return $date->format($format, true);
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.0.0
	 */
	public static function clientAdministrator(): bool
	{
		//returns true when called from the back-end / administrator
		$app = Factory::getApplication();
		return $app->isClient('administrator');
	}

	/**
	 * @throws Exception
	 * @since 3.6.7
	 */
	public static function setUserState(string $key, $value)
	{
		Factory::getApplication()->setUserState($key, $value);
	}

	/**
	 * @throws Exception
	 * @since 3.6.7
	 */
	public static function getUserState(string $key, $default = null)
	{
		return Factory::getApplication()->getUserState($key) ?? $default;
	}

	public static function getSiteName()
	{
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$config = Factory::getContainer()->get('config');
		else
			$config = Factory::getConfig();

		return $config->get('config.sitename');
	}

	/**
	 * @throws \PHPMailer\PHPMailer\Exception
	 * @since 3.4.6
	 */
	static public function sendEmail($email, $emailSubject, $emailBody, $isHTML = true, $attachments = array()): bool
	{
		try {
			if (Version::MAJOR_VERSION >= 5)
				$mailer = Factory::getContainer()->get('mailer');
			else
				$mailer = Factory::getMailer();
		} catch (Exception $e) {
			self::enqueueMessage($e->getMessage());
			return false;
		}

		$sender = array(
			self::getMailFrom(),
			self::getEmailFromName()
		);

		$mailer->setSender($sender);
		$mailer->addRecipient($email);
		$mailer->setSubject($emailSubject);
		$mailer->setBody($emailBody);
		$mailer->isHTML($isHTML);

		foreach ($attachments as $attachment)
			$mailer->addAttachment($attachment);

		try {
			$send = @$mailer->Send();
		} catch (Exception $e) {
			self::enqueueMessage($e->getMessage());
			return false;
		}

		if ($send !== true)
			return false;

		return true;
	}

	public static function getMailFrom()
	{
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$config = Factory::getContainer()->get('config');
		else
			$config = Factory::getConfig();

		return $config->get('mailfrom');
	}

	public static function getEmailFromName()
	{
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$config = Factory::getContainer()->get('config');
		else
			$config = Factory::getConfig();

		return $config->get('fromname');
	}
}