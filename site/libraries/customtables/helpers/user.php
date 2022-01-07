<?php

/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Email;
use \Joomla\CMS\Component\ComponentHelper;
use \JoomlaBasicMisc;
use \JUserHelper;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \JText;

class CTUser
{
	public static function ResetPassword(&$ct,$listing_id)
	{
		if($listing_id==0)
		{
			Factory::getApplication()->enqueueMessage('Table record selected.', 'error');
			return;
		}

		if($ct->Env->clean)
			if (ob_get_contents()) ob_end_clean();
		
		if($ct->Table->useridrealfieldname==null or $ct->Table->useridrealfieldname=='')
		{
			Factory::getApplication()->enqueueMessage('User ID field not found.', 'error');
			return;
		}

		$ct->Table->loadRecord($listing_id);
		
		if($ct->Table->record == null)
		{
			Factory::getApplication()->enqueueMessage('User record ID: "'.$user_listing_id.'" not found.', 'error');
			return;
		}

		$password=strtolower(JUserHelper::genRandomPassword());

		$realuserid=$ct->Table->record[$ct->Table->useridrealfieldname];
		$realuserid=CTUser::SetUserPassword($realuserid,$password);
		$userrow=CTUser::GetUserRow($realuserid);

		$mainframe = Factory::getApplication('site');
		$MailFrom 	= $mainframe->getCfg('mailfrom');
		$FromName 	= $mainframe->getCfg('fromname');
		
		$config = Factory::getConfig();
		$sitename = $config->get( 'sitename' );

		$user_full_name=ucwords(strtolower($userrow['name']));
		$subject='Your {SITENAME} password reset request';

		$subject=str_replace('{SITENAME}', $sitename, $subject);
		
		$uri = URI::getInstance();
		$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port')).'/';
		$siteurl=str_replace('/administrator/','/',$base);

		$messagebody='Hello {NAME},\n\nYou may now log in to {SITEURL} using the following username and password:\n\nUsername: {USERNAME}\nPassword: {PASSWORD_CLEAR}';

		$messagebody=str_replace('\n', '<br/>', $messagebody);
		$messagebody=str_replace('{SITENAME}', $sitename, $messagebody);
		$messagebody=str_replace('{SITEURL}', $siteurl, $messagebody);
		
		$messagebody=str_replace('{NAME}', $user_full_name, $messagebody);
		$messagebody=str_replace('{USERNAME}', $userrow['username'], $messagebody);
		$messagebody=str_replace('{PASSWORD_CLEAR}', $password, $messagebody);
		


		if(Email::sendEmail($userrow['email'],$subject,$messagebody,$isHTML = true))
		{
			if($ct->Env->clean)
				die;//clean exit
		
			Factory::getApplication()->enqueueMessage('User password has been reset and sent to the email "'.$userrow['email'].'".');
			return true;
		}
		
		if($ct->Env->clean)
			die;//clean exit
			
		return false;
	}
	
	static protected function SetUserPassword($userid,$password)
	{
		$db = Factory::getDBO();
		$query='UPDATE #__users SET password=md5("'.$password.'"), requireReset=0 WHERE id='.$userid;

		$db->setQuery( $query );
		$db->execute();
		return $userid;
	}

	static public function GetUserRow($userid)
	{
		$db = Factory::getDBO();
		$query='SELECT * FROM #__users WHERE id='.$userid.' LIMIT 1';

		$db->setQuery( $query );

		$recs=$db->loadAssocList();
		if(count($recs)==0)
			return array();
		else
			return $recs[0];
	}
	
	static public function GetUserGroups($userid)
	{
		$db = Factory::getDBO();

        $groups = Access::getGroupsByUser($userid);
		$groupid_list		= '(' . implode(',', $groups) . ')';
        $query  = $db->getQuery(true);
        $query->select('title');
        $query->from('#__usergroups');
        $query->where('id IN ' .$groupid_list);
        $db->setQuery($query);
        $rows	= $db->loadRowList();
        $grouplist	= array();
		foreach($rows as $group)
			$grouplist[]=$group[0];

        return implode(',',$grouplist);
	}

