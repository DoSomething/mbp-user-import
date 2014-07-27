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
   * Constructor for MBC_UserEvent
   *
   * @param array $credentials
   *   Secret settings from mb-secure-config.inc
   *
   * @param array $config
   *   Configuration settings from mb-config.inc
   */
  public function __construct($credentials, $config, $settings) {

    $this->config = $config;
    $this->credentials = $credentials;

    // Setup RabbitMQ connection
    $this->messageBroker = new MessageBroker($credentials, $config);

    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbp-user-import:');
    $this->statHat->setIsProduction(TRUE);
  }

  /*
   * Produce entries in the MB_USER_IMPORT_QUEUE
   *
   * @param string $targetCSVFile
   *   The file name of the CSV file to import
   */
  public function produceCSVImport($targetCSVFile) {

    echo '------- mbp-user-import->produceCSVImport() START: ' . date('D M j G:i:s T Y') . ' -------', "\n";
    
    $targetCSVFile = __DIR__ . '/' . $targetCSVFile;
    $signups = file($targetCSVFile);

    // Was there a file found
    if ($signups != FALSE) {
      foreach ($signups as $signupCount => $signup) {
        if ($signupCount != 0) {
          $signupData = explode(',', $signup);

          // first_name, last_name, email, hsgradyear, gender, address1, address2,
          // city,state, postal_code, phone_home, gpa, dob, race, religion,
          // hs_name, college_name, degree_type, major, satmath, satverbal,
          // satwriting, actmath, actenglish
          $data = array(
            'email' => $signupData[2],
            'merge_vars' => array(
              'FNAME' => $signup[2],
              'LNAME' => $signupData[3],
            ),
            'birthdate' => strtotime($signupData[11]),
            'subscribed' => 1,
            'activity_timestamp' => time(),
            'application_id' => 10, // Import
          );
  
          $payload = serialize($data);
          $status = $this->messageBroker->publishMessage($payload);
          
          $this->statHat->addStatName('produceCSVImport');
          $this->statHat->reportCount($userCount);
        }
        else {
          $this->statHat->addStatName('produceCSVImport - ERROR: mb-user-import 0 results');
          $this->statHat->reportCount(1);
        }
      }
    }
    else {
      trigger_error('Invalid file ' . $targetCSVFile, E_USER_WARNING);
      return FALSE;
    }

    echo $signupCount . 'email addresses imported.', "\n";
    echo '------- mbp-user-import->produceCSVImport() END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
  }

}
