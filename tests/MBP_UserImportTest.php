<?php
 
use DoSomething\MBP_UserImport\MBP_UserImport;
 
class MBP_UserImportTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){ }
  public function tearDown(){ }
 
  public function testFindNextTargetFile()
  {
    
    date_default_timezone_set('America/New_York');

    // Including that file will also return the autoloader instance, so you can store
    // the return value of the include call in a variable and add more namespaces.
    // This can be useful for autoloading classes in a test suite, for example.
    // https://getcomposer.org/doc/01-basic-usage.md
    $loader = require_once __DIR__ . '/../vendor/autoload.php';
    
    // Load Message Broker settings used mb mbp-user-import.php
    require_once __DIR__ . '/../mbp-user-import.config.inc';
    
    echo 'credentials: ' . print_r($credentials), PHP_EOL;
    echo 'settings: ' . print_r($settings), PHP_EOL;
    echo 'config: ' . print_r($config), PHP_EOL;
    
    // Create  MBP_UserImport object to access findNextTargetFile() method for testing
//    $mbpUserImport = new MBP_UserImport($credentials, $settings, $config);
    
/*
//    echo 'mbpUserDigest: ' . print_r($mbpUserImport, TRUE), PHP_EOL;
    
    // List of valid sources
    // @todo: Move this to include file so production and app can share singe files as settings.
    $sources = array(
      'niche',
      'att-ichannel',
      'hercampus',
      'teenlife',
    );
    
//    echo 'sources: ' . print_r($sources, TRUE), PHP_EOL;
    
    foreach ($sources as $source) {
      $targetCSVFile = $mbpUserImport->findNextTargetFile($source);
      
      // If *.csv file exists, confirm valid data file is found for each source
      $foundCSVFile = TRUE;
      $this->assertTrue($foundCSVFile);
    }
    
*/

  }
 
}
