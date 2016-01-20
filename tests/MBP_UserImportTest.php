<?php
 
namespace MBP_UserImport\Test;

/**
 * MBP_UserImportTest: test coverage for  MBP_UserImport class.
 */
class MBP_UserImportTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){ }
  public function tearDown(){ }
 
  public function testFindNextTargetFile()
  {
    
    date_default_timezone_set('America/New_York');

    // Load Message Broker settings used mb mbp-user-import.php
    require_once __DIR__ . '/../mbp-user-import.config.inc';

    // Create  MBP_UserImport object to access findNextTargetFile() method for testing
    $mbpUserImport = new MBP_UserImport();
    
    // List of valid sources
    // @todo: Move this to include file so production and app can share source values as settings.
    $sources = array(
      'niche',
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
