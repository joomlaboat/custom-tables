<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

class ResetController
{
	/**
	 * Handle password reset confirmation
	 * @throws Exception
	 * @since 3.5.0
	 */
	public function confirmReset()
	{
		$app = Factory::getApplication();
		$input = file_get_contents('php://input');
		$data = json_decode($input);

		// Get token and new password from request
		$token = $data->token ?? '';
		$password = $data->password ?? '';

		if (empty($token) || empty($password)) {
			CTMiscHelper::fireError(400, 'Token and new password are required', 'Invalid request');
			return;
		}

		// Verify token
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__user_keys'))
			->where($db->quoteName('token') . ' = ' . $db->quote($token))
			->where($db->quoteName('series') . ' = ' . $db->quote('password_reset'))
			->where($db->quoteName('invalid') . ' = 0');

		$db->setQuery($query);
		$resetInfo = $db->loadObject();

		if (empty($resetInfo)) {
			CTMiscHelper::fireError(400, 'Invalid or expired reset token', 'Invalid token');
			return;
		}

		// Check if token is expired (24 hours)
		$tokenTime = Factory::getDate($resetInfo->time)->toUnix();
		if ((time() - $tokenTime) > 24 * 3600) {
			CTMiscHelper::fireError(400, 'Reset token has expired', 'Token expired');
			return;
		}

		// Get user
		$user = User::getInstance($resetInfo->user_id);
		if (empty($user->id)) {
			CTMiscHelper::fireError(404, 'User not found', 'Invalid user');
			return;
		}

		// Update password
		$salt = UserHelper::genRandomPassword(32);
		$hashedPassword = UserHelper::hashPassword($password, $salt);

		$user->password = $hashedPassword;
		$user->password_clear = '';

		if (!$user->save(true)) {
			CTMiscHelper::fireError(500, 'Could not update password', 'Save error');
			return;
		}

		// Invalidate reset token
		$query = $db->getQuery(true)
			->update($db->quoteName('#__user_keys'))
			->set($db->quoteName('invalid') . ' = 1')
			->where($db->quoteName('id') . ' = ' . $db->quote($resetInfo->id));

		$db->setQuery($query)->execute();

		CTMiscHelper::fireSuccess(null, [
			'message' => 'Password has been successfully reset'
		], 'Password updated');
	}

	/**
	 * Handle password reset request
	 * @throws Exception
	 * @since 3.5.0
	 */
	function execute()
	{
		$input = file_get_contents('php://input');
		$data = json_decode($input);

		// Get email from request
		$email = $data->email ?? '';

		if (empty($email)) {
			CTMiscHelper::fireError(400, 'Email is required', 'Invalid request');
			return;
		}

		// Find user by email
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__users'))
			->where($db->quoteName('email') . ' = ' . $db->quote($email));

		$db->setQuery($query);
		$user = $db->loadObject();

		if (empty($user)) {
			CTMiscHelper::fireError(404, 'User not found', 'Invalid email');
			return;
		}

		// Generate reset token
		$token = UserHelper::genRandomPassword(32);
		$salt = UserHelper::genRandomPassword(32);

		// Get token expiry time from configuration (24 hours default)
		$expires = Factory::getDate()->toUnix() + 24 * 3600;

		// Store reset token
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__user_keys'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id))
			->where($db->quoteName('series') . ' = ' . $db->quote('password_reset'));

		try {
			@$db->setQuery($query)->execute();
		} catch (Throwable $e) {
			CTMiscHelper::fireError(500, $e->getMessage(), 'Database error');
			return;
		}

		$data = (object)[
			'id' => null,
			'user_id' => $user->id,
			'token' => $token,
			'series' => 'password_reset',
			'invalid' => 0,
			'time' => Factory::getDate()->toSql(),
			'uastring' => $salt
		];

		try {
			$db->insertObject('#__user_keys', $data);
		} catch (Exception $e) {
			CTMiscHelper::fireError(500, 'Could not save reset token', 'Database error');
			return;
		}

		// Send reset email
		$config = Factory::getConfig();
		$mailFrom = $config->get('mailfrom');
		$fromName = $config->get('fromname');
		$siteName = $config->get('sitename');

		$subject = common::translate('COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT', $siteName);
		$subject = str_replace('{SITENAME}', $siteName, $subject);

		// Create reset link - adjust the URL according to your frontend setup
		$resetLink = 'index.php?option=com_users&view=reset&layout=confirm&token=' . $token;

		$body = common::translate('COM_USERS_EMAIL_PASSWORD_RESET_BODY');

		$body = str_replace('{SITENAME}', $siteName, $body);
		$body = str_replace('{TOKEN}', $token, $body);
		$body = str_replace('{LINK_TEXT}', $resetLink, $body);

		try {
			$mailer = Factory::getMailer();
			$mailer->setSender([$mailFrom, $fromName]);
			$mailer->addRecipient($user->email);
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->isHTML(true);
		} catch (Exception $e) {
			CTMiscHelper::fireError(500, $e->getMessage(), 'Could not instantiate mail function.');
			return;
		}

		try {
			if (!$mailer->Send()) {
				CTMiscHelper::fireError(500, 'Could not send reset email', 'Mail error');
				return;
			}
		} catch (Exception $e) {
			CTMiscHelper::fireError(500, $e->getMessage(), 'Could not send reset email.');
			return;
		}

		CTMiscHelper::fireSuccess(null, [
			'message' => 'Password reset instructions have been sent to your email'
		], 'Reset email sent');
	}

}