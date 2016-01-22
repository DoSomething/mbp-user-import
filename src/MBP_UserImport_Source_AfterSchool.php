<?php
/**
 * Service Class to provide properties and methods specific to co-marketing sources.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

/*
 * MBP_UserImport_Source_Niche: Properties and methods related to the co-marketing partner AfterSchool.
 *
 * CSV files are gathered from machines@dosomething.org in a structure defined in this class.
 */
class MBP_UserImport_Source_AfterSchool extends MBP_UserImport_BaseSource
{

  /**
   * Supported key / columns in CSV file from source.
   */
  private function setKeys() {

    $keys = [
      '???',
    ];

    return $keys;
  }
  
  /**
   * Logic to process CSV file based on column / line endings.
   *
   * @param array $CSVRow
   *   A row of user data from the CSV file.
   *
   * @return array $data
   *   CSV values formatted into an array.
   */
  public function process($CSVRow) {

    return $data;
  }

}
