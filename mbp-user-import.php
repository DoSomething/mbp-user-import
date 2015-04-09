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

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';
require __DIR__ . '/mb-config.inc';

require __DIR__ . '/MBP_userImport.class.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$config = array(
  'exchange' => array(
    'name' => getenv("MB_USER_IMPORT_EXCHANGE"),
    'type' => getenv("MB_USER_IMPORT_EXCHANGE_TYPE"),
    'passive' => getenv("MB_USER_IMPORT_EXCHANGE_PASSIVE"),
    'durable' => getenv("MB_USER_IMPORT_EXCHANGE_DURABLE"),
    'auto_delete' => getenv("MB_USER_IMPORT_EXCHANGE_AUTO_DELETE"),
  ),
  'queue' => array(
    array(
      'name' => getenv("MB_USER_IMPORT_QUEUE"),
      'passive' => getenv("MB_USER_IMPORT_QUEUE_PASSIVE"),
      'durable' => getenv("MB_USER_IMPORT_QUEUE_DURABLE"),
      'exclusive' => getenv("MB_USER_IMPORT_QUEUE_EXCLUSIVE"),
      'auto_delete' => getenv("MB_USER_IMPORT_QUEUE_AUTO_DELETE"),
      'bindingKey' => getenv("MB_USER_IMPORT_QUEUE_BINDING_KEY"),
    ),
  ),
  'routingKey' => getenv("MB_USER_IMPORT_ROUTING_KEY"),
);
$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY")
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
  'att-ichannel'
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
