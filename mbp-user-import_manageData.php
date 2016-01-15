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
use DoSomething\MBP_UserImport\MBP_UserCSVfileTools;

require_once __DIR__ . '/mbp-user-import_manageData.config.inc';


echo '------- mbp-user-import_manageData START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off
$allowedSources = array(
  'niche',
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
    $mbpUserCSVfileTools = new MBP_UserCSVfileTools($settings);
    $status = $mbpUserCSVfileTools->gatherIMAP($source);
  }
  else {
    echo 'ERROR - invalid source.', PHP_EOL;
  }

}
else {
  echo '"source" parameter not defined.', PHP_EOL;
}

echo '------- mbp-user-import_manageData  END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
