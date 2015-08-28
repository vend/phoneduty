<?php

// Set these Heroku config variables
$scheduleID      = getenv('PAGERDUTY_SCHEDULE_ID');
$APItoken        = getenv('PAGERDUTY_API_TOKEN');
$serviceAPItoken = getenv('PAGERDUTY_SERVICE_API_TOKEN');
$domain          = getenv('PAGERDUTY_DOMAIN');

$pagerduty = new \Vend\Phoneduty\Pagerduty($APItoken, $serviceAPItoken, $domain);

if (isset($_POST['RecordingUrl'])) {
    #create PD incident with link to recording
    $incident_data = Array(
        'service_key' => '',
        'event_type' => 'trigger',
        'description' => 'New voicemail for the on-call engineer',
        'contexts' => Array(
            'name' => 'Listen to the voicemail',
            'href' => $_POST['RecordingUrl'],
            'type' => 'link');
        'details' => Array(
            'duration' => $_POST['RecordingDuration'],
            'from' => $_POST['From']
        );
    $pagerduty->triggerIncident($incident_data);
}

?>
