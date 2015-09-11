# Phoneduty

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vend/phoneduty/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vend/phoneduty/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/vend/phoneduty/badges/build.png?b=master)](https://scrutinizer-ci.com/g/vend/phoneduty/build-status/master)

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

This is a Twilio Twimlet designed to be hosted on Heroku. It will query PagerDuty to find the currently on-call engineer and forward the inbound call to them. If there is no one on-call or if the on-call engineer doesn't answer, the caller will be prompted to leave a message which will trigger an incident within PagerDuty.

It needs a few environment variables defined to work:

    PAGERDUTY_SCHEDULE_ID
    PAGERDUTY_API_TOKEN
    PAGERDUTY_SERVICE_API_TOKEN
    PAGERDUTY_DOMAIN

Those names should be fairly self-explanatory. The domain is the piece of your PagerDuty URL that is specific to you
i.e.  https://[PAGERDUTY_DOMAIN].pagerduty.com/

You can also optionally set PHONEDUTY_ANNOUNCE_TIME, which if set to a TRUEish value will include the current
time of the engineer being called as part of the answering message. This may help raise awareness that you are potentially getting
somebody out of bed, so be gentle :D

Additionally, you can optionally set a greeting to be played to the caller before connecting them to the on-call engineer or asking them to leave a voicemail with the PHONEDUTY_ANNOUNCE_GREETING configuration option.


# Usage

- Configure your on-call schedule in PagerDuty
- Ensure your rostered staff have a 'phone' contact method defined
- Note the schedule ID of the roster you wish to use.
- Create and note an API key in PagerDuty
- Create the PagerDuty service for any voice messages and note the service API key
- Deploy this app to Heroku.
- Configure the relevant environment variables above in Heroku
- Buy a phone number from Twilio
- Add the generated Heroku URL  as a "Request URL" for the Voice property of this Twilio number
- Call the external Twilio number. You should get a voice prompt telling you who is on call, what time it is in their timezone currently, and then you will get connected to the rostered engineer.


# Relevant Reading

## Twilio

Twilio TwiML reference
<https://www.twilio.com/docs/api/twiml>

Some sample Twimlets:
<https://www.twilio.com/labs/twimlets>


## PagerDuty

PagerDuty API
<http://developer.pagerduty.com/documentation/integration/events>

## Heroku

Setting up and deploying PHP apps on Heroku
<https://devcenter.heroku.com/articles/getting-started-with-php>





