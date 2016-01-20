<?php
/**
 * Service Class to provide properties and methods specific to co-marketing sources.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

/*
 * MBP_UserImport_Source_Niche: Properties and methods related to the co-marketing partner niche.com.
 *
 * CSV files are gathered from machines@dosomething.org in a structure defined in this class.
 */
class MBP_UserImport_Source_Niche extends MBP_UserImport_BaseSource
{
  /**
   * A list of supported keys in the CSV file provided by the source.
   *
   * @var array
   */
  protected $keys;

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   */
  public function __construct($message) {

    $this->keys = $this->setKeys();
  }

  /**
   * Supported key / columns in CSV file from source.
   */
  private function setKeys() {
    
  }
  
  /**
   * Logic to process CSV file based on column / line endings.
   */
  public function process() {
    
  }

}
