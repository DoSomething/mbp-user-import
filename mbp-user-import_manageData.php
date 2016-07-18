<?php
/**
 * mbp-user-import_manageData.php
 *
 * Collect user import data from gmail account as CSV attachment files. Save
 * the file in data directory to be detercted by import script for processing.
 */

use DoSomething\MBP_UserImport\MBP_UserImport_CSVfileTools;
use DoSomething\MBP_UserImport\MBP_UserImport_NorthstarTools;
use DoSomething\MBP_UserImport\MBP_UserImport_Producer;

date_default_timezone_set('America/New_York');
define('CONFIG_PATH', __DIR__ . '/messagebroker-config');

// Manage enviroment setting
if (isset($_GET['enviroment']) && allowedEnviroment($_GET['enviroment'])) {
    define('ENVIROMENT', $_GET['enviroment']);
} elseif (isset($argv[1])&& allowedEnviroment($argv[1])) {
    define('ENVIROMENT', $argv[1]);
} elseif (allowedEnviroment('local')) {
    define('ENVIROMENT', 'local');
}

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings specific to this application
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
            $mbpUserImportNorthstarTools = new MBP_UserImport_NorthstarTools();
            $mobileSignups = $mbpUserImportNorthstarTools->gatherMobileUsers();
            $mbpUserImportProducer = new MBP_UserImport_Producer();
            $status = $mbpUserImportProducer->produceNorthstarMobileUsers($mobileSignups);
            break;

        case 'Niche':
        case 'AfterSchool':
            $mbpUserImportCSVfileTools = new MBP_UserImport_CSVfileTools();
            $status = $mbpUserImportCSVfileTools->gatherIMAP($source);
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
