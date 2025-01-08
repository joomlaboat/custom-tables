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

class Value_user extends BaseValue
{
	function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
	{
		parent::__construct($ct, $field, $rowValue, $option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(): ?string
	{
		return self::renderUserValue($this->rowValue, $this->option_list[0] ?? '', $this->option_list[1] ?? null);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function renderUserValue(?int $value, string $field = '', ?string $format = null): ?string
	{
		if ($value === null)
			return null;

		if (defined('_JEXEC'))
			return self::renderUserValue_Joomla($value, $field, $format);
		elseif (defined('WPINC'))
			return self::renderUserValue_WordPress($value, $field, $format);
		else
			return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function renderUserValue_Joomla(?int $value, string $field = '', ?string $format = null): ?string
	{
		if ($field == 'online') {
			if (empty($value))
				return null;

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('userid', $value);

			$options = database::loadAssocList('#__session', ['userid'], $whereClause, null, null, 1);
			if (count($options) == 0)
				return 0;
			else
				return 1;
		} elseif ($field == 'usergroups') {
			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('user_id', $value);

			$groups = database::loadObjectList('#__user_usergroup_map AS m', ['GROUP_TITLE'], $whereClause);
			$group_list = [];

			foreach ($groups as $group)
				$group_list[] = $group->group_title;

			return implode(',', $group_list);
		} else {
			$allowedFields = array('id', 'name', 'email', 'username', 'registerdate', 'lastvisitdate');

			$field = strtolower($field);
			if ($field == '')
				$field = 'name';
			elseif (!in_array($field, $allowedFields)) {

				$customFieldValue = CTMiscHelper::getCustomFieldValue('com_users.user', $field, $value);
				if ($customFieldValue !== null)
					return $customFieldValue['value'];
				else
					return 'Wrong user field "' . $field . '". Available fields: ' . implode(', ', $allowedFields) . '.';
			}

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('id', $value);

			if ($field == 'registerdate')
				$real_field = 'registerDate';
			elseif ($field == 'lastvisitdate')
				$real_field = 'lastvisitDate';
			else
				$real_field = $field;

			$rows = database::loadAssocList('#__users', [$real_field], $whereClause, null, null, 1);

			if (count($rows) != 0) {
				$row = $rows[0];

				if ($field == 'registerdate') {
					return common::formatDate($row['registerDate'], $format, 'Never');
				} elseif ($field == 'lastvisitdate')
					return common::formatDate($row['lastvisitDate'], $format, 'Never');
				else
					return $row[$field];
			} else {
				if ($value != 0)
					return common::translate('COM_CUSTOMTABLES_FIELDS_USER_NOT_FOUND');
			}
		}
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function renderUserValue_WordPress(?int $value, string $field = '', ?string $format = null): ?string
	{
		if ($field == 'online') {
			if (empty($value))
				return null;


			$session_tokens = self::get_session_tokens_wordpress($value);

			if ($session_tokens) {
				$current_time = current_time('timestamp');

				foreach ($session_tokens as $token => $session_data) {
					if (isset($session_data['expiration']) && $session_data['expiration'] > $current_time) {
						return 1; // User is online
					}
				}
			}
			return 0; // User is offline

		} elseif ($field == 'usergroups') {
			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('user_id', $value);
			$whereClause->addCondition('meta_key', '#__capabilities');
			$capabilities = database::loadObjectList('#__usermeta AS m', ['meta_value'], $whereClause, null, null, 1);
			if (count($capabilities) == 0)
				return null;

			$unserialized_value = maybe_unserialize($capabilities[0]->meta_value);

			// Check if the unserialized value is an array
			if (is_array($unserialized_value)) {
				// Return the keys of the array
				$roles = array_keys($unserialized_value);
			} else
				return null;

			$user = new CTUser();
			$role_titles = [];

			foreach ($roles as $role)
				$role_titles [] = $user->showUserGroup_WordPress($role);

			return implode(',', $role_titles);
		} else {
			$allowedFields = array('id', 'name', 'email', 'username', 'registerdate', 'lastvisitdate');

			$field = strtolower($field);
			if ($field == '')
				$field = 'name';
			elseif (!in_array($field, $allowedFields))
				return 'Wrong user field parameter "' . $field . '". Available fields: id, name, email, username, registerdate, online.';

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('ID', $value);

			$rows = database::loadAssocList('#__users', ['ID', 'user_login', 'user_nicename', 'user_email', 'user_registered', 'display_name'], $whereClause, null, null, 1);

			if (count($rows) != 0) {
				$row = $rows[0];

				switch ($field) {
					case 'id':
						return $row['ID'];
					case 'username':
						return $row['user_login'];
					case 'email':
						return $row['user_email'];
					case 'registerdate':
						return common::formatDate($row['user_registered'], $format, 'Never');
					case 'lastvisitdate':
						return 'Last Visit Date is Not  available in wordpress';
					case 'name':
						return $row['display_name'];
					default:
						return null;
				}
			} else {
				if ($value != 0)
					return common::translate('COM_CUSTOMTABLES_FIELDS_USER_NOT_FOUND');
			}
			return null;
		}
	}

	protected static function get_session_tokens_wordpress($user_id)
	{
		$session_tokens = get_user_meta($user_id, 'session_tokens', true);
		if ($session_tokens) {
			return maybe_unserialize($session_tokens);
		}
		return false;
	}
}