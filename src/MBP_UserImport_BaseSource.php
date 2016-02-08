<?php
/**
 * Base abstract class to provide a template for active source class to extend
 * from.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

/*
 * MBP_UserImport_BaseSource: Used to define the structure of values specific to an
 * import source.
 */
abstract class MBP_UserImport_BaseSource
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
   * A list of supported keys in the CSV file provided by the source.
   *
   * @var array
   */
  protected $keys;
  
  /**
   * The number of user rows from CSV file processed.
   *
   * @var integer
   */
  protected $imported = 0;
  
  /**
   * The number of user rows skipped from CSV file when processing.
   *
   * @var integer
   */
  protected $skipped = 0;

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param array $message
   *   The message to process by the service from the connected queue.
   */
  public function __construct() {

    $this->mbConfig = MB_Configuration::getInstance();
    $this->statHat = $this->mbConfig->getProperty('statHat');
    $this->keys = $this->setKeys();
  }

  /**
   * Supported key / columns in CSV file from source.
   */
  abstract protected function setKeys();
  
  /**
   * Logic to determine if data from row in CSV file can be processed.
   */
  abstract public function canProcess($data);

  /**
   * Assign columns specific to the source to common columns expected by the consumer.
   */
  abstract public function setter(&$data);

  /**
   * Logic to process CSV file based on column / line endings.
   */
  abstract public function process($data);

}
