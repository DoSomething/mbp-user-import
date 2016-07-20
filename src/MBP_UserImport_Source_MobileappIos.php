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
     * Supported values from returned Northstar API data.
     */
    protected function setKeys()
    {
        
        $keys = [
            '',
        ];
        
        return $keys;
    }
  
    /**
     * Logic to determine if data from Mobile Application - IOS user data can be
     * processed.
     *
     * @param array $data
     *   Values being processed from Northstar API GET /users endpoint call.
     */
    public function canProcess($data)
    {

        if (!(preg_match($regex, $data['email']))) {
            echo '** canProcess(): Invalid email address: ' .  $data['email'], PHP_EOL;
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
        $message['email'] = $data['email'];
        $message['mobile'] = $data['mobile'];
        $message['first_name'] = ucwords($data['first_name']);
        $message['subscribed'] = 1;
        $message['activity_timestamp'] = $data['activity_timestamp'];
        $message['application_id'] = 'Mobile Application - IOS';
        $message['source'] = 'user_import' . $data['source'];

        // All After School users are assumed to be from the United States.
        $message['user_country'] = self::USER_COUNTRY;
        $message['user_language'] = self::USER_LANGUAGE;

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
}
