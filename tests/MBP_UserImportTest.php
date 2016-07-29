<?php
/**
 * Test coverage for mbp-user-import.php and mbp-user-import.config.inc. Starting
 * point for mbp-user-import application.
 */
namespace DoSomething\MBP_UserImport;

use DoSomething\MessageBroker\MessageBroker;
use DoSomething\MB_Toolbox\MB_Configuration;

define('ENVIRONMENT', 'local');
define('CONFIG_PATH', __DIR__ . '/../messagebroker-config');

class MBP_UserImportTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var MB_Configuration settings.
     */
    private $mbConfig;

    /**
     * @var array $sources Possible source values.
     */
    private $sources;

    /**
     * @var object $mbpUserImport_Producer Instance of class for running tests against.
     */
    private $mbpUserImport_Producer;

    /**
     * Common functionality to all tests. Load configuration settings and properties.
     */
    public function setUp()
    {
        require_once __DIR__ . '/../mbp-user-import.config.inc';
        $this->mbConfig = MB_Configuration::getInstance();
        $this->sources = unserialize(ALLOWED_SOURCES);

        $this->mbpUserImport_Producer = new MBP_UserImport_Producer();
    }

    /**
     * Ensure mbConfig->getProperty returns a value.
     *
     * @covers \DoSomething\MBP_UserImport\MBP_UserImport_Producer::__construct
     * @uses   \DoSomething\MBP_UserImport\MBP_UserImport_Producer
     */
    public function testMBPUserImportConfigProperties()
    {
        $statHat = $this->mbConfig->getProperty('statHat');
        $this->assertEquals(true, is_object($statHat));
        $rabbit_credentials = $this->mbConfig->getProperty('rabbit_credentials');
        $this->assertEquals(true, is_array($rabbit_credentials));
        $mbRabbitMQManagementAPI = $this->mbConfig->getProperty('mbRabbitMQManagementAPI');
        $this->assertEquals(true, is_object($mbRabbitMQManagementAPI));
        $messageBroker = $this->mbConfig->getProperty('messageBroker');
        $this->assertEquals(true, is_object($messageBroker), '*!* Check that RabbitMQ server is running. *!*');
        $messageBrokerLogging = $this->mbConfig->getProperty('messageBrokerLogging');
        $this->assertEquals(true, is_object($messageBrokerLogging));
        $messageBroker_deadLetter = $this->mbConfig->getProperty('messageBroker_deadLetter');
        $this->assertEquals(true, is_object($messageBroker_deadLetter));
    }

    /**
     * Ensure mbConfig->getProperty returns expected value types.
     *
     * @covers \DoSomething\MBP_UserImport\MBP_UserImport_Producer::__construct
     * @uses   \DoSomething\MBP_UserImport\MBP_UserImport_Producer
     */
    public function testMBPUserImportConfigPropertyTypes()
    {

        $statHat = $this->mbConfig->getProperty('statHat');
        $this->assertEquals(true, get_class($statHat) == 'DoSomething\StatHat\Client');
        $mbRabbitMQManagementAPI = $this->mbConfig->getProperty('mbRabbitMQManagementAPI');
        $this->assertEquals(
            true,
            get_class($mbRabbitMQManagementAPI) == 'DoSomething\MB_Toolbox\MB_RabbitMQManagementAPI'
        );
        $messageBroker = $this->mbConfig->getProperty('messageBroker');
        $this->assertEquals(true, get_class($messageBroker) == 'DoSomething\MessageBroker\MessageBroker');
        $messageBrokerLogging = $this->mbConfig->getProperty('messageBrokerLogging');
        $this->assertEquals(true, get_class($messageBrokerLogging) == 'DoSomething\MessageBroker\MessageBroker');
        $messageBroker_deadLetter = $this->mbConfig->getProperty('messageBroker_deadLetter');
        $this->assertEquals(true, get_class($messageBroker_deadLetter) == 'DoSomething\MessageBroker\MessageBroker');
    }

    /**
     * Ensure mbConfig->getProperty returns expected value types.
     *
     * @covers \DoSomething\MBP_UserImport\MBP_UserImport_Producer::findNextTargetFile()
     * @uses   \DoSomething\MBP_UserImport\MBP_UserImport_Producer
     *
     * @todo Move to MBP_UserImport_ProducerTest
     */
    public function testFindNextTargetFile()
    {

        $sources = [
            'AfterSchool',
            'Niche',
        ];

        foreach ($sources as $source) {

            // Create temporary file "00-test.csv" in each of the source directories
            $testFile = __DIR__ . '/../data/' . $source . '/00-test.csv';
            $testFileStatus = touch ($testFile);
            $targetCSVFile = $this->mbpUserImport_Producer->findNextTargetFile($source);

            // Test that the file was found by the method
            $testNameLoc = strpos($targetCSVFile, '00-test.csv');
            $this->assertGreaterThan (0, $testNameLoc);

            // Remove the test file
            $this->assertTrue (unlink ($testFile));
        }
    }
}
