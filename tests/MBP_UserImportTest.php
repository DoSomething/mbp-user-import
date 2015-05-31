<?php
 
use DoSomething\MBP_UserImport\MBP_UserImport;
 
class MBP_UserImportTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){ }
  public function tearDown(){ }
 
  public function testFindNextTargetFile()
  {
    
    date_default_timezone_set('America/New_York');

    // Load up the Composer autoload magic
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../mbp-user-import.config.inc';
    
    echo 'credentials: ' . print_r($credentials), PHP_EOL;
    echo 'settings: ' . print_r($settings), PHP_EOL;
    echo 'config: ' . print_r($config), PHP_EOL;
    
//    $mbpUserImport = new MBP_UserImport($credentials, $settings, $config);
    
/*
    
//    echo 'mbpUserDigest: ' . print_r($mbpUserImport, TRUE), PHP_EOL;
    
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
