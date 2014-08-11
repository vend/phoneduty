<?php
/**
 * Interface to PagerDuty schedules & user details.
 *
 * @author  Morgan Pyne <morgan@vendhq.com>
 *
 */

namespace Vend\Phoneduty;
use \DateTime;
use \DateTimeZone;
use \DateInterval;


class Pagerduty {

    const DEFAULT_TIMEZONE = 'Pacific/Auckland';

    protected $APItoken;
    protected $URL;
    protected $httpClient;

    /**
     * Constructor. Expects an API token and PagerDuty domain.
     *
     * @param string $APItoken
     * @param string $domain
     *
     */
    public function __construct($APItoken, $domain) {
        $this->APItoken = $APItoken;
        $this->URL = "https://{$domain}.pagerduty.com/api/v1";

        $this->httpClient = new \GuzzleHttp\Client(
            array('defaults' =>
                array('headers' =>
                    array(
                        'Content-Type' => 'application/json',
                        'Authorization' => "Token token={$APItoken}"
                    )
                )
            )
        );
    }

    /**
     * Get the ID of current on-call user for a given schedule
     *
     * @param string $scheduleID
     *
     * @return string $userID
     */
    public function getOncallUserForSchedule($scheduleID)
    {

        // PagerDuty requires a datetime range to provide a final schedule
        // i.e. one accounting for overrides, which we need to find out who
        // is actually on call at a given time.
        $UTC = new DateTimeZone("UTC");
        $datetime = new DateTime('now', $UTC);
        $now = urlencode($datetime->format(DateTime::ISO8601));
        $datetime->add(new DateInterval('PT1S'));
        $oneSecondLater = urlencode($datetime->format(DateTime::ISO8601));

        $pagerDutyScheduleURL = "{$this->URL}/schedules/{$scheduleID}?since={$now}&until={$oneSecondLater}";

        $userID = null;

        // See http://developer.pagerduty.com/documentation/rest/schedules/show for details
        $response = $this->httpClient->get($pagerDutyScheduleURL);

        if ($response->getStatusCode() == 200) {
            $json = json_decode($response->getBody(), true);
            $userID = $json['schedule']['final_schedule']['rendered_schedule_entries'][0]['user']['id'];
        }

        return $userID;
    }


    /**
     *
     * Retrieve the details of the on-call user
     *
     * Details include full name, first name, local time, phone number
     *
     * @param string $userID
     *
     * @return array $user
     */
    public function getUserDetails($userID)
    {
        // See http://developer.pagerduty.com/documentation/rest/users/show
        $pagerDutyUserURL = "{$this->URL}/users/{$userID}";
        $queryString = array('query' => array('include[]' => 'contact_methods'));

        $response = $this->httpClient->get($pagerDutyUserURL, $queryString);

        $user = null;

        if ($response->getStatusCode() == 200) {
            $json = json_decode($response->getBody(), true);

            foreach($json['user']['contact_methods'] as $method) {
                if($method['type'] == 'phone') {
                    $user = array(
                        'full_name'   => $json['user']['name'],
                        'first_name'  => $this->extractFirstName($json['user']['name']),
                        'local_time'    => $this->getCurrentTimeForTimezone($json['user']['time_zone']),
                        'phone_number' => "+{$method['country_code']}{$method['phone_number']}",
                    );
                    break;
                }
            }
        }

        return $user;
    }

    /**
     * Extract the first name from a full name.
     *
     * Perform a a naive split on the full name by space, assume
     * the first piece is the first name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function extractFirstName($name) {

        $pieces = explode(' ', $name);
        return $pieces[0];
    }

    /**
     *
     * Get the current time for the specified timezone.
     * If the timezone is invalid, default to using
     * self::DEFAULT_TIMEZONE
     *
     * (this is a workaround to PagerDuty currently returning
     * some broken timezone data)
     *
     * @param string $tz
     *
     * @return DateTime
     */
    protected function getCurrentTimeForTimezone($tz) {

        try {
            $tzObj = new DateTimeZone($tz);
        } catch (\Exception $e) {
            // TZ is invalid, try default
            $tzObj = new DateTimeZone(self::DEFAULT_TIMEZONE);
        }

        return new DateTime('now', $tzObj);
    }
} 
