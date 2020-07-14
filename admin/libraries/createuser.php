<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CustomTablesCreateUser
{

	static public function UpdateUserLink($establename,$fieldusreidname,$userid,$id)
	{
		$db = JFactory::getDBO();

		$query = 'UPDATE #__customtables_table_'.$establename.' SET '.$fieldusreidname.'='.$userid.' WHERE id='.$id;

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());


	}

	static public function CheckIfUserNameExist($username)
	{
		$db = JFactory::getDBO();

		$query = 'SELECT id FROM #__users WHERE username="'.$username.'" LIMIT 1';

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());
		$recs = $db->loadAssocList();
		if(count($recs)==1)
			return true;

		return false;

	}


	static public function CheckIfUserExist($username, $email)
	{
		$db = JFactory::getDBO();


		$query = 'SELECT id FROM #__users WHERE username="'.$username.'" AND email="'.$email.'" LIMIT 1';


		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());
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
		$db = JFactory::getDBO();

		$query = 'SELECT id, username, name FROM #__users WHERE email="'.$email.'" LIMIT 1';

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());
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




	static public function CreateUser($fullname,$username,$password,$email,$groups,&$msg_warning,$email_content_article_id,$establename,$field_userid,$row_id)
	{
			//Create user
			die;
			if($email=='')
			{
				$msg_warning[]='User "'.$fullname.'" does not have email.';
			}
			else
			{

				$existing_user='';
				$existing_name='';
				$userid=CustomTablesCreateUser::CheckIfUserExist($username, $email);
				if($userid!=0)
				{
					//If this exactlly user already exist, then just update the record
					$msg_warning[]='Update User: "'.$username.'"';
					CustomTablesCreateUser::UpdateUserLink($establename,$field_userid,$userid,$row_id);
				}
				elseif(CustomTablesCreateUser::CheckIfEmailExist($email,$existing_user,$existing_name))
				{
					//If email is ocpupated then yell about it
					$msg_warning[]=JText::_( 'COM_CUSTOMTABLES_ERROR_USER_WITH_EMAIL' )
                                                           .' "'.$email.'" '
                                                           .JText::_( 'COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS' ).' - '.$existing_user.'" ('.$existing_name.').';
				}
				else
				{
					//check if username exists
					$isUnique=false;
					$username_new=$username;

					$i=0;
					do
					{
						$isUnique=!CustomTablesCreateUser::CheckIfUserNameExist($username_new);
						if(!$isUnique)
						{
							$i++;
							$username_new=$username.$i;
						}
					}while(!$isUnique);

					$userid=CustomTablesCreateUser::CreateUserAccount($fullname,$username_new,$password,$email,$groups,$msg,$email_content_article_id);
					$msg_warning[]='* User "'.$username.'" has been added. Email sent to: "'.$email.'" ';


					if($userid>0)
					{

						CustomTablesCreateUser::UpdateUserLink($establename,$field_userid,$userid,$row_id);

					}
					else
						$msg_warning[]='User: '.$username.' email: '.$email.' Error: '.$msg;


				}//if($this->CheckIfEmailExist($email))

			}//if($email=='')
	}

	static public function GetArticleContent($article_id,&$title)
	{
		$db = JFactory::getDBO();

		$query = 'SELECT id, title, introtext FROM #__content WHERE id='.(int)$article_id.' LIMIT 1';

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());
		$recs = $db->loadAssocList();
		if(count($recs)==1)
		{
			$row=$recs[0];

			$title=$row['title'];

			return $row['introtext'];
		}

		return '';
	}

	static public function SetUserPassword($userid,$password)
	{

		$db = JFactory::getDBO();
		$query='UPDATE #__users SET password=md5("'.$password.'"), requireReset=0 WHERE id='.$userid;

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());


		return $userid;
	}

	static public function GetUserRow($userid)
	{

		$db = JFactory::getDBO();
		$query='SELECT * FROM #__users WHERE id='.$userid;

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

		$recs=$db->loadAssocList();
		if(count($recs)==0)
			return array();
		else
			return $recs[0];
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

		$db = JFactory::getDBO();

		$query = 'SELECT id FROM #__usergroups WHERE '.implode(' OR ',$new_names);

		$db->setQuery( $query );
		if (!$db->query())    die ( $db->stderr());

		$rows = $db->loadObjectList();
		$ids=array();
		foreach($rows as $row)
		{
			$ids[]=$row->id;
		}
		return $ids;
	}

	static public function CreateUserAccount($fullname,$username,$password,$email,$group_names,&$msg,$email_content_article_id)
	{
		//Get group IDs
		$group_ids=CustomTablesCreateUser::getUserGroupIDsByName($group_names);


		//Creates active user
		$useractivation=0;//alreadey activated

		$config = JFactory::getConfig();
		$params = JComponentHelper::getParams('com_users');

		// Initialise the table with JUser.
		$user = new JUser;
		$data = array();

		// Prepare the data for the user object.
		$data['name']		= $fullname;
		$data['username']	= $username;
		$data['email']		= $email;
		$data['password']	= $password;


		// Override the base user data with any
		if (($useractivation == 1) || ($useractivation == 2)) {
			jimport('joomla.user.helper');
			$data['activation'] = JUtility::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}

		// Bind the data.
		if (!$user->bind($data)) {
			$msg=JText::sprintf('COM_USERS_REGISTRATION_BIND_FAILED', $user->getError());
			return false;
		}

		// Load the users plugin group.
		JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save()) {

			$msg=JText::sprintf('COM_USERS_REGISTRATION_SAVE_FAILED', $user->getError());
			return false;
		}

		//Apply group
		$db = JFactory::getDBO();

		foreach($group_ids as $group_id)
		{
			$query = 'INSERT #__user_usergroup_map SET user_id='.$user->id.', group_id='.$group_id;

			$db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());
		}

		//---------------------------------------

		// Compile the notification mail values.
		$data = $user->getProperties();
		$data['fromname']	= $config->get('fromname');
		$data['mailfrom']	= $config->get('mailfrom');
		$data['sitename']	= $config->get('sitename');

		$uri = JURI::getInstance();
		$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port')).'/';

		$base=str_replace('/administrator/','/',$base);


		$data['siteurl']	= $base;//JUri::base();

		// Handle account activation/confirmation emails.
		if ($useractivation == 1 or $useractivation == 2)
		{
			// Set the link to activate the user account.
			$data['activate'] = $base.'index.php?option=com_users&task=registration.activate&token='.$data['activation'];
		}

				$admin_libpath=JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
				require_once($admin_libpath.'misc.php');

				$subject=JoomlaBasicMisc::JTextExtended( 'COM_USERS_EMAIL_ACCOUNT_DETAILS' );
				$body=JoomlaBasicMisc::JTextExtended( 'COM_USERS_EMAIL_REGISTERED_BODY' );

				$config = JFactory::getConfig();

				$emailSubject	= JText::sprintf(
					$subject,
					$fullname,
					$config->get( 'sitename' )

				);

				$emailBody = JText::sprintf(
					$body,
					$fullname,
					$config->get( 'sitename' ),
					JUri::base(),
					$username,
					$password
				);

				CustomTablesCreateUser::sendEmail ($email,$emailSubject,$emailBody);

		return $user->id;


	}
	static public function sendEmail($email,$emailSubject,$emailBody)
	{
		$mailer = JFactory::getMailer();



		$config = JFactory::getConfig();


		$sender = array(
		    $config->get( 'mailfrom' ),
		    $config->get( 'fromname' )
		);

		$mailer->setSender($sender);

		$mailer->addRecipient($email);
		$mailer->setSubject($emailSubject);
		$mailer->setBody($emailBody);
		$mailer->isHTML(false);

		$send = $mailer->Send();

		if ( $send !== true )
		{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_CUSTOMTABLES_ERROR_SENDING_EMAIL'), 'error');
		} else {
				JFactory::getApplication()->enqueueMessage(JText::_('COM_CUSTOMTABLES_EMAIL_SENT_TO').' "'.$email.'"');
		}



	}

}
