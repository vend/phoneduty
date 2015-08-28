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
$announceTime = getenv('PHONEDUTY_ANNOUNCE_TIME');

$pagerduty = new \Vend\Phoneduty\Pagerduty($APItoken, $serviceAPItoken, $domain);

$userID = $pagerduty->getOncallUserForSchedule($scheduleID);

$messages['calling_engineer'] = "The current on-call engineer is %s. %s Please hold while we connect you.";
$messages['no_answer'] = "The on-call engineer isn't available. Please leave a message after the beep describing the issue. Press any key or hang up when you are finished.";

if (null !== $userID) {
    $user = $pagerduty->getUserDetails($userID);

    $attributes = array(
        'voice' => 'alice',
        'language' => 'en-GB'
    );

    $twilio = new Services_Twilio_Twiml();

    $time = "";
    if ($announceTime && $user['local_time']) {
        $time = sprintf("The current time in their timezone is %s.", $user['local_time']->format('g:ia'));
    }

    $twilio->say(sprintf($messages['calling_engineer'], $user['first_name'], $time), $attributes);
    $twilio->dial($user['phone_number'], array('timeout' => 20));
    $twilio->say($messages['no_answer'], $attributes);
    $twilio->record(array(
        'action' => 'voicemail.php'
    ));

    // send response
    if (!headers_sent()) {
        header('Content-type: text/xml');
    }

    echo $twilio;
}
