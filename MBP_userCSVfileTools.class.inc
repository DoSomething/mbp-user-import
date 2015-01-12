<?php

use DoSomething\MBStatTracker\StatHat;

/**
 * MBP_UserImport class - functionality related to the Message Broker
 * producer mbp-user-import.
 */
class MBP_userCSVfileTools
{

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $settings;

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
  public function __construct($settings) {

    $this->settings = $settings;

    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbp-user-import:');
    $this->statHat->setIsProduction(TRUE);
  }

  /*
   * Gather CSV file from gmail
   *
   * @param string $targetDate
   *   The date of the desired CSV data
   */
  public function gatherIMAP() {

    echo '------- mbp-user-import_manageData->gatherIMAP() START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
    
    
$bla = FALSE;
if ($bla) {
  $bla = TRUE;
}

    $server = new \Fetch\Server('imap.gmail.com', 993);
    $server->setAuthentication('machines@dosomething.org', 'ChomskyMastiffBronchial194');

    $messages = $server->getMessages();
    /** @var $message \Fetch\Message */
    foreach ($messages as $message) {
      echo "Subject: {$message->getSubject()}\nBody: {$message->getMessageBody()}\n";
    }


    echo '------- mbp-user-import_manageData->gatherIMAP() END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

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
