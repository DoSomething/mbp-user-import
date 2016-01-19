<?php
/**
 * mbp-user-import_manageData.php
 *
 * Collect user import data from gmail account as CSV attachment files. Save
 * the file in data directory to be detercted by import script for processing.
 */

use DoSomething\MBP_UserImport\MBP_UserCSVfileTools;

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mbp-user-import_manageData.config.inc';

echo '------- mbp-user-import_manageData START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off
$source = gatherParameters();
if (!empty($source))  {
    $mbpUserCSVfileTools = new MBP_UserCSVfileTools();
    $status = $mbpUserCSVfileTools->gatherIMAP($source);
}
else {
  echo '"source" parameter not defined.', PHP_EOL;
}

echo '------- mbp-user-import_manageData  END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

/**
 * gatherParameters() - gather parameters set when starting application.
 *
 * @return
 *   $source string: one of the supported source (co-registration) values.
 */
function gatherParameters() {

  $source = NULL;
  if (isset($_GET['source'])) {
    $source = $_GET['source'];
  }
  elseif (isset($argv[1])) {
    $source = $argv[1];
  }

  $allowedSources = unserialize(ALLOWED_SOURCES);
  if (!in_array($source, $allowedSources)) {
    die('Invalid source value. Acceptable values: ' . print_r($allowedSources, true));
  }

  return $source;
}
