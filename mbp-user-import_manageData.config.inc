<?php
/**
 * Message Broker configuration settings for mbp-user-import_manageData
 */

use DoSomething\MessageBroker\MessageBroker;
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use DoSomething\MB_Toolbox\MB_RabbitMQManagementAPI;
use DoSomething\StatHat\Client as StatHat;
use Ddeboer\Imap\Server;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once CONFIG_PATH . '/mb-secure-config/' . ENVIRONMENT . '/mb-secure-config.inc';

define("ALLOWED_SOURCES", serialize([
  'Niche',
  'AfterSchool',
]));

$mbConfig = MB_Configuration::getInstance();

$mbConfig->setProperty('statHat', new StatHat([
    'ez_key' => getenv("STATHAT_EZKEY"),
    'debug' => getenv("DISABLE_STAT_TRACKING")
]));

$mbConfig->setProperty('generalSettings', []);
$mbConfig->setProperty('rabbit_credentials', [
    'host' =>  getenv("RABBITMQ_HOST"),
    'port' => getenv("RABBITMQ_PORT"),
    'username' => getenv("RABBITMQ_USERNAME"),
    'password' => getenv("RABBITMQ_PASSWORD"),
    'vhost' => getenv("RABBITMQ_VHOST"),
]);
$mbConfig->setProperty('mbRabbitMQManagementAPI', new MB_RabbitMQManagementAPI([
    'domain' => getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
    'port' => getenv('MB_RABBITMQ_MANAGEMENT_API_PORT'),
    'vhost' => getenv('MB_RABBITMQ_MANAGEMENT_API_VHOST'),
    'username' => getenv('MB_RABBITMQ_MANAGEMENT_API_USERNAME'),
    'password' => getenv('MB_RABBITMQ_MANAGEMENT_API_PASSWORD')
]));

$emailServer = new Server('imap.gmail.com');

$mbConfig->setProperty('gmail', $emailServer->authenticate(
    getenv("GMAIL_MACHINE_USERNAME"),
    getenv("GMAIL_MACHINE_PASSWORD")
));

// Northstar
$mbConfig->setProperty('northstar_config', [
    'host' => getenv("NORTHSTAR_API_HOST"),
    'id' => getenv("NORTHSTAR_API_ID"),
    'key' => getenv('NORTHSTAR_API_KEY'),
]);

$mbConfig->setProperty('mbToolboxCURL', new MB_Toolbox_cURL());

$rabbitCredentials = $mbConfig->getProperty('rabbit_credentials');

$mbRabbitConfig = $mbConfig->constructRabbitConfig('directUserImport', ['userImportQueue']);
$mbConfig->setProperty('messageBroker', new MessageBroker($rabbitCredentials, $mbRabbitConfig));
$mbRabbitConfig = $mbConfig->constructRabbitConfig('directLoggingGateway', ['loggingGatewayQueue']);
$mbConfig->setProperty('messageBrokerLogging', new MessageBroker($rabbitCredentials, $mbRabbitConfig));
$mbRabbitConfig = $mbConfig->constructRabbitConfig('directDeadLetterExchange', ['deadLetterQueue']);
$mbConfig->setProperty('messageBroker_deadLetter', new MessageBroker($rabbitCredentials, $mbRabbitConfig));
