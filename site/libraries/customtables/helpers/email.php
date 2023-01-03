<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Exception;
use \Joomla\CMS\Factory;

class Email
{
    public static function checkEmail($email): bool
    {
        if (preg_match("/^([a-zA-Z\d])+([a-zA-Z\d\._-])*@([a-zA-Z\d_-])+([a-zA-Z\d\._-]+)+$/", $email)) {
            if (Email::domain_exists($email))
                return true;
            else
                return false;
        }
        return false;
    }

    protected static function domain_exists($email, $record = 'MX'): bool
    {
        $pair = explode('@', $email);
        if (count($pair) == 1)
            return false;

        return checkdnsrr(end($pair), $record);
    }

    static public function sendEmail($email, $emailSubject, $emailBody, $isHTML = true, $attachments = array()): bool
    {
        $mailer = Factory::getMailer();
        $config = Factory::getConfig();

        $sender = array(
            $config->get('mailfrom'),
            $config->get('fromname')
        );

        $mailer->setSender($sender);
        $mailer->addRecipient($email);
        $mailer->setSubject($emailSubject);
        $mailer->setBody($emailBody);
        $mailer->isHTML($isHTML);

        foreach ($attachments as $attachment)
            $mailer->addAttachment($attachment);

        try {
            $send = @$mailer->Send();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            Factory::getApplication()->enqueueMessage($msg, 'error');
            return false;
        }

        if ($send !== true)
            return false;

        return true;
    }
}