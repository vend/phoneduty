<?php

require __DIR__ . '/../vendor/autoload.php';

// Set these Heroku config variables
$scheduleID      = getenv('PAGERDUTY_SCHEDULE_ID');
$APItoken        = getenv('PAGERDUTY_API_TOKEN');
$serviceAPItoken = getenv('PAGERDUTY_SERVICE_API_TOKEN');
$domain          = getenv('PAGERDUTY_DOMAIN');

$pagerduty = new \Vend\Phoneduty\Pagerduty($APItoken, $serviceAPItoken, $domain);

if (isset($_POST['RecordingUrl'])) {
    $attributes = array(
        'voice' => 'alice',
        'language' => 'en-GB'
    );

    $twilio = new Services_Twilio_Twiml();

    #create PD incident with link to recording
    $incident_data = Array(
        'service_key' => $serviceAPItoken,
        'event_type' => 'trigger',
        'description' => 'New voicemail for the on-call engineer',
        'contexts' => [ Array(
            'type' => 'link',
            'text' => 'Listen to the message',
            'href' => $_POST['RecordingUrl'] . '.mp3')],
        'details' => Array(
            'duration' => $_POST['RecordingDuration'],
            'from' => $_POST['From'])
        );
    $pagerduty->triggerIncident($incident_data);

    $twilio->say("Your message has been sent. Thank you.", $attributes);
}

$twilio->hangup();

?>
