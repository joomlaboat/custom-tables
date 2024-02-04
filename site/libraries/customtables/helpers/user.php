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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;
use JApplicationHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Version;
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
	var bool $guestCanAddNew;

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
			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$user = Factory::getUser($this->id);
			else {

				if ($this->id === null)
					$user = Factory::getApplication()->getIdentity();
				else
					$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->id);
			}

			$this->id = $user->id;

			if ($user !== null) {
				$this->groups = $user->get('groups');
				$this->email = $user->email;
				$this->name = $user->name;
				$this->username = $user->username;
			}
			$this->isUserAdministrator = in_array(8, $this->groups);//8 is Super Users

		} elseif (defined('WPINC')) {

			if (function_exists('get_current_user_id')) {
				$this->id = get_current_user_id();

				if (function_exists('wp_get_current_user')) {
					$current_user = wp_get_current_user();
					$this->groups = $current_user->roles;

					if (current_user_can('activate_plugins'))
						$this->isUserAdministrator = true;
					else
						$this->isUserAdministrator = false;

					//$current_user = wp_get_current_user();
					//$has_capability = $current_user->has_cap('capability_name');
				}
			}
		}

		$this->guestCanAddNew = false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function ResetPassword(CT $ct, ?string $listing_id): bool
	{
		if ($listing_id === null or $listing_id === '' or $listing_id == 0) {
			common::enqueueMessage('Table record selected.');
			return false;
		}

		if ($ct->Env->clean)
			if (ob_get_contents()) ob_end_clean();

		if ($ct->Table->useridrealfieldname === null or $ct->Table->useridrealfieldname == '') {
			common::enqueueMessage('User ID field not found.');
			return false;
		}

		$ct->Table->loadRecord($listing_id);

		if ($ct->Table->record === null) {
			common::enqueueMessage('User record ID: "' . $listing_id . '" not found.');
			return false;
		}

		$password = strtolower(JUserHelper::genRandomPassword());

		$realUserId = $ct->Table->record[$ct->Table->useridrealfieldname];
		$realUserId = CTUser::SetUserPassword($realUserId, $password);
		$userRow = CTUser::GetUserRow($realUserId);

		$config = Factory::getConfig();
		$sitename = $config->get('sitename');

		if ($userRow !== null) {
			$user_full_name = ucwords(strtolower($userRow['name']));
			$user_name = $userRow['username'];
			$user_email = $userRow['email'];
		} else {

			return false;
			//$user_full_name = 'user: '.$realUserId.' not found.';
			//$user_name = 'user: '.$realUserId.' not found.';
			//$user_email = 'user: '.$realUserId.' not found.';
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
			die;

		if (Email::sendEmail($user_email, $subject, $messageBody)) {
			//clean exit
			common::enqueueMessage(common::translate('User password has been reset and sent to the email "' . $user_email . '"'));
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

		//$query = 'UPDATE #__users SET password=md5("' . $password . '"), requireReset=0 WHERE id=' . $userid;
		return $userid;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function GetUserRow(int $userid): ?array
	{
		//$query = 'SELECT * FROM #__users WHERE id=' . $userid . ' LIMIT 1';

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', $userid);

		$rows = database::loadAssocList('#__users', ['*'], $whereClause, null, null, 1);
		if (count($rows) == 0)
			return null;
		else
			return $rows[0];
	}

	static public function GetUserGroups(int $userid): string
	{
		$groups = Access::getGroupsByUser($userid);

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', '(' . implode(',', $groups) . ')', 'IN', true);

		$rows = database::loadRowList('#__usergroups', ['title'], $whereClause);

		$groupList = array();
		foreach ($rows as $group)
			$groupList[] = $group[0];

		return implode(',', $groupList);
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	public static function CreateUser(string $realtablename, string $realidfieldname, string $email, string $name, string $usergroups, string $listing_id, string $useridfieldname): bool
	{
		if ($name == '') {
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_USERACCOUNT_NAME_NOT_SET'));
			return false;
		}

		$msg = '';
		$password = strtolower(JUserHelper::genRandomPassword());
		$articleId = 0;

		if (!@Email::checkEmail($email)) {
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_INCORRECT_EMAIL') . ' "' . $email . '"');
			return false;
		}

		$realUserId = CTUser::CreateUserAccount($name, $email, $password, $email, $usergroups, $msg, $articleId);

		if ($msg != '') {
			common::enqueueMessage($msg);
			return false;
		}

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

		$config = Factory::getConfig();

		// Initialise the table with JUser.
		$version_object = new Version;
		$version = (int)$version_object->getShortVersion();

		if ($version < 4)
			$user = Factory::getUser(0);
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
				$version_object = new Version;
				$version = (int)$version_object->getShortVersion();

				if ($version < 4)
					$msg = common::translate('COM_USERS_REGISTRATION_BIND_FAILED') . ': ' . $user->getError() ?? '';
				else
					$msg = common::translate('COM_USERS_REGISTRATION_BIND_FAILED') . ': ' . implode(',', $user->getErrors());
			}

			return null;
		}

		// Load the users' plugin group.
		//JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save()) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$msg = common::translate('COM_USERS_REGISTRATION_SAVE_FAILED') . ': ' . $user->getError() ?? '';
			else
				$msg = common::translate('COM_USERS_REGISTRATION_SAVE_FAILED') . ': ' . implode(',', $user->getErrors());

			return null;
		}

		//Apply group

		foreach ($group_ids as $group_id) {
			//$query = 'INSERT #__user_usergroup_map SET user_id=' . $user->id . ', group_id=' . $group_id;
			database::insert('#__user_usergroup_map', ['user_id' => $user->id, 'group_id' => $group_id]);
		}

		// Compile the notification mail values.
		$data = $user->getProperties();
		$data['fromname'] = $config->get('fromname');
		$data['mailfrom'] = $config->get('mailfrom');
		$data['sitename'] = $config->get('sitename');

		$uri = URI::getInstance();
		$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port')) . '/';

		$base = str_replace('/administrator/', '/', $base);


		$data['siteurl'] = $base;//JUri::base();

		// Handle account activation/confirmation emails.
		if ($userActivation == 1 or $userActivation == 2) {
			// Set the link to activate the user account.
			$data['activate'] = $base . 'index.php?option=com_users&task=registration.activate&token=' . $data['activation'];
		}

		$config = Factory::getConfig();
		$siteName = $config->get('sitename');
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

		Email::sendEmail($email, $emailSubject, $emailBody);
		return $user->id;
	}

	//------------- USER CREATION

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
	static public function UpdateUserField(string $realtablename, string $realidfieldname, string $userIdFieldName, string $existing_user_id, $listing_id): void
	{
		$data = [
			$userIdFieldName => $existing_user_id
		];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addCondition($realidfieldname, $listing_id);
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

	public static function checkIfRecordBelongsToUser(CT &$ct, int $ug): bool
	{
		if (!isset($ct->Env->user->isUserAdministrator))
			return false;

		if ($ug == 1)
			$usergroups = array();
		else
			$usergroups = $ct->Env->user->groups;

		if ($ct->Env->user->isUserAdministrator or in_array($ug, $usergroups)) {
			return true;
		} else {
			if (isset($ct->Table->record) and isset($ct->Table->record['listing_published']) and $ct->Table->useridfieldname != '') {
				$uid = $ct->Table->record[$ct->Table->useridrealfieldname];

				if ($uid == $ct->Env->user->id and $ct->Env->user->id != 0)
					return true;
			}
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function showUserGroup(int $userid): string
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', $userid);

		$options = database::loadAssocList('#__usergroups', ['title'], $whereClause, null, null, 1);
		if (count($options) != 0)
			return $options[0]['title'];

		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function showUserGroups(?string $valueArrayString): string
	{
		if ($valueArrayString == '')
			return '';

		$whereClause = new MySQLWhereClause();

		$valueArray = explode(',', $valueArrayString);
		foreach ($valueArray as $value) {
			if ($value != '') {
				$whereClause->addOrCondition('id', (int)$value);
			}
		}
		$options = database::loadAssocList('#__usergroups', ['title'], $whereClause, 'title');

		if (count($options) == 0)
			return '';

		$groups = array();
		foreach ($options as $opt)
			$groups[] = $opt['title'];

		return implode(',', $groups);
	}

	public function checkUserGroupAccess($group = 0): bool
	{
		if ($group == 0)
			return false;

		if ($this->isUserAdministrator)
			return true;

		if (in_array($group, $this->groups))
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
		return false;
	}
}
