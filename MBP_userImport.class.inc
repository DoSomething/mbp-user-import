<?php

use DoSomething\MBStatTracker\StatHat;

/**
 * MBP_UserImport class - functionality related to the Message Broker
 * producer mbp-user-import.
 */
class MBP_UserImport
{
  
  /**
   * Message Broker object that details the connection to RabbitMQ.
   *
   * @var object
   */
  private $messageBroker;

  /**
   * Collection of configuration settings.
   *
   * @var array
   */
  private $config;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $settings;

  /**
   * Collection of secret connection settings.
   *
   * @var array
   */
  private $credentials;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $statHat;

  /**
   * Track the number of existing email and mobile accounts
   *
   * @var array
   */
  private $existingStatus;

  /**
   * Constructor for MBC_UserEvent
   *
   * @param array $credentials
   *   Secret settings from mb-secure-config.inc
   *
   * @param array $config
   *   Configuration settings from mb-config.inc
   */
  public function __construct($credentials, $config, $settings) {

    $this->credentials = $credentials;
    $this->config = $config;
    $this->settings = $settings;

    // Setup RabbitMQ connection
    $this->messageBroker = new MessageBroker($credentials, $config);

    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbp-user-import:');
    $this->statHat->setIsProduction(FALSE);

    $this->existingStatus['email'] = 0;
    $this->existingStatus['mobile'] = 0;
  }

  /*
   * Produce entries in the MB_USER_IMPORT_QUEUE
   *
   * @param string $targetCSVFile
   *   The file name of the CSV file to import
   */
  public function produceCSVImport($targetCSVFile) {

    echo '------- mbp-user-import->produceCSVImport() START: ' . date('D M j G:i:s T Y') . ' -------', "\n";

    $skipped = 0;
    $signupKeys = array();

    $targetCSVFile = __DIR__ . '/' . $targetCSVFile;
    $signups = file($targetCSVFile);

    // Was there a file found
    if ($signups != FALSE) {
      foreach ($signups as $signupCount => $signup) {

        // Check that the coloumn assignment are as expected
        if ($signupCount == 0 && $signup != "\"first_name\",\"last_name\",\"email\",\"address1\",\"address2\",\"city\",\"state\",\"zip\",\"phone\",\"hs_gradyear\",\"birthdate\",\"race\",\"religion\",\"hs_name\",\"college_name\",\"major_name\",\"degree_type\",\"sat_math\",\"sat_verbal\",\"sat_writing\",\"act_math\",\"act_english\",\"gpa\",\"role\"\r\n") {
          echo 'Import columns are not as expected, halting import.', "\r";
          exit;
        }

        $signup = str_replace('"', '',  $signup);
        $signup = str_replace("\r\n", '',  $signup);

        if ($signupCount == 0) {
           $signupKeys = explode(',', $signup);
        }
        else {
          $signupData = explode(',', $signup);
          
          $data = array();
          $data = array(
            'subscribed' => 1,
            'activity_timestamp' => time(),
            'application_id' => 100, // Import
          );
          foreach ($signupKeys as $signupIndex => $signupKey) {
            if ($signupData[$signupIndex] != '') {
              $data[$signupKey] = $signupData[$signupIndex];
            }
          }

          // Required
          if (isset($data['email']) && $data['email'] != '') {

            // Check for existing email and mobile accounts
            $this->checkExistingAccount($data);

            $payload = json_encode($data);
            $status = $this->messageBroker->publishMessage($payload);

            $this->statHat->addStatName('produceCSVImport');
            $this->statHat->reportCount($signupCount);
          }
          else {
            $skipped++;
          }
        }
      }
    }
    else {
      trigger_error('Invalid file ' . $targetCSVFile, E_USER_WARNING);
      return FALSE;
    }

    echo $this->existingStatus['email'] . ' already exsist in Mailchimp. ' . $this->existingStatus['mobile'] . ' already esist in Mobile Commons.', "\n";
    echo $signupCount . ' email addresses imported.' . $skipped . ' skipped.', "\n";
    echo '------- mbp-user-import->produceCSVImport() END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
  }

  /*
   * Check for the existance of email (Mailchimp) and mobile (Mobile Commons)
   * accounts.
   *
   * @param array $data
   *   Information about a user to be imported into the DoSomething systems.
   */
  private function checkExistingAccount($data) {

    // http://apidocs.mailchimp.com/api/2.0/lists/member-info.php
    $MailChimp = new \Drewm\MailChimp($this->settings['mailchimp_apikey']);
    $mailchimpStatus = $MailChimp->call("/lists/member-info", array(
      'id' => $this->settings['mailchimp_list_id'],
      'emails' => array(
        array(
          'email' => $data['email']
        ),
      )
    ));
    if ($mailchimpStatus) {
      $this->existingStatus['email']++;
    }

    // https://mobilecommons.zendesk.com/hc/en-us/articles/202052534-REST-API#ProfileSummary
    $config = array(
      'username' => $this->settings['mobile_commons_user'],
      'password' => $this->settings['mobile_commons_password'],
    );
    $MobileCommons = new MobileCommons($config);
    $mobilecommonsStatus = $MobileCommons->profile(array('phone_number' => $data['phone']));
    if ($mobilecommonsStatus) {
      $this->existingStatus['mobile']++;
    }
  }

}
