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

    $skipped = 0;

    $targetCSVFile = __DIR__ . '/' . $targetCSVFile;
    $signups = file($targetCSVFile);

    // Was there a file found
    if ($signups != FALSE) {
      foreach ($signups as $signupCount => $signup) {
        // Skip the first line that defines the column names
        if ($signupCount != 0) {
          $signupData = explode(',', $signup);

          // first_name, last_name, email, hsgradyear, gender, address1, address2, 0-6
          // city,state, postal_code, phone_home, gpa, dob, race, religion, 7-14
          // hs_name, college_name, degree_type, major, satmath, satverbal,  15-20
          // satwriting, actmath, actenglish  21-23

          // Required
          if ($signupData[2] != '' &&
              $signupData[0] != '' &&
              $signupData[12] != '') {

            $data = array(
              'email' => $signupData[2],
              'first_name' => $signupData[0],
              'birthdate' => strtotime($signupData[12]),
              'subscribed' => 1,
              'activity_timestamp' => time(),
              'application_id' => 10, // Import
            );

            // Last name
            if (isset($signupData[1]) && $signupData[1] != '') {
             $data['last_name'] = $signupData[1];
            }
            // High School Grad year
            if (isset($signupData[3]) && $signupData[3] != '') {
             $data['high_school_grad_year'] = $signupData[3];
            }
            // Gender
            if (isset($signupData[4]) && $signupData[4] != '') {
             $data['gender'] = $signupData[4];
            }
            // Address1
            if (isset($signupData[5]) && $signupData[5] != '') {
             $data['address1'] = $signupData[5];
            }
            // Address2
            if (isset($signupData[6]) && $signupData[6] != '') {
             $data['address12'] = $signupData[6];
            }
            // City
            if (isset($signupData[7]) && $signupData[7] != '') {
             $data['city'] = $signupData[7];
            }
            // State
            if (isset($signupData[8]) && $signupData[8] != '') {
             $data['state'] = $signupData[8];
            }
            // postal_code
            if (isset($signupData[9]) && $signupData[9] != '') {
             $data['postal_code'] = $signupData[9];
            }
            // phone
            if (isset($signupData[10]) && $signupData[10] != '') {
             $data['phone'] = $signupData[10];
            }
            // GPA
            if (isset($signupData[11]) && $signupData[11] != '') {
             $data['gpa'] = $signupData[11];
            }
            // race
            if (isset($signupData[13]) && $signupData[13] != '') {
             $data['race'] = $signupData[13];
            }
            // religion
            if (isset($signupData[14]) && $signupData[14] != '') {
             $data['religion'] = $signupData[14];
            }
            // hs_name
            if (isset($signupData[15]) && $signupData[15] != '') {
             $data['high_school'] = str_replace("\r", '', $signupData[15]);
            }
            // college_name
            if (isset($signupData[16]) && $signupData[16] != '') {
             $data['college'] = $signupData[16];
            }
            // degree_type
            if (isset($signupData[17]) && $signupData[17] != '') {
             $data['degree_type'] = $signupData[17];
            }
            // major
            if (isset($signupData[18]) && $signupData[18] != '') {
             $data['degree_type'] = $signupData[18];
            }
            // satmath
            if (isset($signupData[19]) && $signupData[19] != '') {
             $data['SAT_math'] = $signupData[19];
            }
            // satverbal
            if (isset($signupData[20]) && $signupData[20] != '') {
             $data['SAT_verbal'] = $signupData[20];
            }
            // satwriting,
            if (isset($signupData[21]) && $signupData[21] != '') {
             $data['SAT_writting'] = $signupData[21];
            }
            // actmath
            if (isset($signupData[22]) && $signupData[22] != '') {
             $data['ACT_math'] = $signupData[22];
            }
            // actenglish
            if (isset($signupData[23]) && $signupData[23] != '' && $signupData[23] != "\r\n") {
             $data['ACT_engligh'] = str_replace("\r\n", '', $signupData[23]);
            }

            $payload = serialize($data);
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

    echo $signupCount . 'email addresses imported.' . $skipped . ' skipped.', "\n";
    echo '------- mbp-user-import->produceCSVImport() END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
  }

}