	//------------- USER CREATION
	
	public static function CreateUser($realtablename, $realidfieldname,$email,$name,$usergroups,$listing_id,$useridfieldname)
	{
		$msg='';
		$password=strtolower(JUserHelper::genRandomPassword());

		$new_password=$password;

		$realuserid=0;

		$articleid=0;
		$msg='';
		//CreateUserAccount($fullname,$username,$password,$email,$group_names,&$msg,$email_content_article_id)
		
		if(!Email::checkEmail($email))
		{
			Factory::getApplication()->enqueueMessage('Incorrect email "'.$email.'"', 'error');
			return false;
		}
		
		$realuserid=CTUser::CreateUserAccount($name,$email,$password,$email,$usergroups,$msg,$articleid);
		if($msg!='')
		{
			Factory::getApplication()->enqueueMessage($msg, 'error');
			return false;
		}

		if($realuserid!=0)
		{
                CTUser::UpdateUserField($realtablename, $realidfieldname,$useridfieldname,$realuserid,$listing_id);
				Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_USER_CREATE_PSW_SENT' ));
		}
		else
		{

				$msg=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_USER_NOTCREATED');
				if(count($msg_warning)>0)
					$msg.='<br/><ul><li>'.implode('</li><li>',$msg_warning).'</li></ul>';

				Factory::getApplication()->enqueueMessage($msg, 'error');
		}

	}

	static public function UpdateUserField($realtablename, $realidfieldname, $useridfieldname,$existing_user_id,$listing_id)
    {
		$db = Factory::getDBO();
		
		$query = 'UPDATE '.$realtablename.' SET '.$useridfieldname.'='.$existing_user_id.' WHERE '.$realidfieldname.'='.$db->quote($listing_id).' LIMIT 1';
		$db->setQuery( $query );
		$db->execute();
    }
	
	static public function UpdateUserLink($realtablename,$fieldusreidname,$userid,$listing_id)
	{
		$db = Factory::getDBO();

		$query = 'UPDATE '.$realtablename.' SET '.$fieldusreidname.'='.$userid.' WHERE '.$realidfieldname.'='.$db->quote($listing_id);

		$db->setQuery( $query );
		$db->execute();
	}

	static public function CheckIfUserNameExist($username)
	{
		$db = Factory::getDBO();

		$query = 'SELECT id FROM #__users WHERE username="'.$username.'" LIMIT 1';

		$db->setQuery( $query );

		$recs = $db->loadAssocList();
		if(count($recs)==1)
			return true;

		return false;

	}

	static public function CheckIfUserExist($username, $email)
	{
		$db = Factory::getDBO();
		$query = 'SELECT id FROM #__users WHERE username="'.$username.'" AND email="'.$email.'" LIMIT 1';

		$db->setQuery( $query );

		$recs = $db->loadAssocList();
		if(count($recs)!=1)
			return 0;

		$rec=$recs[0];
		return $rec['id'];
	}

	static public function CheckIfEmailExist($email,&$existing_user,&$existing_name)
	{
		$existing_user='';
		$existing_name='';
		$db = Factory::getDBO();

		$query = 'SELECT id, username, name FROM #__users WHERE email="'.$email.'" LIMIT 1';

		$db->setQuery( $query );

		$recs = $db->loadAssocList();
		if(count($recs)==1)
		{
			$rec=$recs[0];
			$existing_user=$rec['username'];
			$existing_name=$rec['name'];
			return $rec['id'];
		}

		return false;
	}

