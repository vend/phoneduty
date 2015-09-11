<?php

require __DIR__ . '/../vendor/autoload.php';

if (isset($_POST['CallSid'])) {
    session_id($_POST['CallSid']);
}
session_start();

$attributes = array(
    'voice' => 'alice',
    'language' => 'en-GB'
    );

$twilio = new Services_Twilio_Twiml();

if (isset($_POST['DialCallStatus'])) {
    if ($_POST['DialCallStatus'] == 'completed') {
        if ($_SESSION['engineer_accepted_call']) {
            error_log("engineer_accepted_call is true");
            $twilio->hangup();
        }
    }
}

$twilio->redirect('voicemail.php');

// send response
if (!headers_sent()) {
    header('Content-type: text/xml');
}

echo $twilio;

?>
