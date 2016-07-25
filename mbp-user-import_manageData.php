<?php
/**
 * mbp-user-import_manageData.php
 *
 * Collect user import data from gmail account as CSV attachment files. Save
 * the file in data directory to be detercted by import script for processing.
 */

use DoSomething\MBP_UserImport\MBP_UserImport_CSVfileTools;
use DoSomething\MBP_UserImport\MBP_UserImport_Producer;

date_default_timezone_set('America/New_York');
define('CONFIG_PATH', __DIR__ . '/messagebroker-config');

// Manage enviroment setting
if (isset($_GET['environment']) && allowedEnvironment($_GET['environment'])) {
    define('ENVIRONMENT', $_GET['environment']);
} elseif (isset($argv[1])&& allowedEnvironment($argv[1])) {
    define('ENVIRONMENT', $argv[1]);
} elseif ($env = loadConfig()) {
    echo 'environment.php exists, ENVIRONMENT defined as: ' . ENVIRONMENT, PHP_EOL;
} elseif (allowedEnvironment('local')) {
    define('ENVIRONMENT', 'local');
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
} elseif (isset($argv[2])) {
    $source = $argv[2];
}

try {

    $source = validateSource($source);
    if (!empty($source)) {
        switch ($source) {
            case 'mobileapp_ios':
            case 'mobileapp_android':
                $page = 1;
                if (isset($_GET['page'])) {
                    $page = $_GET['page'];
                } elseif (isset($argv[3])) {
                    $page = $argv[3];
                }

                $startDate = null;
                if (isset($_GET['startDate'])) {
                    $startDate = mktime(0, 0, 0, date("n", $_GET['startDate']), date("j", $_GET['startDate']) - 1,
                        date("Y", $_GET['startDate']));
                } elseif (isset($argv[4])) {
                    $startDate = date('c', mktime(0, 0, 0, date("n", $argv[4]), date("j", $argv[4]) - 1,
                        date("Y", $argv[4])));
                }

                $mbpUserImportProducer = new MBP_UserImport_Producer();
                $status = $mbpUserImportProducer->produceNorthstarMobileUsers($source, $startDate, $page);
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

}
catch (Exception $e) {
    echo $e->getMessage() . ' - ' . date('j D M Y G:i:s T'), PHP_EOL;
}

echo '------- mbp-user-import_manageData  END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

/**
 * Validate source parameter to ensure it's a supported value.
 *
 * @param string $source
 *   The name of the source of user data.
 *
 * @return
 *   $source string: one of the supported source (co-registration) values.
 */
function validateSource($source) {
    $allowedSources = unserialize(ALLOWED_SOURCES);
    if (!in_array($source, $allowedSources)) {
        die('Invalid source value. Acceptable values: ' . print_r($allowedSources, true));
    }
    return $source;
}

/**
 * Test if environment setting is a supported value.
 *
 * @param string $setting Requested environment setting.
 *
 * @return boolean
 */
function allowedEnvironment($setting)
{

    $allowedEnvironments = [
        'local',
        'dev',
        'prod'
    ];

    if (in_array($setting, $allowedEnvironments)) {
        return true;
    }

    return false;
}

/**
 * Gather configuration settings for current application environment.
 *
 * @return boolean
 */
function loadConfig() {

    // Check that environment config file exists
    if (!file_exists (environment.php)) {
        return false;
    }
    include('./environment.php');

    return true;
}