	static protected function getUserGroupIDsByName($group_names)
	{
		$new_names=array();
		$names=explode(',',$group_names);
		foreach($names as $name)
		{
			$n=trim($name);
			if($n!='')
				$new_names[]='title="'.$n.'"';
		}

		$db = Factory::getDBO();

		$query = 'SELECT id FROM #__usergroups WHERE '.implode(' OR ',$new_names);

		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		$usergroup_ids=array();
		foreach($rows as $row)
		{
			$usergroup_ids[]=$row->id;
		}
		return $usergroup_ids;
	}

	static public function CreateUserAccount($fullname,$username,$password,$email,$group_names,&$msg)
	{
		//Get group IDs
		$group_ids=CTUser::getUserGroupIDsByName($group_names);

		//Creates active user
		$useractivation=0;//alreadey activated

		$config = Factory::getConfig();

		// Initialise the table with JUser.
		$user = Factory::getUser(0);
		
		$data = array();

		// Prepare the data for the user object.
		$data['name']		= $fullname;
		$data['username']	= $username;
		$data['email']		= $email;
		$data['password']	= $password;
		$data['password2']	= $password;


		// Override the base user data with any
		if (($useractivation == 1) || ($useractivation == 2)) {
			jimport('joomla.user.helper');
			$data['activation'] = JUtility::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}

		// Bind the data.
		if (!$user->bind($data)) {
			$msg=JoomlaBasicMisc::JTextExtended('COM_USERS_REGISTRATION_BIND_FAILED', $user->getError());
			return false;
		}

		// Load the users plugin group.
		//JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save()) {

			$msg=JText::sprintf('COM_USERS_REGISTRATION_SAVE_FAILED', $user->getError());
			return false;
		}

		//Apply group
		$db = Factory::getDBO();

		foreach($group_ids as $group_id)
		{
			$query = 'INSERT #__user_usergroup_map SET user_id='.$user->id.', group_id='.$group_id;

			$db->setQuery( $query );
			$db->execute();
		}

		//---------------------------------------

		// Compile the notification mail values.
		$data = $user->getProperties();
		$data['fromname']	= $config->get('fromname');
		$data['mailfrom']	= $config->get('mailfrom');
		$data['sitename']	= $config->get('sitename');

		$uri = URI::getInstance();
		$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port')).'/';

		$base=str_replace('/administrator/','/',$base);


		$data['siteurl']	= $base;//JUri::base();

		// Handle account activation/confirmation emails.
		if ($useractivation == 1 or $useractivation == 2)
		{
			// Set the link to activate the user account.
			$data['activate'] = $base.'index.php?option=com_users&task=registration.activate&token='.$data['activation'];
		}

				$subject=JoomlaBasicMisc::JTextExtended( 'COM_USERS_EMAIL_ACCOUNT_DETAILS' );
				$body=JoomlaBasicMisc::JTextExtended( 'COM_USERS_EMAIL_REGISTERED_BODY' );

				$config = Factory::getConfig();

				$emailSubject	= JText::sprintf(
					$subject,
					$fullname,
					$config->get( 'sitename' )

				);

				$emailBody = JText::sprintf(
					$body,
					$fullname,
					$config->get( 'sitename' ),
					Uri::base(),
					$username,
					$password
				);

				Email::sendEmail ($email,$emailSubject,$emailBody);

		return $user->id;
	}


	//checkAccess
	public static function checkIfRecordBelongsToUser(&$ct,$ug)
	{
        if(!isset($ct->Env->isUserAdministrator))
            return false;

		if($ug==1)
			$usergroups =array();
		else
			$usergroups = $ct->Env->user->get('groups');

		$isok=false;

		if($ct->Env->isUserAdministrator or in_array($ug,$usergroups))
			$isok=true;
		else
		{
			if(isset($ct->Table->record) and isset($ct->Table->record['listing_published']) and $ct->Table->useridfieldname!='')
			{
				$uid = $ct->Table->record[$ct->Table->useridrealfieldname];

				if($uid==$ct->Env->userid and $ct->Env->userid!=0)
					$isok=true;
			}
		}

		return $isok;
	}

}
