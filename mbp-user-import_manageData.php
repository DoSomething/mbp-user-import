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
$source = null;
if (isset($_GET['source'])) {
    $source = $_GET['source'];
} elseif (isset($argv[1])) {
    $source = $argv[1];
}

$source = validateSource($source);
if (!empty($source)) {
    switch ($source) {
        case 'Northstar':
            $mbpNorthstarTools = new MBP_NorthstarTools();
            $status = $mbpNorthstarTools->gatherMobileUsers();
            break;

        case 'Niche':
        case 'AfterSchool':
            $mbpUserCSVfileTools = new MBP_UserCSVfileTools();
            $status = $mbpUserCSVfileTools->gatherIMAP($source);
            break;

        default:
            echo 'Source setting passed validation but is not defined: ' . $source, PHP_EOL;
            break;
    }
} else {
    echo '"source" parameter not defined.', PHP_EOL;
}

echo '------- mbp-user-import_manageData  END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

/**
 * Validate that source settings is valid.
 *
 * @param string $source The name of the source of user data.
 *
 * @return string $source string: one of the supported source (co-registration) values.
 */
function validateSource($source)
{

    $allowedSources = unserialize(ALLOWED_SOURCES);
    if (!in_array($source, $allowedSources)) {
        die('Invalid source value. Acceptable values: ' . print_r($allowedSources, true));
    }

    return $source;
}
