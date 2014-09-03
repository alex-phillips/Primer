<?php
/**
 * @author Alex Phillips
 * Date: 8/30/14
 * Time: 7:38 AM
 */

namespace Primer\Mail;

use PHPMailer;
use EMAIL_CONFIG;

class Mail
{
    private $_config;
    private $_mailer;

    public function __construct($config)
    {
        $this->_mailer = new PHPMailer();

        $this->_config = $config;

        if ($config['use_smtp']) {
            $this->_mailer->IsSMTP(); // Set mailer to use SMTP
            $this->_mailer->Host = $config['smtp_host']; // Specify main and backup server
            $this->_mailer->SMTPAuth = $config['smtp_auth']; // Enable SMTP authentication
            $this->_mailer->Username = $config['smtp_username']; // SMTP username
            $this->_mailer->Password = $config['smtp_password']; // SMTP password

            if ($config['smtp_encryption']) {
                $this->_mailer->SMTPSecure = $config['smtp_encryption']; // Enable encryption, 'ssl' also accepted
            }

        }
        else {
            $this->_mailer->IsMail();
        }
    }

    public function send($data = array())
    {
        extract($data);

        $this->_mailer->From = $from;
        $this->_mailer->FromName = $fromName;

        foreach ($recipients as $recipient) {
            $this->_mailer->AddAddress($recipient);
        }

        $this->_mailer->Subject = $subject;
        $this->_mailer->Body = $body;

        // After send attempt, reset the PHPMailer so it can be used fresh again
        if (!$this->_mailer->Send()) {
            return false;
        }

        return true;
    }
}