<?php
/**
 * mbp-user-import_manageData.php
 *
 * Collect user import data from gmail account as CSV attachment files. Save
 * the file in data directory to be detercted by import script for processing.
 */

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';
require_once __DIR__ . '/MBP_userCSVfileTools.class.inc';

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'use_stathat_tracking' => getenv('USE_STAT_TRACKING'),
  'gmail_machine_username' => getenv("GMAIL_MACHINE_USERNAME"),
  'gmail_machine_password' => getenv("GMAIL_MACHINE_PASSWORD"),
);


echo '------- mbp-user-import_manageData START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off
$allowedSources = array(
  'niche',
  'hercampus',
  'att-ichannel',
  'teenlife'
);

$source = NULL;
if (isset($_GET['source'])) {
  $source = $_GET['source'];
}
elseif (isset($argv[1])) {
  $source = $argv[1];
}

if ($source != NULL)  {

  if (in_array($source, $allowedSources)) {
    $mbpUserCSVfileTools = new MBP_userCSVfileTools($settings);
    $status = $mbpUserCSVfileTools->gatherIMAP($source);
  }
  else {
    echo '', PHP_EOL;
  }

}
else {
  echo '"source" parameter not defined.', PHP_EOL;
}

echo '------- mbp-user-import_manageData  END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
