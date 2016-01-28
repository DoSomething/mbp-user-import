<?php
/**
 * MBP_UserImport - class to manage importing user data via CSV files to the
 * Message Broker system.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseProducer;
use \Exception;

/**
 * MBP_UserImport class - functionality related to the Message Broker
 * producer mbp-user-import.
 */
class MBP_UserImport_Producer extends MB_Toolbox_BaseProducer
{

  /**
   * The number of sets of user data that were processed.
   *
   * @var integer
   */
  private $imported;

  /**
   * The number of sets of user data that resulted in users being imported.
   *
   * @var integer
   */
  private $skipped;

  /**
   * Message Broker Logging object that details the connection to RabbitMQ for logging messages.
   *
   * @var object
   */
  private $messageBrokerLogging;

  /**
   * Constructor for MBC_UserImport. Load settings to be used by instance of class. Settings
   * based on Singleton configuration values defined in .config.in file.
   */
  public function __construct() {

    parent:: __construct();
    $this->mbConfig = MB_Configuration::getInstance();
    $this->messageBrokerLogging = $this->mbConfig->getProperty('messageBrokerLogging');
    
    $this->imported = 0;
    $this->skipped = 0;
  }

  /*
   * Produce entries in the MB_USER_IMPORT_QUEUE
   *
   * @param string $targetCSVFile
   *   The file name of the CSV file to import
   * @param string $source
   *   The source of the import data
   */
  public function produceCSVImport($targetCSVFile, $source = NULL) {

    echo '------- mbp-user-import->produceCSVImport() ' . $source . ' START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
    
    // Create instance of source class to use values specific to the source type.
    $allowedSources = unserialize(ALLOWED_SOURCES);
    if (in_array($source, $allowedSources)) {
      $sourceClassName = __NAMESPACE__ . '\MBP_UserImport_Source_' . $source;
      $this->source = new $sourceClassName();
    }
    else {
      throw new Exception('Invalid source value.');
    }

    if ($targetCSVFile == 'nextFile') {
      $targetCSVFile = $this->findNextTargetFile($source);
      $targetFilePaths = explode('/', $targetCSVFile);
      $targetCSVFileName = $targetFilePaths[count($targetFilePaths) - 1];
    }
    else {
      $targetCSVFile = __DIR__ . '/../data/' . $source . '/' . $targetCSVFile;
      $targetCSVFileName = $targetCSVFile;
    }

    // Is there a file found?
    if ($targetCSVFile == false || !file_exists($targetCSVFile)) {
      throw new Exception($targetCSVFile . ' not found.');
    }

    $signups = file($targetCSVFile);

    // Support files missing line breaks "\n" (Windoz and old OSX). Explode CSV data by line breaks to
    // define each user data row.
    if (count($signups) == 1 && substr_count($signups[0], "\r") > 0) {
      $signups = explode("\r", $signups[0]);
    }

    // Process file contents by row
    $totalSignups = count($signups) - 1;
    for ($signupCount = 1; $signupCount <= $totalSignups; $signupCount++) {

      $signup = $signups[$signupCount];
      $data = $this->source->process($signup);

      $data['subscribed'] = 1;
      $data['activity_timestamp'] =  time();
      $data['application_id'] = 100; // Import
      $data['source'] = $source;
      $data['source_file'] = $targetCSVFileName;

      // email Required, create message for user row
      if (isset($data['email']) && $data['email'] != '') {

        $payload = parent::generatePayload($data);
        $payload = parent::produceMessage($payload, 'userImport');
        
        $this->imported++;
      }
      elseif ($signupCount < count($signups)) {
        $this->skipped++;
      }
    }

    // Log activity
    $this->logging($source, $targetCSVFileName);

    // Archive file to prevent processing again and to backup to box.com
    $this->archiveCSV($targetCSVFile);

    echo $this->imported . ' email addresses imported.' . $this->skipped . ' skipped.', PHP_EOL;
    echo '------- mbp-user-import->produceCSVImport() ' . $source . ' END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
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
  private function logging($source, $targetCSVFile) {

    $message = [];
    $message['log-type'] = 'file-import';
    $message['log-timestamp'] = time();
    $message['signup-count'] = $this->imported;
    $message['skipped'] = $this->skipped;
    $message['source'] = $source;
    $message['target-CSV-file'] = $targetCSVFile;
    $message = json_encode($message);
    $this->messageBrokerLogging->publish($message, 'loggingGateway');
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
      echo '-> mbp-user-import->archiveCSV(): ' . $targetCSVFile . ' archived.', PHP_EOL;
        // @todo: Move file to box.com
    }
    else {
      throw new Exception('Failed to archive mbp-user-import->archiveCSV(): ' . $targetCSVFile . '. The file name needs to change to prevent further re-processing of the file on the next run of the script.');
    }

  }

}
