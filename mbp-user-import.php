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
  'mailchimp_apikey' => getenv("MAILCHIMP_APIKEY"),
  'mailchimp_list_id' => getenv("MAILCHIMP_LIST_ID"),
  'mobile_commons_username' => getenv("MOBILE_COMMONS_USER"),
  'mobile_commons_password' => getenv("MOBILE_COMMONS_PASSWORD"),
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
);


echo '------- mbp-user-import START: ' . date('D M j G:i:s T Y') . ' -------', "\n";

// Kick off
// Create entries in userImportQueue based on csv.
$mbpUserImport = new MBP_UserImport($credentials, $config, $settings);

// Collect targetCSV / targetUsers parameters
if (isset($_GET['targetFile'])) {
  $mbpUserImport->produceCSVImport($_GET['targetFile']);
}
elseif (isset($argv[1])) {
  $mbpUserImport->produceCSVImport($argv[1]);
}
else {
  echo 'targetFile not supplied.', "\n";
}

echo '------- mbp-user-import END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
