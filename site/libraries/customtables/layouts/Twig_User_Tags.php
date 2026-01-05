<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use CustomTables\ctProHelpers;

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
				throw new Exception('Warning: The {{ user.name }} tag is not supported in the current version of the Custom Tables.');
			}
		}

		throw new Exception('Warning: User: ' . $user_id . ' not found.');
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
			if (defined('_JEXEC'))
				return $userRow['username'];
			elseif (defined('WPINC'))
				return $userRow['user_login'];
			else
				throw new Exception('Warning: The {{ user.username }} tag is not supported in the current version of the Custom Tables.');
		}

		throw new Exception('Warning: User: ' . $user_id . ' not found.');
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
			throw new Exception('Warning: The {{ user.email }} tag: User not logged in.');

		$userRow = CTUser::GetUserRow($user_id);
		if ($userRow !== null) {
			if (defined('_JEXEC'))
				return $userRow['email'];
			elseif (defined('WPINC'))
				return $userRow['user_email'];
			else
				throw new Exception('Warning: The {{ user.email }} tag is not supported in the current version of the Custom Tables.');
		}

		throw new Exception('Warning: The {{ user.email }} tag: User: ' . $user_id . ' not found.');
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
			throw new Exception('Warning: The {{ user.lastvisitdate }} tag is not supported in the current version of the Custom Tables.');
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

			if ($isJoomla)
				$date = $userRow['registerDate'];
			elseif ($isWordPress)
				$date = $userRow['user_registered'];
			else
				throw new Exception('Warning: The {{ user.registerdate }} tag is not supported in the current version of the Custom Tables.');

			if ($date == '0000-00-00 00:00:00') {
				return 'Never';
			} else {

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

	/**
	 * @throws Exception
	 * @since 3.3.5
	 */
	function customfield($customFieldName, $user_id = 0): ?string
	{
		if ((int)$user_id == 0)
			$user_id = $this->user_id;

		$customFieldValue = CTMiscHelper::getCustomFieldValue('com_users.user', $customFieldName, (int)$user_id);

		if ($customFieldValue !== null)
			return $customFieldValue['value'];
		else
			return 'Wrong custom field name "' . $customFieldName . '".';
	}
}

