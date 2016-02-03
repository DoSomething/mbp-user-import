<?php
/**
 * MBP_userCSVfileTools - functionality related to the Message Broker
 * producer mbp-user-import to coordinate CSV files.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\MB_Toolbox\MB_Configuration;
use \Exception;
use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Email\FromAddress;
use Ddeboer\Imap\Server;
use DoSomething\StatHat\Client as StatHat;

/**
 *
 */
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
   */
  public function __construct() {

    $this->mbConfig = MB_Configuration::getInstance();

    $this->settings = $this->mbConfig->getProperty('generalSettings');
    $this->statHat = $this->mbConfig->getProperty('statHat');
    $this->gmail = $this->mbConfig->getProperty('gmail');
  }

  /*
   * Gather CSV file from gmail
   *
   * @param string $source
   *   The target source. Currentl supports "niche" and "att-ichannel"
   */
  public function gatherIMAP($source) {

    echo '------- mbp-user-import_manageData->gatherIMAP() ** ' . $source . ' ** START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    $targetSourceDetails = [
      'niche' => [
        'from' => 'no-reply@batchrobot.com',
        'subject' => 'Niche-DoSomething Daily Co-regs',
      ],
      'afterSchool' => [
        'from' => '',
        'subject' => '',
      ],
    ];

    $existingFiles = scandir(__DIR__ . '/../data/' . $source);
    unset($existingFiles[0]);
    unset($existingFiles[1]);

    $mailbox = $this->gmail->getMailbox('INBOX');
    $mailboxProcessed = $this->gmail->getMailbox('user-import/niche-processed');

    $search = new SearchExpression();
    $search->addCondition(new FromAddress($targetSourceDetails[$source]['from']));
    $messages = $mailbox->getMessages($search);

    foreach ($messages as $message) {
      if ($message->getSubject() == $targetSourceDetails[$source]['subject']) {

        $attachments = $message->getAttachments();
        foreach ($attachments as $attachment) {

          $filename = $attachment->getFilename();
          if (file_exists(__DIR__ . '/../data/' . $source . '/' . $filename) == FALSE) {

            foreach ($existingFiles as $existingCount => $existingFile) {
              if (strpos($existingFile, $filename) !== FALSE) {
                break 2;
              }
            }
            echo $attachment->getFilename() . ' retrieved from gmail account.', PHP_EOL;
            file_put_contents(__DIR__ . '/../data/' . $source . '/' . $filename, $attachment->getDecodedContent());
            $message->move($mailboxProcessed);

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
