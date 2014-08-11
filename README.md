# Phoneduty

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vend/phoneduty/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vend/phoneduty/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/vend/phoneduty/badges/build.png?b=master)](https://scrutinizer-ci.com/g/vend/phoneduty/build-status/master)

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

This is a Twilio Twimlet designed to be hosted on Heroku. It will query PagerDuty to find the currently on-call engineer and forward the inbound call to them.

It needs a few environment variables defined to work:

    PAGERDUTY_SCHEDULE_ID
    PAGERDUTY_API_TOKEN
    PAGERDUTY_DOMAIN

Those names should be fairly self-explanatory. The domain is the piece of your PagerDuty URL that is specific to you 
i.e.  https://[PAGERDUTY_DOMAIN].pagerduty.com/


# Usage

- Configure your on-call schedule in PagerDuty
- Ensure your rostered staff have a 'phone' contact method defined
- Note the schedule ID of the roster you wish to use.
- Create and note an API key in PagerDuty
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





