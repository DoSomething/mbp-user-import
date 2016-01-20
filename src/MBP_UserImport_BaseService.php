<?php
/**
 * Base abstract class to provide a template for active source class to extend
 * from.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

/*
 * MBP_UserImport_BaseService: Used to define the structure of values specific to an
 * import source.
 */
abstract class MBP_UserImport_BaseService
{

  /**
   * Singleton instance of MB_Configuration application settings and service objects
   *
   * @var object
   */
  protected $mbConfig;

  /**
   * StatHat object for logging of activity
   *
   * @var object
   */
  protected $statHat;

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param array $message
   *   The message to process by the service from the connected queue.
   */
  public function __construct($message) {

    $this->mbConfig = MB_Configuration::getInstance();
    $this->statHat = $this->mbConfig->getProperty('statHat');
  }

  /**
   * Supported key / columns in CSV file from source.
   */
  abstract public function keys();
  
  /**
   * Logic to process CSV file based on column / line endings.
   */
  abstract public function process();

}
