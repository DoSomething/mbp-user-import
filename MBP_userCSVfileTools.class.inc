<?php

use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Email\FromAddress;
use Ddeboer\Imap\Server;
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

    $existingFiles = scandir(__DIR__ . '/data/niche');
    unset($existingFiles[0]);
    unset($existingFiles[1]);

    $search = new SearchExpression();
    $search->addCondition(new FromAddress('mike@niche.com'));

    $server = new Server('imap.gmail.com');
    $connection = $server->authenticate('machines@dosomething.org', 'ChomskyMastiffBronchial194');

    $mailboxes = $connection->getMailboxes();
    foreach ($mailboxes as $mailbox) {
      if ($mailbox->getName() == 'INBOX') {

        $messages = $mailbox->getMessages($search);
        foreach ($messages as $message) {
          if ($message->getSubject() == 'Niche-DoSomething Daily Co-reg File') {

            $attachments = $message->getAttachments();
            foreach ($attachments as $attachment) {

              if (file_exists(__DIR__ . '/data/niche/' . $attachment->getFilename()) == FALSE) {

                foreach ($existingFiles as $existingCount => $existingFile) {
                  if (strpos($existingFile, $attachment->getFilename()) !== FALSE) {
                    break 2;
                  }
                }
                echo $attachment->getFilename() . ' retrieved from gmail account.', PHP_EOL;
                file_put_contents(__DIR__ . '/data/niche/' . $attachment->getFilename(), $attachment->getDecodedContent());
              }

            }

          }
        }

      }
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
