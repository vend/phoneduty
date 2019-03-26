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

    const DEFAULT_TIMEZONE = 'Europe/Paris';

    protected $APItoken;
    protected $URL;
    protected $httpClient;

    /**
     * Constructor. Expects a PagerDuty API token.
     *
     * @param string $APItoken
     *
     */
    public function __construct($APItoken) {
        $this->APItoken = $APItoken;
        $this->URL = "https://api.pagerduty.com";

        $this->httpClient = new \GuzzleHttp\Client(
            ['headers' =>
                [ 'Accept' => 'application/vnd.pagerduty+json;version=2',
                  'Authorization' => "Token token=$APItoken"
                ]
            ]
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

        $pagerDutyScheduleURL = "{$this->URL}/schedules/{$scheduleID}?time_zone=UTC&since={$now}&until={$oneSecondLater}";

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
        $queryString = [
            'query' => [
                'include[]' => 'contact_methods'
            ]
        ];

        $response = $this->httpClient->get($pagerDutyUserURL, $queryString);

        $user = null;

        if ($response->getStatusCode() == 200) {
            $json = json_decode($response->getBody(), true);

            foreach($json['user']['contact_methods'] as $method) {
                if($method['type'] == 'phone_contact_method') {
                    $user = [
                        'full_name'   => $json['user']['name'],
                        'first_name'  => $this->extractFirstName($json['user']['name']),
                        'local_time'    => $this->getCurrentTimeForTimezone(
                            $this->convertFriendlyTimezoneToFull($json['user']['time_zone'])),
                        'phone_number' => "+{$method['country_code']}{$method['address']}",
                    ];
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

    /*
     * The PagerDuty API does not return fully qualified timezone strings,
     * which means PHP won't parse them properly
     *
     * Details of what it returns are found here:
     * https://developer.pagerduty.com/documentation/rest/types
     *
     * These tz shortnames are apparently derived from the ActiveSupport::Timezone
     * class from Rails:
     * http://api.rubyonrails.org/classes/ActiveSupport/TimeZone.html
     *
     * @param string $tz
     *
     * @return string
     */
    protected function convertFriendlyTimezoneToFull($tz)
    {
        $timezones = [
            "International Date Line West" => "Pacific/Midway",
            "Midway Island" => "Pacific/Midway",
            "American Samoa" => "Pacific/Pago_Pago",
            "Hawaii" => "Pacific/Honolulu",
            "Alaska" => "America/Juneau",
            "Pacific Time (US & Canada)" => "America/Los_Angeles",
            "Tijuana" => "America/Tijuana",
            "Mountain Time (US & Canada)" => "America/Denver",
            "Arizona" => "America/Phoenix",
            "Chihuahua" => "America/Chihuahua",
            "Mazatlan" => "America/Mazatlan",
            "Central Time (US & Canada)" => "America/Chicago",
            "Saskatchewan" => "America/Regina",
            "Guadalajara" => "America/Mexico_City",
            "Mexico City" => "America/Mexico_City",
            "Monterrey" => "America/Monterrey",
            "Central America" => "America/Guatemala",
            "Eastern Time (US & Canada)" => "America/New_York",
            "Indiana (East)" => "America/Indiana/Indianapolis",
            "Bogota" => "America/Bogota",
            "Lima" => "America/Lima",
            "Quito" => "America/Lima",
            "Atlantic Time (Canada)" => "America/Halifax",
            "Caracas" => "America/Caracas",
            "La Paz" => "America/La_Paz",
            "Santiago" => "America/Santiago",
            "Newfoundland" => "America/St_Johns",
            "Brasilia" => "America/Sao_Paulo",
            "Buenos Aires" => "America/Argentina/Buenos_Aires",
            "Montevideo" => "America/Montevideo",
            "Georgetown" => "America/Guyana",
            "Greenland" => "America/Godthab",
            "Mid-Atlantic" => "Atlantic/South_Georgia",
            "Azores" => "Atlantic/Azores",
            "Cape Verde Is." => "Atlantic/Cape_Verde",
            "Dublin" => "Europe/Dublin",
            "Edinburgh" => "Europe/London",
            "Lisbon" => "Europe/Lisbon",
            "London" => "Europe/London",
            "Casablanca" => "Africa/Casablanca",
            "Monrovia" => "Africa/Monrovia",
            "UTC" => "Etc/UTC",
            "Belgrade" => "Europe/Belgrade",
            "Bratislava" => "Europe/Bratislava",
            "Budapest" => "Europe/Budapest",
            "Ljubljana" => "Europe/Ljubljana",
            "Prague" => "Europe/Prague",
            "Sarajevo" => "Europe/Sarajevo",
            "Skopje" => "Europe/Skopje",
            "Warsaw" => "Europe/Warsaw",
            "Zagreb" => "Europe/Zagreb",
            "Brussels" => "Europe/Brussels",
            "Copenhagen" => "Europe/Copenhagen",
            "Madrid" => "Europe/Madrid",
            "Paris" => "Europe/Paris",
            "Amsterdam" => "Europe/Amsterdam",
            "Berlin" => "Europe/Berlin",
            "Bern" => "Europe/Berlin",
            "Rome" => "Europe/Rome",
            "Stockholm" => "Europe/Stockholm",
            "Vienna" => "Europe/Vienna",
            "West Central Africa" => "Africa/Algiers",
            "Bucharest" => "Europe/Bucharest",
            "Cairo" => "Africa/Cairo",
            "Helsinki" => "Europe/Helsinki",
            "Kyiv" => "Europe/Kiev",
            "Riga" => "Europe/Riga",
            "Sofia" => "Europe/Sofia",
            "Tallinn" => "Europe/Tallinn",
            "Vilnius" => "Europe/Vilnius",
            "Athens" => "Europe/Athens",
            "Istanbul" => "Europe/Istanbul",
            "Minsk" => "Europe/Minsk",
            "Jerusalem" => "Asia/Jerusalem",
            "Harare" => "Africa/Harare",
            "Pretoria" => "Africa/Johannesburg",
            "Kaliningrad" => "Europe/Kaliningrad",
            "Moscow" => "Europe/Moscow",
            "St. Petersburg" => "Europe/Moscow",
            "Volgograd" => "Europe/Volgograd",
            "Samara" => "Europe/Samara",
            "Kuwait" => "Asia/Kuwait",
            "Riyadh" => "Asia/Riyadh",
            "Nairobi" => "Africa/Nairobi",
            "Baghdad" => "Asia/Baghdad",
            "Tehran" => "Asia/Tehran",
            "Abu Dhabi" => "Asia/Muscat",
            "Muscat" => "Asia/Muscat",
            "Baku" => "Asia/Baku",
            "Tbilisi" => "Asia/Tbilisi",
            "Yerevan" => "Asia/Yerevan",
            "Kabul" => "Asia/Kabul",
            "Ekaterinburg" => "Asia/Yekaterinburg",
            "Islamabad" => "Asia/Karachi",
            "Karachi" => "Asia/Karachi",
            "Tashkent" => "Asia/Tashkent",
            "Chennai" => "Asia/Kolkata",
            "Kolkata" => "Asia/Kolkata",
            "Mumbai" => "Asia/Kolkata",
            "New Delhi" => "Asia/Kolkata",
            "Kathmandu" => "Asia/Kathmandu",
            "Astana" => "Asia/Dhaka",
            "Dhaka" => "Asia/Dhaka",
            "Sri Jayawardenepura" => "Asia/Colombo",
            "Almaty" => "Asia/Almaty",
            "Novosibirsk" => "Asia/Novosibirsk",
            "Rangoon" => "Asia/Rangoon",
            "Bangkok" => "Asia/Bangkok",
            "Hanoi" => "Asia/Bangkok",
            "Jakarta" => "Asia/Jakarta",
            "Krasnoyarsk" => "Asia/Krasnoyarsk",
            "Beijing" => "Asia/Shanghai",
            "Chongqing" => "Asia/Chongqing",
            "Hong Kong" => "Asia/Hong_Kong",
            "Urumqi" => "Asia/Urumqi",
            "Kuala Lumpur" => "Asia/Kuala_Lumpur",
            "Singapore" => "Asia/Singapore",
            "Taipei" => "Asia/Taipei",
            "Perth" => "Australia/Perth",
            "Irkutsk" => "Asia/Irkutsk",
            "Ulaanbaatar" => "Asia/Ulaanbaatar",
            "Seoul" => "Asia/Seoul",
            "Osaka" => "Asia/Tokyo",
            "Sapporo" => "Asia/Tokyo",
            "Tokyo" => "Asia/Tokyo",
            "Yakutsk" => "Asia/Yakutsk",
            "Darwin" => "Australia/Darwin",
            "Adelaide" => "Australia/Adelaide",
            "Canberra" => "Australia/Melbourne",
            "Melbourne" => "Australia/Melbourne",
            "Sydney" => "Australia/Sydney",
            "Brisbane" => "Australia/Brisbane",
            "Hobart" => "Australia/Hobart",
            "Vladivostok" => "Asia/Vladivostok",
            "Guam" => "Pacific/Guam",
            "Port Moresby" => "Pacific/Port_Moresby",
            "Magadan" => "Asia/Magadan",
            "Srednekolymsk" => "Asia/Srednekolymsk",
            "Solomon Is." => "Pacific/Guadalcanal",
            "New Caledonia" => "Pacific/Noumea",
            "Fiji" => "Pacific/Fiji",
            "Kamchatka" => "Asia/Kamchatka",
            "Marshall Is." => "Pacific/Majuro",
            "Auckland" => "Pacific/Auckland",
            "Wellington" => "Pacific/Auckland",
            "Nuku'alofa" => "Pacific/Tongatapu",
            "Tokelau Is." => "Pacific/Fakaofo",
            "Chatham Is." => "Pacific/Chatham",
            "Samoa" => "Pacific/Apia"
        ];

        return (array_key_exists($tz, $timezones) ? $timezones[$tz] : null);
    }
}
?>
