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
class MBP_UserImport_CSVfileTools
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
    public function __construct()
    {

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
    public function gatherIMAP($source)
    {

        echo '------- mbp-user-import_manageData->gatherIMAP() ** ' . $source . ' ** START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

        $targetSourceDetails = [
        'Niche' => [
        'from' => 'delivery@yourlead.info',
        'subject' => 'Niche-DoSomething Daily Co-regs',
        ],
        'AfterSchool' => [
        'from' => 'updates@afterschoolapp.com',
        'subject' => 'AfterSchool-DoSomething Daily Co-regs',
        ],
        ];

        $existingFiles = scandir(__DIR__ . '/../data/' . $source);
        unset($existingFiles[0]);
        unset($existingFiles[1]);

        $mailbox = $this->gmail->getMailbox('INBOX');
        $mailboxProcessed = $this->gmail->getMailbox('user-import/' . strtolower($source) . '-processed');

        $search = new SearchExpression();
        $search->addCondition(new FromAddress($targetSourceDetails[$source]['from']));
        $messages = $mailbox->getMessages($search);

        foreach ($messages as $message) {
            if ($message->getSubject() == $targetSourceDetails[$source]['subject']) {
                $attachments = $message->getAttachments();
                foreach ($attachments as $attachment) {
                    $filename = $attachment->getFilename();
                    if (file_exists(__DIR__ . '/../data/' . $source . '/' . $filename) == false) {
                        foreach ($existingFiles as $existingCount => $existingFile) {
                            if (strpos($existingFile, $filename) !== false) {
                                break 2;
                            }
                        }
                        echo $attachment->getFilename() . ' retrieved from gmail account.', PHP_EOL;
                        $this->statHat->ezCount('mbp-user-import:  MBP_UserCSVfileTools: gatherIMAP attachment: ' . $source, 1);
                        file_put_contents(__DIR__ . '/../data/' . $source . '/' . $filename, $attachment->getDecodedContent());
                        $message->move($mailboxProcessed);
                    }
                }
            }
        }

        echo '------- mbp-user-import_manageData->gatherIMAP() ** ' . $source . ' ** END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    }
}
