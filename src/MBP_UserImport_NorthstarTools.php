<?php
/**
 * MBP_UserImport_NorthstarTools - functionality related to the Message Broker
 * producer mbp-user-import to coordinate gathering of user data from
 * Northstar (DoSomething User API).
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use DoSomething\StatHat\Client as StatHat;
use \Exception;

/**
 * A collection of functionality and settings related to user imports from Northstar.
 */
class MBP_UserImport_NorthstarTools
{
    /*
     * The version of the Northstar API
     */
    const NORTHSTAR_API_VERSION = 'v1';

    /**
     * Configuration settings loaded from singleton instances.
     *
     * @var array
     */
    private $mbConfig;

    /**
     * General settings not related to a specific service.
     *
     * @var array
     */
    private $settings;

    /**
     * Configuration settings for connecting to Northstar API.
     *
     * @var array
     */
    private $northstarAPIConfig;

    /**
     * Configuration settings for connecting to Northstar API.
     *
     * @var array
     */
    private $mbToolboxcURL;

    /**
     * Logging script activity.
     *
     * @var object
     */
    private $statHat;

    /**
     * Constructor for MBP_UserImport_NorthstarTools. Load configuration settings to be used
     * throughout the class.
     */
    public function __construct()
    {

        $this->mbConfig = MB_Configuration::getInstance();

        $this->settings = $this->mbConfig->getProperty('generalSettings');
        $this->mbToolboxcURL = $this->mbConfig->getProperty('mbToolboxCURL');
        $this->northstarAPIConfig = $this->mbConfig->getProperty('northstar_config');
        $this->statHat = $this->mbConfig->getProperty('statHat');
    }

    /*
     * Gather user data from Northstar of users created from mobile application. This is a short term solution to
     * ensure mobile signup users are being added to MailChimp. Longterms a Quicksilver-API endpoint will be available
     * to call to trigger the MailChimp signup functionality mbc- ???
     *
     * @return array
     */
    public function gatherMobileUsers()
    {

        echo '------- MBP_UserImport_NorthstarTools->MobileUsers() START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

        $page = 0;
        $mobileSignups = [];
        $targetSources = [
            'mobileapp_ios',
            'mobileapp_android'
        ];

        // Gather all mobile user data which based on all source types
        foreach ($targetSources as $source) {

            do {
                $page++;
                $results = $this->getNorthstarData($source, $page);
                // $totalPages = $results[0]->meta->pagination->total_pages;
                $totalPages = 3;

                foreach ($results[0]->data as $result) {
                    $mobileSignups[] = [
                        'mobile' => $result->mobile,
                        'email' => $result->email,
                        'first_name' => $result->first_name,
                        'birthdate' => $result->birthdate,
                        'user_language' => $result->language,
                        'user_country' => $result->country,
                        'source' => $result->source
                    ];
                }

            } while($page < $totalPages);

        }

        return $mobileSignups;
    }

    /**
     * Using cURL request, gather all mobile user signups from Northstar.
     *
     * @param string $source Various types of sources of mobile user signups.
     * @param integer $page  Page through GET request results based on limit parameter in GET request.
     *
     * @return object
     */
    private function getNorthstarData($source, $page) {

        if (empty($this->northstarAPIConfig['host'])) {
            throw new Exception('MBP_UserImport_NorthstarTools->getNorthstarData() northstar_config ' .
                'missing host setting.');
        }

        // Build query based on endpoint specs:
        $northstarUrl  =  $this->northstarAPIConfig['host'];
        $northstarUrl .= '/' . self::NORTHSTAR_API_VERSION . '/users' .
            '?search[source]=' . $source .
            '&limit=100&page=' . $page;
        $results = $this->mbToolboxcURL->curlGET($northstarUrl);

        return $results;
    }
}
