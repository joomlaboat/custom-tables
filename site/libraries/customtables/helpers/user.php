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
use JApplicationHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use JUserHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;

class CTUser
{
	var ?int $id;
	var array $groups;
	var ?string $email;
	var bool $isUserAdministrator;
	var ?string $name;
	var ?string $username;

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	public function __construct(?int $id = null)
	{
		if ($id === 0)
			$id = null;

		$this->id = $id;
		$this->groups = [];
		$this->email = null;
		$this->name = null;
		$this->username = null;
		$this->isUserAdministrator = false;

		if (defined('_JEXEC')) {

			if (!CUSTOMTABLES_JOOMLA_MIN_4)
				$user = Factory::getUser($this->id);//For older Joomla versions
			else {

				if ($this->id === null)
					$user = Factory::getApplication()->getIdentity();
				else
					$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->id);
			}

			if ($user !== null) {
				$this->id = $user->id;
				$this->groups = $user->get('groups');//For older Joomla versions
				$this->email = $user->email;
				$this->name = $user->name;
				$this->username = $user->username;
			} else {
				$this->groups [] = 9; //Guest
			}

			$this->groups [] = 1; //Public

			$this->isUserAdministrator = in_array(8, $this->groups);//8 is Super Users

		} elseif (defined('WPINC')) {

			if (function_exists('get_current_user_id')) {

				if (function_exists('wp_get_current_user')) {
					$current_user = wp_get_current_user(); //This is WordPress method
					$this->groups = $current_user->roles;

					if (current_user_can('activate_plugins')) //This is WordPress method
						$this->isUserAdministrator = true;
					else
						$this->isUserAdministrator = false;
				}

				$this->id = get_current_user_id(); //This is WordPress method

				if (empty($this->id))
					$this->groups [] = 'guest';
			}
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function ResetPassword(CT $ct, ?string $listing_id): bool
	{
		if (empty($listing_id)) {
			common::enqueueMessage('Table record selected.');
			return false;
		}

		if ($ct->Env->clean)
			if (ob_get_contents()) ob_end_clean();

		if ($ct->Table->useridrealfieldname === null or $ct->Table->useridrealfieldname == '') {
			common::enqueueMessage('User ID field not found.');
			return false;
		}

		if (!empty($listing_id)) {
			$ct->Params->listing_id = $listing_id;
			$ct->getRecord();
		}

		if ($ct->Table->record === null) {
			common::enqueueMessage('User record ID: "' . $listing_id . '" not found.');
			return false;
		}

		$password = strtolower(JUserHelper::genRandomPassword());
		$realUserId = $ct->Table->record[$ct->Table->useridrealfieldname];
		$realUserId = CTUser::SetUserPassword($realUserId, $password);
		$userRow = CTUser::GetUserRow($realUserId);
		$sitename = common::getSiteName();

		if ($userRow !== null) {
			$user_full_name = ucwords(strtolower($userRow['name']));
			$user_name = $userRow['username'];
			$user_email = $userRow['email'];
		} else {
			return false;
		}
		$subject = 'Your {SITENAME} password reset request';

		$subject = str_replace('{SITENAME}', $sitename, $subject);

		$uri = URI::getInstance();
		$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port')) . '/';
		$siteURL = str_replace('/administrator/', '/', $base);

		$messageBody = 'Hello {NAME},\n\nYou may now log in to {SITEURL} using the following username and password:\n\nUsername: {USERNAME}\nPassword: {PASSWORD_CLEAR}';

		$messageBody = str_replace('\n', '<br/>', $messageBody);
		$messageBody = str_replace('{SITENAME}', $sitename, $messageBody);
		$messageBody = str_replace('{SITEURL}', $siteURL, $messageBody);

		$messageBody = str_replace('{NAME}', $user_full_name, $messageBody);
		$messageBody = str_replace('{USERNAME}', $user_name, $messageBody);
		$messageBody = str_replace('{PASSWORD_CLEAR}', $password, $messageBody);

		if ($ct->Env->clean)
			die;//Clean Exit

		if (common::sendEmail($user_email, $subject, $messageBody)) {
			//clean exit
			common::enqueueMessage(sprintf("User password has been reset and sent to the email '%s'", $user_email));
			return true;
		}

		//clean exit
		return true;
	}

	/**
	 * Sets the password for a user identified by the provided user ID.
	 *
	 * @param int $userid The ID of the user for whom the password will be set.
	 * @param string $password The new password to set for the user.
	 *
	 * @return int Returns the ID of the user for whom the password was set.
	 * @throws Exception If there's an issue setting the user password.
	 * @since 3.2.1
	 */
	static protected function SetUserPassword(int $userid, string $password): int
	{
		$data = [
			'password' => md5($password),
			'requireReset' => 0
		];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addCondition('id', $userid);
		database::update('#__users', $data, $whereClauseUpdate);
		return $userid;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function GetUserRow(int $userid): ?array
	{
		$whereClause = new MySQLWhereClause();

		if (defined('_JEXEC')) {
			$whereClause->addCondition('ID', $userid);
		} elseif (defined('WPINC')) {
			$whereClause->addCondition('id', $userid);
		} else {
			echo 'GetUserRow not supported.';
			return null;
		}

		$rows = database::loadAssocList('#__users', ['*'], $whereClause, null, null, 1);
		if (count($rows) == 0)
			return null;
		else
			return $rows[0];
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	static public function GetUserGroups(int $userid): array
	{
		if (defined('_JEXEC')) {
			$groups = Access::getGroupsByUser($userid);

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('id', '(' . implode(',', $groups) . ')', 'IN', true);

			$rows = database::loadRowList('#__usergroups', ['title'], $whereClause);

			$groupList = array();
			foreach ($rows as $group)
				$groupList[] = $group[0];

			return $groupList;

		} elseif (defined('WPINC')) {
			$user = get_userdata($userid); //This is WordPress method
			$roles = $user->roles;

			$role_names = array();

			global $wp_roles;
			$all_roles = $wp_roles->roles;

			foreach ($roles as $role) {
				if (isset($all_roles[$role]['name'])) {
					$role_names[] = $all_roles[$role]['name'];
				}
			}

			return $role_names;
		} else {
			return [];
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	public static function CreateUser(string $realtablename, string $realidfieldname, string $email, string $name, string $usergroups, string $listing_id, string $useridfieldname): bool
	{
		if ($name == '')
			throw new Exception(common::translate('COM_CUSTOMTABLES_USERACCOUNT_NAME_NOT_SET'));

		$msg = '';
		$password = strtolower(JUserHelper::genRandomPassword());

		if (!@CTMiscHelper::checkEmail($email))
			throw new Exception(common::translate('COM_CUSTOMTABLES_INCORRECT_EMAIL') . ' "' . $email . '"');

		$realUserId = CTUser::CreateUserAccount($name, $email, $password, $email, $usergroups, $msg);

		if ($msg != '')
			throw new Exception($msg);

		if ($realUserId !== null) {
			CTUser::UpdateUserField($realtablename, $realidfieldname, $useridfieldname, $realUserId, $listing_id);
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_USER_CREATE_PSW_SENT'));
		} else {
			$msg = common::translate('COM_CUSTOMTABLES_ERROR_USER_NOTCREATED');
			common::enqueueMessage($msg);
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function CreateUserAccount(string $fullName, string $username, string $password, string $email, string $group_names, string &$msg): ?int
	{
		if ($fullName == '') {
			$msg = common::translate('COM_CUSTOMTABLES_USERACCOUNT_NAME_NOT_SET');
			return null;
		}

		//Get group IDs
		$group_ids = CTUser::getUserGroupIDsByName($group_names);

		if ($group_ids === null)
			return null;

		//Creates active user
		$userActivation = 0;//already activated

		// Initialise the table with JUser.
		if (!CUSTOMTABLES_JOOMLA_MIN_4)
			$user = Factory::getUser(0);//For older Joomla versions
		else
			$user = new User();

		$data = array();

		// Prepare the data for the user object.
		$data['name'] = $fullName;
		$data['username'] = $username;
		$data['email'] = $email;
		$data['password'] = $password;
		$data['password2'] = $password;

		// Override the base user data with any
		if (($userActivation == 1) || ($userActivation == 2)) {
			jimport('joomla.user.helper');

			$data['activation'] = JApplicationHelper::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}

		// Bind the data.
		if (!$user->bind($data)) {

			if (defined('_JEXEC')) {
				if (!CUSTOMTABLES_JOOMLA_MIN_4)
					$msg = common::translate('COM_CUSTOMTABLES_USERS_REGISTRATION_BIND_FAILED') . ': ' . $user->getError() ?? '';
				else
					$msg = common::translate('COM_CUSTOMTABLES_USERS_REGISTRATION_BIND_FAILED') . ': ' . implode(',', $user->getErrors());
			}

			return null;
		}

		// Store the data.
		if (!$user->save()) {
			if (!CUSTOMTABLES_JOOMLA_MIN_4)
				$msg = $user->getError() ?? '';
			else
				$msg = implode(',', $user->getErrors());

			return null;
		}

		//Apply group
		foreach ($group_ids as $group_id)
			database::insert('#__user_usergroup_map', ['user_id' => $user->id, 'group_id' => $group_id]);

		// Compile the notification mail values.
		$data = $user->getProperties();
		$data['fromname'] = common::getEmailFromName();
		$data['mailfrom'] = common::getMailFrom();
		$data['sitename'] = common::getSiteName();

		$uri = URI::getInstance();
		$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port')) . '/';
		$base = str_replace('/administrator/', '/', $base);

		$data['siteurl'] = $base;//JUri::base();

		// Handle account activation/confirmation emails.
		if ($userActivation == 1 or $userActivation == 2) {
			// Set the link to activate the user account.
			$data['activate'] = $base . 'index.php?option=com_users&task=registration.activate&token=' . $data['activation'];
		}

		$siteName = common::getSiteName();
		$subject = common::translate('COM_USERS_EMAIL_ACCOUNT_DETAILS');
		$emailSubject = str_replace('{NAME}', $fullName, $subject);
		$emailSubject = str_replace('{SITENAME}', $siteName, $emailSubject);

		$body = common::translate('COM_USERS_EMAIL_REGISTERED_BODY');
		$UriBase = Uri::base();

		$emailBody = str_replace('{NAME}', $fullName, $body);
		$emailBody = str_replace('{SITENAME}', $siteName, $emailBody);
		$emailBody = str_replace('{SITEURL}', $UriBase, $emailBody);
		$emailBody = str_replace('{USERNAME}', $username, $emailBody);
		$emailBody = str_replace('{PASSWORD_CLEAR}', $password, $emailBody);
		$emailBody = str_replace("\n", '<br/>', $emailBody);

		common::sendEmail($email, $emailSubject, $emailBody);
		return $user->id;
	}

	/**
	 * @throws Exception
	 * @since 3.3.3
	 */
	static protected function getUserGroupIDsByName(string $group_names): ?array
	{
		$names = explode(',', $group_names);
		$whereClause = new MySQLWhereClause();

		foreach ($names as $name) {
			$n = preg_replace("/[^[:alnum:][:space:]]/u", '', trim($name));
			if ($n != '')
				$whereClause->addOrCondition('title', $n);
		}

		if (!$whereClause->hasConditions())
			return null;

		try {
			$rows = database::loadObjectList('#__usergroups', ['id'], $whereClause);
		} catch (Exception $e) {
			return null;
		}

		$usergroup_ids = array();
		foreach ($rows as $row) {
			$usergroup_ids[] = $row->id;
		}
		return $usergroup_ids;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function UpdateUserField(string $realtablename, string $realIdFieldName, string $userIdFieldName, string $existing_user_id, $listing_id): void
	{
		$data = [
			$userIdFieldName => $existing_user_id
		];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addCondition($realIdFieldName, $listing_id);
		database::update($realtablename, $data, $whereClauseUpdate);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function CheckIfUserNameExist(string $username): bool
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('username', $username);

		$rows = database::loadAssocList('#__users', ['id'], $whereClause, null, null, 1);
		if (count($rows) == 1)
			return true;

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function CheckIfUserExist(string $username, string $email)
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('username', $username);
		$whereClause->addCondition('email', $email);

		$rows = database::loadAssocList('#__users', ['id'], $whereClause, null, null, 1);
		if (count($rows) != 1)
			return 0;

		$row = $rows[0];
		return $row['id'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function CheckIfEmailExist(string $email, &$existing_user, &$existing_name)
	{
		$existing_user = '';
		$existing_name = '';

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('email', $email);

		$rows = database::loadAssocList('#__users', ['id', 'username', 'name'], $whereClause, null, null, 1);
		if (count($rows) == 1) {
			$row = $rows[0];
			$existing_user = $row['username'];
			$existing_name = $row['name'];
			return $row['id'];
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function showUserGroup_Joomla(?int $userid): ?string
	{
		if ($userid === null or $userid == 0)
			return null;

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', $userid);

		$options = database::loadAssocList('#__usergroups', ['title'], $whereClause, null, null, 1);
		if (count($options) != 0)
			return $options[0]['title'];

		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function showUserGroup_WordPress(?string $value): ?string
	{
		if ($value === null)
			return null;

		$records = $this->getUserGroupArray(null);

		foreach ($records as $record) {
			if ($record['id'] == $value)
				return $record['name'];
		}

		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	public function getUserGroupArray(?array $availableUserGroupList, string $innerJoin = null): array
	{
		if (defined('_JEXEC')) {
			return self::getUserGroupArray_Joomla($availableUserGroupList, $innerJoin);
		} elseif (defined('WPINC')) {
			return self::getUserGroupArray_WordPress($availableUserGroupList);
		} else {
			return [];
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function getUserGroupArray_Joomla(?array $availableUserGroupList, ?string $innerJoin = null): array
	{
		$from = '#__usergroups';
		if ($innerJoin !== null)
			$from .= $innerJoin;

		$whereClause = new MySQLWhereClause();

		if ($availableUserGroupList === null or count($availableUserGroupList) == 0) {
			$whereClause->addCondition('#__usergroups.title', 'Super Users', '!=');
		} else {
			foreach ($availableUserGroupList as $availableUserGroup) {
				if ($availableUserGroup != '')
					$whereClause->addOrCondition('#__usergroups.title', $availableUserGroup);
			}
		}
		return database::loadAssocList($from, ['#__usergroups.id AS id', '#__usergroups.title AS name'], $whereClause, '#__usergroups.title', null, null, null, '#__usergroups.id');
	}

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	protected function getUserGroupArray_WordPress(?array $availableUserGroupList): array
	{
		$whereClause = new MySQLWhereClause();

		$whereClause->addCondition('option_name', '#__user_roles');

		$records = database::loadAssocList('#__options', ['option_value'], $whereClause, null, null, 1);
		if (count($records) != 1)
			return [];

		$str = $records[0]['option_value'];
		$groups = unserialize($str);

		$roles = array();

		foreach ($groups as $role => $role_data) {

			if ($availableUserGroupList !== null and count($availableUserGroupList) > 0) {
				// Exclude roles that are not in the whitelist
				if (!in_array(strtolower($role), $availableUserGroupList)) {
					continue;
				}
			}

			$roles[] = array(
				'id' => $role,
				'name' => $role_data['name']
			);
		}

		// Sort the roles by name
		usort($roles, function ($a, $b) {
			return strcasecmp($a['name'], $b['name']);
		});

		return $roles;
	}

	/**
	 * @throws Exception
	 * @since 3.3.4
	 */
	public function showUserGroups(?string $valueArrayString): string
	{
		if (empty($valueArrayString))
			return '';

		if (defined('_JEXEC')) {
			return $this->resolveUserGroups_Joomla($valueArrayString);
		} elseif (defined('WPINC')) {
			return $this->resolveUserGroups_WordPress($valueArrayString);
		} else {
			return 'User Groups field type is not supported in this environment.';
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function resolveUserGroups_Joomla(?string $valueArrayString): string
	{
		$whereClause = new MySQLWhereClause();

		if (empty($valueArrayString))
			return '';

		$valueArray = explode(',', $valueArrayString);
		foreach ($valueArray as $value) {
			if ($value != '') {
				$whereClause->addOrCondition('id', (int)$value);
			}
		}

		if (!$whereClause->hasConditions())
			return '';

		$options = database::loadAssocList('#__usergroups', ['title'], $whereClause, 'title');

		if (count($options) == 0)
			return '';

		$groups = array();
		foreach ($options as $opt)
			$groups[] = $opt['title'];

		return implode(',', $groups);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function resolveUserGroups_WordPress(?string $valueArrayString): string
	{
		$records = $this->getUserGroupArray(null);
		$valueArray = explode(',', $valueArrayString);

		$groups = [];

		foreach ($valueArray as $value) {
			if ($value != '') {

				foreach ($records as $record) {
					if ($record['id'] == $value)
						$groups[] = $record['name'];
				}
			}
		}

		return implode(',', $groups);
	}

	public function checkUserGroupAccess(array $groups = []): bool
	{
		if (count($groups) == 0)
			return false;

		if ($this->isUserAdministrator)
			return true;

		if (!empty(array_intersect($groups, $this->groups)))
			return true;

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.3.3
	 */
	function authorise(string $action, ?string $assetName): bool
	{
		if (defined('_JEXEC')) {
			$user = Factory::getApplication()->getIdentity();
			return $user->authorise($action, $assetName);
		} else {
			throw new Exception('User->authorise not implemented for WordPress yet.');
		}
	}
}
