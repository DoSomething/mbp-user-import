<?php
/**
 * Service Class to provide properties and methods specific to users created with the
 * Mobile Application - IOS.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;
use \Exception;

/*
 * Properties and methods related to the users create in Mobile Application - IOS.
 *
 * User data is gathered from the Northstar API /users endpoint.
 */
class MBP_UserImport_Source_MobileappIos extends MBP_UserImport_BaseSource
{

    /**
     *
     */
    const USER_LANGUAGE = 'en';

    /**
     *
     */
    const USER_COUNTRY = 'US';

    /**
     *
     */
    const MAILCHIMP_LIST_ID = 'f2fab1dfd4';

    /**
     * Logic to determine if data from Mobile Application - IOS user data can be
     * processed.
     *
     * @param array $data
     *   Values being processed from Northstar API GET /users endpoint call.
     */
    public function canProcess($data)
    {

        if (empty($data['email'])) {
            echo '- canProcess(), email not set.', PHP_EOL;
            return false;
        }
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            echo '- canProcess(), failed FILTER_VALIDATE_EMAIL: ' . $data['email'], PHP_EOL;
            return false;
        }

        return true;
    }

    /**
     * Assign columns specific to the Mobile Application IOS common columns expected
     * by the consumer.
     *
     * @param array $data
     *   Values collected from call to Northstar API /users GET endpoint for assignment to
     *   expected indexes in the consumer.
     */
    public function setter(&$data)
    {

        $message = [];

        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) !== false) {
            $message['email'] = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        } else {
            $message['email'] = $data['email'];
        }
        // Default to general Do Something Memebers list. Logic in mbc-registration-email will rest list ID
        // if user is deemed international.
        $message['mailchimp_list_id'] = self::MAILCHIMP_LIST_ID;

        $message['subscribed'] = 1;
        $message['activity'] = 'user_import';
        $message['activity_timestamp'] = time();
        $message['application_id'] = 'MA-IOS';
        $message['transactions'] = 0;
        $message['source'] = $data['source'];

        if (isset($data['first_name'])) {
            // Validate all characters in first name are supported as following UTF-8 encoding
            if (mb_check_encoding($data['first_name'], 'UTF-8')) {
                $message['first_name'] = ucwords($data['first_name']);
            } else {
                $message['first_name'] = ucwords(mb_convert_encoding($data['first_name'], "UTF-8"));
            }
        }

        if (isset($data['mobile'])) {
            // Validate phone number based on the North American Numbering Plan
            // https://en.wikipedia.org/wiki/North_American_Numbering_Plan
            $regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";
            if (preg_match ($regex, $data['mobile'])) {
                $message['mobile'] = $data['mobile'];
            }
        }

        $message['id'] = $data['northstar_id'];
        if (isset($data['drupal_id'])) {
            $message['drupal_uid'] = $data['drupal_id'];
        }
        if (isset($data['birthdate'])) {
            $message['birthdate'] = $data['birthdate'];
        }

        if (!empty($data['user_country'])) {
            $message['user_country'] = $data['user_country'];
        } else {
            $message['user_country'] = self::USER_COUNTRY;
        }
        if (!empty($data['user_language'])) {
            $message['user_language'] = $data['user_language'];
        } else {
            $message['user_language'] = self::USER_LANGUAGE;
        }

        // Wipe data values with formatted $message values
        $data = $message;
    }

    /**
     * Publish message to userImport queue.
     *
     * @param array $message
     *   User data from Northstar API GET /users.
     *
     * @return none
     */
    public function process($message)
    {

        $payload = json_encode($message);
        $this->messageBroker->publish($payload, 'userImport');
    }

    /**
     * NOT USED
     * Supported key / columns in CSV file from source.
     */
    public function setKeys()
    {
    }
}
