<?php
/**
 * MBP_userCSVfileTools - functionality related to the Message Broker
 * producer mbp-user-import to coordinate CSV files.
 */

namespace DoSomething\MBP_UserImport;

use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Email\FromAddress;
use Ddeboer\Imap\Server;
use DoSomething\StatHat\Client as StatHat;


class MBP_UserCSVfileTools
{

  /**
   * Setting from external services - Gmail.
   *
   * @var array
   */
  private $settings;

  /**
   * Logging script activity.
   *
   * @var object
   */
  private $statHat;

  /**
   * Constructor for MBP_userCSVfileTools
   *
   * @param array $credentials
   *   Secret settings from mb-secure-config.inc
   *
   * @param array $config
   *   Configuration settings from mb-config.inc
   */
  public function __construct($settings) {

    $this->settings = $settings;

    $this->statHat = new StatHat([
      'ez_key' => $settings['stathat_ez_key'],
      'debug' => $settings['stathat_disable_tracking']
    ]);
  }

  /*
   * Gather CSV file from gmail
   *
   * @param string $source
   *   The target source. Currentl supports "niche" and "att-ichannel"
   */
  public function gatherIMAP($source) {

    echo '------- mbp-user-import_manageData->gatherIMAP() ** ' . $source . ' ** START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    $targetSourceDetails = array(
      'niche' => array(
        'from' => '	no-reply@batchrobot.com',
        'subject' => 'Niche-DoSomething Daily Co-regs',
      ),
      'hercampus' => array(
        'from' => 'chelseaevans@hercampus.com',
        'subject' => 'Comeback Clothes + Her Campus',
      ),
      'att-ichannel' => array(
        'from' => 'p1ia1c1@klph070.kcdc.att.com',
        'subject' => 'AT&T U-Verse DoSomething Fulfillment File',
      ),
      'teenlife' => array(
        'from' => 'stephanie@teenlife.com',
        'subject' => 'TeenLife + DoSomething.org Co-Marketing',
      ),
    );

    $existingFiles = scandir(__DIR__ . '/../data/' . $source);
    unset($existingFiles[0]);
    unset($existingFiles[1]);

    $search = new SearchExpression();
    $search->addCondition(new FromAddress($targetSourceDetails[$source]['from']));

    $server = new Server('imap.gmail.com');
    $connection = $server->authenticate($this->settings['gmail_machine_username'], $this->settings['gmail_machine_password']);

    $mailboxes = $connection->getMailboxes();
    foreach ($mailboxes as $mailbox) {
      if ($mailbox->getName() == 'INBOX') {

        $messages = $mailbox->getMessages($search);
        foreach ($messages as $message) {
          if ($message->getSubject() == $targetSourceDetails[$source]['subject']) {

            $attachments = $message->getAttachments();
            foreach ($attachments as $attachment) {

              if (file_exists(__DIR__ . '/../data/' . $source . '/' . $attachment->getFilename()) == FALSE) {

                foreach ($existingFiles as $existingCount => $existingFile) {
                  if (strpos($existingFile, $attachment->getFilename()) !== FALSE) {
                    break 2;
                  }
                }
                echo $attachment->getFilename() . ' retrieved from gmail account.', PHP_EOL;
                file_put_contents(__DIR__ . '/../data/' . $source . '/' . $attachment->getFilename(), $attachment->getDecodedContent());
                $this->statHat->ezCount('mbp-user-import_manageData: ' . $source, 1);
              }

            }

          }

        }

      }
    }

    echo '------- mbp-user-import_manageData->gatherIMAP() ** ' . $source . ' ** END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

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
