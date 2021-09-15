<?php

/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');

class CustomTablesViewResetUserPassword extends JViewLegacy {

	function display($tpl = null)
	{
		$jinput=JFactory::getApplication()->input;

		$clean=(bool)$jinput->getInt('clean',0);
		if($clean)
		{
			if (ob_get_contents()) ob_end_clean();
		}

		$app		= JFactory::getApplication();
		$params=$app->getParams();

		$userid=JFactory::getApplication()->input->get('listing_id',0,'INT');
		if($userid==0)
		{
				echo '<p style="padding:3px;color:white;background-color:red;">User ID not set.</p>';
				return;
		}

		//get Table
		$establename=$params->get( 'establename' );
		if($establename=='')
		{
				echo '<p style="padding:3px;color:white;background-color:red;">Table not set.</p>';
				return;
		}




		$esTable=new ESTables;
		$tablerow=$esTable->getTableRowByNameAssoc($establename);
		$estableid=$tablerow['id'];
		$tablename='#__customtables_table_'.$establename;

		$esfields = ESFields::getFields($estableid);

		$useridfieldname=CustomTablesViewResetUserPassword::getUserField($params->get('useridfield'),$esfields);

		$row=CustomTablesViewResetUserPassword::getRow($userid,$tablename);
		if(count($row)==0)
		{
				echo '<p style="padding:3px;color:white;background-color:red;">User ID: "'.$userid.'" not found.</p>';
				return;
		}



		$password=strtolower(JUserHelper::genRandomPassword());

		$realuserid=$row['es_'.$useridfieldname];

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


	protected static function getUserField($uf,&$esfields)
	{
		if($uf)
		{
					return $uf;
		}
		else
		{
				foreach($esfields as $fld)
				{
						if($fld['type']=='userid')
						{
							return $fld['fieldname'];
							break;

						}
				}
		}
		return '';
	}



    protected static function getRow($id,$tablename)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT * FROM '.$tablename.' WHERE id='.$id.' LIMIT 1';
		$db->setQuery( $query );
	
		$recs = $db->loadAssocList( );
		if(!$recs) return array();
		if (count($recs)<1) return array();

		$r=$recs[0];
		return $r;
	}
}
