<?php
 
use DoSomething\MBP_UserImport\MBP_UserImport;

// Including that file will also return the autoloader instance, so you can store
// the return value of the include call in a variable and add more namespaces.
// This can be useful for autoloading classes in a test suite, for example.
// https://getcomposer.org/doc/01-basic-usage.md
$loader = require_once __DIR__ . '/../vendor/autoload.php';
 
class MBP_UserImportTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){ }
  public function tearDown(){ }
 
  public function testFindNextTargetFile()
  {
    
    date_default_timezone_set('America/New_York');

    // Load Message Broker settings used mb mbp-user-import.php
    require_once __DIR__ . '/../mbp-user-import.config.inc';

    // Create  MBP_UserImport object to access findNextTargetFile() method for testing
    $mbpUserImport = new DoSomething\MBP_UserImport\MBP_UserImport($credentials, $config, $settings);
    
    // List of valid sources
    // @todo: Move this to include file so production and app can share source values as settings.
    $sources = array(
      'niche',
      'att-ichannel',
      'hercampus',
      'teenlife',
    );
    
    foreach ($sources as $source) {
      
      // Create temporary file "00-test.csv" in each of the source directories
      $testFile = __DIR__ . '/../data/' . $source . '/00-test.csv';
      $testFileStatus = touch($testFile);
      $targetCSVFile = $mbpUserImport->findNextTargetFile($source);
      
      $testNameLoc = strpos($targetCSVFile, '00-test.csv');
      $this->assertGreaterThan(0, $testNameLoc);
      
      // Remove the test file
      $this->assertTrue(unlink($testFile));
      
    }

  }
 
}
