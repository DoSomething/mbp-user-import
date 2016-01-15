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
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $statHat;

  /**
   * Constructor for MBC_UserImport. Load settings to be used by instance of class. Settings
   * based on Singleton configuration values defined in .config.in file.
   */
  public function __construct() {

    $this->mbConfig = MB_Configuration::getInstance();
    $this->messageBroker = $this->mbConfig->getProperty($targetMBconfig);
    $this->statHat = $this->mbConfig->getProperty('statHat');
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

            default:
              echo 'produceCSVImport(): Undefined source. ', PHP_EOL;
              exit;
          }

        }
        else {

          $signup = $signups[$signupCount];
          $signup = str_replace('"', '',  $signup);
          $signup = str_replace("\r\n", '',  $signup);
          $signupData = explode(',', $signup);

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
    $loggingGatewayExchange = $mbConfig->exchangeSettings('directLoggingGateway');
    $config = array(
      'exchange' => array(
        'name' => $loggingGatewayExchange->name,
        'type' => $loggingGatewayExchange->type,
        'passive' => $loggingGatewayExchange->passive,
        'durable' => $loggingGatewayExchange->durable,
        'auto_delete' => $loggingGatewayExchange->auto_delete,
      ),
      'queue' => array(
        array(
          'name' => $loggingGatewayExchange->queues->loggingGatewayQueue->name,
          'passive' => $loggingGatewayExchange->queues->loggingGatewayQueue->passive,
          'durable' =>  $loggingGatewayExchange->queues->loggingGatewayQueue->durable,
          'exclusive' =>  $loggingGatewayExchange->queues->loggingGatewayQueue->exclusive,
          'auto_delete' =>  $loggingGatewayExchange->queues->loggingGatewayQueue->auto_delete,
          'bindingKey' => $loggingGatewayExchange->queues->loggingGatewayQueue->binding_key,
        ),
      ),
    );
    $config['routingKey'] = $loggingGatewayExchange->queues->loggingGatewayQueue->routing_key;

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
