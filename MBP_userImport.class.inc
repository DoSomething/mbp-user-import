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
        
        // Check that the coloumn assignment are as expected
        if ($signupCount == 0 && $signup != "\"first_name\",\"last_name\",\"email\",\"address1\",\"address2\",\"city\",\"state\",\"zip\",\"phone\",\"hs_gradyear\",\"birthdate\",\"race\",\"religion\",\"hs_name\",\"college_name\",\"major_name\",\"degree_type\",\"sat_math\",\"sat_verbal\",\"sat_writing\",\"act_math\",\"act_english\",\"gpa\",\"role\"\r\n") {
          echo 'Import columns are not as expected, halting import.', "\r";
          exit;
        }
        elseif ($signupCount > 0) {
          $signupData = explode(',', $signup);

          // Required
          if ($signupData[2] != '' &&
              $signupData[0] != '' &&
              $signupData[10] != '') {

            $data = array();
            $data = array(
              'email' => str_replace('"', '', $signupData[2]),
              'first_name' => str_replace('"', '', $signupData[0]),
              'subscribed' => 1,
              'activity_timestamp' => time(),
              'application_id' => 100, // Import
            );

            // Last name
            if (isset($signupData[1]) && $signupData[1] != '') {
             $data['last_name'] = str_replace('"', '', $signupData[1]);
            }
            // Address 1
            if (isset($signupData[3]) && $signupData[3] != '') {
             $data['address_1'] = str_replace('"', '', $signupData[3]);
            }
            // Address 2
            if (isset($signupData[4]) && $signupData[4] != '') {
             $data['address_2'] = str_replace('"', '', $signupData[4]);
            }
            // City
            if (isset($signupData[5]) && $signupData[5] != '') {
             $data['city'] = str_replace('"', '', $signupData[5]);
            }
            // State
            if (isset($signupData[6]) && $signupData[6] != '') {
             $data['state'] = str_replace('"', '', $signupData[6]);
            }
            // ZIP
            if (isset($signupData[7]) && $signupData[7] != '') {
             $data['zip'] = str_replace('"', '', $signupData[7]);
            }
            // phone
            if (isset($signupData[8]) && $signupData[8] != '') {
             $data['phone'] = str_replace('"', '', $signupData[8]);
            }
            // High School Grad year
            if (isset($signupData[9]) && $signupData[9] != '') {
             $data['high_school_grad_year'] = str_replace('"', '', $signupData[9]);
            }
            // Birthdate
            if (isset($signupData[10]) && $signupData[10] != '' && $signupData[10] != 0) {
             $data['high_school_grad_year'] = str_replace('"', '', $signupData[9]);
            }
            // race
            if (isset($signupData[11]) && $signupData[11] != '' && $signupData[11] != '""') {
             $data['race'] = str_replace('"', '', $signupData[11]);
            }
            // religion
            if (isset($signupData[12]) && $signupData[12] != '' && $signupData[12] != '""') {
             $data['religion'] = str_replace('"', '', $signupData[12]);
            }
            // hs_name
            if (isset($signupData[13]) && $signupData[13] != '' && $signupData[13] != '""') {
             $data['high_school'] = str_replace("\r", '', $signupData[13]);
            }
            // college_name
            if (isset($signupData[14]) && $signupData[14] != '' && $signupData[14] != '""') {
             $data['college'] = $signupData[14];
            }
            // major
            if (isset($signupData[15]) && $signupData[15] != '' && $signupData[15] != '""') {
             $data['major_name'] = str_replace('"', '', $signupData[15]);
            }
            // degree_type
            if (isset($signupData[16]) && $signupData[16] != '' && $signupData[16] != '""') {
             $data['degree_type'] = str_replace('"', '', $signupData[16]);
            }
            // satmath
            if (isset($signupData[17]) && $signupData[17] != '' && $signupData[17] != '""') {
             $data['SAT_math'] = $signupData[17];
            }
            // satverbal
            if (isset($signupData[18]) && $signupData[18] != '' && $signupData[18] != '""') {
             $data['SAT_verbal'] = $signupData[18];
            }
            // satwriting,
            if (isset($signupData[19]) && $signupData[19] != '' && $signupData[19] != '""') {
             $data['SAT_writting'] = $signupData[19];
            }
            // actmath
            if (isset($signupData[20]) && $signupData[20] != '' && $signupData[20] != '""') {
             $data['ACT_math'] = $signupData[20];
            }
            // actenglish
            if (isset($signupData[21]) && $signupData[21] != '' && $signupData[20] != '""') {
             $data['ACT_engligh'] = $signupData[21];
            }
            // GPA
            if (isset($signupData[22]) && $signupData[22] != '' && $signupData[20] != '""') {
             $data['gpa'] = $signupData[22];
            }
            // role
            if (isset($signupData[23]) && $signupData[23] != '' && $signupData[23] != "\r\n") {
             $data['role'] = str_replace("\r\n", '', str_replace('"', '', $signupData[23]));
            }
            
            
$bla = FALSE;
if ($bla) {
  $bla = TRUE;
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
