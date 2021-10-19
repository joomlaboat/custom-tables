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

use \Joomla\CMS\Factory;

class Email
{
	public static function checkEmail($email)
	{
		if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",  $email))
        {
            if(Email::domain_exists($email))
                return true;
            else
                return false;
		}
		return false;
	}
	
	protected static function domain_exists($email, $record = 'MX')
    {
    	$pair = explode('@', $email);
        if(count($pair)==1)
            return false;

    	return checkdnsrr(end($pair), $record);
    }
	
	static public function sendEmail($email,$emailSubject,$emailBody,$isHTML = true,$attachments=array())
	{
		$mailer = Factory::getMailer();
		$config = Factory::getConfig();

		$sender = array(
		    $config->get( 'mailfrom' ),
		    $config->get( 'fromname' )
		);

		$mailer->setSender($sender);

		$mailer->addRecipient($email);
		$mailer->setSubject($emailSubject);
		$mailer->setBody($emailBody);
		$mailer->isHTML($isHTML);
		
		foreach($attachments as $attachment)
			$mail->addAttachment($attachment);

		$send = $mailer->Send();

		if ( $send !== true )
			return false;

		return true;
	}
}