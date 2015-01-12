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

require __DIR__ . '/MBP_userCSVfileTools.class.inc';

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY")
);


echo '------- mbp-user-import_manageData START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off
// Gather CSV from possible sources
$mbpUserCSVfileTools = new MBP_userCSVfileTools($settings);
$status = $mbpUserCSVfileTools->gatherIMAP();

echo '------- mbp-user-import_manageData  END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
