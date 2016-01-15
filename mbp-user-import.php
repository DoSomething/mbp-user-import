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
use DoSomething\MBP_UserImport\MBP_UserImport;

require_once __DIR__ . '/mbp-user-import.config.inc';

echo '------- mbp-user-import START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

$targetFile = 'nextFile';
if (isset($_GET['targetFile'])) {
  $targetFile = $_GET['targetFile'];
}
elseif (isset($argv[1])) {
  $targetFile = $argv[1];
}

$allowedSources = array(
  'niche',
);

$source = NULL;
if (isset($_GET['source'])) {
  $source = $_GET['source'];
}
elseif (isset($argv[2])) {
  $source = $argv[2];
}

if ($source != NULL)  {

  if (in_array($source, $allowedSources)) {
    $mbpUserImport = new MBP_UserImport($credentials, $config, $settings);
    $mbpUserImport->produceCSVImport($targetFile, $source);
  }
  else {
    echo 'ERROR - invalid source.', PHP_EOL;
  }

}
else {
  echo '"source" parameter not defined.', PHP_EOL;
}

echo '------- mbp-user-import END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
