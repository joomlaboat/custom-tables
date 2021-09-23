<?php

/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;

jimport( 'joomla.application.component.view');

class CustomTablesViewResetUserPassword extends JViewLegacy
{
	var $ct;

	function display($tpl = null)
	{
		$this->ct = new CT;
		
		$jinput=JFactory::getApplication()->input;

		$clean=(bool)$jinput->getInt('clean',0);
		if($clean)
		{
			if (ob_get_contents()) ob_end_clean();
		}

		$app		= JFactory::getApplication();
		$params=$app->getParams();

		$user_listing_id=JFactory::getApplication()->input->get('listing_id',0,'INT');
		if($user_listing_id==0)
		{
				echo '<p style="padding:3px;color:white;background-color:red;">User ID not set.</p>';
				return;
		}

		//get Table
		$this->ct->getTable($params->get( 'establename' ), null);
				
		if($this->ct->Table->tablename=='')
		{
			$Itemid=JFactory::getApplication()->input->getInt('Itemid', 0);
			JFactory::getApplication()->enqueueMessage('Table not selected.', 'error');
			return;
		}

		$this->ct->Table->loadRecord($userid);
		
		if($this->ct->Table->record == null)
		{
			echo '<p style="padding:3px;color:white;background-color:red;">User record ID: "'.$user_listing_id.'" not found.</p>';
			return;
		}

		$password=strtolower(JUserHelper::genRandomPassword());

		$realuserid=$this->ct->Table->record[$this->ct->Table->useridrealfieldname];

		$realuserid=CustomTablesCreateUser::SetUserPassword($realuserid,$password);

		$userrow=CustomTablesCreateUser::GetUserRow($realuserid);

		$mainframe = JFactory::getApplication('site');

		$MailFrom 	= $mainframe->getCfg('mailfrom');
		$FromName 	= $mainframe->getCfg('fromname');

		$user_full_name=ucwords(strtolower($userrow['name']));
		$subject=$params->get('sendemailsubject');
		$messagebody=$params->get('messagebody');

		$messagebody=str_replace('%name', $user_full_name, $messagebody);
		$messagebody=str_replace('%username', $userrow['username'], $messagebody);
		$messagebody=str_replace('%password', $password, $messagebody);

		CustomTablesCreateUser::sendEmail($userrow['email'],$subject,$messagebody);

		echo '
New Password: '.$password;

		parent::display($tpl);

		if($clean)
			die ;//clean exit
	}

	/*
	static protected function sendPasswordByEmail($username,$password,$email)
	{

		$mainframe = JFactory::getApplication('site');

		$MailFrom 	= $mainframe->getCfg('mailfrom');
		$FromName 	= $mainframe->getCfg('fromname');

		$subject=$params->get('sendemailsubject');
		$messagebody=$params->get('messagebody');

		$mail->IsHTML(false);
		$mail->addRecipient($email);
		$mail->setSender( array($MailFrom,$FromName) );
		$mail->setSubject( $subject);
		$mail->setBody( $messagebody);

		$sent = $mail->Send();

	}
	*/

}
