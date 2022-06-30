<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use ESTables;
use JoomlaBasicMisc;
use JUserHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Language\Text;
use tagProcessor_If;

class CTUser
{
    public static function ResetPassword(CT $ct, $listing_id)
    {
        if ($listing_id == 0) {
            Factory::getApplication()->enqueueMessage('Table record selected.', 'error');
            return false;
        }

        if ($ct->Env->clean)
            if (ob_get_contents()) ob_end_clean();

        if ($ct->Table->useridrealfieldname === null or $ct->Table->useridrealfieldname == '') {
            Factory::getApplication()->enqueueMessage('User ID field not found.', 'error');
            return false;
        }

        $ct->Table->loadRecord($listing_id);

        if ($ct->Table->record === null) {
            Factory::getApplication()->enqueueMessage('User record ID: "' . $listing_id . '" not found.', 'error');
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

        if (Email::sendEmail($user_email, $subject, $messageBody, $isHTML = true)) {
            //clean exit

            Factory::getApplication()->enqueueMessage('User password has been reset and sent to the email "' . $user_email . '".');
            return true;
        }

        //clean exit
        return false;
    }

    static protected function SetUserPassword(int $userid, string $password): int
    {
        $db = Factory::getDBO();
        $query = 'UPDATE #__users SET password=md5("' . $password . '"), requireReset=0 WHERE id=' . $userid;

        $db->setQuery($query);
        $db->execute();
        return $userid;
    }

    static public function GetUserRow(int $userid): ?array
    {
        $db = Factory::getDBO();
        $query = 'SELECT * FROM #__users WHERE id=' . $userid . ' LIMIT 1';

        $db->setQuery($query);

        $recs = $db->loadAssocList();
        if (count($recs) == 0)
            return null;
        else
            return $recs[0];
    }

    static public function GetUserGroups(int $userid)
    {
        $db = Factory::getDBO();

        $groups = Access::getGroupsByUser($userid);
        $groupid_list = '(' . implode(',', $groups) . ')';
        $query = $db->getQuery(true);
        $query->select('title');
        $query->from('#__usergroups');
        $query->where('id IN ' . $groupid_list);
        $db->setQuery($query);
        $rows = $db->loadRowList();
        $grouplist = array();
        foreach ($rows as $group)
            $grouplist[] = $group[0];

        return implode(',', $grouplist);
    }

    public static function CreateUser($realtablename, $realidfieldname, $email, $name, $usergroups, $listing_id, $useridfieldname): bool
    {
        $msg = '';
        $password = strtolower(JUserHelper::genRandomPassword());

        $articleId = 0;

        if (!Email::checkEmail($email)) {
            Factory::getApplication()->enqueueMessage('Incorrect email "' . $email . '"', 'error');
            return false;
        }

        $realUserId = CTUser::CreateUserAccount($name, $email, $password, $email, $usergroups, $msg, $articleId);

        if ($msg != '') {
            Factory::getApplication()->enqueueMessage($msg, 'error');
            return false;
        }

        if ($realUserId != 0) {
            CTUser::UpdateUserField($realtablename, $realidfieldname, $useridfieldname, $realUserId, $listing_id);
            Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_USER_CREATE_PSW_SENT'));
        } else {

            $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_USER_NOTCREATED');
            Factory::getApplication()->enqueueMessage($msg, 'error');
        }

        return true;
    }

    //------------- USER CREATION

    static public function CreateUserAccount($fullname, $username, $password, $email, $group_names, &$msg)
    {
        //Get group IDs
        $group_ids = CTUser::getUserGroupIDsByName($group_names);

        //Creates active user
        $useractivation = 0;//alreadey activated

        $config = Factory::getConfig();

        // Initialise the table with JUser.
        $user = Factory::getUser(0);

        $data = array();

        // Prepare the data for the user object.
        $data['name'] = $fullname;
        $data['username'] = $username;
        $data['email'] = $email;
        $data['password'] = $password;
        $data['password2'] = $password;


        // Override the base user data with any
        if (($useractivation == 1) || ($useractivation == 2)) {
            jimport('joomla.user.helper');

            $data['activation'] = \JApplicationHelper::getHash(JUserHelper::genRandomPassword());
            $data['block'] = 1;
        }

        // Bind the data.
        if (!$user->bind($data)) {
            $msg = JoomlaBasicMisc::JTextExtended('COM_USERS_REGISTRATION_BIND_FAILED', $user->getError());
            return false;
        }

        // Load the users plugin group.
        //JPluginHelper::importPlugin('user');

        // Store the data.
        if (!$user->save()) {

            $msg = Text::sprintf('COM_USERS_REGISTRATION_SAVE_FAILED', $user->getError());
            return false;
        }

        //Apply group
        $db = Factory::getDBO();

        foreach ($group_ids as $group_id) {
            $query = 'INSERT #__user_usergroup_map SET user_id=' . $user->id . ', group_id=' . $group_id;

            $db->setQuery($query);
            $db->execute();
        }

        //---------------------------------------

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
        if ($useractivation == 1 or $useractivation == 2) {
            // Set the link to activate the user account.
            $data['activate'] = $base . 'index.php?option=com_users&task=registration.activate&token=' . $data['activation'];
        }

        $subject = JoomlaBasicMisc::JTextExtended('COM_USERS_EMAIL_ACCOUNT_DETAILS');
        $body = JoomlaBasicMisc::JTextExtended('COM_USERS_EMAIL_REGISTERED_BODY');

        $config = Factory::getConfig();

        $emailSubject = Text::sprintf(
            $subject,
            $fullname,
            $config->get('sitename')

        );

        $emailBody = Text::sprintf(
            $body,
            $fullname,
            $config->get('sitename'),
            Uri::base(),
            $username,
            $password
        );

        Email::sendEmail($email, $emailSubject, $emailBody);

        return $user->id;
    }

    static protected function getUserGroupIDsByName($group_names)
    {
        $new_names = array();
        $names = explode(',', $group_names);
        foreach ($names as $name) {
            $n = trim($name);
            if ($n != '')
                $new_names[] = 'title="' . $n . '"';
        }

        $db = Factory::getDBO();

        $query = 'SELECT id FROM #__usergroups WHERE ' . implode(' OR ', $new_names);

        $db->setQuery($query);

        $rows = $db->loadObjectList();
        $usergroup_ids = array();
        foreach ($rows as $row) {
            $usergroup_ids[] = $row->id;
        }
        return $usergroup_ids;
    }

    static public function UpdateUserField(string $realtablename, string $realidfieldname, string $useridfieldname, string $existing_user_id, $listing_id)
    {
        $db = Factory::getDBO();

        $query = 'UPDATE ' . $realtablename . ' SET ' . $useridfieldname . '=' . $existing_user_id . ' WHERE ' . $realidfieldname . '=' . $db->quote($listing_id) . ' LIMIT 1';

        $db->setQuery($query);
        $db->execute();
    }

    static public function CheckIfUserNameExist(string $username): bool
    {
        $db = Factory::getDBO();

        $query = 'SELECT id FROM #__users WHERE username=' . $db->quote($username) . ' LIMIT 1';

        $db->setQuery($query);

        $recs = $db->loadAssocList();
        if (count($recs) == 1)
            return true;

        return false;

    }

    static public function CheckIfUserExist(string $username, string $email)
    {
        $db = Factory::getDBO();
        $query = 'SELECT id FROM #__users WHERE username=' . $db->quote($username) . ' AND email=' . $db->quote($email) . ' LIMIT 1';

        $db->setQuery($query);

        $recs = $db->loadAssocList();
        if (count($recs) != 1)
            return 0;

        $rec = $recs[0];
        return $rec['id'];
    }

    static public function CheckIfEmailExist(string $email, &$existing_user, &$existing_name)
    {
        $existing_user = '';
        $existing_name = '';
        $db = Factory::getDBO();

        $query = 'SELECT id, username, name FROM #__users WHERE email=' . $db->quote($email) . ' LIMIT 1';

        $db->setQuery($query);

        $recs = $db->loadAssocList();
        if (count($recs) == 1) {
            $rec = $recs[0];
            $existing_user = $rec['username'];
            $existing_name = $rec['name'];
            return $rec['id'];
        }

        return false;
    }

    public static function checkIfRecordBelongsToUser(CT &$ct, int $ug)
    {
        if (!isset($ct->Env->isUserAdministrator))
            return false;

        if ($ug == 1)
            $usergroups = array();
        else
            $usergroups = $ct->Env->user->get('groups');

        $isok = false;

        if ($ct->Env->isUserAdministrator or in_array($ug, $usergroups))
            $isok = true;
        else {
            if (isset($ct->Table->record) and isset($ct->Table->record['listing_published']) and $ct->Table->useridfieldname != '') {
                $uid = $ct->Table->record[$ct->Table->useridrealfieldname];

                if ($uid == $ct->Env->userid and $ct->Env->userid != 0)
                    $isok = true;
            }
        }

        return $isok;
    }


    //checkAccess

    public static function CheckAuthorization(CT &$ct, int $action = 1)
    {
        if ($action == 5) //force edit
        {
            $action = 1;
        } else {
            if ($action == 1 and $ct->Params->listing_id == 0)
                $action = 4; //add new
        }

        if ($ct->Params->guestCanAddNew == 1)
            return true;

        if ($ct->Params->guestCanAddNew == -1 and $ct->Params->listing_id == 0)
            return false;

        //check is authorized or not
        if ($action == 1)
            $userGroup = $ct->Params->editUserGroups;
        elseif ($action == 2)
            $userGroup = $ct->Params->publishUserGroups;
        elseif ($action == 3)
            $userGroup = $ct->Params->deleteUserGroups;
        elseif ($action == 4)
            $userGroup = $ct->Params->addUserGroups;
        else
            $userGroup = null;

        if ($ct->Env->userid == 0)
            return false;

        if ($ct->Env->isUserAdministrator) {
            //Super Users have access to everything
            return true;
        }

        if ($ct->Params->listing_id == 0 or $ct->Params->userIdField == '')
            return JoomlaBasicMisc::checkUserGroupAccess($userGroup);

        $theAnswerIs = false;

        if ($ct->Params->userIdField != '')
            $theAnswerIs = self::checkIfItemBelongsToUser($ct, $ct->Params->userIdField, $ct->Params->listing_id);

        if (!$theAnswerIs)
            return JoomlaBasicMisc::checkUserGroupAccess($userGroup);

        return true;
    }

    public static function checkIfItemBelongsToUser(CT &$ct, string $userIdField, string $listing_id): bool
    {
        $wheres = self::UserIDField_BuildWheres($ct, $userIdField, $listing_id);

        $query = 'SELECT c.' . $ct->Table->realidfieldname . ' FROM ' . $ct->Table->realtablename . ' AS c WHERE ' . implode(' AND ', $wheres) . ' LIMIT 1';

        $ct->db->setQuery($query);
        $ct->db->execute();

        if ($ct->db->getNumRows() == 1) {
            return true;
        }
        return false;
    }

    public static function UserIDField_BuildWheres(CT &$ct, string $userIdField, string $listing_id): array
    {
        $wheres = [];

        $statement_items = tagProcessor_If::ExplodeSmartParams($userIdField); //"and" and "or" as separators

        $wheres_owner = array();

        foreach ($statement_items as $item) {
            $field = $item[1];
            if (!str_contains($field, '.')) {
                //example: user
                //check if the record belong to the current user
                $user_field_row = Fields::FieldRowByName($field, $ct->Table->fields);
                $wheres_owner[] = [$item[0], $user_field_row['realfieldname'] . '=' . $ct->Env->userid];
            } else {
                //example: parents(children).user
                $statement_parts = explode('.', $field);
                if (count($statement_parts) != 2) {
                    $ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has a syntax error. Error is about "." character - only one is permitted. Correct example: parent(children).user'), 'error');
                    return [];
                }

                $table_parts = explode('(', $statement_parts[0]);
                if (count($table_parts) != 2) {
                    $ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has a syntax error. Error is about "(" character. Correct example: parent(children).user'), 'error');
                    return [];
                }

                $parent_tablename = $table_parts[0];
                $parent_join_field = str_replace(')', '', $table_parts[1]);
                $parent_user_field = $statement_parts[1];

                $parent_table_row = ESTables::getTableRowByName($parent_tablename);

                if (!is_object($parent_table_row)) {
                    $ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: Table "' . $parent_tablename . '" not found.'), 'error');
                    return [];
                }

                $parent_table_fields = Fields::getFields($parent_table_row->id);

                $parent_join_field_row = Fields::FieldRowByName($parent_join_field, $parent_table_fields);

                if (count($parent_join_field_row) == 0) {
                    $ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: Join field "' . $parent_join_field . '" not found.'), 'error');
                    return [];
                }

                if ($parent_join_field_row['type'] != 'sqljoin' and $parent_join_field_row['type'] != 'records') {
                    $ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: Wrong join field type "' . $parent_join_field_row['type'] . '". Accepted types: "sqljoin" and "records" .'), 'error');
                    return [];
                }

                //User field

                $parent_user_field_row = Fields::FieldRowByName($parent_user_field, $parent_table_fields);

                if (count($parent_user_field_row) == 0) {
                    $ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: User field "' . $parent_user_field . '" not found.'), 'error');
                    return [];
                }

                if ($parent_user_field_row['type'] != 'userid' and $parent_user_field_row['type'] != 'user') {
                    $ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: Wrong user field type "' . $parent_join_field_row['type'] . '". Accepted types: "userid" and "user" .'), 'error');
                    return [];
                }

                $parent_wheres = [];

                $parent_wheres[] = 'p.' . $parent_user_field_row['realfieldname'] . '=' . $ct->Env->userid;

                $fieldType = $parent_join_field_row['type'];
                if ($fieldType != 'sqljoin' and $fieldType != 'records')
                    return [];

                if ($fieldType == 'sqljoin')
                    $parent_wheres[] = 'p.' . $parent_join_field_row['realfieldname'] . '=c.listing_id';

                if ($fieldType == 'records')
                    $parent_wheres[] = 'INSTR(p.' . $parent_join_field_row['realfieldname'] . ',CONCAT(",",c.' . $ct->Table->realidfieldname . ',","))';

                $q = '(SELECT p.' . $parent_table_row->realidfieldname . ' FROM ' . $parent_table_row->realtablename . ' AS p WHERE ' . implode(' AND ', $parent_wheres) . ' LIMIT 1) IS NOT NULL';

                $wheres_owner[] = [$item[0], $q];
            }
        }

        $wheres_owner_str = '';
        $index = 0;
        foreach ($wheres_owner as $field) {
            if ($index == 0)
                $wheres_owner_str .= $field[1];
            else
                $wheres_owner_str .= ' ' . strtoupper($field[0]) . ' ' . $field[1];

            $index += 1;
        }

        if ($listing_id != '' and $listing_id != 0)
            $wheres[] = $ct->Table->realidfieldname . '=' . $ct->db->quote($listing_id);

        if ($wheres_owner_str != '')
            $wheres[] = '(' . $wheres_owner_str . ')';

        return $wheres;
    }

}
