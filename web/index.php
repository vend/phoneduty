<?php

/**
 *
 * Twilio twimlet for forwarding inbound calls
 * to the on-call engineer as defined in PagerDuty
 *
 * Designed to be hosted on Heroku
 *
 * (c) 2014 Vend Ltd.
 *
 */

require __DIR__ . '/../vendor/autoload.php';

// Set these Heroku config variables
$scheduleID      = getenv('PAGERDUTY_SCHEDULE_ID');
$APItoken        = getenv('PAGERDUTY_API_TOKEN');
$serviceAPItoken = getenv('PAGERDUTY_SERVICE_API_TOKEN');
$domain          = getenv('PAGERDUTY_DOMAIN');

// Should we announce the local time of the on-call person?
// (helps raise awareness you might be getting somebody out of bed)
$announceTime    = getenv('PHONEDUTY_ANNOUNCE_TIME');

if (isset($_POST['CallSid']) {
    session_id($_POST['CallSid']);
}
session_start();
$_SESSION['engineer_accepted_call'] = false;

$pagerduty = new \Vend\Phoneduty\Pagerduty($APItoken, $serviceAPItoken, $domain);

$userID = $pagerduty->getOncallUserForSchedule($scheduleID);
$user = $pagerduty->getUserDetails($userID);

$twilio = new Services_Twilio_Twiml();

$attributes = array(
    'voice' => 'alice',
    'language' => 'en-GB'
);

if (null !== $userID) {
    $time = "";
    if (($announceTime == 'true' || $announceTime == 'True') && $user['local_time']) {
        $time = sprintf("The current time in their timezone is %s.", $user['local_time']->format('g:ia'));
    }

    $twilio->say(sprintf("The current on-call engineer is %s." .
        "%s Please hold while we connect you.",
        $user['first_name'], $time), $attributes);

    $dial = $twilio->dial(NULL, array('action' => "check_if_completed_by_human.php", 'timeout' => 30));
    $dial->number($user['phone_number'], array('url' => "check_for_human.php"));
} else {
    $twilio->redirect('voicemail.php');
}

// send response
if (!headers_sent()) {
    header('Content-type: text/xml');
}

echo $twilio;

?>
