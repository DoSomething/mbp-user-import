<?php
/**
 * Message Broker configuration settings for mbp-user-import
 */

use DoSomething\MessageBroker\MessageBroker;
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_RabbitMQManagementAPI;
use DoSomething\StatHat\Client as StatHat;

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

// Settings
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

$mbRabbitConfig = $mbConfig->constructRabbitConfig('directUserImport', ['userImportQueue']);
$rabbitCredentials = $mbConfig->getProperty('rabbit_credentials');
$mbConfig->setProperty('messageBroker', new MessageBroker($rabbitCredentials, $mbRabbitConfig));

$mbRabbitConfig = $mbConfig->constructRabbitConfig('directLoggingGateway', ['loggingGatewayQueue']);
$mbConfig->setProperty('messageBrokerLogging', new MessageBroker($rabbitCredentials, $mbRabbitConfig));

$mbRabbitConfig = $mbConfig->constructRabbitConfig('directDeadLetterExchange', ['deadLetterQueue']);
$mbConfig->setProperty('messageBroker_deadLetter', new MessageBroker($rabbitCredentials, $mbRabbitConfig));
