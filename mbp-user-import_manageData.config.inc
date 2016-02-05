<?php
/**
 * Message Broker configuration settings for mbp-user-import
 */
 
use DoSomething\MB_Toolbox\MB_Configuration;
use Ddeboer\Imap\Server;
use DoSomething\StatHat\Client as StatHat;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

define("ALLOWED_SOURCES", serialize([
  'Niche',
  'afterSchool'
]));

$mbConfig = MB_Configuration::getInstance();

$emailServer = new Server('imap.gmail.com');

$mbConfig->setProperty('gmail', $emailServer->authenticate(
  getenv("GMAIL_MACHINE_USERNAME"),
  getenv("GMAIL_MACHINE_PASSWORD")
));
$mbConfig->setProperty('statHat', new StatHat([
  'ez_key' => getenv("STATHAT_EZKEY"),
  'debug' => getenv("DISABLE_STAT_TRACKING")
]));
