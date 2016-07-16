<?php
/**
 * MBP_userCSVfileTools - functionality related to the Message Broker
 * producer mbp-user-import to coordinate gathering of user data from
 * Northstar (DoSomething User API).
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use \Exception;

/**
 *
 */
class MBP_NorthstarTools
{

  /**
   * General settings not related to a specific service.
   *
   * @var array
   */
    private $settings;

  /**
   * Logging script activity.
   *
   * @var object
   */
    private $statHat;

  /**
   * Constructor for MBP_NorthstarTools. Load configuration settings to be used
   * throughout the class.
   */
    public function __construct()
    {

        $this->mbConfig = MB_Configuration::getInstance();

        $this->settings = $this->mbConfig->getProperty('generalSettings');
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

        echo '------- MBP_NorthstarTools->MobileUsers() START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

        $mobileSignups[] = [
            'mobile' => '',
        ];

        return $mobileSignups;
    }
}
