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

use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use LayoutProcessor;
use CustomTables\ctProHelpers;

class Twig_Fields_Tags
{
	var CT $ct;
	var bool $isTwig;

	function __construct(CT &$ct, bool $isTwig = true)
	{
		$this->ct = &$ct;
		$this->isTwig = $isTwig;
	}

	function json(): string
	{
		return common::ctJsonEncode(Fields::shortFieldObjects($this->ct->Table->fields));
	}

	function list($param = 'fieldname'): array
	{
		$available_params = ['fieldname', 'title', 'defaultvalue', 'description', 'isrequired', 'isdisabled', 'type', 'typeparams', 'valuerule', 'valuerulecaption'];

		if (!in_array($param, $available_params)) {
			$this->ct->errors[] = '{{ fields.array("' . $param . '") }} - Unknown parameter.';
			return [];
		}

		$fields = Fields::shortFieldObjects($this->ct->Table->fields);
		$list = [];
		foreach ($fields as $field)
			$list[] = $field[$param];

		return $list;
	}

	function count(): int
	{
		return count($this->ct->Table->fields);
	}
}

class Twig_User_Tags
{
	var CT $ct;
	var int $user_id;

	function __construct(&$ct)
	{
		$this->ct = &$ct;
		$this->user_id = (int)$this->ct->Env->user->id;
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function name($user_id = 0): ?string
	{
		if ($user_id == 0)
			$user_id = $this->user_id;

		if ($user_id == 0)
			return '';

		$userRow = CTUser::GetUserRow($user_id);
		if ($userRow !== null) {
			if (defined('_JEXEC')) {
				return $userRow['name'];
			} elseif (defined('WPINC')) {
				return $userRow['display_name'];
			} else {
				common::enqueueMessage('Warning: The {{ user.name }} tag is not supported in the current version of the Custom Tables.');
				return null;
			}
		}

		common::enqueueMessage('Warning: User: ' . $user_id . ' not found.');
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function username($user_id = 0): ?string
	{
		if ($user_id == 0)
			$user_id = $this->user_id;

		if ($user_id == 0)
			return '';

		$userRow = CTUser::GetUserRow($user_id);
		if ($userRow !== null) {
			if (defined('_JEXEC')) {
				return $userRow['username'];
			} elseif (defined('WPINC')) {
				return $userRow['user_login'];
			} else {
				common::enqueueMessage('Warning: The {{ user.username }} tag is not supported in the current version of the Custom Tables.');
				return null;
			}
		}

		common::enqueueMessage('Warning: User: ' . $user_id . ' not found.');
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function email($user_id = 0): ?string
	{
		if ($user_id == 0)
			$user_id = $this->user_id;

		if ($user_id == 0)
			return '';

		$userRow = CTUser::GetUserRow($user_id);
		if ($userRow !== null) {
			if (defined('_JEXEC')) {
				return $userRow['email'];
			} elseif (defined('WPINC')) {
				return $userRow['user_email'];
			} else {
				common::enqueueMessage('Warning: The {{ user.email }} tag is not supported in the current version of the Custom Tables.');
				return null;
			}
		}

		return 'user: ' . $user_id . ' not found.';
	}

	function id(): int
	{
		if ($this->user_id == 0)
			return 0;

		return $this->user_id;
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function lastvisitdate(int $user_id = 0, string $format = 'Y-m-d H:i:s'): ?string
	{
		if ($user_id == 0)
			$user_id = $this->user_id;

		if ($user_id == 0)
			return '';

		// Check if the environment is recognized
		$isJoomla = defined('_JEXEC');
		$isWordPress = defined('WPINC');

		if ($isJoomla) {
			$userRow = CTUser::GetUserRow($user_id);
			if ($userRow !== null) {
				if ($userRow['lastvisitDate'] == '0000-00-00 00:00:00')
					return 'Never';
				else
					$date = $userRow['lastvisitDate'];
			} else
				return 'user: ' . $user_id . ' not found.';

			$timestamp = strtotime($date);
		} elseif ($isWordPress) {
			$timestamp = null;
			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('user_id', $user_id);
			$whereClause->addCondition('meta_key', 'session_tokens');
			$rows = database::loadAssocList('#__usermeta', ['meta_value AS session_tokens'], $whereClause, 'umeta_id', 'desc', 1);

			if (count($rows) === 0)
				return 'Never';

			$serialized_session_tokens = $rows[0]['session_tokens'];

			// Unserialize the data
			$session_tokens_array = unserialize($serialized_session_tokens);

			// The unserialized data is now an array, where the keys are the session token strings
			// and the values are arrays containing the session token data
			$found = false;
			foreach ($session_tokens_array as $token_data) {
				// Check if the token data array has a 'login' key
				if (isset($token_data['login'])) {
					$timestamp = $token_data['login'];
					$found = true;
					break; // Exit the loop after finding the first 'login' value
				}
			}
			if (!$found)
				return 'Probably never';
		} else {
			common::enqueueMessage('Warning: The {{ user.lastvisitdate }} tag is not supported in the current version of the Custom Tables.');
			return null;
		}

		if ($format === 'timestamp')
			return (string)$timestamp;

		if ($isJoomla)
			return HTMLHelper::date($timestamp, $format);
		elseif ($isWordPress)
			return date_i18n($format, $timestamp);

		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function registerdate(int $user_id = 0, string $format = 'Y-m-d H:i:s'): ?string
	{
		if ($user_id == 0)
			$user_id = $this->user_id;

		if ($user_id == 0)
			return '';

		$userRow = CTUser::GetUserRow($user_id);
		if ($userRow !== null) {

			// Check if the environment is recognized
			$isJoomla = defined('_JEXEC');
			$isWordPress = defined('WPINC');

			if ($isJoomla) {
				$date = $userRow['registerDate'];
			} elseif ($isWordPress) {
				$date = $userRow['user_registered'];
			} else {
				common::enqueueMessage('Warning: The {{ user.registerdate }} tag is not supported in the current version of the Custom Tables.');
				return null;
			}

			if ($date == '0000-00-00 00:00:00')
				return 'Never';
			else {

				$timestamp = strtotime($date);

				if ($format === 'timestamp')
					return (string)$timestamp;

				if ($isJoomla) {
					return HTMLHelper::date($timestamp, $format);
				}

				if ($isWordPress) {
					return date_i18n($format, $timestamp);
				}
			}
		}
		return 'user: ' . $user_id . ' not found.';
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function usergroups($user_id = 0): array
	{
		if ($user_id == 0)
			$user_id = $this->user_id;

		if ($user_id == 0)
			return [];

		return CTUser::GetUserGroups($user_id);
	}
}

class Twig_Url_Tags
{
	var CT $ct;
	var bool $isTwig;

	function __construct(CT &$ct, $isTwig = true)
	{
		$this->ct = &$ct;
		$this->isTwig = $isTwig;
	}

	function link(): string
	{
		return $this->ct->Env->current_url;
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function base64(): ?string
	{
		if (defined('_JEXEC')) {
			return $this->ct->Env->encoded_current_url;
		} else {
			common::enqueueMessage('Warning: The {{ url.base64() }} tag is not supported in the current version of the Custom Tables for WordPress plugin.');
			return null;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function root(): ?string
	{
		if (!$this->ct->Env->advancedTagProcessor) {
			//$this->ct->errors[] = 'url.root: This Field Type available in PRO version only.';
			common::enqueueMessage('Warning: The {{ url.root }} ' . common::translate('COM_CUSTOMTABLES_AVAILABLE'));
			return null;
		}

		$include_host = false;

		$functionParams = func_get_args();
		if (isset($functionParams[0])) {

			if (is_bool($functionParams[0]))
				$include_host = $functionParams[0];
			elseif ($functionParams[0] == 'includehost')
				$include_host = true;
		}

		$add_trailing_slash = true;
		if (isset($functionParams[1])) {
			if (is_bool($functionParams[1]))
				$add_trailing_slash = $functionParams[1];
			elseif ($functionParams[0] == 'notrailingslash')
				$add_trailing_slash = false;
		}

		if ($include_host) {
			if (defined('_JEXEC'))
				$WebsiteRoot = Uri::root();
			else
				$WebsiteRoot = home_url();
		} else
			$WebsiteRoot = CUSTOMTABLES_MEDIA_HOME_URL;

		if ($add_trailing_slash) {
			if ($WebsiteRoot == '' or $WebsiteRoot[strlen($WebsiteRoot) - 1] != '/') //Root must have a slash character / in the end
				$WebsiteRoot .= '/';
		} else {
			$l = strlen($WebsiteRoot);
			if ($WebsiteRoot != '' and $WebsiteRoot[$l - 1] == '/')
				$WebsiteRoot = substr($WebsiteRoot, 0, $l - 1);//delete trailing slash
		}

		return $WebsiteRoot;
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getuint($param, $default = 0): ?int
	{
		return common::inputGetUInt($param, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getfloat($param, $default = 0): float
	{
		return common::inputGetFloat($param, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getword($param, $default = ''): string
	{
		return common::inputGetWord($param, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getalnum($param, $default = ''): string
	{
		return common::inputGetCmd($param, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getcmd($param, $default = ''): string
	{
		return common::inputGetCmd($param, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getstringandencode($param, $default = ''): ?string
	{
		if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers')) {
			return ctProHelpers::getstringandencode($param, $default);
		} else {
			common::enqueueMessage('Warning: The {{ url.getstringandencode() }} ' . common::translate('COM_CUSTOMTABLES_AVAILABLE'));
			return null;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getstring($param, $default = ''): string
	{
		return common::inputGetString($param, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getstringanddecode($param, $default = ''): ?string
	{
		if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers')) {
			return ctProHelpers::getstringanddecode($param, $default);
		} else {
			common::enqueueMessage('Warning: The {{ url.getstringanddecode() }} ' . common::translate('COM_CUSTOMTABLES_AVAILABLE'));
			return null;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function itemid(): ?int
	{
		if (defined('_JEXEC'))
			return common::inputGetInt('Itemid', 0);
		else {
			common::enqueueMessage('Warning: The {{ url.itemid }} tag is not supported in the current version of the Custom Tables for WordPress plugin.');
			return null;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function getint($param, $default = 0): ?int
	{
		return common::inputGetInt($param, $default);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function set($option, $param = ''): void
	{
		if (defined('_JEXEC'))
			common::inputSet($option, $param);
		else
			common::enqueueMessage('Warning: The {{ url.set() }} tag is not supported in the current version of the Custom Tables for WordPress plugin.');
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function server($param): ?string
	{
		if (!$this->ct->Env->advancedTagProcessor) {
			common::enqueueMessage('Warning: The {{ url.server }} ' . common::translate('COM_CUSTOMTABLES_AVAILABLE'));
			return null;
		}
		return common::getServerParam($param);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function format($format, $link_type = 'anchor', $image = '', $imagesize = '', $layoutname = '', $csv_column_separator = ','): ?string
	{
		if (defined('_JEXEC')) {
			if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
				return '';

			$link = CTMiscHelper::deleteURLQueryOption($this->ct->Env->current_url, 'frmt');
			$link = CTMiscHelper::deleteURLQueryOption($link, 'layout');
			//}

			$link = Route::_($link);

			//check if format supported
			$allowed_formats = ['csv', 'json', 'xml', 'xlsx', 'pdf', 'image'];
			if ($format == '' or !in_array($format, $allowed_formats))
				$format = 'csv';

			$link .= (!str_contains($link, '?') ? '?' : '&') . 'frmt=' . $format . '&clean=1';

			if ($layoutname != '')
				$link .= '&layout=' . $layoutname;

			if ($format == 'csv' and $csv_column_separator != ',')
				$link .= '&sep=' . $csv_column_separator;

			if ($link_type == 'anchor' or $link_type == '') {
				$allowed_sizes = ['16', '32', '48'];
				if ($imagesize == '' or !in_array($imagesize, $allowed_sizes))
					$imagesize = 32;

				if ($format == 'image')
					$format_image = 'jpg';
				else
					$format_image = $format;

				$alt = 'Download ' . strtoupper($format) . ' file';

				if ($image == '') {
					if ($this->ct->Env->toolbarIcons != '' and $format == 'csv') {
						$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-file-csv" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-file-csv" title="' . $alt . '"></i>';
					} else {
						$image = '/components/com_customtables/libraries/customtables/media/images/fileformats/' . $imagesize . 'px/' . $format_image . '.png';
						$img = '<img src="' . $image . '" alt="' . $alt . '" title="' . $alt . '" style="width:' . $imagesize . 'px;height:' . $imagesize . 'px;">';
					}
				} else
					$img = '<img src="' . $image . '" alt="' . $alt . '" title="' . $alt . '" style="width:' . $imagesize . 'px;height:' . $imagesize . 'px;">';

				return '<a href="' . $link . '" class="toolbarIcons" id="ctToolBarExport2CSV" target="_blank">' . $img . '</a>';

			} elseif ($link_type == '_value' or $link_type == 'linkonly') {
				//link only
				return $link;
			}
			return '';
		} else {
			common::enqueueMessage('Warning: The {{ url.format() }} tag is not supported in the current version of the Custom Tables for WordPress plugin.');
			return null;
		}
	}
}

class Twig_Document_Tags
{
	var CT $ct;

	function __construct(CT &$ct)
	{
		$this->ct = &$ct;
	}

	function setmetakeywords($metakeywords): void
	{
		$this->ct->document->setMetaData('keywords', $metakeywords);
	}

	function setmetadescription($metadescription): void
	{
		$this->ct->document->setMetaData('description', $metadescription);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function setpagetitle($pageTitle): void
	{
		if (defined('_JEXEC')) {
			$this->ct->document->setTitle(common::translate($pageTitle));
		} elseif (defined('WPINC')) {
			common::enqueueMessage('Warning: The {{ document.setpagetitle }} tag is not supported in the current version of the Custom Tables for WordPress.');
		} else
			common::enqueueMessage('Warning: The {{ document.setpagetitle }} tag is not supported in the current version of the Custom Tables.');
	}

	function setheadtag($tag): void
	{
		$this->ct->document->addCustomTag($tag);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function layout(string $layoutName = ''): ?string
	{
		if ($layoutName == '') {
			common::enqueueMessage('Warning: The {{ document.layout("layout_name") }} layout name is required.');
			return null;
		}

		if (!isset($this->ct->Table)) {
			$this->ct->errors[] = '{{ document.layout }} - Table not loaded.';
			return '';
		}

		$layouts = new Layouts($this->ct);
		$layout = $layouts->getLayout($layoutName);

		if (is_null($layouts->tableId)) {
			$this->ct->errors[] = '{{ document.layout("' . $layoutName . '") }} - Layout "' . $layoutName . ' not found.';
			return '';
		}

		if ($layouts->tableId != $this->ct->Table->tableid) {
			$this->ct->errors[] = '{{ document.layout("' . $layoutName . '") }} - Layout Table ID and Current Table ID do not match.';
			return '';
		}

		$twig = new TwigProcessor($this->ct, $layout, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
		$number = 1;
		$html_result = '';

		if ($layouts->layoutType == 6 and !is_null($this->ct->Records)) {
			foreach ($this->ct->Records as $row) {
				$row['_number'] = $number;
				$row['_islast'] = $number == count($this->ct->Records);

				$html_result_layout = $twig->process($row);
				if ($twig->errorMessage !== null)
					$this->ct->errors[] = $twig->errorMessage;

				if ($this->ct->Env->legacySupport) {
					$LayoutProc = new LayoutProcessor($this->ct);
					$LayoutProc->layout = $html_result_layout;
					$html_result_layout = $LayoutProc->fillLayout($row);
				}

				$html_result .= $html_result_layout;

				$number++;
			}
		} else {
			$html_result = $twig->process($this->ct->Table->record);
			if ($twig->errorMessage !== null)
				$this->ct->errors[] = $twig->errorMessage;

			if ($this->ct->Env->legacySupport) {
				$LayoutProc = new LayoutProcessor($this->ct);
				$LayoutProc->layout = $html_result;
				$html_result = $LayoutProc->fillLayout($this->ct->Table->record);
			}
		}
		return $html_result;
	}

	function sitename(): ?string
	{
		if (defined('_JEXEC'))
			return $this->ct->app->get('sitename');
		elseif (defined('WPINC'))
			return get_bloginfo('name');
		else
			common::enqueueMessage('Warning: The {{ document.sitename }} tag is not supported in the current version of the Custom Tables.');

		return null;
	}

	function languagepostfix(): string
	{
		return $this->ct->Languages->Postfix;
	}
}
