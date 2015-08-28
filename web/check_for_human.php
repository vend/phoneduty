<?php

require __DIR__ . '/../vendor/autoload.php';

session_id($_POST['ParentCallSid']);
session_start();

$attributes = array(
    'voice' => 'alice',
    'language' => 'en-GB'
    );

$twilio = new Services_Twilio_Twiml();

if (isset($_POST['Digits'])) {
    if ($_POST['Digits'] != '') {
        $_SESSION['engineer_accepted_call'] = true;
        error_log("session ID: " . session_id() . "; engineer_accepted_call: " . $_SESSION['engineer_accepted_call']);
        $twilio->say("Connecting", $attributes);
    }
} else {
    $twilio->pause(array('length' => 2));
    $gather = $twilio->gather(array('timeout' => 15, 'numDigits' => 1));
    $gather->say("Press 1 to accept this call.", $attributes);
    $twilio->say("Goodbye.", $attributes);
    $twilio->hangup();
}

// send response
if (!headers_sent()) {
    header('Content-type: text/xml');
}

echo $twilio;

?>
