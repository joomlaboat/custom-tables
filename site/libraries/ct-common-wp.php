<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use JoomlaBasicMisc;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class common
{
	public static function enqueueMessage($text, $type): void
	{
		echo '<div class="success-message">' . $text . '</div>';
	}

	public static function translate(string $text, int|float $value = null)
	{
		return __($text, 'customtables');
	}

	public static function inputPostString($parameter, $default = null)
	{
		if (isset($_POST['_wpnonce'])) {
			$nonce = wp_unslash($_POST['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'post'))
				return $default;
		}

		if (!isset($_POST[$parameter]))
			return $default;

		$source = wp_strip_all_tags($_POST[$parameter]);
		return sanitize_text_field($source);
	}

	public static function inputGetString($parameter, $default = null)
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter];

		//$value = get_query_var($parameter);

		if ($value === null)
			return $default;

		$source = wp_strip_all_tags(wp_unslash($value));
		return sanitize_text_field($source);
	}

	public static function inputPostFloat($parameter, $default = null)
	{
		if (isset($_POST['_wpnonce'])) {
			$nonce = wp_unslash($_POST['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'post'))
				return $default;
		}

		// Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
		if (!isset($_POST[$parameter]))
			return $default;

		// Only use the first floating point value
		preg_match('/-?\d+(\.\d+)?/', (string)$_POST[$parameter], $matches);
		return @ (float)$matches[0];
	}

	public static function inputGetFloat($parameter, $default = null)
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter];

		if ($value === null)
			return $default;

		// Only use the first floating point value
		preg_match('/-?\d+(\.\d+)?/', (string)$value, $matches);
		return @ (float)$matches[0];
	}

	public static function inputPostInt($parameter, $default = null)
	{
		if (isset($_POST['_wpnonce'])) {
			$nonce = wp_unslash($_POST['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'post'))
				return $default;
		}

		// Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
		if (!isset($_POST[$parameter]))
			return $default;

		preg_match('/-?\d+/', (string)$_POST[$parameter], $matches);
		return @ (int)$matches[0];
	}

	public static function inputPostUInt($parameter, $default = null)
	{
		if (isset($_POST['_wpnonce'])) {
			$nonce = wp_unslash($_POST['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'post'))
				return $default;
		}

		// Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
		if (!isset($_POST[$parameter]))
			return $default;

		preg_match('/-?\d+/', (string)$_POST[$parameter], $matches);
		return @ abs((int)$matches[0]);
	}

	public static function inputGetUInt($parameter, $default = null)
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter];

		if ($value === null)
			return $default;

		preg_match('/-?\d+/', (string)$_GET[$parameter], $matches);
		return @ abs((int)$matches[0]);
	}

	public static function inputPostCmd(string $parameter, $default = null)
	{
		if (isset($_POST['_wpnonce'])) {
			$nonce = wp_unslash($_POST['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'post'))
				return $default;
		}

		// Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
		if (!isset($_POST[$parameter]))
			return $default;

		$result = (string)preg_replace('/[^A-Z\d_\.-]/i', '', $_POST[$parameter]);
		return ltrim($result, '.');
	}

	public static function inputGetCmd(string $parameter, $default = null)
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter];

		//$value = get_query_var($parameter);

		if ($value === null)
			return $default;

		$result = (string)preg_replace('/[^A-Z\d_\.-]/i', '', $value);
		return ltrim($result, '.');
	}

	public static function inputPostRow(string $parameter, $default = null)
	{
		if (isset($_POST['_wpnonce'])) {
			$nonce = wp_unslash($_POST['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'post'))
				return $default;
		}

		// Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
		if (!isset($_POST[$parameter]))
			return $default;

		return stripslashes($_POST[$parameter]);
	}

	public static function inputGetRow(string $parameter, $default = null)
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter];

		//$value = get_query_var($parameter);

		if ($value === null)
			return $default;

		return stripslashes($value);
	}

	public static function inputPostBase64(string $parameter, $default = null)
	{
		if (isset($_POST['_wpnonce'])) {
			$nonce = wp_unslash($_POST['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'post'))
				return $default;
		}

		// Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
		if (!isset($_POST[$parameter]))
			return $default;

		// Allow a-z, 0-9, slash, plus, equals.
		return (string)preg_replace('/[^A-Z\d\/+=]/i', '', $_POST[$parameter]);
	}

	public static function inputGetBase64(string $parameter, $default = null)
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter];

		//$value = get_query_var($parameter);

		if ($value === null)
			return $default;

		// Allow a-z, 0-9, slash, plus, equals.
		return (string)preg_replace('/[^A-Z\d\/+=]/i', '', $value);
	}

	public static function inputGetWord(string $parameter, $default = null)
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter];

		//$value = get_query_var($parameter);

		if ($value === null)
			return $default;

		// Only allow characters a-z, and underscores
		return (string)preg_replace('/[^A-Z_]/i', '', $value);
	}

	public static function inputPostAlnum(string $parameter, $default = null)
	{
		if (isset($_POST['_wpnonce'])) {
			$nonce = wp_unslash($_POST['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'post'))
				return $default;
		}

		// Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
		if (!isset($_POST[$parameter]))
			return $default;

		// Allow a-z and 0-9 only
		return (string)preg_replace('/[^A-Z\d]/i', '', $_POST[$parameter]);
	}

	public static function inputGetAlnum(string $parameter, $default = null)
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce']);
			if (function_exists('\wp_verify_nonce') and !wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter];

		if ($value === null)
			return $default;

		// Allow a-z and 0-9 only
		return (string)preg_replace('/[^A-Z\d]/i', '', $value);
	}

	public static function inputPost($parameter, $default = null, $filter = null)
	{
		echo 'common::inputPost not supported in WordPress';
		return null;
	}

	public static function inputSet(string $parameter, string $value): void
	{
		echo 'common::inputSet not supported in WordPress';
	}

	public static function inputFiles(string $fileId)
	{
		echo 'common::inputFiles not supported in WordPress';
		return null;
	}

	public static function inputCookieSet(string $parameter, $value, $time, $path, $domain): void
	{
		die('common::inputCookieSet not supported in WordPress');
	}

	public static function inputCookieGet($parameter)
	{
		die('common::inputCookieGet not supported in WordPress');
	}

	public static function inputServer($parameter, $default = null, $filter = null)
	{
		die('common::inputServer not supported in WordPress');
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
			$string = wp_strip_all_tags($decoded);

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

	public static function ctJsonEncode($argument): bool|string
	{
		return wp_json_encode($argument);
	}

	public static function ctStripTags($argument): bool|string
	{
		return wp_strip_all_tags($argument);
	}

	public static function getReturnToURL(bool $decode = true): ?string
	{
		$returnto_id = common::inputGetInt('returnto', null);

		if (empty($returnto_id))
			return null;

		if ($decode) {
			// Construct the session variable key from the received returnto ID
			$returnto_key = 'returnto_' . $returnto_id;

			// Start the session (if not started already)
			if (!headers_sent() and !session_id()) {
				session_start();
			}

			// Retrieve the value associated with the returnto key from the $_SESSION
			return $_SESSION[$returnto_key] ?? '';
		} else
			return $returnto_id;
	}

	public static function inputGetInt(string $parameter, ?int $default = null): ?int
	{
		if (isset($_GET['_wpnonce'])) {
			$nonce = wp_unslash($_GET['_wpnonce'] ?? '');
			if (function_exists('\wp_verify_nonce') and !\wp_verify_nonce($nonce, 'get')) {
				//return $default;
			}
		}

		if (!isset($_GET[$parameter]))
			return $default;

		$value = $_GET[$parameter] ?? null;

		if ($value === null)
			return $default;

		// Allow a-z, 0-9, underscore, dot, dash. Also remove leading dots from result.
		preg_match('/-?\d+/', (string)$value, $matches);
		return @ (int)$matches[0];
	}

	public static function makeReturnToURL(string $currentURL = null): ?string
	{
		if ($currentURL === null) {
			// Get the current URL
			//$current_url = esc_url_raw(home_url(add_query_arg(array(), $wp->request)));

			$currentURL = JoomlaBasicMisc::curPageURL();
		}

		// Generate a unique identifier for the session variable
		$returnto_id = uniqid();

		$returnto_key = 'returnto_' . $returnto_id;

		// Start the session (if not started already)
		if (!headers_sent() and !session_id()) {
			session_start();
		}

		// Set the session variable using the generated ID as the key
		$_SESSION[$returnto_key] = $currentURL;

		return $returnto_id;
	}

	public static function curPageURL(): string
	{
		$WebsiteRoot = str_replace(site_url(), '', home_url());
		$RequestURL = $_SERVER["REQUEST_URI"];

		if ($WebsiteRoot !== '' && str_ends_with($WebsiteRoot, '/')) {
			if ($RequestURL !== '' && $RequestURL[0] === '/') {
				$WebsiteRoot = rtrim($WebsiteRoot, '/');
			}
		}

		return $WebsiteRoot . $RequestURL;
	}

	public static function inputGet(string $parameter, $default, string $filter)
	{
		echo 'common::inputGet not supported in WordPress';
		return null;
	}

	public static function ctParseUrl($argument)
	{
		return wp_parse_url($argument);
	}

	public static function generateRandomString(int $length = 32): string
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++)
			$randomString .= $characters[wp_rand(0, $charactersLength - 1)];

		return $randomString;
	}
}