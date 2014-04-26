<?php

require_once(__DIR__ . '/../Config/config.php');

$recipients = array(
    'ahp118@gmail.com',
);

logMessage('starting...');
$active_ip = file_get_contents('http://ipecho.net/plain');

if ($active_ip) {
    logMessage('active IP is ' . $active_ip);
}
else {
    logMessage('unable to retrieve active IP');
}

if (file_exists('monitors/current_ip')) {
    $old_ip = file_get_contents('monitors/current_ip');
    logMessage('recorded IP address is ' . $old_ip);
    if ($active_ip !== $old_ip) {
        logMessage('IP address has changed. Updating file.');
        sendEmail('IP Monitor - IP Address Changed', 'New IP Address:' . $active_ip, $recipients);
	file_put_contents('monitors/current_ip', $active_ip);
    }
    else {
        logMessage('IP address has not changed.');
    }
}
else {
    logMessage('no recorded IP address file found in monitors/current_ip. creating a new one.');
    file_put_contents('monitors/current_ip', $active_ip);
}

logMessage('done.');

function sendEmail ($subject, $message, $recipients)
{
    $headers = 'From: noreply@codersmanifesto.com' . "\r\n" .
                'Reply-To: noreply@codersmanifesto.com' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

    foreach ($recipients as $recipient) {
        mail($recipient, $subject, $message, $headers);
    }
}

function logMessage ($message)
{
    Primer::logMessage($message, 'ip_checker');
}
