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
    $existingStatus = array(
      'email' => 0,
      'mobile' => 0,
      'drupal' => 0
    );

    $targetCSVFileName = $targetCSVFile;
    $targetCSVFile = __DIR__ . '/data/' . $targetCSVFile;
    // Was there a file found
    if (file_exists($targetCSVFile)) {
      $signups = file($targetCSVFile);
      foreach ($signups as $signupCount => $signup) {

        // Check that the coloumn assignment are as expected
        if ($signupCount == 0 && $signup != "\"first_name\",\"last_name\",\"email\",\"address1\",\"address2\",\"city\",\"state\",\"zip\",\"phone\",\"hs_gradyear\",\"birthdate\",\"race\",\"religion\",\"hs_name\",\"college_name\",\"major_name\",\"degree_type\",\"sat_math\",\"sat_verbal\",\"sat_writing\",\"act_math\",\"act_english\",\"gpa\",\"role\"\r\n") {
          echo 'Import columns are not as expected, halting import.', "\r";
          exit;
        }

        // Clean up first
        $signup = str_replace('"', '',  $signup);
        $signup = str_replace("\r\n", '',  $signup);

        // The first row (zero index) defines the column names
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
            if (isset($signupData[$signupIndex]) && $signupData[$signupIndex] != '') {
              $data[$signupKey] = $signupData[$signupIndex];
            }
          }

          // Required
          $this->statHat->clearAddedStatNames();
          if (isset($data['email']) && $data['email'] != '') {

            $payload = json_encode($data);
            $status = $this->messageBroker->publishMessage($payload);
            $this->statHat->addStatName('produceCSVImport');
          }
          elseif ($signupCount < count($signups) - 1) {
            $skipped++;
            $this->statHat->addStatName('skippedCSVImport - invalid email');
          }
          $this->statHat->reportCount(1);
        }
      }

      // Log activity
      $this->logging($signupCount, $skipped, $targetCSVFileName);

      // Archive file to prevent processing again and to backup to box.com
      $this->archiveCSV($targetCSVFile);
    }
    else {
      trigger_error('Invalid file ' . $targetCSVFile, E_USER_WARNING);
      return FALSE;
    }

    echo $signupCount . ' email addresses imported.' . $skipped . ' skipped.', "\n";
    echo '------- mbp-user-import->produceCSVImport() END: ' . date('D M j G:i:s T Y') . ' -------', "\n";

  }

  /*
   * Produce entries in the MB_USER_IMPORT_LOGGING_QUEUE to log the total number
   * of user impoarts and skipped entries from a CSV import file.
   *
   * @param integer $signupCount
   *   Total number of entries added to the queue.
   *
   * @param integer $skipped
   *   Total number of entries skipped.
   *
   * @param string $targetCSVFile
   *   The file name of the CSV import file.
   */
  private function logging($signupCount, $skipped, $targetCSVFile) {

    $config = array(
      'exchange' => array(
        'name' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE"),
        'type' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE_TYPE"),
        'passive' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE_PASSIVE"),
        'durable' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE_DURABLE"),
        'auto_delete' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE_AUTO_DELETE"),
      ),
      'queue' => array(
        'user_import' => array(
          'name' => getenv("MB_USER_IMPORT_LOGGING_QUEUE"),
          'passive' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_PASSIVE"),
          'durable' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_DURABLE"),
          'exclusive' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_EXCLUSIVE"),
          'auto_delete' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_AUTO_DELETE"),
          'bindingKey' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_TOPIC_MB_TRANSACTIONAL_EXCHANGE_PATTERN"),
        ),
      ),
    );
    $mbUserImportLogging = new MessageBroker($this->credentials, $config);

    $importStat['log-type'] = 'file-import';
    $importStat['target-CSV-file'] = $targetCSVFile;
    $importStat['signup-count'] = $signupCount;
    $importStat['skipped'] = $skipped;
    $importStat['log-timestamp'] = time();
    $importStat['source'] = 'niche.com';
    $payload = serialize($importStat);
    $mbUserImportLogging->publishMessage($payload);

  }

  /*
   * Archive import files for long term / archive storage. In doing so rename/move
   * the CSV file so mbp-user-import can run more than once a day an not process
   * the same data file more than once.
   *
   * @param string $targetCSVFile
   *   Total number of entries added to the queue.
   */
  private function archiveCSV($targetCSVFile) {

    $processedCSVFile = $targetCSVFile . '.' . time();
    $archived = rename ($targetCSVFile, $processedCSVFile);

    if ($archived) {
      echo '-> mbp-user-import->archiveCSV(): ' . $targetCSVFile . ' archived.', "\n";

      // @todo: Move file to box.com
    }
    else {
      echo '-> ERROR: Failed to archive mbp-user-import->archiveCSV(): ' . $targetCSVFile . '.', "\n";
    }

  }

}
