<?php
/**
 * Message Broker configuration settings for mbp-user-import
 */
 
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use Ddeboer\Imap\Server;
use DoSomething\StatHat\Client as StatHat;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once CONFIG_PATH . '/mb-secure-config/' . ENVIRONMENT . '/mb-secure-config.inc';

define("ALLOWED_SOURCES", serialize([
  'Niche',
  'AfterSchool',
  'mobileapp_ios',
  'mobileapp_android'
]));

$mbConfig = MB_Configuration::getInstance();

$mbConfig->setProperty('generalSettings', []);
$mbConfig->setProperty('statHat', new StatHat([
    'ez_key' => getenv("STATHAT_EZKEY"),
    'debug' => getenv("DISABLE_STAT_TRACKING")
]));

$emailServer = new Server('imap.gmail.com');

$mbConfig->setProperty('gmail', $emailServer->authenticate(
    getenv("GMAIL_MACHINE_USERNAME"),
    getenv("GMAIL_MACHINE_PASSWORD")
));

// Northstar
$mbConfig->setProperty('northstar_config', [
    'host' => getenv("NORTHSTAR_API_HOST"),
    'id' => getenv("NORTHSTAR_API_ID"),
    'key' => getenv('NORTHSTAR_API_KEY'),
]);

$mbConfig->setProperty('mbToolboxCURL', new MB_Toolbox_cURL());
