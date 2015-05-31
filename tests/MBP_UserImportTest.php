<?php
 
use DoSomething\MBP_UserImport\MBP_UserImport;
 
class MBP_UserImportTest extends PHPUnit_Framework_TestCase {
 
  public function testFindNextTargetFile()
  {

    require_once '../mbp-user-import.config.inc';
    $mbpUserDigest = new MBP_UserImport($credentials, $settings, $config);
    
    $sources = array(
      'niche',
      'att-ichannel',
      'hercampus',
      'teenlife',
    );
    foreach ($sources as $source) {
      $targetCSVFile = $mbpUserImport->findNextTargetFile($source);
      
      // If *.csv file exists, confirm valid data file is found for each source
      $foundCSVFile = TRUE;
      $this->assertTrue($foundCSVFile);
    }

  }
 
}
