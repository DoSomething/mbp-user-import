<?php
/**
 * mbp-user-import.php
 *
 * Import user data from CSV file supplied by supported sources of users interested
 * in DoSomething.org. Entries in the userImportQueue will be consumed by
 * mbc-user-import consumer. User creation in the Drupal website as well as the
 * userAPI, Mailchimp and Mandrill transactional signup email message will be
 * triggered by each entry.
 */

use DoSomething\MBP_UserImport\MBP_UserImport_Producer;

date_default_timezone_set('America/New_York');
define('CONFIG_PATH', __DIR__ . '/messagebroker-config');

// Manage enviroment setting
if (isset($_GET['environment']) && allowedEnviroment($_GET['environment'])) {
    define('ENVIRONMENT', $_GET['environment']);
} elseif (isset($argv[1])&& allowedEnviroment($argv[1])) {
    define('ENVIRONMENT', $argv[1]);
} elseif ($env = loadConfig()) {
    echo 'environment.php exists, ENVIRONMENT defined as: ' . ENVIRONMENT, PHP_EOL;
} elseif (allowedEnviroment('local')) {
    define('ENVIRONMENT', 'local');
}

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings specific to this application
require_once __DIR__ . '/mbp-user-import.config.inc';

// Kickoff
echo '------- mbp-user-import START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
try {
    $targetFile = 'nextFile';
    if (isset($_GET['targetFile'])) {
        $targetFile = $_GET['targetFile'];
    } elseif (isset($argv[2])) {
        $targetFile = $argv[2];
    }

    $source = null;
    if (isset($_GET['source'])) {
        $source = $_GET['source'];
    } elseif (isset($argv[3])) {
        $source = $argv[3];
    }

    $source = validateSource($source);
    if (!empty($source)) {
        $mbpUserImport = new MBP_UserImport_Producer();
        $mbpUserImport->produceCSVImport($targetFile, $source);
    } else {
        throw new Exception('"source" parameter not defined.');
    }
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
}
echo '------- mbp-user-import END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

/**
 * Gather parameters set when application starts.
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
 * @param string $setting Requested enviroment setting.
 *
 * @return boolean
 */
function allowedEnviroment($setting)
{

    $allowedEnviroments = [
        'local',
        'dev',
        'prod'
    ];

    if (in_array($setting, $allowedEnviroments)) {
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
