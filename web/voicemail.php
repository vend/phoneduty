<?php

require __DIR__ . '/../vendor/autoload.php';

// Set these Heroku config variables
$scheduleID      = getenv('PAGERDUTY_SCHEDULE_ID');
$APItoken        = getenv('PAGERDUTY_API_TOKEN');
$serviceAPItoken = getenv('PAGERDUTY_SERVICE_API_TOKEN');
$domain          = getenv('PAGERDUTY_DOMAIN');

// What language should Twilio use?
$language        = getenv('TWILIO_LANGUAGE');

$attributes = array(
    'voice' => 'alice',
    'language' => $language
);

if (isset($_POST['CallSid'])) {
    session_id($_POST['CallSid']);
}
session_start();

$pagerduty = new \Vend\Phoneduty\Pagerduty($APItoken, $serviceAPItoken, $domain);

$twilio = new Services_Twilio_Twiml();

if (isset($_POST['RecordingUrl'])) {
    #create PD incident with link to recording
    $incident_data = Array(
        'service_key' => $serviceAPItoken,
        'event_type' => 'trigger',
        'description' => 'New voicemail from ' . $_POST['From'],
        'contexts' => [ Array(
            'type' => 'link',
            'text' => 'Listen to the message',
            'href' => $_POST['RecordingUrl'] . '.mp3')],
        'details' => Array(
            'duration' => $_POST['RecordingDuration'] . ' seconds',
            'from' => $_POST['From'])
        );
    $pagerduty->triggerIncident($incident_data);

    $twilio->say("Your message has been sent. Thank you.", $attributes);

    session_unset();
    session_destroy();
} else {
    $twilio->say("The on-call engineer isn't available. " .
        "Please leave a message after the beep describing the issue. " .
        "Press any key or hang up when you are finished. ", $attributes);

    $twilio->record(array(
        'action' => 'voicemail.php'
    ));
}

$twilio->hangup();

// send response
if (!headers_sent()) {
    header('Content-type: text/xml');
}

echo $twilio;

?>
