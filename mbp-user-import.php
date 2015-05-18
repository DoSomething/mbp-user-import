<?php
/**
 * mbp-user-import.php
 *
 * Import user data from CSV file supplied by niche.com of users interested in
 * DoSomething scholarships. Entries in the userImportQueue will be consumed by
 * mbc-user-import consumer. User creation in the Drupal website as well as the
 * userAPI, Mailchimp and Mandrill transactional signup email message will be
 * triggered by each entry.
 */

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';
require_once __DIR__ . '/MBP_userImport.class.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'use_stathat_tracking' => getenv('USE_STAT_TRACKING'),
);

$config = array();
$source = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$userImportExchange = $mb_config->exchangeSettings('directUserImport');

$config['exchange'] = array(
  'name' => $userImportExchange->name,
  'type' => $userImportExchange->type,
  'passive' => $userImportExchange->passive,
  'durable' => $userImportExchange->durable,
  'auto_delete' => $userImportExchange->auto_delete,
);
$config['queue'][] = array(
  'name' => $userImportExchange->queues->userImportQueue->name,
  'passive' => $userImportExchange->queues->userImportQueue->passive,
  'durable' =>  $userImportExchange->queues->userImportQueue->durable,
  'exclusive' =>  $userImportExchange->queues->userImportQueue->exclusive,
  'auto_delete' =>  $userImportExchange->queues->userImportQueue->auto_delete,
  'bindingKey' => $userImportExchange->queues->userImportQueue->binding_key,
);


echo '------- mbp-user-import START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

$targetFile = 'nextFile';
if (isset($_GET['targetFile'])) {
  $targetFile = $_GET['targetFile'];
}
elseif (isset($argv[1])) {
  $targetFile = $argv[1];
}

$allowedSources = array(
  'niche',
  'hercampus',
  'att-ichannel',
  'teenlife',
);

$source = NULL;
if (isset($_GET['source'])) {
  $source = $_GET['source'];
}
elseif (isset($argv[2])) {
  $source = $argv[2];
}

if (in_array($source, $allowedSources)) {
  $mbpUserImport = new MBP_UserImport($credentials, $config, $settings);
  $mbpUserImport->produceCSVImport($targetFile, $source);
}
else {
  echo 'ERROR - invalid source.', PHP_EOL;
}

echo '------- mbp-user-import END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
