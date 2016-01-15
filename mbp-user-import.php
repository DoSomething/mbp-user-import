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

use DoSomething\MBP_UserImport\MBP_UserImport;

date_default_timezone_set('America/New_York');
define("ALLOWED_SOURCES", serialize([
  'niche',
  'afterSchool'
]));

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mbp-user-import.config.inc';

echo '------- mbp-user-import START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

list($targetFile, $source) = gatherParameters();
if (!empty($source))  {
  $mbpUserImport = new MBP_UserImport();
  $mbpUserImport->produceCSVImport($targetFile, $source);
}
else {
  echo '"source" parameter not defined.', PHP_EOL;
}

echo '------- mbp-user-import END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

/**
 * gatherParameters() - gather parameters set when starting application.
 *
 * @return
 *   $targetFile string: the name of the file to process or "nextFile" (default).
 *   $source string: one of the supported source (co-registration) values.
 */
function gatherParameters() {

  $targetFile = 'nextFile';
  if (isset($_GET['targetFile'])) {
    $targetFile = $_GET['targetFile'];
  }
  elseif (isset($argv[1])) {
    $targetFile = $argv[1];
  }

  $source = NULL;
  if (isset($_GET['source'])) {
    $source = $_GET['source'];
  }
  elseif (isset($argv[2])) {
    $source = $argv[2];
  }

  $allowedSources = unserialize(ALLOWED_SOURCE);
  if (in_array($source, $allowedSources)) {
    die('Invalid source value. Acceptable values: ' . print_r($allowedSources, true));
  }

  return [$targetFile, $source];
}