<?php
/**
 * MBP_UserImport - class to manage importing user data via CSV files to the
 * Message Broker system.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;

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
    $this->messageBroker = new \MessageBroker($credentials, $config);

    $this->statHat = new StatHat([
      'ez_key' => $settings['stathat_ez_key'],
      'debug' => $settings['stathat_disable_tracking']
    ]);

  }

  /*
   * Produce entries in the MB_USER_IMPORT_QUEUE
   *
   * @param string $targetCSVFile
   *   The file name of the CSV file to import
   * @param string $source
   *   The source of the import data
   */
  public function produceCSVImport($targetCSVFile, $source) {

    echo '------- mbp-user-import->produceCSVImport() ' . $source . ' START: ' . date('j D M Y G:i:s T') . ' -------', "\n";

    $imported = 0;
    $skipped = 0;
    $signupKeys = array();
    $existingStatus = array(
      'email' => 0,
      'mobile' => 0,
      'drupal' => 0
    );

    if ($targetCSVFile == 'nextFile') {
      $targetCSVFile = $this->findNextTargetFile($source);
      $targetFilePaths = explode('/', $targetCSVFile);
      $targetCSVFileName = $targetFilePaths[count($targetFilePaths) - 1];
    }
    else {
      $targetCSVFileName = $targetCSVFile;
      $targetCSVFile = __DIR__ . '/../data/' . $source . '/' . $targetCSVFile;
    }

    // Is there a file found?
    if ($targetCSVFile != FALSE && file_exists($targetCSVFile)) {
      $signups = file($targetCSVFile);

      // Explode CSV data by line breaks
      if (count($signups) == 1 && substr_count($signups[0], "\r") > 0) {
        $signups = explode("\r", $signups[0]);
      }

      $totalSignups = count($signups) - 1;
      for ($signupCount = 0; $signupCount <= $totalSignups; $signupCount++) {

        // Check that the coloumn assignment are as expected
        if ($signupCount == 0) {

          switch ($source) {
            case 'niche':

              $signupKeys = array (
                'first_name',
                'last_name',
                'email',
                'address1',
                'address2',
                'city',
                'state',
                'zip',
                'phone',
                'hs_gradyear',
                'birthdate',
                'race',
                'religion',
                'hs_name',
                'college_name',
                'major_name',
                'degree_type',
                'sat_math',
                'sat_verbal',
                'sat_writing',
                'act_math',
                'act_english',
                'gpa',
                'role',
              );
              break;

            case 'hercampus':

              $signupKeys = array (
                // 'entry_id',
                'first_name',
                'last_name',
                'email',
                'phone',
              );
              break;

            case 'att-ichannel':

              // DTL,03/18/2015 16:33:01,kimberly Xxx,1234567890,xxx@att.com
              // ATTR,Date of Birth,12061988
              $signupKeys = array (
                'name',
                'phone',
                'email',
                'birthdate',
              );
              break;

            case 'teenlife':

              $signupKeys = array (
                'first_name',
                'last_name',
                'email',
                'zip_code',
                'mobile_number',
                'member_type',
                'graduation_year',
                'birthdate',
                'member_source',
                'conversion_date',
                'conversion_page',
              );
              break;

            default:
              echo 'produceCSVImport(): Undefined source. ', PHP_EOL;
              exit;
          }

        }
        else {

          if ($source == 'att-ichannel') {

            // Skip the last line - END OF FILE
            if (isset( $signups[$signupCount + 1])) {

              // Combine DTL and ATTR rows
              $signupsATTR = explode(',', $signups[$signupCount + 1]);
              $birthdate = substr($signupsATTR[2], 0, 2) . '/' . substr($signupsATTR[2], 2, 2) . '/' . substr($signupsATTR[2], 4, 4);
              $signup = $signups[$signupCount] . ',' . $birthdate;
              $signup = str_replace("\r\n", '',   $signup);
              $signupData = explode(',', $signup);

              // Remove column heading and creation date
              unset($signupData[0]);
              unset($signupData[1]);
              $signupData = array_values($signupData);

            }

            $signupCount++;
          }
          else {
            $signup = $signups[$signupCount];
            $signup = str_replace('"', '',  $signup);
            $signup = str_replace("\r\n", '',  $signup);
            $signupData = explode(',', $signup);
          }

          $data = array();
          $data = array(
            'subscribed' => 1,
            'activity_timestamp' => time(),
            'application_id' => 100, // Import
            'source' => $source,
            'source_file' => $targetCSVFileName
          );

          foreach ($signupKeys as $signupIndex => $signupKey) {
            if (isset($signupData[$signupIndex]) && $signupData[$signupIndex] != '') {
              $data[$signupKey] = $signupData[$signupIndex];
            }
          }

          // Required
          if (isset($data['email']) && $data['email'] != '') {
            $payload = json_encode($data);
            $status = $this->messageBroker->publishMessage($payload);
            $this->statHat->ezCount('mbp-user-import: produceCSVImport', 1);
            $imported++;
          }
          elseif ($signupCount < count($signups)) {
            $skipped++;
            $this->statHat->ezCount('mbp-user-import: skippedCSVImport - invalid email', 1);
          }
        }
      }

      // Log activity
      $this->logging($imported, $skipped, $source, $targetCSVFileName);

      // Archive file to prevent processing again and to backup to box.com
      $this->archiveCSV($targetCSVFile);
    }
    else {
      trigger_error('Invalid file ' . $targetCSVFile, E_USER_WARNING);
      echo 'ERROR - ' . $targetCSVFile . ' file not fount.', PHP_EOL;
      return FALSE;
    }

    echo $imported . ' email addresses imported.' . $skipped . ' skipped.', "\n";
    echo '------- mbp-user-import->produceCSVImport() ' . $source . ' END: ' . date('j D M Y G:i:s T') . ' -------', "\n";

  }

  /*
   * Gather next file name to process based on the define source..
   *
   * @param string $source
   *   The name of the source, used to define which directory to search for the
   *   next file available to be processed.
   *
   * @return string $targetCSVFile
   *   The name of the file to process.
   */
  public function findNextTargetFile($source) {

    $targetCSVFile = FALSE;
    $targetCSVDir = __DIR__ . '/../data/' . $source;
    $files = scandir($targetCSVDir);

    // Target next file that ends in ".csv"
    foreach ($files as $file) {
      $csvPosition = strpos($file, '.csv');
      $fileLength = strlen($file) - 4;
      if ($csvPosition == $fileLength) {
       $targetCSVFile = $targetCSVDir . '/' . $file;
        break;
      }
    }

    return $targetCSVFile;
  }

  /*
   * Produce entries in the MB_USER_IMPORT_LOGGING_QUEUE to log the total number
   * of user impoarts and skipped entries from a CSV import file.
   *
   * @param integer $signupCount
   *   Total number of entries added to the queue.
   * @param integer $skipped
   *   Total number of entries skipped.
   * @param string $source
   *   The source to log for the import data.
   * @param string $targetCSVFile
   *   The file name of the CSV import file.
   */
  private function logging($signupCount, $skipped, $source, $targetCSVFile) {

    $configSource = __DIR__ . '/../messagebroker-config/mb_config.json';
    $mbConfig = new MB_Configuration($configSource, $this->settings);
    $userImportExistingLoggingExchange = $mbConfig->exchangeSettings('directUserImportExistingLogging');

    $config = array(
      'exchange' => array(
        'name' => $userImportExistingLoggingExchange->name,
        'type' => $userImportExistingLoggingExchange->type,
        'passive' => $userImportExistingLoggingExchange->passive,
        'durable' => $userImportExistingLoggingExchange->durable,
        'auto_delete' => $userImportExistingLoggingExchange->auto_delete,
      ),
      'queue' => array(
        array(
          'name' => $userImportExistingLoggingExchange->queues->userImportExistingLoggingQueue->name,
          'passive' => $userImportExistingLoggingExchange->queues->userImportExistingLoggingQueue->passive,
          'durable' =>  $userImportExistingLoggingExchange->queues->userImportExistingLoggingQueue->durable,
          'exclusive' =>  $userImportExistingLoggingExchange->queues->userImportExistingLoggingQueue->exclusive,
          'auto_delete' =>  $userImportExistingLoggingExchange->queues->userImportExistingLoggingQueue->auto_delete,
          'bindingKey' => $userImportExistingLoggingExchange->queues->userImportExistingLoggingQueue->binding_key,
        ),
      ),
    );
    $config['routingKey'] = $userImportExistingLoggingExchange->queues->userImportExistingLoggingQueue->routing_key;
    $mbUserImportLogging = new \MessageBroker($this->credentials, $config);

    $importStat['log-type'] = 'file-import';
    $importStat['target-CSV-file'] = $targetCSVFile;
    $importStat['signup-count'] = $signupCount;
    $importStat['skipped'] = $skipped;
    $importStat['log-timestamp'] = time();
    $importStat['source'] = $source;
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
      echo '-> ERROR: Failed to archive mbp-user-import->archiveCSV(): ' . $targetCSVFile . '. The file name needs to change to prevent further re-processing of the file on the next run of the script.', "\n";
    }

  }

}
